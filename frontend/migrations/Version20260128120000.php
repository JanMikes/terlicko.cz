<?php

declare(strict_types=1);

namespace Terlicko\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260128120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add moderation strikes and blocked_until to ai_conversations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_conversations ADD moderation_strikes INT DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_conversations ADD moderation_blocked_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN ai_conversations.moderation_blocked_until IS '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_conversations DROP moderation_strikes
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_conversations DROP moderation_blocked_until
        SQL);
    }
}
