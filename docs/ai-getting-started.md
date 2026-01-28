# AI RAG Chatbot - Getting Started Guide

## 🎉 Implementation Complete!

The AI RAG chatbot for Těrlicko municipality is **fully implemented** and ready to use.

---

## 📋 Prerequisites

Before starting, ensure you have:

1. **Docker & Docker Compose** installed and running
2. **OpenAI API Key** (sign up at https://platform.openai.com/)
3. All services started: `docker compose up -d`

---

## ⚙️ Configuration

### 1. Set OpenAI API Key

Edit `frontend/.env`:

```bash
OPENAI_API_KEY=sk-proj-...YOUR_KEY_HERE...
```

### 2. Verify Other Settings

The following are already configured, but you can adjust them:

```bash
REDIS_HOST=redis
REDIS_PORT=6379
AI_EMBEDDING_MODEL=text-embedding-3-small
AI_CHAT_MODEL=gpt-4o-mini
AI_CHUNK_SIZE=1000
AI_CHUNK_OVERLAP=100
```

**Model Options:**
- **Embeddings**: `text-embedding-3-small` (cheaper) or `text-embedding-3-large` (better quality)
- **Chat**: `gpt-4o-mini` (faster, cheaper) or `gpt-4o` (more capable)

---

## 🚀 Initial Setup

### Step 1: Verify Services

```bash
docker compose ps
```

All services should be `Up (healthy)`:
- ✅ frontend
- ✅ postgres
- ✅ redis
- ✅ strapi
- ✅ adminer

### Step 2: Run Database Migrations

```bash
docker compose exec frontend bin/console doctrine:migrations:migrate --no-interaction
```

This creates the necessary database tables:
- `ai_documents`
- `ai_chunks`
- `ai_embeddings`
- `ai_conversations`
- `ai_messages`

### Step 3: Run Initial Ingestion

```bash
docker compose exec frontend bin/console ai:ingest
```

This will:
- Fetch all PDFs from Strapi and extract text
- Fetch all images from Strapi and extract text via OCR (OpenAI Vision)
- Fetch all web content (aktuality, sekce, uredni deska, kalendar akci) from Strapi
- Chunk text and generate embeddings via OpenAI
- Store everything in PostgreSQL

**Expected output:**
```
AI Document Ingestion
=====================

Processing PDF Documents
Found X PDF files
[============================] 100%

Processing Image Documents (OCR)
Found X image files
[============================] 100%

Processing Web Content
Found X content items (aktuality, sekce, uredni deska, kalendar akci)
[============================] 100%

Summary
-------
[OK] Processed X documents, created Y chunks
```

⏱️ **Time**: ~2-5 minutes depending on document count and OpenAI API speed.

---

## 🧪 Testing the Chatbot

### Test 1: Frontend UI

1. Open your browser: http://localhost:8080
2. Look for the **blue chat button** in the bottom-right corner
3. Click the button to open the chat modal
4. Send a test message: "Co je nového v Těrlicku?"
5. Watch the streaming response appear
6. Verify citations are shown at the bottom

### Test 2: API Endpoints

**Start Conversation:**
```bash
curl -X POST http://localhost:8080/chat/start \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -v
```

Response:
```json
{
  "conversation_id": "01939b52-...",
  "guest_id": "01939b52-...",
  "started_at": "2025-11-06T..."
}
```

**Send Message:**
```bash
CONVERSATION_ID="..." # from above

curl -X POST "http://localhost:8080/chat/$CONVERSATION_ID/messages" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"message":"Jak funguje sběr odpadu?"}' \
  --no-buffer
```

You'll see SSE events streaming:
```
event: sources
data: [{"index":1,"url":"...","title":"..."}]

event: message
data: {"content":"Sběr"}

event: message
data: {"content":" odpadu"}

event: done
data: {"status":"complete"}
```

**Get Conversation (restore on page reload):**
```bash
curl "http://localhost:8080/chat/$CONVERSATION_ID" \
  -b cookies.txt | jq
```

**End Conversation:**
```bash
curl -X POST "http://localhost:8080/chat/$CONVERSATION_ID/end" \
  -H "Content-Type: application/json" \
  -b cookies.txt
```

---

## 🔧 Additional Console Commands

### Search Test
Test the vector search quality with a query:

```bash
docker compose exec frontend bin/console ai:search-test "sběr odpadu"
```

This shows ranked search results with distance scores, document types, and content previews.

### Ingestion Options

```bash
# Ingest only PDFs
docker compose exec frontend bin/console ai:ingest --pdf-only

# Ingest only images (OCR)
docker compose exec frontend bin/console ai:ingest --images-only

# Ingest only web content
docker compose exec frontend bin/console ai:ingest --content-only

# Force re-ingestion of all documents
docker compose exec frontend bin/console ai:ingest --force
```

---

## 🔄 Regular Maintenance

### Update Content (Run Periodically)

To keep the AI up-to-date with new content from Strapi:

```bash
docker compose exec frontend bin/console ai:ingest
```

**Recommended schedule:**
- **Development**: Manual as needed
- **Production**: Every 30 minutes via cron

### Cron Setup (Production)

Add to your server's crontab:

```cron
# Update AI knowledge base every 30 minutes
*/30 * * * * cd /path/to/terlicko && docker compose exec -T frontend bin/console ai:ingest >> /var/log/ai-ingest.log 2>&1
```

### Monitor Database

Check how many documents are indexed:

```bash
docker compose exec postgres psql -U postgres -d terlicko -c "
SELECT
    (SELECT COUNT(*) FROM ai_documents) as documents,
    (SELECT COUNT(*) FROM ai_chunks) as chunks,
    (SELECT COUNT(*) FROM ai_embeddings) as embeddings,
    (SELECT COUNT(*) FROM ai_conversations) as conversations,
    (SELECT COUNT(*) FROM ai_messages) as messages;
"
```

---

## 🐛 Troubleshooting

### Problem: Chat button doesn't appear

**Check:**
1. Is cache cleared? `docker compose exec frontend bin/console cache:clear`
2. Is the component registered? Check `frontend/src/Components/ChatWidget.php` exists
3. Check browser console for JavaScript errors

### Problem: "Failed to start conversation"

**Check:**
1. Is Redis running? `docker compose ps redis`
2. Are cookies enabled in browser?
3. Check frontend logs: `docker compose logs frontend -f`

### Problem: "Rate limit exceeded"

This is expected behavior! Limits are:
- 10 messages per minute
- 100 messages per day
- 12 new conversations per hour

**To adjust**, edit `frontend/config/packages/framework.php`:

```php
'rate_limiter' => [
    'ai_chat_messages' => [
        'policy' => 'sliding_window',
        'limit' => 20,  // Change from 10 to 20
        'interval' => '1 minute',
    ],
    // ...
]
```

### Problem: OpenAI API errors

**Common causes:**
1. Invalid API key → Check `.env`
2. Insufficient credits → Check https://platform.openai.com/usage
3. Rate limit → Wait or upgrade plan

**Debug:**
```bash
# Check if API key is set
docker compose exec frontend bin/console debug:container --parameter=openai.api_key

# Test API connection
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer YOUR_KEY" \
  -H "Content-Type: application/json"
```

### Problem: pgvector errors

If you see "type vector does not exist":

```bash
# Restart postgres to load pgvector extension
docker compose restart postgres

# Wait 10 seconds, then run migrations again
docker compose exec frontend bin/console doctrine:migrations:migrate --no-interaction
```

### Problem: Slow responses

**Causes:**
1. **Too many chunks retrieved** → Reduce in `VectorSearchService` (change limit from 10 to 5)
2. **Large context** → Reduce `maxTokens` in `ContextBuilder`
3. **Network latency** → OpenAI API response time varies

**Optimize:**
```bash
# Create vector index for faster searches
docker compose exec postgres psql -U postgres -d terlicko -c "
CREATE INDEX IF NOT EXISTS ai_embeddings_vector_idx
ON ai_embeddings
USING ivfflat (vector vector_cosine_ops)
WITH (lists = 100);
"
```

---

## 📊 Monitoring

### View Recent Conversations

```bash
docker compose exec postgres psql -U postgres -d terlicko -c "
SELECT
    id,
    guest_id,
    started_at,
    ended_at,
    (SELECT COUNT(*) FROM ai_messages WHERE conversation_id = c.id) as message_count
FROM ai_conversations c
ORDER BY started_at DESC
LIMIT 10;
"
```

### View Popular Queries

```bash
docker compose exec postgres psql -U postgres -d terlicko -c "
SELECT
    content,
    COUNT(*) as count
FROM ai_messages
WHERE role = 'user'
GROUP BY content
ORDER BY count DESC
LIMIT 10;
"
```

### Check Redis Cache

```bash
docker compose exec redis redis-cli

# Inside redis-cli:
KEYS *
INFO stats
```

---

## 🎨 Customization

### Change Chat Button Position

Edit `frontend/templates/components/ChatWidget.html.twig`:

```html
<!-- Move to bottom-left -->
<button ... class="... bottom-0 start-0 m-4 ...">

<!-- Move to top-right -->
<button ... class="... top-0 end-0 m-4 ...">
```

### Change Chat Colors

Edit the button classes in ChatWidget template:

```html
<!-- Change from primary (blue) to success (green) -->
<button ... class="btn btn-success ...">

<!-- Change modal header -->
<div class="modal-header bg-success text-white">
```

### Customize System Prompt

Edit `frontend/src/Services/Ai/OpenAiChatService.php`:

```php
private const SYSTEM_PROMPT = <<<'PROMPT'
    You are a helpful assistant for ...
    // Your custom instructions here
    PROMPT;
```

### Adjust Rate Limits

Edit `frontend/config/packages/framework.php`:

```php
'rate_limiter' => [
    'ai_chat_messages' => [
        'limit' => 20,      // messages per interval
        'interval' => '1 minute',
    ],
    'ai_chat_daily' => [
        'limit' => 200,     // messages per day
        'interval' => '1 day',
    ],
],
```

---

## 🔒 Security Checklist

- [x] Input moderation enabled (OpenAI Moderation API)
- [x] Rate limiting configured
- [x] Guest IDs stored in HttpOnly cookies
- [x] SQL injection protected (Doctrine ORM)
- [x] XSS protected (Twig auto-escaping)
- [x] CSRF tokens not needed (stateless API)
- [ ] Add content security policy (optional)
- [ ] Monitor API usage and costs
- [ ] Set up alerts for unusual activity

---

## 💰 Cost Estimation

Based on OpenAI pricing (as of 2025):

**Embeddings** (text-embedding-3-small):
- $0.02 per 1M tokens
- ~100 documents × 1000 tokens each = 100K tokens
- Initial ingestion: ~$0.002
- Updates: ~$0.002 per update

**Chat** (gpt-4o-mini):
- $0.150 per 1M input tokens
- $0.600 per 1M output tokens
- Average conversation: ~2000 tokens
- 1000 conversations: ~$1.50

**Monthly estimate** (100 conversations/day):
- Embeddings: ~$0.20 (weekly updates)
- Chat: ~$45 (3000 conversations)
- **Total: ~$45-50/month**

**To reduce costs:**
1. Use smaller context (fewer chunks)
2. Cache common queries
3. Limit conversation length
4. Use gpt-4o-mini instead of gpt-4o

---

## 📚 Additional Resources

- **OpenAI Documentation**: https://platform.openai.com/docs
- **Symfony Documentation**: https://symfony.com/doc/current/
- **pgvector Documentation**: https://github.com/pgvector/pgvector
- **Stimulus Documentation**: https://stimulus.hotwired.dev/

---

## 🎯 Next Steps

Now that the chatbot is running:

1. ✅ **Test thoroughly** with various questions
2. ✅ **Monitor API usage** on OpenAI dashboard
3. ✅ **Adjust rate limits** based on actual usage
4. ✅ **Set up regular ingestion** via cron
5. ✅ **Gather user feedback** and improve prompts
6. ⏳ **Add analytics** to track popular queries
7. ⏳ **Add feedback buttons** (thumbs up/down)
8. ⏳ **Multi-language support** if needed

---

## ✅ Success!

Your AI RAG chatbot is now **live and operational**! 🎉

Users can click the chat button and get instant, accurate answers about Těrlicko municipality services, based on official documents and website content.

**Questions?** Check the troubleshooting section above or review the implementation status document at `docs/ai-implementation-status.md`.
