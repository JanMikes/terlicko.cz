<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller\Chat;

use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Entity\AiMessage;
use Terlicko\Web\Entity\AiMessageFeedback;

#[Route('/chat/messages/{messageId}/feedback', name: 'chat_submit_feedback', methods: ['POST'])]
final class SubmitFeedbackController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Request $request, string $messageId): Response
    {
        // Get guest ID from cookie
        $guestIdCookie = $request->cookies->get('ai_guest_id');
        if (!$guestIdCookie || !Uuid::isValid($guestIdCookie)) {
            throw new BadRequestHttpException('Missing or invalid guest ID');
        }
        $guestId = Uuid::fromString($guestIdCookie);

        // Validate message ID
        if (!Uuid::isValid($messageId)) {
            throw new BadRequestHttpException('Invalid message ID');
        }

        // Find the message
        $message = $this->entityManager->getRepository(AiMessage::class)->find(Uuid::fromString($messageId));
        if ($message === null) {
            throw new NotFoundHttpException('Message not found');
        }

        // Verify the message belongs to a conversation owned by this guest
        $conversation = $message->getConversation();
        if (!$conversation->getGuestId()->equals($guestId)) {
            throw new NotFoundHttpException('Message not found');
        }

        // Verify it's an assistant message
        if (!$message->isAssistantMessage()) {
            throw new BadRequestHttpException('Feedback can only be submitted for assistant messages');
        }

        // Get feedback text from request
        /** @var array{feedback?: string} $data */
        $data = json_decode($request->getContent(), true);
        $feedbackText = trim($data['feedback'] ?? '');

        if ($feedbackText === '') {
            throw new BadRequestHttpException('Feedback text cannot be empty');
        }

        // Create and save feedback
        $feedback = new AiMessageFeedback($message, $feedbackText);
        $this->entityManager->persist($feedback);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
