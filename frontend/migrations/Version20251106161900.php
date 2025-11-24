<?php

declare(strict_types=1);

namespace Terlicko\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251106161900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable pgvector extension for AI embeddings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS vector');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP EXTENSION IF EXISTS vector');
    }
}
