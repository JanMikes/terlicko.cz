<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Doctrine\ORM\EntityManagerInterface;
use Terlicko\Web\Entity\AiChunk;
use Terlicko\Web\Entity\AiDocument;
use Terlicko\Web\Entity\AiEmbedding;
use Terlicko\Web\Repository\AiDocumentRepository;

readonly final class IngestionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AiDocumentRepository $documentRepository,
        private PdfParser $pdfParser,
        private TextChunker $textChunker,
        private EmbeddingService $embeddingService,
        private DocumentHasher $documentHasher,
    ) {
    }

    /**
     * Ingest a PDF document
     *
     * @param array{source_url: string, title: string, size_bytes: int, published_at: string} $fileData
     * @return array{status: string, message: string, chunks_created: int}
     */
    public function ingestPdfDocument(array $fileData): array
    {
        $sourceUrl = str_replace('localhost:8080', 'frontend:80', $fileData['source_url']);
        $title = $fileData['title'];

        // Calculate content hash
        $contentHash = $this->documentHasher->hashUrl($sourceUrl);

        // Check if document exists and hasn't changed
        $existingDocument = $this->documentRepository->findBySourceUrl($sourceUrl);

        if ($existingDocument && $existingDocument->getContentHash() === $contentHash) {
            return [
                'status' => 'skipped',
                'message' => 'Document unchanged',
                'chunks_created' => 0,
            ];
        }

        // Parse PDF
        $pdfData = $this->pdfParser->extractText($sourceUrl);
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
     * @return array{status: string, message: string, chunks_created: int}
     */
    public function ingestWebpage(array $pageData): array
    {
        $sourceUrl = str_replace('localhost:8080', 'frontend:80', $pageData['url']);
        $title = $pageData['title'];
        $content = $pageData['content']['normalized_text'];

        // Calculate content hash
        $contentHash = $this->documentHasher->hashContent($content);

        // Check if document exists and hasn't changed
        $existingDocument = $this->documentRepository->findBySourceUrl($sourceUrl);

        if ($existingDocument && $existingDocument->getContentHash() === $contentHash) {
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

        return [
            'status' => $existingDocument ? 'updated' : 'created',
            'message' => sprintf('Processed %d chunks', $chunksCreated),
            'chunks_created' => $chunksCreated,
        ];
    }
}
