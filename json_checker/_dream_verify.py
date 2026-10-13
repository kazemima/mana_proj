import sqlite3, json

DB = "C:/Users/Kazemi/.local/share/mimocode/mimocode.db"
conn = sqlite3.connect(DB)
cur = conn.cursor()

# Search for user statements containing key words across all sessions
keywords = ["always", "never", "remember", "rule", "decision", "decided", 
            "repeat", "again", "every time", "workflow", "مهم", "规矩", "决策"]

# Also search for Persian equivalents
persian_keywords = ["باید", "نباید", "همیشه", "هرگز", "یادت باشه", "قانون"]

print("=== Searching for user directives in recent sessions ===")

# Get sessions from last 7 days (7 * 24 * 60 * 60 * 1000 ms)
import time
seven_days_ago = int(time.time() * 1000) - (7 * 24 * 60 * 60 * 1000)

for kw in keywords + persian_keywords:
    cur.execute("""
        SELECT m.session_id, json_extract(m.data, '$.role') as role,
               p.data as part_data
        FROM message m
        JOIN part p ON p.message_id = m.id
        WHERE json_extract(m.data, '$.role') = 'user'
          AND json_extract(p.data, '$.type') = 'text'
          AND json_extract(p.data, '$.text') LIKE ?
          AND m.time_created > ?
        ORDER BY m.time_created DESC
        LIMIT 5
    """, (f'%{kw}%', seven_days_ago))
    
    rows = cur.fetchall()
    if rows:
        print(f"\n--- Keyword: '{kw}' ---")
        for r in rows:
            pd = json.loads(r[2])
            text = pd.get("text", "")[:300]
            print(f"  Session: {r[0]}")
            print(f"  Text: {text}")
            print()

# Also look for repeated file paths
print("\n=== File write operations in recent sessions ===")
cur.execute("""
    SELECT m.session_id, p.data as part_data
    FROM message m
    JOIN part p ON p.message_id = m.id
    WHERE json_extract(m.data, '$.role') = 'assistant'
      AND json_extract(p.data, '$.type') = 'tool'
      AND json_extract(p.data, '$.tool') = 'write'
      AND m.time_created > ?
    ORDER BY m.time_created DESC
    LIMIT 30
""", (seven_days_ago,))

for r in cur.fetchall():
    pd = json.loads(r[1])
    state = pd.get("state", {})
    inp = state.get("input", {})
    fp = inp.get("file_path", "?")
    print(f"  Session: {r[0]} -> {fp}")

# Check for repeated error patterns
print("\n=== Error messages in tool outputs (last 7 days) ===")
cur.execute("""
    SELECT m.session_id, p.data as part_data
    FROM message m
    JOIN part p ON p.message_id = m.id
    WHERE json_extract(m.data, '$.role') = 'assistant'
      AND json_extract(p.data, '$.type') = 'tool'
      AND json_extract(p.data, '$.tool') = 'bash'
      AND m.time_created > ?
    ORDER BY m.time_created DESC
    LIMIT 20
""", (seven_days_ago,))

for r in cur.fetchall():
    pd = json.loads(r[1])
    state = pd.get("state", {})
    output = str(state.get("output", ""))
    if "error" in output.lower() or "Error" in output:
        inp = state.get("input", {})
        cmd = inp.get("command", "")[:150]
        print(f"  Session: {r[0]}")
        print(f"  Cmd: {cmd}")
        print(f"  Error: {output[:200]}")
        print()

conn.close()
