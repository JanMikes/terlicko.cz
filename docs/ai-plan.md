# AI RAG Chatbot Implementation Plan

## Overview
Build a RAG-based chatbot system integrated into the existing Symfony application with all services running in Docker. The system will leverage existing AI services (`ContentNormalizer`, `FileExtractor`) and follow established Symfony patterns.

## Architecture Decisions
- **Models**: Configurable via ENV (text-embedding-3-small/large, gpt-4o-mini/gpt-4o)
- **Ingestion**: PHP-based Symfony Console Command (cron scheduled)
- **Chunking**: 1000 tokens with 100 token overlap
- **Scope**: Core features only per ai.md specification

---

## Phase 1: Infrastructure Setup

### 1.1 Docker Services
- [x] Add **Redis 7** service to `compose.yaml` for rate limiting
- [x] Add **pgvector extension** to existing PostgreSQL 17 service
- [x] Configure health checks and networks

### 1.2 Environment Configuration
- [x] Add AI-related environment variables to `.env` and `.env.test`:
  - OPENAI_API_KEY
  - REDIS_HOST / REDIS_PORT
  - AI_EMBEDDING_MODEL
  - AI_CHAT_MODEL
  - AI_CHUNK_SIZE
  - AI_CHUNK_OVERLAP

### 1.3 Symfony Configuration
- [x] Configure **OpenAI HTTP client** in `config/packages/http_client.php`
- [x] Configure **Redis** in `config/packages/framework.php`
- [x] Configure **Rate Limiter** with policies (10/min, 100/day, 12 conversations/hour)

---

## Phase 2: Database Layer

### 2.1 Doctrine Entities
Create entities in `frontend/src/Entity/`:
- [x] `AiDocument` - Stores document metadata (source URL, title, type, hash)
- [x] `AiChunk` - Document chunks with text content
- [x] `AiEmbedding` - Vector embeddings (using pgvector)
- [x] `AiConversation` - Chat sessions (guest_id, started_at, ended_at)
- [x] `AiMessage` - Individual messages (conversation, role, content, citations)

### 2.2 Database Schema
- [x] Enable pgvector extension migration
- [x] Generate migrations for all entities
- [x] Add vector index on embeddings table
- [x] Run migrations

### 2.3 Repositories
- [x] Custom repository for vector similarity search (pgvector queries)
- [x] Repository methods for conversation lookup
- [x] Repository methods for document change detection

---

## Phase 3: RAG Core Services

### 3.1 Data Extraction Services
Instead of HTTP JSON feed controllers, data is extracted directly from Strapi via services:
- [x] `FileExtractor` - Extracts PDF and image files directly from Strapi upload API
- [x] `AiContentExtractor` - Extracts web content (aktuality, sekce, uredni deska, kalendar akci) from Strapi
- [x] `ContentNormalizer` - Normalizes Strapi component content to text
- ~~`AiFilesJsonController`~~ - Not implemented (replaced by direct Strapi integration)
- ~~`AiContentJsonController`~~ - Not implemented (replaced by direct Strapi integration)

### 3.2 Ingestion Service
Create `frontend/src/Services/Ai/`:
- [x] `PdfParser` - Extracts text from PDFs (use `smalot/pdfparser` library)
- [x] `TextChunker` - Splits text into 1000-token chunks with 100 overlap
- [x] `EmbeddingService` - Calls OpenAI to generate embeddings
- [x] `DocumentHasher` - Detects changed documents
- [x] `IngestionService` - Orchestrates ingestion pipeline
- [x] `ImageOcrService` - Extracts text from images via OpenAI Vision API
- [x] `TextSanitizer` - UTF-8 text sanitization

### 3.3 Retrieval Service
- [x] `VectorSearchService` - Hybrid search (pgvector + keyword) with Czech query preprocessing and expansion
- [x] `ContextBuilder` - Assembles top chunks into context
- [x] `CitationFormatter` - Formats source references
- [x] `QueryNormalizerService` - LLM-based Czech query normalization (declension, synonyms)

### 3.4 Chat Service
- [x] `OpenAiChatService` - Calls GPT with streaming support
- [x] `ModerationService` - Input moderation via OpenAI
- [x] `ConversationManager` - Manages chat sessions and history

---

## Phase 4: Chat API Endpoints

### 4.1 Controllers
Create in `frontend/src/Controller/Chat/`:
- [x] `StartChatController` - POST `/chat/start` - Creates conversation, sets guest_id cookie
- [x] `SendMessageController` - POST `/chat/{cid}/messages` - Accepts message, streams SSE response
- [x] `EndChatController` - POST `/chat/{cid}/end` - Ends conversation
- [x] `GetConversationController` - GET `/chat/{cid}` - Retrieves conversation with messages

### 4.2 Rate Limiting
- [x] Apply rate limiters to all chat endpoints
- [x] Configure HTTP 429 responses with retry-after header
- [x] Track by guest_id + IP combination

### 4.3 Guest Identification
- [x] Generate UUID guest_id on first visit
- [x] Store as HttpOnly, SameSite=Lax cookie
- [x] Configure 1-year expiration

---

## Phase 5: Frontend Chat Widget

### 5.1 Symfony UX Component
- [x] Create `frontend/src/Components/ChatWidget.php`
- [x] Create Twig template with Bootstrap 5 modal
- [x] Add message list container
- [x] Add input form

### 5.2 Stimulus Controller
- [x] Create `frontend/assets/controllers/chat_controller.js`
- [x] Implement modal open/close toggle
- [x] Implement message submission
- [x] Implement SSE streaming connection
- [x] Implement localStorage persistence (conversation_id)
- [x] Implement cooldown timer display
- [x] Implement citation link rendering

### 5.3 Integration
- [x] Add `<twig:ChatWidget/>` to base template
- [x] Add floating chat button (bottom-right corner)
- [x] Include necessary Bootstrap JavaScript

---

## Phase 6: Ingestion Worker

### 6.1 Console Command
- [x] Create `frontend/src/ConsoleCommands/AiIngestCommand.php` (`bin/console ai:ingest`)
- [x] Implement direct Strapi extraction (PDF files, images, web content)
- [x] Implement PDF parsing
- [x] Implement image OCR via OpenAI Vision
- [x] Implement text chunking
- [x] Implement embedding generation
- [x] Implement database storage with change detection
- [x] Add progress bar output
- [x] Add `--force` flag
- [x] Add `--pdf-only`, `--images-only`, `--content-only` flags
- [x] Create `AiSearchTestCommand` (`bin/console ai:search-test`) for testing search

### 6.2 Scheduling
- [x] Document cron setup in getting-started guide
- [ ] Add deployment instructions

---

## Phase 7: Testing & Documentation

### 7.1 Tests
- [ ] Test chunking logic
- [ ] Test vector search queries
- [ ] Test rate limiter
- [ ] Test chat flow (start → message → end)

### 7.2 Documentation
- [ ] Update README with AI setup instructions
- [ ] Document ENV variables
- [ ] Document cron setup
- [ ] Add API endpoint documentation

---

## Implementation Status

**Current Phase**: Phases 1-6 COMPLETE ✅ | Phase 7 Partial
**Last Updated**: 2026-01-28
**Overall Progress**: All core features implemented (Phases 1-6). Phase 3.1 was redesigned (direct Strapi integration instead of JSON feed controllers). Phase 7 (testing) not yet done.
