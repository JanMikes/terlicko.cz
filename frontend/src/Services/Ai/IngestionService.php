<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Terlicko\Web\Entity\AiChunk;
use Terlicko\Web\Entity\AiDocument;
use Terlicko\Web\Entity\AiEmbedding;
use Terlicko\Web\Repository\AiDocumentRepository;
use Terlicko\Web\Value\Ai\AiContentItem;

readonly final class IngestionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AiDocumentRepository $documentRepository,
        private PdfParser $pdfParser,
        private ImageOcrService $imageOcrService,
        private TextChunker $textChunker,
        private EmbeddingService $embeddingService,
        private DocumentHasher $documentHasher,
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Check if a file URL is accessible (returns 2xx status)
     */
    private function isFileAccessible(string $url): bool
    {
        try {
            $response = $this->httpClient->request('HEAD', $url, [
                'timeout' => 10,
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Ingest a PDF document
     *
     * @param array{source_url: string, title: string, size_bytes: int, published_at: string} $fileData
     * @param bool $force Force re-ingestion even if document is unchanged
     * @return array{status: string, message: string, chunks_created: int}
     */
    public function ingestPdfDocument(array $fileData, bool $force = false): array
    {
        $sourceUrl = $fileData['source_url'];
        // Internal URL for downloading PDF content within Docker network
        $downloadUrl = str_replace('https://terlicko.cz', 'http://frontend:80', $sourceUrl);
        $title = $fileData['title'];

        // Check if file is accessible before processing
        if (!$this->isFileAccessible($downloadUrl)) {
            return [
                'status' => 'skipped',
                'message' => 'File not accessible',
                'chunks_created' => 0,
            ];
        }

        // Calculate content hash using internal URL for download
        $contentHash = $this->documentHasher->hashUrl($downloadUrl);

        // Check if document exists and hasn't changed (skip if not forcing)
        $existingDocument = $this->documentRepository->findBySourceUrl($sourceUrl);

        if (!$force && $existingDocument && $existingDocument->getContentHash() === $contentHash) {
            return [
                'status' => 'skipped',
                'message' => 'Document unchanged',
                'chunks_created' => 0,
            ];
        }

        // Parse PDF using internal Docker URL
        $pdfData = $this->pdfParser->extractText($downloadUrl);
        $cleanedText = $this->pdfParser->cleanText($pdfData['text']);

        // If document exists but changed, remove old chunks
        if ($existingDocument) {
            foreach ($existingDocument->getChunks() as $chunk) {
                $this->entityManager->remove($chunk);
            }
            $this->entityManager->flush();

            $existingDocument->updateContentHash($contentHash);
            $document = $existingDocument;
        } else {
            // Create new document
            $document = new AiDocument(
                sourceUrl: $sourceUrl,
                title: $title,
                type: 'pdf',
                contentHash: $contentHash,
                metadata: json_encode([
                    'pages' => $pdfData['pages'],
                    'size' => $fileData['size_bytes'],
                    'pdf_metadata' => $pdfData['metadata'],
                ], JSON_THROW_ON_ERROR),
            );
            $this->entityManager->persist($document);
        }

        // Chunk the text
        $chunks = $this->textChunker->chunkText($cleanedText);

        // Create chunks and embeddings
        $chunksCreated = 0;
        foreach ($chunks as $chunkData) {
            // Create chunk entity
            $text = $chunkData['text'];
            $chunk = new AiChunk(
                document: $document,
                content: $text,
                chunkIndex: $chunkData['index'],
                tokenCount: $chunkData['token_count'],
                metadata: null,
            );
            $this->entityManager->persist($chunk);
            $document->addChunk($chunk);

            // Generate embedding
            $embeddingData = $this->embeddingService->generateEmbedding($text);

            // Create embedding entity
            $embedding = new AiEmbedding(
                chunk: $chunk,
                vectorArray: $embeddingData['embedding'],
                model: $embeddingData['model'],
                dimensions: $embeddingData['dimensions'],
            );
            $this->entityManager->persist($embedding);
            $chunk->setEmbedding($embedding);

            $chunksCreated++;

            // Flush every 10 chunks to avoid memory issues
            if ($chunksCreated % 10 === 0) {
                $this->entityManager->flush();
            }
        }

        // Final flush
        $this->entityManager->flush();

        // Clear entity manager to free memory after processing document
        $this->entityManager->clear();

        return [
            'status' => $existingDocument ? 'updated' : 'created',
            'message' => sprintf('Processed %d chunks', $chunksCreated),
            'chunks_created' => $chunksCreated,
        ];
    }

    /**
     * Ingest an image document using OCR
     *
     * @param array{source_url: string, title: string, size_bytes: int, published_at: string, ext: string} $fileData
     * @param bool $force Force re-ingestion even if document is unchanged
     * @return array{status: string, message: string, chunks_created: int}
     */
    public function ingestImageDocument(array $fileData, bool $force = false): array
    {
        $sourceUrl = $fileData['source_url'];
        $title = $fileData['title'];

        // Check if file is accessible before processing (internal URL for hashing)
        $downloadUrl = str_replace('https://terlicko.cz', 'http://frontend:80', $sourceUrl);
        if (!$this->isFileAccessible($downloadUrl)) {
            return [
                'status' => 'skipped',
                'message' => 'File not accessible (internal)',
                'chunks_created' => 0,
            ];
        }

        // Also check public URL that OpenAI will use for OCR
        if (!$this->isFileAccessible($sourceUrl)) {
            return [
                'status' => 'skipped',
                'message' => 'File not accessible (public URL)',
                'chunks_created' => 0,
            ];
        }

        // Calculate content hash using file URL (image content hash)
        $contentHash = $this->documentHasher->hashUrl($downloadUrl);

        // Check if document exists and hasn't changed (skip if not forcing)
        $existingDocument = $this->documentRepository->findBySourceUrl($sourceUrl);

        if (!$force && $existingDocument && $existingDocument->getContentHash() === $contentHash) {
            return [
                'status' => 'skipped',
                'message' => 'Document unchanged',
                'chunks_created' => 0,
            ];
        }

        // Extract text from image using OCR (use public URL for OpenAI Vision)
        $ocrResult = $this->imageOcrService->extractText($sourceUrl);
        $extractedText = trim($ocrResult['text']);

        // If no text was extracted, still create/update document to prevent re-processing
        if ($extractedText === '') {
            if ($existingDocument) {
                $existingDocument->updateContentHash($contentHash);
            } else {
                $document = new AiDocument(
                    sourceUrl: $sourceUrl,
                    title: $title,
                    type: 'image',
                    contentHash: $contentHash,
                    metadata: json_encode([
                        'size' => $fileData['size_bytes'],
                        'extension' => $fileData['ext'],
                        'ocr_result' => 'no_text',
                    ], JSON_THROW_ON_ERROR),
                );
                $this->entityManager->persist($document);
            }
            $this->entityManager->flush();
            $this->entityManager->clear();

            return [
                'status' => 'skipped',
                'message' => 'No text found in image',
                'chunks_created' => 0,
            ];
        }

        // If document exists but changed, remove old chunks
        if ($existingDocument) {
            foreach ($existingDocument->getChunks() as $chunk) {
                $this->entityManager->remove($chunk);
            }
            $this->entityManager->flush();

            $existingDocument->updateContentHash($contentHash);
            $document = $existingDocument;
        } else {
            // Create new document
            $document = new AiDocument(
                sourceUrl: $sourceUrl,
                title: $title,
                type: 'image',
                contentHash: $contentHash,
                metadata: json_encode([
                    'size' => $fileData['size_bytes'],
                    'extension' => $fileData['ext'],
                    'ocr_model' => $ocrResult['model'],
                    'ocr_tokens' => $ocrResult['tokens'],
                ], JSON_THROW_ON_ERROR),
            );
            $this->entityManager->persist($document);
        }

        // Chunk the text
        $chunks = $this->textChunker->chunkText($extractedText);

        // Create chunks and embeddings
        $chunksCreated = 0;
        foreach ($chunks as $chunkData) {
            // Create chunk entity
            $text = $chunkData['text'];
            $chunk = new AiChunk(
                document: $document,
                content: $text,
                chunkIndex: $chunkData['index'],
                tokenCount: $chunkData['token_count'],
                metadata: null,
            );
            $this->entityManager->persist($chunk);
            $document->addChunk($chunk);

            // Generate embedding
            $embeddingData = $this->embeddingService->generateEmbedding($text);

            // Create embedding entity
            $embedding = new AiEmbedding(
                chunk: $chunk,
                vectorArray: $embeddingData['embedding'],
                model: $embeddingData['model'],
                dimensions: $embeddingData['dimensions'],
            );
            $this->entityManager->persist($embedding);
            $chunk->setEmbedding($embedding);

            $chunksCreated++;

            // Flush every 10 chunks to avoid memory issues
            if ($chunksCreated % 10 === 0) {
                $this->entityManager->flush();
            }
        }

        // Final flush
        $this->entityManager->flush();

        // Clear entity manager to free memory after processing document
        $this->entityManager->clear();

        return [
            'status' => $existingDocument ? 'updated' : 'created',
            'message' => sprintf('Processed %d chunks', $chunksCreated),
            'chunks_created' => $chunksCreated,
        ];
    }

    /**
     * Ingest a webpage/content
     *
     * @param array{url: string, title: string, content: array{format: string, normalized_text: string}} $pageData
     * @param bool $force Force re-ingestion even if document is unchanged
     * @return array{status: string, message: string, chunks_created: int}
     */
    public function ingestWebpage(array $pageData, bool $force = false): array
    {
        $sourceUrl = $pageData['url'];
        $title = $pageData['title'];
        $rawContent = $pageData['content']['normalized_text'];

        // Prepend title to content for better embedding context
        // Strip markdown formatting for cleaner embeddings
        $content = $this->prepareWebpageContentForEmbedding($title, $rawContent);

        // Calculate content hash
        $contentHash = $this->documentHasher->hashContent($content);

        // Check if document exists and hasn't changed (skip if not forcing)
        $existingDocument = $this->documentRepository->findBySourceUrl($sourceUrl);

        if (!$force && $existingDocument && $existingDocument->getContentHash() === $contentHash) {
            return [
                'status' => 'skipped',
                'message' => 'Document unchanged',
                'chunks_created' => 0,
            ];
        }

        // If document exists but changed, remove old chunks
        if ($existingDocument) {
            foreach ($existingDocument->getChunks() as $chunk) {
                $this->entityManager->remove($chunk);
            }
            $this->entityManager->flush();

            $existingDocument->updateContentHash($contentHash);
            $document = $existingDocument;
        } else {
            // Create new document
            $document = new AiDocument(
                sourceUrl: $sourceUrl,
                title: $title,
                type: 'webpage',
                contentHash: $contentHash,
                metadata: null,
            );
            $this->entityManager->persist($document);
        }

        // Chunk the text
        $chunks = $this->textChunker->chunkText($content);

        // Create chunks and embeddings
        $chunksCreated = 0;
        foreach ($chunks as $chunkData) {
            // Create chunk entity
            $chunk = new AiChunk(
                document: $document,
                content: $chunkData['text'],
                chunkIndex: $chunkData['index'],
                tokenCount: $chunkData['token_count'],
                metadata: null,
            );
            $this->entityManager->persist($chunk);
            $document->addChunk($chunk);

            // Generate embedding
            $embeddingData = $this->embeddingService->generateEmbedding($chunkData['text']);

            // Create embedding entity
            $embedding = new AiEmbedding(
                chunk: $chunk,
                vectorArray: $embeddingData['embedding'],
                model: $embeddingData['model'],
                dimensions: $embeddingData['dimensions'],
            );
            $this->entityManager->persist($embedding);
            $chunk->setEmbedding($embedding);

            $chunksCreated++;

            // Flush every 10 chunks to avoid memory issues
            if ($chunksCreated % 10 === 0) {
                $this->entityManager->flush();
            }
        }

        // Final flush
        $this->entityManager->flush();

        // Clear entity manager to free memory after processing document
        $this->entityManager->clear();

        return [
            'status' => $existingDocument ? 'updated' : 'created',
            'message' => sprintf('Processed %d chunks', $chunksCreated),
            'chunks_created' => $chunksCreated,
        ];
    }

    /**
     * Ingest an AiContentItem directly
     *
     * @param bool $force Force re-ingestion even if document is unchanged
     * @return array{status: string, message: string, chunks_created: int}
     */
    public function ingestContentItem(AiContentItem $item, bool $force = false): array
    {
        return $this->ingestWebpage([
            'url' => $item->url,
            'title' => $item->title,
            'content' => [
                'format' => 'text',
                'normalized_text' => $item->normalizedText,
            ],
        ], $force);
    }

    /**
     * Prepare webpage content for embedding by:
     * 1. Prepending the title for context
     * 2. Stripping markdown formatting for cleaner embeddings
     */
    private function prepareWebpageContentForEmbedding(string $title, string $content): string
    {
        // Strip markdown formatting
        $cleanContent = $this->stripMarkdown($content);

        // Prepend title for better semantic context
        // This helps embeddings understand what the content is about
        return "Stránka: {$title}\n\n{$cleanContent}";
    }

    /**
     * Strip markdown formatting from text to create cleaner embeddings
     * Converts structured content into natural prose for better semantic search
     */
    private function stripMarkdown(string $text): string
    {
        // Remove code blocks (```code```)
        $text = preg_replace('/```[\s\S]*?```/', '', $text) ?? $text;

        // Remove inline code (`code` -> code)
        $text = preg_replace('/`([^`]+)`/', '$1', $text) ?? $text;

        // Remove images ![alt](url)
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '$1', $text) ?? $text;

        // Convert links [text](url) -> text
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text) ?? $text;

        // Remove reference-style links [text][ref]
        $text = preg_replace('/\[([^\]]+)\]\[[^\]]*\]/', '$1', $text) ?? $text;

        // Remove link references [ref]: url
        $text = preg_replace('/^\[[^\]]+\]:\s*\S+.*$/m', '', $text) ?? $text;

        // Convert headers to emphasized text (### Header -> Header:)
        $text = preg_replace('/^#{1,6}\s*(.+)$/m', '$1:', $text) ?? $text;

        // Remove bold markers (**text** or __text__ -> text)
        $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text) ?? $text;
        $text = preg_replace('/__([^_]+)__/', '$1', $text) ?? $text;

        // Remove italic markers (*text* or _text_ -> text) - careful not to match list items
        $text = preg_replace('/(?<![*\s])\*([^*\n]+)\*(?![*])/', '$1', $text) ?? $text;
        $text = preg_replace('/(?<![_\s])_([^_\n]+)_(?![_])/', '$1', $text) ?? $text;

        // Remove strikethrough (~~text~~ -> text)
        $text = preg_replace('/~~([^~]+)~~/', '$1', $text) ?? $text;

        // Convert unordered lists to readable text
        $text = preg_replace('/^[\s]*[-*+]\s+/m', '• ', $text) ?? $text;

        // Convert ordered lists to readable text
        $text = preg_replace('/^[\s]*(\d+)\.\s+/m', '$1. ', $text) ?? $text;

        // Remove blockquotes (> text -> text)
        $text = preg_replace('/^>\s*/m', '', $text) ?? $text;

        // Remove horizontal rules (---, ***, ___)
        $text = preg_replace('/^[-*_]{3,}$/m', '', $text) ?? $text;

        // Remove HTML tags if any
        $text = strip_tags($text);

        // Clean up field-value patterns for better readability
        // "**Funkce:** Starosta" is already handled by bold removal
        // Keep "Label: Value" format as it reads naturally

        // Normalize multiple spaces
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        // Normalize multiple newlines (keep paragraph structure with single blank lines)
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        // Trim each line
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);

        // Remove empty lines but preserve paragraph breaks
        $result = [];
        $previousEmpty = false;
        foreach ($lines as $line) {
            if ($line === '') {
                if (!$previousEmpty) {
                    $result[] = '';
                    $previousEmpty = true;
                }
            } else {
                $result[] = $line;
                $previousEmpty = false;
            }
        }

        return trim(implode("\n", $result));
    }

    /**
     * Record a failed image ingestion to prevent retrying on subsequent runs
     *
     * @param array{source_url: string, title: string, size_bytes: int, published_at: string, ext: string} $fileData
     */
    public function recordFailedImage(array $fileData, string $errorMessage): void
    {
        $sourceUrl = $fileData['source_url'];
        $existingDocument = $this->documentRepository->findBySourceUrl($sourceUrl);

        if ($existingDocument === null) {
            $document = new AiDocument(
                sourceUrl: $sourceUrl,
                title: $fileData['title'],
                type: 'image',
                contentHash: 'failed:' . md5($errorMessage),
                metadata: json_encode([
                    'error' => $errorMessage,
                    'failed_at' => date('c'),
                    'size' => $fileData['size_bytes'],
                    'extension' => $fileData['ext'],
                ], JSON_THROW_ON_ERROR),
            );
            $this->entityManager->persist($document);
            $this->entityManager->flush();
            $this->entityManager->clear();
        }
    }
}
