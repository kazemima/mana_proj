import sqlite3, json

DB = "C:/Users/Kazemi/.local/share/mimocode/mimocode.db"
conn = sqlite3.connect(DB)
cur = conn.cursor()

# Check if there's a project table
try:
    cur.execute("SELECT name FROM sqlite_master WHERE type='table'")
    tables = [r[0] for r in cur.fetchall()]
    print(f"Tables: {tables}")
except Exception as e:
    print(f"Error: {e}")

# Check schema for relevant tables
for t in ['project', 'session']:
    try:
        cur.execute(f"PRAGMA table_info({t})")
        print(f"\n{t} schema: {[r[1] for r in cur.fetchall()]}")
    except:
        pass

# Search all user messages for Persian directives (broader search)
print("\n=== User messages with Persian keywords (ALL sessions) ===")
persian_directive_words = ["باید", "نباید", "هرگز", "یادت", "قانون", "规矩", "决策", "اولویت", "ترجیح"]
for kw in persian_directive_words:
    cur.execute("""
        SELECT m.session_id, s.title, json_extract(p.data, '$.text') as text
        FROM message m
        JOIN part p ON p.message_id = m.id
        JOIN session s ON s.id = m.session_id
        WHERE json_extract(m.data, '$.role') = 'user'
          AND json_extract(p.data, '$.type') = 'text'
          AND json_extract(p.data, '$.text') LIKE ?
        ORDER BY m.time_created DESC
        LIMIT 5
    """, (f'%{kw}%',))
    rows = cur.fetchall()
    if rows:
        print(f"\n  Keyword '{kw}':")
        for r in rows:
            text = r[2][:200] if r[2] else ""
            print(f"    [{r[1]}] {text}")

# Also check for repeated patterns in session titles
print("\n=== All unique session titles (grouped by project) ===")
cur.execute("""
    SELECT project_id, title, COUNT(*) as cnt
    FROM session
    WHERE title NOT LIKE 'checkpoint-writer:%'
    GROUP BY project_id, title
    ORDER BY cnt DESC
""")
for r in cur.fetchall():
    print(f"  [{r[0]}] {r[1]} (x{r[2]})")

conn.close()
