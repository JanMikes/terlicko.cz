<?php

declare(strict_types=1);

namespace Terlicko\Web\ConsoleCommands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Terlicko\Web\Services\Strapi\StrapiApiClient;

#[AsCommand(
    name: 'strapi:cache:clear',
    description: 'Clear Strapi API cache'
)]
final class ClearStrapiCacheCommand extends Command
{
    public function __construct(
        private readonly StrapiApiClient $strapiApiClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->strapiApiClient->clearCache()) {
            $io->success('Strapi cache cleared successfully');
            return Command::SUCCESS;
        }

        $io->error('Failed to clear Strapi cache');
        return Command::FAILURE;
    }
}
