import re
from pathlib import Path
from collections import defaultdict

FLUTTER_SCREENS_DIR = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib\screens")

# Match Text('...') where string starts with a capital letter and has spaces, and is not a variable/interpolation
text_pattern = re.compile(r'\bText\s*\(\s*["\']([A-Z][A-Za-z0-9,\s.:;?!/()\-]{4,})["\']')

hardcoded_by_file = defaultdict(list)

for p in FLUTTER_SCREENS_DIR.rglob("*.dart"):
    content = p.read_text(encoding="utf-8", errors="replace")
    rel = p.relative_to(FLUTTER_SCREENS_DIR).as_posix()
    lines = content.splitlines()
    for idx, line in enumerate(lines, 1):
        for m in text_pattern.finditer(line):
            val = m.group(1).strip()
            # ignore obvious non-text (e.g., date formats, font families)
            if val not in ("Montserrat", "NotoSansEthiopic", "ETB", "USD"):
                hardcoded_by_file[rel].append((idx, val))

print(f"Total screens with hardcoded UI text: {len(hardcoded_by_file)}")
total_instances = sum(len(v) for v in hardcoded_by_file.values())
print(f"Total hardcoded text instances in screens: {total_instances}")
print("\nTop 15 screens with hardcoded English strings (bypassing l10n):")
for f, items in sorted(hardcoded_by_file.items(), key=lambda x: -len(x[1]))[:15]:
    print(f"  {f:50} : {len(items):2} strings (e.g., '{items[0][1]}')")

