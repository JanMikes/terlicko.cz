<?php

declare(strict_types=1);

namespace Terlicko\Web\ConsoleCommands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Terlicko\Web\Services\Ai\AiContentExtractor;
use Terlicko\Web\Services\Ai\FileExtractor;
use Terlicko\Web\Services\Ai\IngestionService;

#[AsCommand(
    name: 'ai:ingest',
    description: 'Ingest documents and content for AI RAG chatbot'
)]
final class AiIngestCommand extends Command
{
    public function __construct(
        private readonly IngestionService $ingestionService,
        private readonly AiContentExtractor $contentExtractor,
        private readonly FileExtractor $fileExtractor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force ingestion even if documents are unchanged')
            ->addOption('pdf-only', null, InputOption::VALUE_NONE, 'Ingest only PDF documents')
            ->addOption('content-only', null, InputOption::VALUE_NONE, 'Ingest only web content');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('AI Document Ingestion');

        $force = (bool) $input->getOption('force');
        $pdfOnly = $input->getOption('pdf-only');
        $contentOnly = $input->getOption('content-only');

        if ($force) {
            $io->warning('Force mode enabled - all documents will be re-ingested');
        }

        $totalProcessed = 0;
        $totalChunks = 0;

        // Ingest PDF documents (directly from Strapi, no HTTP needed)
        if (!$contentOnly) {
            $io->section('Processing PDF Documents');
            $io->comment('Extracting PDF files directly from Strapi...');

            $files = $this->fileExtractor->extractAllPdfFiles();
            $filesCount = count($files);

            $io->comment(sprintf('Found %d PDF files', $filesCount));

            $progressBar = $io->createProgressBar($filesCount);
            $progressBar->start();

            foreach ($files as $file) {
                $fileData = [
                    'source_url' => 'https://terlicko.cz' . $file['url'],
                    'title' => $file['caption'] ?? $file['name'],
                    'size_bytes' => $file['size'],
                    'published_at' => $file['created_at']->format(\DateTimeInterface::ATOM),
                ];

                $result = $this->ingestionService->ingestPdfDocument($fileData, $force);
                $totalProcessed++;
                $totalChunks += $result['chunks_created'];

                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);
        }

        // Ingest web content (directly from Strapi, no HTTP timeout issues)
        if (!$pdfOnly) {
            $io->section('Processing Web Content');
            $io->comment('Extracting content directly from Strapi...');

            // Collect all items first for progress bar
            $contentItems = iterator_to_array($this->contentExtractor->extractAll());
            $pagesCount = count($contentItems);

            $io->comment(sprintf('Found %d content items (aktuality, sekce, uredni deska, kalendar akci)', $pagesCount));

            $progressBar = $io->createProgressBar($pagesCount);
            $progressBar->start();

            foreach ($contentItems as $item) {
                $result = $this->ingestionService->ingestContentItem($item, $force);
                $totalProcessed++;
                $totalChunks += $result['chunks_created'];

                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);
        }

        // Summary
        $io->section('Summary');
        $io->success(sprintf(
            'Processed %d documents, created %d chunks',
            $totalProcessed,
            $totalChunks
        ));

        return Command::SUCCESS;
    }
}
