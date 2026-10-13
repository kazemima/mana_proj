import sqlite3, json

DB = "C:/Users/Kazemi/.local/share/mimocode/mimocode.db"
conn = sqlite3.connect(DB)
cur = conn.cursor()

# Get ALL parts in the user session, including tool details
session_id = "ses_063377100ffe0ZpmMZXrd1tD01"
cur.execute("""
    SELECT m.id as msg_id, json_extract(m.data, '$.role') as role, 
           p.id as part_id, p.data as part_data
    FROM message m
    JOIN part p ON p.message_id = m.id
    WHERE m.session_id = ?
    ORDER BY m.time_created, p.time_created
""", (session_id,))

for row in cur.fetchall():
    msg_id, role, part_id, part_data_str = row
    pd = json.loads(part_data_str)
    ptype = pd.get("type", "?")
    
    if ptype == "tool":
        tool = pd.get("tool", "?")
        state = pd.get("state", {})
        inp = state.get("input", {})
        out = state.get("output", {})
        
        # For write tool, show the file path
        if tool == "write":
            fp = inp.get("file_path", "?")
            content_len = len(inp.get("content", ""))
            print(f"  WRITE: {fp} ({content_len} chars)")
        elif tool == "read":
            fp = inp.get("file_path", "?")
            print(f"  READ: {fp}")
        elif tool == "edit":
            fp = inp.get("file_path", "?")
            old = inp.get("old_string", "")[:80]
            new = inp.get("new_string", "")[:80]
            print(f"  EDIT: {fp}")
            print(f"    old: {old}")
            print(f"    new: {new}")
        elif tool == "bash":
            cmd = inp.get("command", "?")[:200]
            print(f"  BASH: {cmd}")
            output = out.get("output", "") if isinstance(out, dict) else str(out)[:200]
            print(f"    output: {output[:200]}")
        elif tool == "glob":
            print(f"  GLOB: {inp.get('pattern', '?')}")
        elif tool == "grep":
            print(f"  GREP: {inp.get('pattern', '?')} in {inp.get('path', '.')}")
        else:
            print(f"  TOOL {tool}: {json.dumps(inp)[:200]}")
        print()

conn.close()
