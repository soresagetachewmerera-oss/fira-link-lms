import os
import re
from pathlib import Path

FLUTTER_LIB = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib")

fake_patterns = [
    (r'\[[a-zA-Z0-9_\s-]+\]', "Bracketed placeholder text"),
    (r'lorem\s+ipsum', "Lorem ipsum placeholder text"),
    (r'TODO|FIXME|XXX', "TODO/FIXME comments"),
    (r'mock|dummy|fake|sample[0-9]|temp[0-9]', "Mock/Dummy references"),
]

findings = []
for p in FLUTTER_LIB.rglob("*.dart"):
    # skip test files
    if "test" in p.parts:
        continue
    content = p.read_text(encoding="utf-8", errors="replace")
    rel = p.relative_to(FLUTTER_LIB).as_posix()
    lines = content.splitlines()
    for idx, line in enumerate(lines, 1):
        for pat, desc in fake_patterns:
            matches = re.findall(pat, line, re.IGNORECASE)
            if matches:
                # filter out false positives if needed (e.g. valid regex or comments)
                if desc == "Bracketed placeholder text":
                    # Check if it's within a string literal: '...' or "..."
                    str_matches = re.findall(r'["\'][^"\']*(\[[a-zA-Z0-9_\s-]+\])[^"\']*["\']', line)
                    if str_matches:
                        findings.append((rel, idx, str_matches[0], desc, line.strip()))
                elif desc == "Lorem ipsum placeholder text":
                    findings.append((rel, idx, matches[0], desc, line.strip()))
                elif desc == "Mock/Dummy references":
                    # Check if it's in a string or class/function
                    if re.search(r'["\'].*(?:mock|dummy|fake).*["\']', line, re.IGNORECASE):
                        findings.append((rel, idx, matches[0], desc, line.strip()))

print(f"Total fake/placeholder findings: {len(findings)}")
for rel, idx, match, desc, line in findings[:30]:
    print(f"  {rel}:{idx} [{desc}: {match}] -> {line[:90]}")

