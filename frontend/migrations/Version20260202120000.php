<?php

declare(strict_types=1);

namespace Terlicko\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add title field to ai_conversations table
 */
final class Version20260202120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add title column to ai_conversations table for conversation titles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_conversations ADD COLUMN title VARCHAR(100) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_conversations DROP COLUMN title
        SQL);
    }
}
