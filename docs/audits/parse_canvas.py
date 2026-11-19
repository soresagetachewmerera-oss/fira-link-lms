import json
from pathlib import Path

canvas_path = Path("docs/design/flows/canvas.json")
with open(canvas_path, encoding="utf-8") as f:
    d = json.load(f)

print(f"Total boards in canvas: {len(d.get('order', []))}")
for k in d.get("order", []):
    b = d.get("boards", {}).get(k, {})
    title = b.get("title", "")
    tags = b.get("tags", [])
    print(f"{k} | {title} | {tags}")

