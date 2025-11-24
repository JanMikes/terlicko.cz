<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller\Chat;

use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Ai\ConversationManager;

#[Route('/chat/{conversationId}', name: 'chat_get_conversation', methods: ['GET'])]
final class GetConversationController extends AbstractController
{
    public function __construct(
        private readonly ConversationManager $conversationManager,
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

        // Get conversation
        if (!Uuid::isValid($conversationId)) {
            throw new BadRequestHttpException('Invalid conversation ID');
        }

        $conversation = $this->conversationManager->getConversation(
            Uuid::fromString($conversationId),
            $guestId
        );

        if ($conversation === null) {
            throw new NotFoundHttpException('Conversation not found');
        }

        // Build messages array
        $messages = [];
        foreach ($conversation->getMessages() as $message) {
            $messageData = [
                'id' => $message->getId()->toString(),
                'role' => $message->getRole(),
                'content' => $message->getContent(),
                'created_at' => $message->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];

            // Parse citations if present
            if ($message->getCitations() !== null) {
                $citations = json_decode($message->getCitations(), true);
                if ($citations !== null) {
                    $messageData['citations'] = $citations;
                }
            }

            $messages[] = $messageData;
        }

        return new JsonResponse([
            'conversation_id' => $conversation->getId()->toString(),
            'is_active' => $conversation->isActive(),
            'started_at' => $conversation->getStartedAt()->format(\DateTimeInterface::ATOM),
            'messages' => $messages,
        ]);
    }
}
