<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller\Chat;

use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Ai\CitationFormatter;
use Terlicko\Web\Services\Ai\ContextBuilder;
use Terlicko\Web\Services\Ai\ConversationManager;
use Terlicko\Web\Services\Ai\ModerationService;
use Terlicko\Web\Services\Ai\OpenAiChatService;
use Terlicko\Web\Services\Ai\VectorSearchService;

#[Route('/chat/{conversationId}/messages', name: 'chat_send_message', methods: ['POST'])]
final class SendMessageController extends AbstractController
{
    public function __construct(
        private readonly ConversationManager $conversationManager,
        private readonly ModerationService $moderationService,
        private readonly VectorSearchService $vectorSearchService,
        private readonly ContextBuilder $contextBuilder,
        private readonly OpenAiChatService $openAiChatService,
        private readonly CitationFormatter $citationFormatter,
        private readonly RateLimiterFactory $aiChatMessagesLimiter,
        private readonly RateLimiterFactory $aiChatDailyLimiter,
    ) {
    }

    public function __invoke(Request $request, string $conversationId): Response
    {
        // Get guest ID
        $guestIdCookie = $request->cookies->get('ai_guest_id');
        if (!$guestIdCookie || !Uuid::isValid($guestIdCookie)) {
            throw new BadRequestHttpException('Missing or invalid guest ID');
        }
        $guestId = Uuid::fromString($guestIdCookie);

        // Check rate limits
        $messagesLimiter = $this->aiChatMessagesLimiter->create($guestId->toString());
        if (!$messagesLimiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(
                $messagesLimiter->consume(0)->getRetryAfter()->getTimestamp(),
                'Příliš mnoho zpráv. Zkuste to prosím za chvíli.'
            );
        }

        $dailyLimiter = $this->aiChatDailyLimiter->create($guestId->toString());
        if (!$dailyLimiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(
                null,
                'Dosáhli jste denního limitu zpráv.'
            );
        }

        // Get conversation
        if (!Uuid::isValid($conversationId)) {
            throw new BadRequestHttpException('Invalid conversation ID');
        }

        $conversation = $this->conversationManager->getConversation(
            Uuid::fromString($conversationId),
            $guestId
        );

        if ($conversation === null || !$conversation->isActive()) {
            throw new NotFoundHttpException('Conversation not found or ended');
        }

        // Get user message
        /** @var array{message?: string} $data */
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (trim($userMessage) === '') {
            throw new BadRequestHttpException('Message cannot be empty');
        }

        // Moderate input
        if ($this->moderationService->shouldBlock($userMessage)) {
            return $this->json([
                'error' => 'message_flagged',
                'message' => 'Vaše zpráva byla označena jako nevhodná.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Save user message
        $this->conversationManager->addMessage($conversation, 'user', $userMessage);

        // Search for relevant context
        $searchResults = $this->vectorSearchService->hybridSearch($userMessage, 10);
        $contextData = $this->contextBuilder->buildContext($searchResults);

        // Get conversation history
        $history = $this->conversationManager->getConversationHistory($conversation, 5);

        // Add current message to history
        $history[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        // Stream response
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        $conversationManager = $this->conversationManager;
        $openAiChatService = $this->openAiChatService;
        $citationFormatter = $this->citationFormatter;

        $response->setCallback(function () use (
            $conversationManager,
            $openAiChatService,
            $citationFormatter,
            $conversation,
            $history,
            $contextData
        ) {
            $fullResponse = '';

            try {
                // Send sources first
                if (!empty($contextData['sources'])) {
                    $sourcesData = $citationFormatter->formatForApi($contextData['sources']);
                    echo "event: sources\n";
                    echo 'data: ' . json_encode($sourcesData) . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                // Stream AI response
                foreach ($openAiChatService->generateStreamingCompletion($history, $contextData['context']) as $chunk) {
                    $fullResponse .= $chunk;
                    echo "event: message\n";
                    echo 'data: ' . json_encode(['content' => $chunk]) . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                // Save assistant message
                $citations = !empty($contextData['sources'])
                    ? $citationFormatter->formatAsJson($contextData['sources'])
                    : null;

                $conversationManager->addMessage(
                    $conversation,
                    'assistant',
                    $fullResponse,
                    $citations
                );

                // Send done event
                echo "event: done\n";
                echo "data: {\"status\":\"complete\"}\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            } catch (\Throwable $e) {
                error_log('Chat error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                echo "event: error\n";
                echo 'data: ' . json_encode(['error' => 'Nastala chyba při generování odpovědi.']) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        });

        return $response;
    }
}
