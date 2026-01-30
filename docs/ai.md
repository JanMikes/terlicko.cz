## Overview

The **City AI Chatbot** provides citizens with accurate, cited answers based on official city documents and website content.  
It uses a **Retrieval-Augmented Generation (RAG)** architecture built around the existing Symfony application, OpenAI API, and PostgreSQL with the **pgvector** extension.

---

## Goals

- Deliver concise, verified answers about city services.
- Retrieve responses from official documents and website text.
- Automatically stay up to date with newly published materials.
- Maintain chat persistence across page reloads.
- Control usage with rate limiting for anonymous visitors.
- Keep the chatbot accessible on every page via a modal.

---

## Core Components

### 1. **Symfony Application**
- Main web application and user interface.
- Hosts:
    - Chat API endpoints (`/chat/start`, `/chat/{cid}/messages`, `/chat/{cid}` (GET), `/chat/{cid}/end`).
- Provides the chat widget as a **Symfony UX component** (`<twig:ChatWidget/>`), embedded once in the base template.

### 2. **Ingestion Worker**
- Console command (`bin/console ai:ingest`) that updates the knowledge base.
- Extracts PDF files directly from Strapi upload API via `FileExtractor` service.
- Extracts web content (aktuality, sekce, uredni deska, kalendar akci) directly from Strapi via `AiContentExtractor` service.
- Extracts text from images using OpenAI Vision OCR via `ImageOcrService`.
- Parses PDF text using `smalot/pdfparser` library.
- Splits content into semantic chunks and generates embeddings using **OpenAI text-embedding-3-small**.
- Saves chunks and embeddings into PostgreSQL with **pgvector** for semantic search.

### 3. **Database Layer**
- Uses the existing **PostgreSQL** database with **pgvector** extension.
- Tables for:
    - `documents` and `chunks` metadata.
    - `embeddings` vectors.
    - `conversations` and `messages` for persistent chat.
- Redis instance for rate-limiting counters.

### 4. **Retrieval and Chat Service**
- Performs hybrid search combining:
    - Vector similarity search (pgvector).
    - Keyword or BM25-style lexical search.
- Merges and ranks top chunks to build contextual answers.
- Includes Czech language query normalization and expansion via `QueryNormalizerService`.
- Calls **OpenAI GPT-4o-mini** (default) or **GPT-4o** for generation.
- Streams model responses and citations to the user interface.

### 5. **Frontend Chat Widget**
- Implemented as a reusable **Symfony UX component** mounted globally.
- Stimulus controller handles:
    - Modal open/close.
    - Message streaming (SSE).
    - Conversation persistence using `localStorage`.
    - End conversation actions and cooldown handling.

---

## Data Flow Summary

1. The ingestion command (`bin/console ai:ingest`) fetches data directly from Strapi:
    - PDF files via `FileExtractor` (Strapi upload API).
    - Web content via `AiContentExtractor` (aktuality, sekce, uredni deska, kalendar akci).
    - Image files via `FileExtractor` + OCR via `ImageOcrService` (OpenAI Vision).
2. Extracted and chunked text is embedded via OpenAI and stored in PostgreSQL (pgvector).
3. When a user sends a message:
    - Symfony backend retrieves relevant chunks from the vector database.
    - Builds a context and sends it to OpenAI for an answer.
    - Streams the generated response back to the chat widget.
4. The assistant always includes **citations** pointing to the original source (URL and page).

---

## Conversation Persistence

- Each visitor is identified by a **guest_id** cookie.
- Conversations have a unique **conversation_id** stored in `localStorage`.
- Reloading the page restores the previous chat history.
- “End conversation” clears stored IDs and starts a new session.
- Conversations and messages are persisted in PostgreSQL for continuity and analytics.

---

## Rate Limiting

- Implemented with Symfony’s **RateLimiter** component using Redis.
- Based on a combination of:
    - `guest_id` cookie.
    - User IP address.
- Default configuration:
    - 10 messages per minute (burst 3).
    - 100 messages per day.
    - 12 new conversations per hour.
- Returns HTTP `429` with cooldown time if limits are exceeded.

---

## Security & Privacy

- No authentication required; all users are anonymous.
- `guest_id` stored as an HttpOnly, SameSite cookie.
- Chat data auto-expired after a defined retention period.
- Input moderated via OpenAI moderation API.
- Prompt-injection and unsafe text sanitized before processing.

---

## Key Technologies

| Layer | Technology | Purpose |
|-------|-------------|----------|
| Web Framework | **Symfony 7** | Main app, APIs, frontend |
| Frontend | **Twig + Stimulus + Symfony UX** | Modal chat interface |
| Database | **PostgreSQL + pgvector** | Document and vector storage |
| Cache / Limits | **Redis** | Rate limiter, session cache |
| Embeddings | **OpenAI text-embedding-3-small** | Semantic search vectors |
| LLM | **OpenAI GPT-4o-mini / GPT-4o** | Response generation |
| Streaming | **Server-Sent Events (SSE)** | Real-time chat updates |

---

## Deployment Notes

- The chatbot runs within the existing Symfony app.
- The ingestion worker can run as a console command or background job.
- PostgreSQL must have **pgvector** installed and indexed.
- Environment variables:
    - `OPENAI_API_KEY`
    - `DATABASE_URL`
    - `REDIS_HOST` / `REDIS_PORT`

---

## Summary

The chatbot combines city-published information with OpenAI’s language models using a RAG pipeline built entirely around Symfony and PostgreSQL.  
It provides reliable, cited answers, runs cost-efficiently, and integrates seamlessly with the existing website through a single reusable modal component.
