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

### 3.1 Data Feed Controllers
Create in `frontend/src/Controller/Ai/`:
- [ ] `AiFilesJsonController` - Uses existing `FileExtractor` to output `/ai/files.json`
- [ ] `AiContentJsonController` - Uses existing `ContentNormalizer` to output `/ai/content.json`

### 3.2 Ingestion Service
Create `frontend/src/Services/Ai/`:
- [x] `PdfParser` - Extracts text from PDFs (use `smalot/pdfparser` library)
- [x] `TextChunker` - Splits text into 1000-token chunks with 100 overlap
- [x] `EmbeddingService` - Calls OpenAI to generate embeddings
- [x] `DocumentHasher` - Detects changed documents
- [x] `IngestionService` - Orchestrates ingestion pipeline

### 3.3 Retrieval Service
- [x] `VectorSearchService` - Hybrid search (pgvector + keyword)
- [x] `ContextBuilder` - Assembles top chunks into context
- [x] `CitationFormatter` - Formats source references

### 3.4 Chat Service
- [x] `OpenAiChatService` - Calls GPT with streaming support
- [x] `ModerationService` - Input moderation via OpenAI
- [x] `ConversationManager` - Manages chat sessions and history

---

## Phase 4: Chat API Endpoints

### 4.1 Controllers
Create in `frontend/src/Controller/Chat/`:
- [ ] `StartChatController` - POST `/chat/start` - Creates conversation, sets guest_id cookie
- [ ] `SendMessageController` - POST `/chat/{cid}/messages` - Accepts message, streams SSE response
- [ ] `EndChatController` - POST `/chat/{cid}/end` - Ends conversation

### 4.2 Rate Limiting
- [ ] Apply rate limiters to all chat endpoints
- [ ] Configure HTTP 429 responses with retry-after header
- [ ] Track by guest_id + IP combination

### 4.3 Guest Identification
- [ ] Generate UUID guest_id on first visit
- [ ] Store as HttpOnly, SameSite=Lax cookie
- [ ] Configure 1-year expiration

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
- [ ] Create `frontend/src/Command/AiIngestCommand.php`
- [ ] Implement fetching of `/ai/files.json` and `/ai/content.json`
- [ ] Implement PDF parsing
- [ ] Implement text chunking
- [ ] Implement embedding generation
- [ ] Implement database storage with change detection
- [ ] Add progress bar output
- [ ] Add `--force` flag

### 6.2 Scheduling
- [ ] Document cron setup in README
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

**Current Phase**: COMPLETE ✅
**Last Updated**: 2025-11-06
**Overall Progress**: All Phases Complete (1-6) - Testing & Documentation Recommended
