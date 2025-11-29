<?php

declare(strict_types=1);

namespace Terlicko\Web\ConsoleCommands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Terlicko\Web\Services\Ai\VectorSearchService;

#[AsCommand(
    name: 'ai:search-test',
    description: 'Test AI search functionality'
)]
final class AiSearchTestCommand extends Command
{
    public function __construct(
        private readonly VectorSearchService $vectorSearchService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('query', InputArgument::REQUIRED, 'Search query');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $query = $input->getArgument('query');
        \assert(\is_string($query));

        $io->title("Search Results for: \"$query\"");

        $results = $this->vectorSearchService->hybridSearch($query, 20);

        foreach ($results as $i => $result) {
            $io->section(sprintf('#%d [%s] %s', $i + 1, $result['document_type'], $result['title']));
            $io->text([
                sprintf('URL: %s', $result['source_url']),
                sprintf('Distance: %.4f | Score: %.4f', $result['distance'], $result['combined_score']),
                sprintf('Preview: %s...', substr($result['content'], 0, 150)),
            ]);
        }

        $pdfCount = count(array_filter($results, fn($r) => $r['document_type'] === 'pdf'));
        $webpageCount = count(array_filter($results, fn($r) => $r['document_type'] === 'webpage'));
        $io->success(sprintf('Results: %d PDFs, %d Webpages', $pdfCount, $webpageCount));

        return Command::SUCCESS;
    }
}
