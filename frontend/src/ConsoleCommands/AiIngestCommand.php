<?php

declare(strict_types=1);

namespace Terlicko\Web\ConsoleCommands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Terlicko\Web\Services\Ai\IngestionService;

#[AsCommand(
    name: 'ai:ingest',
    description: 'Ingest documents and content for AI RAG chatbot'
)]
final class AiIngestCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly IngestionService $ingestionService,
        private readonly string $baseUrl = 'http://frontend:80', // we run this within container -> port 80
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

        $pdfOnly = $input->getOption('pdf-only');
        $contentOnly = $input->getOption('content-only');

        $totalProcessed = 0;
        $totalChunks = 0;
        $errors = [];

        // Ingest PDF documents
        if (!$contentOnly) {
            $io->section('Processing PDF Documents');

            $filesResponse = $this->httpClient->request('GET', $this->baseUrl . '/ai/files.json');
            $filesData = $filesResponse->toArray();

            $filesCount = count($filesData['items']);

            $io->comment(sprintf('Found %d PDF files', $filesCount));

            $progressBar = $io->createProgressBar($filesCount);
            $progressBar->start();

            foreach ($filesData['items'] as $file) {
                $result = $this->ingestionService->ingestPdfDocument($file);
                $totalProcessed++;
                $totalChunks += $result['chunks_created'];

                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);
        }

        // Ingest web content
        if (!$pdfOnly) {
            $io->section('Processing Web Content');

            $contentResponse = $this->httpClient->request('GET', $this->baseUrl . '/ai/content.json');
            $contentData = $contentResponse->toArray();

            $pagesCount = count($contentData['items']);

            $io->comment(sprintf('Found %d pages', $pagesCount));

            $progressBar = $io->createProgressBar($pagesCount);
            $progressBar->start();

            foreach ($contentData['items'] as $page) {
                $result = $this->ingestionService->ingestWebpage($page);
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

        if (!empty($errors)) {
            $io->warning('Some documents failed to process:');
            $io->listing($errors);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
