<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

final class TextSanitizer
{
    /**
     * Sanitize text to valid UTF-8, dropping invalid characters as fallback
     */
    public static function sanitizeUtf8(string $text): string
    {
        // First, try to convert to UTF-8 if it's in a different encoding
        $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding !== false && $encoding !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        }

        // Remove any remaining invalid UTF-8 sequences
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }
}
