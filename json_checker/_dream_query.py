import sqlite3, json, sys

DB = "C:/Users/Kazemi/.local/share/mimocode/mimocode.db"
conn = sqlite3.connect(DB)
cur = conn.cursor()

# 1. List messages in the json_checker user session
session_id = "ses_063377100ffe0ZpmMZXrd1tD01"
print(f"=== Messages in session {session_id} ===")
cur.execute("SELECT id, agent_id, time_created, data FROM message WHERE session_id = ? ORDER BY time_created ASC", (session_id,))
rows = cur.fetchall()
for r in rows:
    d = json.loads(r[3])
    role = d.get("role", "?")
    print(f"  MSG id={r[0]} agent={r[1]} role={role} time={r[2]}")

# 2. Get all parts for user messages to understand what was asked
print("\n=== User messages ===")
for r in rows:
    d = json.loads(r[3])
    if d.get("role") == "user":
        msg_id = r[0]
        cur2 = conn.cursor()
        cur2.execute("SELECT id, data FROM part WHERE message_id = ? ORDER BY time_created ASC", (msg_id,))
        parts = cur2.fetchall()
        for p in parts:
            pd = json.loads(p[1])
            ptype = pd.get("type", "?")
            if ptype == "text":
                text = pd.get("text", "")[:500]
                print(f"  Part {p[0]} ({ptype}): {text}")
            else:
                print(f"  Part {p[0]} ({ptype}): {json.dumps(pd)[:300]}")

# 3. Get assistant responses (text parts)
print("\n=== Assistant responses (text) ===")
for r in rows:
    d = json.loads(r[3])
    if d.get("role") == "assistant":
        msg_id = r[0]
        cur2 = conn.cursor()
        cur2.execute("SELECT id, data FROM part WHERE message_id = ? ORDER BY time_created ASC", (msg_id,))
        parts = cur2.fetchall()
        for p in parts:
            pd = json.loads(p[1])
            ptype = pd.get("type", "?")
            if ptype == "text":
                text = pd.get("text", "")[:800]
                print(f"  Part {p[0]} ({ptype}): {text}")
                print()

# 4. Get tool calls/results
print("\n=== Tool calls ===")
for r in rows:
    d = json.loads(r[3])
    if d.get("role") == "assistant":
        msg_id = r[0]
        cur2 = conn.cursor()
        cur2.execute("SELECT id, data FROM part WHERE message_id = ? ORDER BY time_created ASC", (msg_id,))
        parts = cur2.fetchall()
        for p in parts:
            pd = json.loads(p[1])
            ptype = pd.get("type", "?")
            if ptype == "tool":
                tool = pd.get("tool", "?")
                state = pd.get("state", {})
                inp = json.dumps(state.get("input", {}))[:400]
                out = json.dumps(state.get("output", {}))[:400]
                print(f"  Tool: {tool}")
                print(f"    Input: {inp}")
                print(f"    Output: {out}")
                print()

conn.close()
