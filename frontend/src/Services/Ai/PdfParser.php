<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Smalot\PdfParser\Parser as SmalotPdfParser;

readonly final class PdfParser
{
    public function __construct(
        private SmalotPdfParser $parser,
    ) {
    }

    /**
     * Extract text from PDF file or URL
     *
     * @return array{text: string, pages: int, metadata: array<string, mixed>}
     */
    public function extractText(string $filePathOrUrl): array
    {
        try {
            $pdf = $this->parser->parseFile($filePathOrUrl);
            $text = $pdf->getText();
            $pages = count($pdf->getPages());

            $details = $pdf->getDetails();

            return [
                'text' => $text,
                'pages' => $pages,
                'metadata' => [
                    'title' => $details['Title'] ?? null,
                    'author' => $details['Author'] ?? null,
                    'subject' => $details['Subject'] ?? null,
                    'keywords' => $details['Keywords'] ?? null,
                    'creator' => $details['Creator'] ?? null,
                    'producer' => $details['Producer'] ?? null,
                    'creation_date' => $details['CreationDate'] ?? null,
                    'mod_date' => $details['ModDate'] ?? null,
                ],
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Failed to parse PDF: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Clean and normalize PDF text
     */
    public function cleanText(string $text): string
    {
        // Sanitize to valid UTF-8 first
        $text = TextSanitizer::sanitizeUtf8($text);

        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        // Remove non-printable characters except newlines
        $text = preg_replace('/[^\P{C}\n]+/u', '', $text) ?? $text;

        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove multiple consecutive newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
