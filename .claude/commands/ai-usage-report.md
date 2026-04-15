---
description: Generate the monthly AI assistant PDF report for the municipality
argument-hint: "[YYYY-MM]  (optional; defaults to previous calendar month)"
---

Run the Těrlicko AI-usage report generator for the requested month.

**Argument:** `$ARGUMENTS` (either empty → previous month, or `YYYY-MM` like `2026-04`)

## Steps

1. Validate the argument. If non-empty, it must match `YYYY-MM`. Otherwise pass nothing (script defaults to previous month).

2. Run the generator from the repo root:
   ```bash
   python3 docs/ai-usage-report/generate.py $ARGUMENTS
   ```
   It SSHes to the production server read-only, queries Postgres, renders `template.html`, and produces a PDF via WeasyPrint.

3. When it finishes, report back to the user:
   - the PDF path (`docs/ai-usage-report/output/ai-usage-YYYY-MM.pdf`)
   - headline numbers from the month (conversations, unique visitors, messages, top topic)

## Context Claude should already know

- Script entry point: `docs/ai-usage-report/generate.py`
- Data source: Postgres on `root@spare.srv.thedevs.cz` inside `docker compose exec postgres`
- Tables used: `ai_conversations`, `ai_messages`, `ai_message_feedback`, `ai_offtopic_violations`
- Output is Czech-localised, A4, branded with the Těrlicko logo and corporate palette
- Output directory (`output/`) is gitignored — do not commit PDFs
- Full documentation: `docs/ai-usage-report.md`

## Troubleshooting

- `weasyprint: command not found` → tell the user to `brew install weasyprint`
- SSH prompts for password → tell the user to install an SSH key via `ssh-copy-id`
- Month outside data range (before 2025-11) → report would be empty; confirm with the user before running
