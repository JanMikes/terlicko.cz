<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller\Chat;

use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Repository\AiConversationRepository;

#[Route('/chat/conversations', name: 'chat_list_conversations', methods: ['GET'])]
final class ListConversationsController extends AbstractController
{
    public function __construct(
        private readonly AiConversationRepository $conversationRepository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Get guest ID
        $guestIdCookie = $request->cookies->get('ai_guest_id');
        if (!$guestIdCookie || !Uuid::isValid($guestIdCookie)) {
            throw new BadRequestHttpException('Missing or invalid guest ID');
        }
        $guestId = Uuid::fromString($guestIdCookie);

        // Get all conversations for this guest
        $conversations = $this->conversationRepository->findAllByGuestId($guestId, 20);

        // Build response array
        $result = [];
        foreach ($conversations as $conversation) {
            $result[] = [
                'id' => $conversation->getId()->toString(),
                'title' => $conversation->getTitle(),
                'started_at' => $conversation->getStartedAt()->format(\DateTimeInterface::ATOM),
                'is_active' => $conversation->isActive(),
            ];
        }

        return new JsonResponse($result);
    }
}
