-- Collect all stats for a single month report + whole-history trend.
-- Parameters (as psql variables passed via -v):
--   :'month_start'  – first day of target month (YYYY-MM-01)
--   :'month_end'    – first day of next month  (YYYY-MM-01)
-- Returns a single JSON object on one line.

WITH bounds AS (
    SELECT :'month_start'::timestamp AS m_start,
           :'month_end'::timestamp   AS m_end
),
mconvs AS (
    SELECT c.*
    FROM ai_conversations c, bounds b
    WHERE c.started_at >= b.m_start AND c.started_at < b.m_end
),
mmsgs AS (
    SELECT m.*
    FROM ai_messages m
    JOIN mconvs c ON c.id = m.conversation_id
),
guest_first_seen AS (
    SELECT guest_id, MIN(started_at) AS first_at
    FROM ai_conversations
    GROUP BY guest_id
)
SELECT json_build_object(
    'totals', (
        SELECT json_build_object(
            'conversations',      (SELECT COUNT(*) FROM mconvs),
            'unique_visitors',    (SELECT COUNT(DISTINCT guest_id) FROM mconvs),
            'messages',           (SELECT COUNT(*) FROM mmsgs),
            'user_messages',      (SELECT COUNT(*) FROM mmsgs WHERE role = 'user'),
            'assistant_messages', (SELECT COUNT(*) FROM mmsgs WHERE role = 'assistant'),
            'total_chars',        (SELECT COALESCE(SUM(LENGTH(content)), 0) FROM mmsgs),
            'user_chars',         (SELECT COALESCE(SUM(LENGTH(content)), 0) FROM mmsgs WHERE role = 'user'),
            'assistant_chars',    (SELECT COALESCE(SUM(LENGTH(content)), 0) FROM mmsgs WHERE role = 'assistant'),
            'total_words',        (SELECT COALESCE(SUM(array_length(regexp_split_to_array(trim(content), '\s+'), 1)), 0) FROM mmsgs WHERE length(trim(content)) > 0),
            'user_words',         (SELECT COALESCE(SUM(array_length(regexp_split_to_array(trim(content), '\s+'), 1)), 0) FROM mmsgs WHERE role = 'user' AND length(trim(content)) > 0),
            'assistant_words',    (SELECT COALESCE(SUM(array_length(regexp_split_to_array(trim(content), '\s+'), 1)), 0) FROM mmsgs WHERE role = 'assistant' AND length(trim(content)) > 0),
            'avg_user_msg_len',      (SELECT COALESCE(ROUND(AVG(LENGTH(content))), 0) FROM mmsgs WHERE role = 'user'),
            'avg_assistant_msg_len', (SELECT COALESCE(ROUND(AVG(LENGTH(content))), 0) FROM mmsgs WHERE role = 'assistant'),
            'avg_msgs_per_conversation', (
                SELECT COALESCE(ROUND(AVG(mc)::numeric, 2), 0)
                FROM (SELECT COUNT(*) AS mc FROM mmsgs GROUP BY conversation_id) q
            ),
            'max_msgs_per_conversation', (
                SELECT COALESCE(MAX(mc), 0)
                FROM (SELECT COUNT(*) AS mc FROM mmsgs GROUP BY conversation_id) q
            ),
            'substantive_conversations', (
                SELECT COUNT(*) FROM (
                    SELECT COUNT(*) AS mc FROM mmsgs GROUP BY conversation_id
                ) q WHERE mc >= 4
            ),
            'new_guests', (
                SELECT COUNT(DISTINCT c.guest_id)
                FROM mconvs c JOIN guest_first_seen g USING (guest_id), bounds b
                WHERE g.first_at >= b.m_start AND g.first_at < b.m_end
            ),
            'returning_guests', (
                SELECT COUNT(DISTINCT c.guest_id)
                FROM mconvs c JOIN guest_first_seen g USING (guest_id), bounds b
                WHERE g.first_at < b.m_start
            ),
            'offtopic_violations', (
                SELECT COUNT(*) FROM ai_offtopic_violations v, bounds b
                WHERE v.created_at >= b.m_start AND v.created_at < b.m_end
            ),
            'feedback_count', (
                SELECT COUNT(*) FROM ai_message_feedback f, bounds b
                WHERE f.created_at >= b.m_start AND f.created_at < b.m_end
            )
        )
    ),
    'daily', (
        SELECT COALESCE(json_agg(row_to_json(d) ORDER BY d.day), '[]'::json) FROM (
            SELECT DATE(started_at) AS day,
                   COUNT(*)         AS conversations,
                   COUNT(DISTINCT guest_id) AS visitors
            FROM mconvs GROUP BY 1
        ) d
    ),
    'hourly', (
        SELECT COALESCE(json_agg(row_to_json(h) ORDER BY h.hour), '[]'::json) FROM (
            SELECT EXTRACT(HOUR FROM started_at)::int AS hour,
                   COUNT(*)                          AS conversations
            FROM mconvs GROUP BY 1
        ) h
    ),
    'weekday', (
        SELECT COALESCE(json_agg(row_to_json(w) ORDER BY w.dow), '[]'::json) FROM (
            SELECT EXTRACT(ISODOW FROM started_at)::int AS dow,
                   COUNT(*)                            AS conversations
            FROM mconvs GROUP BY 1
        ) w
    ),
    'top_topics', (
        SELECT COALESCE(json_agg(row_to_json(t)), '[]'::json) FROM (
            SELECT title, COUNT(*) AS count
            FROM mconvs
            WHERE title IS NOT NULL AND trim(title) <> ''
            GROUP BY title
            ORDER BY count DESC, title ASC
            LIMIT 15
        ) t
    ),
    'history', (
        SELECT COALESCE(json_agg(row_to_json(h) ORDER BY h.month), '[]'::json) FROM (
            SELECT TO_CHAR(date_trunc('month', c.started_at), 'YYYY-MM') AS month,
                   COUNT(DISTINCT c.id)           AS conversations,
                   COUNT(DISTINCT c.guest_id)     AS visitors,
                   COUNT(m.id)                    AS messages
            FROM ai_conversations c
            LEFT JOIN ai_messages m ON m.conversation_id = c.id
            GROUP BY 1
        ) h
    )
) AS report;
