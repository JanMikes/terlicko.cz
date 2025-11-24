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

#[Route('/chat/{conversationId}/end', name: 'chat_end', methods: ['POST'])]
final class EndChatController extends AbstractController
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

        // End conversation
        $this->conversationManager->endConversation($conversation);

        return new JsonResponse([
            'status' => 'ended',
            'message' => 'Konverzace byla ukončena.',
            'ended_at' => $conversation->getEndedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }
}
