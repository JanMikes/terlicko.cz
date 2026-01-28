<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class ModerationService
{
    private const int MAX_STRIKES = 3;
    private const int COOLDOWN_MINUTES = 5;

    private const array COOLDOWN_MESSAGES = [
        'Pojďte si dát pauzu a třeba si zajít na procházku kolem Těrlické přehrady. Uvidíme se za chvíli.',
        'Dám si teď malou přestávku. Zkuste se zatím podívat na krásné fotky Těrlicka.',
        'I roboti potřebují občas vydechnout. Vraťte se za pár minut a začneme znovu.',
        'Teď je dobrý čas na šálek čaje. Až se vrátíte, rád vám pomůžu.',
        'Navrhuji krátkou pauzu. Co třeba svačinka? Uvidíme se za chvilku.',
        'Dávám si oddechový čas. Mezitím můžete prozkoumat aktuality na webu obce.',
        'Pojďme si dát restart. Vracím se za moment s čistou hlavou.',
        'Krátká přestávka prospěje nám oběma. Brzy se zase ozvu.',
        'Teď bych doporučil malý oddych. Třeba se podívejte na kalendář akcí v Těrlicku.',
        'I digitální asistent si občas potřebuje odpočinout. Za chvíli jsem zpět.',
        'Dám si krátkou pauzu na dobití baterií. Uvidíme se brzy.',
        'Čas na malou přestávku. Co takhle skočit na čerstvý vzduch?',
        'Navrhuji krátký timeout. Zkuste se zatím podívat na úřední desku.',
        'Odpočívám na chvilku. Až se vrátíte, rád zodpovím vaše otázky o Těrlicku.',
        'Malá pauza nikomu neuškodí. Těším se na pokračování za pár minut.',
        'Dávám si chvilku volno. Mezitím se klidně podívejte na stránky obce.',
        'Krátký oddech a pak pokračujeme. Zkuste se zatím projít po Těrlicku.',
        'Na chvíli si odpočinu. Až budete připraveni, jsem tu pro vás.',
        'Pauza je někdy nejlepší odpověď. Vraťte se za moment.',
        'Dám si pár minut na zchladnutí. Pak vám rád pomůžu s čímkoli o Těrlicku.',
        'Chvilka klidu nám oběma prospěje. Za moment jsem zpátky.',
        'Teď je ideální čas na sklenici vody. Brzy se uvidíme.',
        'Krátký restart a jedeme dál. Vraťte se za chviličku.',
        'Potřebuji malou přestávku. Co kdybyste se zatím podívali, co je nového v obci?',
        'Dávám si čas na rozmyšlenou. Uvidíme se za pár minut.',
        'I asistenti si občas potřebují dát pauzu. Hned jsem zpátky.',
        'Navrhuji krátké nadechnutí pro nás oba. Brzy pokračujeme.',
        'Na chvilku odcházím, ale nebojte, vrátím se. Zkuste zatím prozkoumat web obce.',
        'Přestávka na občerstvení? Já si zatím dobiju energii. Za chvíli jsem tu.',
        'Krátká meditace a pak jsem zase k dispozici. Uvidíme se brzy.',
    ];

    public function __construct(
        private HttpClientInterface $openaiClient,
    ) {
    }

    /**
     * Check if text violates OpenAI's usage policies
     *
     * @return array{flagged: bool, categories: array<string, bool>}
     */
    public function moderateText(string $text): array
    {
        $response = $this->openaiClient->request('POST', 'moderations', [
            'json' => [
                'input' => $text,
            ],
        ]);

        /** @var array{results: array<array{flagged: bool, categories: array<string, bool>}>} $data */
        $data = $response->toArray();

        if (!isset($data['results'][0])) {
            throw new \RuntimeException('Invalid response from OpenAI moderation API');
        }

        $result = $data['results'][0];

        return [
            'flagged' => $result['flagged'],
            'categories' => $result['categories'],
        ];
    }

    /**
     * Check if text should be blocked
     */
    public function shouldBlock(string $text): bool
    {
        $result = $this->moderateText($text);
        return $result['flagged'];
    }

    public function getMaxStrikes(): int
    {
        return self::MAX_STRIKES;
    }

    public function getCooldownMinutes(): int
    {
        return self::COOLDOWN_MINUTES;
    }

    public function getRandomCooldownMessage(): string
    {
        return self::COOLDOWN_MESSAGES[array_rand(self::COOLDOWN_MESSAGES)];
    }
}
