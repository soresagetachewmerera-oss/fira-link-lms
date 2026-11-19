import os
import re
from pathlib import Path

FLUTTER_LIB = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib")

# Emoji regex range
emoji_pattern = re.compile(r'[\U00010000-\U0010ffff]|[\u2600-\u27BF]|[\u2300-\u23FF]')

emoji_matches = []
for p in FLUTTER_LIB.rglob("*.dart"):
    content = p.read_text(encoding="utf-8", errors="replace")
    for i, line in enumerate(content.splitlines(), start=1):
        found = emoji_pattern.findall(line)
        if found:
            # check if it's in a comment or string
            emoji_matches.append((p.relative_to(FLUTTER_LIB).as_posix(), i, line.strip(), "".join(found)))

print(f"Total lines with emojis in Flutter UI: {len(emoji_matches)}")
for path, line_no, text, ems in emoji_matches[:30]:
    print(f"  {path}:{line_no} [{ems}] -> {text[:80]}")

