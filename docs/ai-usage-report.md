# AI Assistant Monthly Report – Generation Guide

This document describes how to generate the **monthly PDF report** about AI assistant
usage for the municipality of Těrlicko. The report can be regenerated every month with
consistent branding and layout — only the numbers change.

The **PDF itself is in Czech** (it is sent to the municipality as the end customer).
This guide is in English for internal / developer use.

---

## TL;DR

Report for the **previous complete month**:

```bash
cd docs/ai-usage-report
python3 generate.py
```

Report for a specific month:

```bash
python3 generate.py 2026-03
```

Output:

- `docs/ai-usage-report/output/ai-usage-YYYY-MM.html` – intermediate for manual tweaks
- `docs/ai-usage-report/output/ai-usage-YYYY-MM.pdf`  – final deliverable for the client

---

## What the report contains

Six-page A4 PDF in the municipality branding:

1. **Cover** – month label + three hero numbers (conversations / visitors / messages)
2. **Monthly overview** – six KPI cards (conversations, visitors, messages, words, characters, avg messages per conversation)
3. **Activity over time** – daily bar chart, day-of-week and hour-of-day breakdown, peaks
4. **What was discussed** – top 15 topics by auto-generated conversation titles
5. **Content characteristics** – user vs. assistant split (messages / words / characters / avg length)
6. **Quality & moderation** – off-topic attempts, feedback, historical trend line chart

---

## Prerequisites

1. **SSH access** to the production server `root@spare.srv.thedevs.cz`
   (deployment lives in `/deployment/terlicko`)
2. **Python 3.10+** locally — standard library only, no `pip install` needed
3. **WeasyPrint** CLI in `$PATH` — tested with `WeasyPrint 68.1`
   ```bash
   which weasyprint && weasyprint --version
   ```
   macOS install: `brew install weasyprint`

---

## How it works under the hood

The pipeline is **reproducible** and **read-only** — nothing is written on the server.

```
┌────────────────┐    SSH    ┌──────────────────────────────┐    JSON    ┌─────────────┐
│  generate.py   │ ────────▶ │  docker compose exec -T      │ ─────────▶ │  HTML       │
│  (local)       │  query.sql│  postgres psql … (read-only) │            │  (Python    │
│                │           └──────────────────────────────┘            │   render)   │
└────────────────┘                                                        └──────┬──────┘
                                                                                  │
                                                                           weasyprint
                                                                                  │
                                                                            ┌─────▼─────┐
                                                                            │    PDF    │
                                                                            └───────────┘
```

### Key files

| File | Role |
|---|---|
| `docs/ai-usage-report/generate.py` | orchestration — SSH call, HTML rendering, WeasyPrint invocation |
| `docs/ai-usage-report/query.sql` | single `SELECT` returning all metrics as one JSON blob |
| `docs/ai-usage-report/template.html` | HTML/CSS template with `{{PLACEHOLDER}}` tokens |
| `docs/ai-usage-report/output/` | generated HTML + PDF |

### The database query

All aggregation happens **on the Postgres side** via a single
`SELECT json_build_object(...)`. One JSON row is returned and Python just parses it —
no message content or PII is ever written to disk.

Tables used:

- `ai_conversations` — conversations (guest_id, started_at, title, …)
- `ai_messages` — messages (role, content, created_at, conversation_id)
- `ai_offtopic_violations` — off-topic attempts caught by moderation
- `ai_message_feedback` — negative feedback submitted by citizens

### Rendering

- **Charts** are generated as inline SVG directly in Python — no matplotlib / Chart.js.
  Works offline, stays crisp, no JS runtime needed.
- **Localisation**: Czech month names in nominative + genitive (for sentences like
  "v březnu 2026") and Czech weekday names are hardcoded in `generate.py`.
- **Layout**: WeasyPrint consumes the HTML + CSS `@page` rules and produces A4 PDF
  with footer and page numbering.

---

## Generating a report for a new month — step by step

### 1. Pull data and build the PDF

```bash
cd docs/ai-usage-report
python3 generate.py 2026-04     # replace with target month
```

The script:

1. Reads `query.sql`
2. SSHes to the server and runs it through `docker compose exec postgres psql`
3. Parses the JSON response
4. Renders `template.html` with the values substituted
5. Calls `weasyprint` to produce the PDF

### 2. Sanity-check the output

```bash
open output/ai-usage-2026-04.pdf
```

What to check:

- **Cover** — month label matches, hero numbers look right
- **Overview** — "Co to znamená" paragraph reads well (all numbers are auto-interpolated)
- **Topics** — no nonsense / test data in the top 15
- **History trend** — the last data point matches the month you just generated

### 3. Optional manual tweaks

If you need a one-off edit before sending:

- Edit `output/ai-usage-YYYY-MM.html` and re-run:
  ```bash
  weasyprint output/ai-usage-YYYY-MM.html output/ai-usage-YYYY-MM.pdf
  ```
- For permanent changes, edit `template.html` / `generate.py` and run `generate.py` again.

### 4. Hand it over to the client

Final deliverable is `output/ai-usage-YYYY-MM.pdf` (~110 KB).

---

## Ad-hoc queries if the client asks for extra numbers

Run SQL directly when something is missing from the report:

```bash
ssh root@spare.srv.thedevs.cz \
  "cd /deployment/terlicko && docker compose exec -T postgres \
   psql -U terlickodatabase -d terlicko -c \"SELECT ...;\""
```

Useful ready-made queries (all read-only, safe on production):

**Month-over-month comparison — conversations / messages / unique visitors:**
```sql
SELECT
  TO_CHAR(date_trunc('month', c.started_at), 'YYYY-MM') AS month,
  COUNT(DISTINCT c.id) AS conversations,
  COUNT(DISTINCT c.guest_id) AS visitors,
  COUNT(m.id) AS messages
FROM ai_conversations c
LEFT JOIN ai_messages m ON m.conversation_id = c.id
GROUP BY 1 ORDER BY 1;
```

**Top citizen questions (first user message of each conversation):**
```sql
SELECT c.started_at::date AS day, m.content
FROM ai_conversations c
JOIN LATERAL (
  SELECT content FROM ai_messages
  WHERE conversation_id = c.id AND role = 'user'
  ORDER BY created_at LIMIT 1
) m ON true
WHERE c.started_at >= '2026-03-01' AND c.started_at < '2026-04-01'
ORDER BY c.started_at DESC;
```

---

## Possible extensions

Small additions (edit `query.sql` + `template.html`):

- **List of specific questions** — first user message, top N longest conversations
- **Day × hour heatmap** — SVG grid, `hourly` + `daily` data is already there
- **Median / p95 conversation length** — `percentile_cont(0.95) WITHIN GROUP …`
- **Completion rate** — % of conversations ending without / with feedback
- **Month-over-month delta** — run the query twice and render delta numbers

For pure visual changes (weekly grouping, colour palette) touch only `template.html`
(CSS) and/or `generate.py` (colour args in `svg_bar_chart` / `svg_line_chart`).

---

## Troubleshooting

| Problem | Diagnosis |
|---|---|
| `Empty response from database` | SSH worked but psql returned nothing. Check `docker compose ps` on the server and make sure postgres is healthy. |
| `weasyprint: command not found` | `brew install weasyprint` (macOS) or `pip install weasyprint` (needs system Pango/Cairo). |
| `Invalid month: 'xxx'` | Parameter must be `YYYY-MM` (e.g. `2026-03`, not `March 2026`). |
| Broken layout / empty PDF | Open `output/ai-usage-YYYY-MM.html` in a browser — layout errors are easier to spot there. |
| SSH asks for a password | Install an SSH key (`ssh-copy-id root@spare.srv.thedevs.cz`); the script cannot handle interactive password prompts. |

---

## Security notes

- The script connects to the database **read-only** via `psql -c "SELECT …"`
  — no `UPDATE` / `INSERT` / `DELETE` anywhere.
- The query never logs raw message content — it only aggregates (counts, lengths).
- The `output/*.pdf` contains **only aggregated numbers and conversation titles** — no
  PII of individual citizens.
- If the client ever asks for a dump of specific questions, treat it as a GDPR matter
  (the municipality is the data controller, not us).
