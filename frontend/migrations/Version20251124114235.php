<?php

declare(strict_types=1);

namespace Terlicko\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124114235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make ai_embeddings.vector column NOT NULL';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_embeddings ALTER vector TYPE vector(1536)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_embeddings ALTER vector SET NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_embeddings ALTER vector TYPE vector(1536)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_embeddings ALTER vector DROP NOT NULL
        SQL);
    }
}
