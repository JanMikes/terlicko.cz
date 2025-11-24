<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller\Chat;

use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Ai\ConversationManager;

#[Route('/chat/start', name: 'chat_start', methods: ['POST'])]
final class StartChatController extends AbstractController
{
    public function __construct(
        private readonly ConversationManager $conversationManager,
        private readonly RateLimiterFactory $aiNewConversationsLimiter,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Get or create guest ID
        $guestIdCookie = $request->cookies->get('ai_guest_id');
        $guestId = $this->conversationManager->getOrCreateGuestId($guestIdCookie);

        // Check rate limit
        $limiter = $this->aiNewConversationsLimiter->create($guestId->toString());
        if (!$limiter->consume(1)->isAccepted()) {
            $retryAfter = $limiter->consume(0)->getRetryAfter();
            return new JsonResponse([
                'error' => 'rate_limit_exceeded',
                'message' => 'Příliš mnoho nových konverzací. Zkuste to prosím později.',
                'retry_after' => $retryAfter->getTimestamp(),
            ], Response::HTTP_TOO_MANY_REQUESTS, [
                'Retry-After' => $retryAfter->getTimestamp(),
            ]);
        }

        // Check conversation limit
        if (!$this->conversationManager->canStartNewConversation($guestId)) {
            return new JsonResponse([
                'error' => 'conversation_limit_exceeded',
                'message' => 'Dosáhli jste limitu počtu konverzací za hodinu.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Create new conversation
        $conversation = $this->conversationManager->startConversation(
            $guestId,
            $request->getClientIp()
        );

        $response = new JsonResponse([
            'conversation_id' => $conversation->getId()->toString(),
            'guest_id' => $guestId->toString(),
            'started_at' => $conversation->getStartedAt()->format(\DateTimeInterface::ATOM),
        ]);

        // Set guest ID cookie (1 year expiry)
        $cookie = Cookie::create('ai_guest_id')
            ->withValue($guestId->toString())
            ->withExpires(new \DateTimeImmutable('+1 year'))
            ->withPath('/')
            ->withSecure($request->isSecure())
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        return $response;
    }
}
