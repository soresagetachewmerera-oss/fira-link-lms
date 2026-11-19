import re
from pathlib import Path

EN_FILE = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib\l10n\lang_en.dart")
AM_FILE = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib\l10n\lang_am.dart")
FLUTTER_LIB = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib")

def extract_keys(path):
    content = path.read_text(encoding="utf-8", errors="replace")
    return set(re.findall(r'["\']([a-zA-Z0-9_]+)["\']\s*:\s*', content))

en_keys = extract_keys(EN_FILE)
am_keys = extract_keys(AM_FILE)

print(f"Total English keys: {len(en_keys)}")
print(f"Total Amharic keys: {len(am_keys)}")
missing_in_am = en_keys - am_keys
missing_in_en = am_keys - en_keys
print(f"Keys in EN missing in AM: {len(missing_in_am)}")
print(f"Keys in AM missing in EN: {len(missing_in_en)}")

# Check for overpromising jargon in code and l10n: "guaranteed", "escrow", "100%", "insured"
jargon_patterns = [
    (r'\bescrow\b', "Escrow (Fira Link is NOT an escrow provider)"),
    (r'\bguaranteed\b', "Guaranteed"),
    (r'\b100%\s*(?:safe|secure|verified)', "100% claim"),
    (r'\binsured\b', "Insured"),
]

overpromising = []
for p in FLUTTER_LIB.rglob("*.dart"):
    content = p.read_text(encoding="utf-8", errors="replace")
    rel = p.relative_to(FLUTTER_LIB).as_posix()
    for idx, line in enumerate(content.splitlines(), 1):
        for pat, desc in jargon_patterns:
            if re.search(pat, line, re.IGNORECASE):
                overpromising.append((rel, idx, desc, line.strip()))

print(f"\nTotal overpromising / legally risky copy findings: {len(overpromising)}")
for rel, idx, desc, line in overpromising[:20]:
    print(f"  {rel}:{idx} [{desc}] -> {line[:90]}")

