import sqlite3, json

DB = "C:/Users/Kazemi/.local/share/mimocode/mimocode.db"
conn = sqlite3.connect(DB)
cur = conn.cursor()

# List all projects
cur.execute("SELECT id, name, worktree FROM project")
print("=== Projects ===")
for r in cur.fetchall():
    print(f"  ID: {r[0]}, Name: {r[1]}, Worktree: {r[2]}")

# Check the session for json_checker more carefully - look at the second message
session_id = "ses_063377100ffe0ZpmMZXrd1tD01"
print(f"\n=== Full assistant message details for session {session_id} ===")
cur.execute("""
    SELECT m.id as msg_id, json_extract(m.data, '$.role') as role, p.id as part_id, p.data as part_data
    FROM message m
    JOIN part p ON p.message_id = m.id
    WHERE m.session_id = ?
    ORDER BY m.time_created, p.time_created
""", (session_id,))
for r in cur.fetchall():
    pd = json.loads(r[3])
    ptype = pd.get("type", "?")
    if ptype == "tool":
        tool = pd.get("tool", "?")
        state = pd.get("state", {})
        inp = state.get("input", {})
        out = state.get("output", {})
        
        if tool == "write":
            fp = inp.get("file_path", "?")
            content = inp.get("content", "")
            # Extract first few lines
            lines = content.split("\n")[:5]
            print(f"  WRITE: {fp}")
            print(f"    First lines: {lines}")
            print(f"    Content length: {len(content)} chars")
        else:
            print(f"  TOOL {tool}: {json.dumps(inp)[:200]}")
    elif ptype == "text":
        text = pd.get("text", "")[:500]
        print(f"  TEXT: {text}")
    print()

conn.close()
