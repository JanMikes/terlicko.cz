<?php

declare(strict_types=1);

namespace Terlicko\Web\Services;

use Twig\Extra\Markdown\DefaultMarkdown;
use Twig\Extra\Markdown\MarkdownRuntime;

readonly final class TextProcessor
{
    private MarkdownRuntime $markdownRuntime;

    public function __construct()
    {
        $this->markdownRuntime = new MarkdownRuntime(new DefaultMarkdown());
    }

    public function createPerex(string $text): string
    {
        // Convert markdown to HTML
        $htmlContent = $this->markdownRuntime->convert($text);
        
        // Strip HTML tags
        $plainText = strip_tags($htmlContent);
        
        // Truncate to 150 characters with ellipsis
        if (mb_strlen($plainText) > 150) {
            return mb_substr($plainText, 0, 150) . '…';
        }
        
        return $plainText;
    }
}