<?php

declare(strict_types=1);

namespace Terlicko\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202043713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE ai_message_feedback (id UUID NOT NULL, feedback_text TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, message_id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_message_feedback_message ON ai_message_feedback (message_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_message_feedback_created ON ai_message_feedback (created_at)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_message_feedback ADD CONSTRAINT FK_C28F07F6537A1329 FOREIGN KEY (message_id) REFERENCES ai_messages (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_message_feedback DROP CONSTRAINT FK_C28F07F6537A1329
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ai_message_feedback
        SQL);
    }
}
