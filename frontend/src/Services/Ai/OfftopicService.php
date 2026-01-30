<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;
use Terlicko\Web\Entity\AiOfftopicViolation;
use Terlicko\Web\Repository\AiOfftopicViolationRepository;

readonly final class OfftopicService
{
    private const string MARKER = '[OFFTOPIC]';
    private const int MAX_VIOLATIONS = 5;
    private const int WINDOW_HOURS = 24;

    private const array OFFTOPIC_MESSAGES = [
        'Tato otázka se netýká Těrlicka. Zkuste se zeptat na něco o naší obci.',
        'Omlouvám se, ale odpovídám pouze na otázky o Těrlicku. Co vás zajímá o naší obci?',
        'Na toto vám bohužel nemohu odpovědět. Rád vám ale pomohu s čímkoli o Těrlicku.',
        'Toto nespadá do mé působnosti. Mohu vám pomoci s informacemi o Těrlicku.',
        'Zkuste se zeptat na něco o Těrlicku – rád pomohu s úřady, akcemi nebo službami.',
        'Tato otázka je mimo téma. Zajímá vás něco o životě v Těrlicku?',
        'Na tohle odpovědět nemohu. Co kdybyste se zeptali na Těrlickou přehradu?',
        'Omlouvám se, specializuji se na Těrlicko. Jak vám mohu pomoci s naší obcí?',
        'Toto téma neřeším. Ale vím vše o Těrlicku – ptejte se!',
        'Na obecné otázky neodpovídám. Zajímá vás něco konkrétního o Těrlicku?',
        'Moje znalosti se týkají Těrlicka. V čem vám mohu pomoci?',
        'Toto je mimo moji oblast. Rád odpovím na dotazy o obci Těrlicko.',
        'Na toto se nezaměřuji. Zkuste se zeptat na úřední hodiny nebo akce v Těrlicku.',
        'Bohužel na tohle neodpovím. Mohu vám ale říct vše o Těrlicku.',
        'Toto téma nemám v repertoáru. Co vás zajímá o naší krásné obci?',
        'Omlouvám se, ale tohle není v mé kompetenci. Zeptejte se na Těrlicko!',
        'Na obecné věci neodpovídám. Rád pomohu s informacemi o Těrlicku.',
        'Toto je mimo téma. Víte, že Těrlicko má krásnou přehradu? Zeptejte se na ni!',
        'Na tohle nemám odpověď. Ale znám všechny služby a akce v Těrlicku.',
        'Specializuji se na Těrlicko. V čem vám mohu být nápomocen?',
    ];

    private const array BLOCKED_MESSAGES = [
        'Dosáhli jste denního limitu dotazů mimo téma. Vraťte se zítra nebo se zeptejte na Těrlicko.',
        'Příliš mnoho dotazů nesouvisejících s obcí. Zkuste to znovu zítra.',
        'Dnes jste vyčerpali limit. Zítra vám rád pomohu s dotazy o Těrlicku.',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AiOfftopicViolationRepository $repository,
    ) {
    }

    public function isOfftopicResponse(string $response): bool
    {
        return str_contains($response, self::MARKER);
    }

    public function recordViolation(UuidInterface $guestId, string $question): void
    {
        $violation = new AiOfftopicViolation($guestId, $question);
        $this->entityManager->persist($violation);
        $this->entityManager->flush();
    }

    public function isBlocked(UuidInterface $guestId): bool
    {
        return $this->getViolationCount($guestId) >= self::MAX_VIOLATIONS;
    }

    public function getViolationCount(UuidInterface $guestId): int
    {
        $since = new \DateTimeImmutable(sprintf('-%d hours', self::WINDOW_HOURS));

        return $this->repository->countRecentByGuestId($guestId, $since);
    }

    public function getRandomOfftopicMessage(): string
    {
        return self::OFFTOPIC_MESSAGES[array_rand(self::OFFTOPIC_MESSAGES)];
    }

    public function getRandomBlockedMessage(): string
    {
        return self::BLOCKED_MESSAGES[array_rand(self::BLOCKED_MESSAGES)];
    }

    public function replaceMarkerWithMessage(string $response): string
    {
        return str_replace(self::MARKER, $this->getRandomOfftopicMessage(), $response);
    }
}
