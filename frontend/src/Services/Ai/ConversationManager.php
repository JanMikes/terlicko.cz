<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Terlicko\Web\Entity\AiConversation;
use Terlicko\Web\Entity\AiMessage;
use Terlicko\Web\Repository\AiConversationRepository;

readonly final class ConversationManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AiConversationRepository $conversationRepository,
    ) {
    }

    /**
     * Get or create guest ID
     */
    public function getOrCreateGuestId(?string $guestIdString): UuidInterface
    {
        if ($guestIdString !== null && Uuid::isValid($guestIdString)) {
            return Uuid::fromString($guestIdString);
        }

        return Uuid::uuid7();
    }

    /**
     * Start a new conversation
     */
    public function startConversation(UuidInterface $guestId, ?string $ipAddress = null): AiConversation
    {
        $conversation = new AiConversation($guestId, $ipAddress);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $conversation;
    }

    /**
     * Get active conversation for guest
     */
    public function getActiveConversation(UuidInterface $guestId): ?AiConversation
    {
        return $this->conversationRepository->findActiveByGuestId($guestId);
    }

    /**
     * Get conversation by ID for guest
     */
    public function getConversation(UuidInterface $conversationId, UuidInterface $guestId): ?AiConversation
    {
        return $this->conversationRepository->findByIdAndGuestId($conversationId, $guestId);
    }

    /**
     * Add message to conversation
     */
    public function addMessage(
        AiConversation $conversation,
        string $role,
        string $content,
        ?string $citations = null,
        ?string $metadata = null
    ): AiMessage {
        $message = new AiMessage(
            conversation: $conversation,
            role: $role,
            content: $content,
            citations: $citations,
            metadata: $metadata
        );

        $this->entityManager->persist($message);
        $conversation->addMessage($message);
        $this->entityManager->flush();

        return $message;
    }

    /**
     * End conversation
     */
    public function endConversation(AiConversation $conversation): void
    {
        $conversation->end();
        $this->entityManager->flush();
    }

    /**
     * Get conversation history as messages array
     *
     * @return array<array{role: string, content: string}>
     */
    public function getConversationHistory(AiConversation $conversation, int $maxMessages = 10): array
    {
        /** @var array<AiMessage> $messages */
        $messages = $conversation->getMessages()->slice(-$maxMessages);
        /** @var array<array{role: string, content: string}> $history */
        $history = [];

        foreach ($messages as $message) {
            $history[] = [
                'role' => $message->getRole(),
                'content' => $message->getContent(),
            ];
        }

        return $history;
    }

    /**
     * Check rate limits for guest
     */
    public function canStartNewConversation(UuidInterface $guestId): bool
    {
        $oneHourAgo = new \DateTimeImmutable('-1 hour');
        $recentCount = $this->conversationRepository->countRecentByGuestId($guestId, $oneHourAgo);

        return $recentCount < 12; // Max 12 new conversations per hour
    }
}
