<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Ai;

use DateTimeImmutable;

/**
 * Normalized content item for AI RAG ingestion
 */
readonly final class AiContentItem
{
    public function __construct(
        public string $url,
        public string $title,
        public string $type,
        public string $normalizedText,
        public DateTimeImmutable $createdAt,
    ) {}

    /**
     * @return array{url: string, title: string, type: string, content: array{format: string, normalized_text: string}, created_at: string}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'title' => $this->title,
            'type' => $this->type,
            'content' => [
                'format' => 'text',
                'normalized_text' => $this->normalizedText,
            ],
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
