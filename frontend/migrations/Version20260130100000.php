<?php

declare(strict_types=1);

namespace Terlicko\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260130100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ai_offtopic_violations table for tracking off-topic questions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE ai_offtopic_violations (
                id UUID NOT NULL,
                guest_id UUID NOT NULL,
                question TEXT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_offtopic_guest_created ON ai_offtopic_violations (guest_id, created_at)
        SQL);

        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN ai_offtopic_violations.id IS '(DC2Type:uuid)'
        SQL);

        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN ai_offtopic_violations.guest_id IS '(DC2Type:uuid)'
        SQL);

        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN ai_offtopic_violations.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE ai_offtopic_violations
        SQL);
    }
}
