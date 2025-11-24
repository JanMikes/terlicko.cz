<?php

declare(strict_types=1);

namespace Terlicko\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106161958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE ai_chunks (id UUID NOT NULL, content TEXT NOT NULL, chunk_index INT NOT NULL, token_count INT NOT NULL, metadata TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, document_id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_chunks_document ON ai_chunks (document_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE ai_conversations (id UUID NOT NULL, guest_id UUID NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_conversations_guest ON ai_conversations (guest_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_conversations_started ON ai_conversations (started_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_conversations_ended ON ai_conversations (ended_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE ai_documents (id UUID NOT NULL, source_url VARCHAR(1000) NOT NULL, title VARCHAR(500) NOT NULL, type VARCHAR(50) NOT NULL, content_hash VARCHAR(64) NOT NULL, metadata TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_documents_source_url ON ai_documents (source_url)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_documents_content_hash ON ai_documents (content_hash)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_documents_updated_at ON ai_documents (updated_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE ai_embeddings (id UUID NOT NULL, vector vector(1536), model VARCHAR(100) NOT NULL, dimensions INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, chunk_id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_4B0E48E58BD543CD ON ai_embeddings (chunk_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_embeddings_chunk ON ai_embeddings (chunk_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE ai_messages (id UUID NOT NULL, role VARCHAR(20) NOT NULL, content TEXT NOT NULL, citations TEXT DEFAULT NULL, metadata TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, conversation_id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_messages_conversation ON ai_messages (conversation_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ai_messages_created ON ai_messages (created_at)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_chunks ADD CONSTRAINT FK_C1A7C691C33F7837 FOREIGN KEY (document_id) REFERENCES ai_documents (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_embeddings ADD CONSTRAINT FK_4B0E48E58BD543CD FOREIGN KEY (chunk_id) REFERENCES ai_chunks (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_messages ADD CONSTRAINT FK_C4E498F69AC0396 FOREIGN KEY (conversation_id) REFERENCES ai_conversations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_chunks DROP CONSTRAINT FK_C1A7C691C33F7837
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_embeddings DROP CONSTRAINT FK_4B0E48E58BD543CD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_messages DROP CONSTRAINT FK_C4E498F69AC0396
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ai_chunks
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ai_conversations
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ai_documents
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ai_embeddings
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ai_messages
        SQL);
    }
}
