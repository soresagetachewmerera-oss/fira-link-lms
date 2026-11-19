import re
from pathlib import Path

FLUTTER_LIB = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib")
matches = []
for p in FLUTTER_LIB.rglob("*.dart"):
    if "test" in p.parts:
        continue
    text = p.read_text(encoding="utf-8", errors="replace")
    rel = p.relative_to(FLUTTER_LIB).as_posix()
    for idx, l in enumerate(text.splitlines(), 1):
        found = re.findall(r'["\'][^"\']*(\[[A-Za-z\s]+\])[^"\']*["\']', l)
        for f in found:
            if f.lower() not in ('[a-z]', '[0-9]', '[a-za-z0-9]'):
                matches.append((rel, idx, f, l.strip()))

print("Found bracket placeholders in strings:", len(matches))
for r, i, f, l in matches[:20]:
    print(f"{r}:{i} -> {f} in: {l[:80]}")

