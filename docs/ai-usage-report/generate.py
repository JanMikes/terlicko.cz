#!/usr/bin/env python3
"""
Generate a branded monthly AI-usage PDF report for the Těrlicko chatbot.

Usage:
    ./generate.py               # last complete calendar month
    ./generate.py 2026-03       # specific month

Reads data read-only from the production Postgres over SSH.
Produces an HTML intermediate and a PDF using WeasyPrint.
"""

from __future__ import annotations

import json
import subprocess
import sys
from datetime import date, datetime
from pathlib import Path

SSH_HOST = "root@spare.srv.thedevs.cz"
DEPLOY_DIR = "/deployment/terlicko"

SCRIPT_DIR = Path(__file__).resolve().parent
QUERY_FILE = SCRIPT_DIR / "query.sql"
TEMPLATE_FILE = SCRIPT_DIR / "template.html"
OUTPUT_DIR = SCRIPT_DIR / "output"
LOGO_WHITE_FILE = SCRIPT_DIR.parent.parent / "frontend" / "assets" / "images" / "logo-white.svg"

# Těrlicko corporate palette (from frontend/assets/styles/app.css)
COLOR_NAVY     = "#1E2648"
COLOR_NAVY_MID = "#32416C"
COLOR_BLUE     = "#8CADD5"
COLOR_BLUE_LT  = "#D3E5F7"
COLOR_WARM     = "#c5761f"

CS_MONTHS_NOM = [
    "", "leden", "únor", "březen", "duben", "květen", "červen",
    "červenec", "srpen", "září", "říjen", "listopad", "prosinec",
]
CS_MONTHS_GEN = [
    "", "lednu", "únoru", "březnu", "dubnu", "květnu", "červnu",
    "červenci", "srpnu", "září", "říjnu", "listopadu", "prosinci",
]
CS_WEEKDAY_SHORT = ["Po", "Út", "St", "Čt", "Pá", "So", "Ne"]
CS_WEEKDAY_LONG  = ["pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota", "neděle"]


# ---------------------------------------------------------------------------
# data fetch
# ---------------------------------------------------------------------------

def month_bounds(yyyy_mm: str) -> tuple[str, str]:
    y, m = map(int, yyyy_mm.split("-"))
    start = date(y, m, 1)
    end = date(y + (m // 12), (m % 12) + 1, 1)
    return start.isoformat(), end.isoformat()


def previous_month() -> str:
    today = date.today()
    y, m = today.year, today.month
    if m == 1:
        return f"{y-1:04d}-12"
    return f"{y:04d}-{m-1:02d}"


def fetch_data(month: str) -> dict:
    start, end = month_bounds(month)
    query = QUERY_FILE.read_text(encoding="utf-8")

    remote = (
        f"cd {DEPLOY_DIR} && docker compose exec -T postgres "
        f"psql -U terlickodatabase -d terlicko -t -A "
        f"-v month_start={start} -v month_end={end}"
    )
    proc = subprocess.run(
        ["ssh", SSH_HOST, remote],
        input=query, text=True, capture_output=True, check=True,
    )
    payload = proc.stdout.strip()
    if not payload:
        raise RuntimeError(f"Empty response from database\nstderr: {proc.stderr}")
    return json.loads(payload)


# ---------------------------------------------------------------------------
# formatting helpers
# ---------------------------------------------------------------------------

def fmt_int(n: int | float | None) -> str:
    if n is None:
        return "0"
    return f"{int(n):,}".replace(",", "\u00a0")  # non-breaking space


def fmt_float(n: float | int | None, digits: int = 1) -> str:
    if n is None:
        return "0"
    return f"{float(n):.{digits}f}".replace(".", ",")


def pct(num, den) -> str:
    if not den:
        return "0"
    return f"{round(num * 100 / den)}"


def month_label(month: str) -> tuple[str, str]:
    y, m = map(int, month.split("-"))
    return f"{CS_MONTHS_NOM[m]} {y}", f"{CS_MONTHS_GEN[m]} {y}"


# ---------------------------------------------------------------------------
# SVG charts (self-contained, no external deps)
# ---------------------------------------------------------------------------

def svg_bar_chart(items: list[tuple[str, int]], *, height_px: int = 120, bar_color: str = COLOR_NAVY_MID) -> str:
    """Horizontal x-axis bar chart. items = [(label, value)]."""
    if not items:
        return '<svg viewBox="0 0 400 80"><text x="200" y="40" text-anchor="middle" fill="#8a93a3" font-size="12">Žádná data</text></svg>'

    w = 800
    h = height_px
    pad_l, pad_r, pad_t, pad_b = 32, 12, 12, 28
    chart_w = w - pad_l - pad_r
    chart_h = h - pad_t - pad_b

    max_v = max((v for _, v in items), default=1) or 1
    n = len(items)
    gap = 4
    bar_w = max(2.0, (chart_w - gap * (n - 1)) / n)

    # y-axis ticks (0, max/2, max)
    ticks = [0, max_v / 2, max_v]
    tick_lines = ""
    for t in ticks:
        y = pad_t + chart_h - (t / max_v) * chart_h
        tick_lines += (
            f'<line x1="{pad_l}" x2="{w - pad_r}" y1="{y:.1f}" y2="{y:.1f}" '
            f'stroke="{COLOR_BLUE_LT}" stroke-width="0.6"/>'
            f'<text x="{pad_l - 4}" y="{y + 3:.1f}" text-anchor="end" font-size="8" fill="#8CADD5">{int(round(t))}</text>'
        )

    bars = ""
    labels = ""
    # gradient def
    grad_id = f"grad_{id(items) & 0xffff:x}"
    defs = (
        f'<defs><linearGradient id="{grad_id}" x1="0" y1="0" x2="0" y2="1">'
        f'<stop offset="0%" stop-color="{COLOR_NAVY_MID}"/>'
        f'<stop offset="100%" stop-color="{COLOR_NAVY}"/>'
        f'</linearGradient></defs>'
    )
    fill = f"url(#{grad_id})" if bar_color == COLOR_NAVY_MID else bar_color
    for i, (label, v) in enumerate(items):
        x = pad_l + i * (bar_w + gap)
        bh = (v / max_v) * chart_h if max_v else 0
        y = pad_t + chart_h - bh
        bars += (
            f'<rect x="{x:.1f}" y="{y:.1f}" width="{bar_w:.1f}" height="{bh:.1f}" '
            f'fill="{fill}" rx="1.2"/>'
        )
        if n <= 32 or i % max(1, n // 16) == 0:
            labels += (
                f'<text x="{x + bar_w/2:.1f}" y="{h - pad_b + 11}" '
                f'text-anchor="middle" font-size="8" fill="#5a6b8c">{label}</text>'
            )

    return (
        f'<svg viewBox="0 0 {w} {h}" xmlns="http://www.w3.org/2000/svg" '
        f'preserveAspectRatio="xMidYMid meet">'
        f'{defs}{tick_lines}{bars}{labels}'
        f'</svg>'
    )


def svg_line_chart(items: list[tuple[str, int]], *, height_px: int = 140) -> str:
    """Simple line + area chart for monthly history."""
    if not items:
        return ""
    w = 800
    h = height_px
    pad_l, pad_r, pad_t, pad_b = 40, 12, 14, 28
    chart_w = w - pad_l - pad_r
    chart_h = h - pad_t - pad_b

    max_v = max((v for _, v in items), default=1) or 1
    n = len(items)
    if n == 1:
        xs = [pad_l + chart_w / 2]
    else:
        xs = [pad_l + i * chart_w / (n - 1) for i in range(n)]
    ys = [pad_t + chart_h - (v / max_v) * chart_h for _, v in items]

    pts = " ".join(f"{x:.1f},{y:.1f}" for x, y in zip(xs, ys))
    area = (
        f'<polygon points="{pad_l},{pad_t + chart_h} {pts} {pad_l + chart_w},{pad_t + chart_h}" '
        f'fill="{COLOR_BLUE}" fill-opacity="0.22"/>'
    )
    line = f'<polyline points="{pts}" fill="none" stroke="{COLOR_NAVY}" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>'
    dots = "".join(
        f'<circle cx="{x:.1f}" cy="{y:.1f}" r="2.8" fill="{COLOR_NAVY}" stroke="#fff" stroke-width="1.2"/>'
        for x, y in zip(xs, ys)
    )

    # value labels on dots
    val_labels = ""
    for (x, y, (_, v)) in zip(xs, ys, items):
        val_labels += (
            f'<text x="{x:.1f}" y="{y - 7:.1f}" text-anchor="middle" '
            f'font-size="8.5" fill="{COLOR_NAVY}" font-weight="700">{v}</text>'
        )

    # y-axis
    ticks = [0, max_v / 2, max_v]
    tick_lines = ""
    for t in ticks:
        y = pad_t + chart_h - (t / max_v) * chart_h
        tick_lines += (
            f'<line x1="{pad_l}" x2="{w - pad_r}" y1="{y:.1f}" y2="{y:.1f}" '
            f'stroke="{COLOR_BLUE_LT}" stroke-width="0.6"/>'
            f'<text x="{pad_l - 4}" y="{y + 3:.1f}" text-anchor="end" font-size="8" fill="#8CADD5">{int(round(t))}</text>'
        )

    # x labels
    x_labels = ""
    for (x, (label, _)) in zip(xs, items):
        x_labels += (
            f'<text x="{x:.1f}" y="{h - pad_b + 12}" text-anchor="middle" '
            f'font-size="8.5" fill="#5a6b8c">{label}</text>'
        )

    return (
        f'<svg viewBox="0 0 {w} {h}" xmlns="http://www.w3.org/2000/svg">'
        f'{tick_lines}{area}{line}{dots}{val_labels}{x_labels}'
        f'</svg>'
    )


# ---------------------------------------------------------------------------
# rendering
# ---------------------------------------------------------------------------

def topics_block(topics: list[dict]) -> str:
    if not topics:
        return (
            '<p style="color:#5a6b8c;">V tomto období nebyly u konverzací '
            'vygenerovány žádné názvy tématu.</p>'
        )
    max_c = max((t["count"] for t in topics), default=1)
    rows = []
    for t in topics:
        width = int(round(t["count"] * 100 / max_c))
        rows.append(
            f'<div class="topic-row">'
            f'  <div class="label">{escape(t["title"])}</div>'
            f'  <div class="bar-track"><div class="bar-fill" style="width:{width}%"></div></div>'
            f'  <div class="val">{t["count"]}×</div>'
            f'</div>'
        )
    return "\n".join(rows)


def escape(s: str) -> str:
    return (
        s.replace("&", "&amp;")
         .replace("<", "&lt;")
         .replace(">", "&gt;")
    )


def render(month: str, data: dict) -> str:
    t = data["totals"]
    daily = data["daily"]
    hourly = data["hourly"]
    weekday = data["weekday"]
    topics = data["top_topics"]
    history = data["history"]

    label_nom, label_gen = month_label(month)
    start, end = month_bounds(month)

    # Peaks
    peak_day = max(daily, key=lambda d: d["conversations"], default=None)
    peak_hour = max(hourly, key=lambda d: d["conversations"], default=None)
    peak_dow = max(weekday, key=lambda d: d["conversations"], default=None)

    # Daily chart: keep day label like "05.03"
    daily_items = [
        (datetime.fromisoformat(d["day"]).strftime("%d.%m."), d["conversations"])
        for d in daily
    ]
    # Hourly chart 0..23
    hourly_map = {h["hour"]: h["conversations"] for h in hourly}
    hourly_items = [(f"{h}", hourly_map.get(h, 0)) for h in range(24)]
    # Weekday Mon..Sun
    wd_map = {w["dow"]: w["conversations"] for w in weekday}
    weekday_items = [(CS_WEEKDAY_SHORT[i], wd_map.get(i + 1, 0)) for i in range(7)]
    # History line chart (last 12 months max)
    CS_MONTHS_ABBR = ["", "led", "úno", "bře", "dub", "kvě", "čer",
                      "čec", "srp", "zář", "říj", "lis", "pro"]
    history = history[-12:]

    def _hlabel(ym: str) -> str:
        y, m = map(int, ym.split("-"))
        return f"{CS_MONTHS_ABBR[m]} {y % 100:02d}"

    history_items = [(_hlabel(h["month"]), h["conversations"]) for h in history]

    subs = {
        "REPORT_TITLE": f"AI asistent – {label_nom}",
        "MONTH_LABEL": label_nom,
        "MONTH_LABEL_GEN": label_gen,

        "T_CONVERSATIONS":     fmt_int(t["conversations"]),
        "T_UNIQUE_VISITORS":   fmt_int(t["unique_visitors"]),
        "T_MESSAGES":          fmt_int(t["messages"]),
        "T_USER_MESSAGES":     fmt_int(t["user_messages"]),
        "T_ASSISTANT_MESSAGES":fmt_int(t["assistant_messages"]),
        "T_WORDS":             fmt_int(t["total_words"]),
        "T_USER_WORDS":        fmt_int(t["user_words"]),
        "T_ASSISTANT_WORDS":   fmt_int(t["assistant_words"]),
        "T_CHARS":             fmt_int(t["total_chars"]),
        "T_USER_CHARS":        fmt_int(t["user_chars"]),
        "T_ASSISTANT_CHARS":   fmt_int(t["assistant_chars"]),
        "T_ASSISTANT_CHARS_PCT": pct(t["assistant_chars"], t["total_chars"]),
        "T_AVG_MSGS":          fmt_float(t["avg_msgs_per_conversation"], 1),
        "T_MAX_MSGS":          fmt_int(t["max_msgs_per_conversation"]),
        "T_AVG_USER_LEN":      fmt_int(t["avg_user_msg_len"]),
        "T_AVG_ASSISTANT_LEN": fmt_int(t["avg_assistant_msg_len"]),
        "T_SUBSTANTIVE":       fmt_int(t["substantive_conversations"]),
        "T_NEW_GUESTS":        fmt_int(t["new_guests"]),
        "T_RETURNING_GUESTS":  fmt_int(t["returning_guests"]),
        "T_NEW_GUESTS_PCT":    pct(t["new_guests"], t["unique_visitors"]),
        "T_OFFTOPIC":          fmt_int(t["offtopic_violations"]),
        "T_FEEDBACK":          fmt_int(t["feedback_count"]),

        "PEAK_DAY_LABEL":  (
            datetime.fromisoformat(peak_day["day"]).strftime("%d.%m.%Y")
            if peak_day else "—"
        ),
        "PEAK_DAY_COUNT":  fmt_int(peak_day["conversations"]) if peak_day else "0",
        "PEAK_HOUR":       str(peak_hour["hour"]) if peak_hour else "—",
        "PEAK_HOUR_COUNT": fmt_int(peak_hour["conversations"]) if peak_hour else "0",
        "PEAK_DOW":        CS_WEEKDAY_LONG[peak_dow["dow"] - 1] if peak_dow else "—",
        "PEAK_DOW_COUNT":  fmt_int(peak_dow["conversations"]) if peak_dow else "0",

        "CHART_DAILY":   svg_bar_chart(daily_items, height_px=140),
        "CHART_WEEKDAY": svg_bar_chart(weekday_items, height_px=120, bar_color="#1c5d99"),
        "CHART_HOURLY":  svg_bar_chart(hourly_items, height_px=120, bar_color="#1c5d99"),
        "CHART_HISTORY": svg_line_chart(history_items, height_px=160),

        "TOPICS_BLOCK": topics_block(topics),
        "PERIOD_START": start,
        "PERIOD_END":   end,
        "LOGO_WHITE_SVG": LOGO_WHITE_FILE.read_text(encoding="utf-8"),
    }

    html = TEMPLATE_FILE.read_text(encoding="utf-8")
    for k, v in subs.items():
        html = html.replace("{{" + k + "}}", str(v))
    return html


def main(argv: list[str]) -> int:
    month = argv[1] if len(argv) > 1 else previous_month()
    try:
        datetime.strptime(month, "%Y-%m")
    except ValueError:
        print(f"Invalid month: {month!r}. Use YYYY-MM.", file=sys.stderr)
        return 1

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    print(f"Fetching data for {month} …")
    data = fetch_data(month)

    html = render(month, data)

    html_path = OUTPUT_DIR / f"ai-usage-{month}.html"
    pdf_path  = OUTPUT_DIR / f"ai-usage-{month}.pdf"
    html_path.write_text(html, encoding="utf-8")
    print(f"HTML: {html_path}")

    print("Rendering PDF with WeasyPrint …")
    subprocess.run(
        ["weasyprint", str(html_path), str(pdf_path)],
        check=True,
    )
    print(f"PDF:  {pdf_path}")
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
