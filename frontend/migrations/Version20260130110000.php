<?php

declare(strict_types=1);

namespace Terlicko\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260130110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_offtopic_violations ALTER id TYPE UUID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_offtopic_violations ALTER guest_id TYPE UUID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_offtopic_violations ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN ai_offtopic_violations.id IS ''
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN ai_offtopic_violations.guest_id IS ''
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN ai_offtopic_violations.created_at IS ''
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_offtopic_violations ALTER id TYPE UUID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_offtopic_violations ALTER guest_id TYPE UUID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_offtopic_violations ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
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
}
