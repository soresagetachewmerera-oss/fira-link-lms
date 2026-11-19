import re
from pathlib import Path
from collections import defaultdict

FLUTTER_SCREENS_DIR = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib\screens")

overflow_risks = defaultdict(list)
missing_safe_area = []

for p in FLUTTER_SCREENS_DIR.rglob("*.dart"):
    content = p.read_text(encoding="utf-8", errors="replace")
    rel = p.relative_to(FLUTTER_SCREENS_DIR).as_posix()
    
    # 1. Check if screen scaffold has SafeArea
    if "Scaffold(" in content and "SafeArea(" not in content:
        missing_safe_area.append(rel)
        
    # 2. Check for fixed width containers with Text children
    fixed_width_matches = re.finditer(r'Container\s*\(\s*width:\s*([0-9.]+)[^;]+child:\s*Text\(', content)
    for m in fixed_width_matches:
        overflow_risks[rel].append(f"Fixed width Container ({m.group(1)}px) holding Text")
        
    # 3. Check for Row containing Text without Expanded or Flexible
    # Simple heuristic: Row( ... children: [ ... Text( ... without Expanded( ... Text(
    rows = re.finditer(r'Row\s*\(\s*(?:[^;]+?)\s*children:\s*\[([^\]]+)\]', content, re.DOTALL)
    for r in rows:
        children_str = r.group(1)
        # If children contains Text( but doesn't wrap with Expanded or Flexible
        if "Text(" in children_str:
            # check lines
            for line in children_str.splitlines():
                if "Text(" in line and "Expanded" not in line and "Flexible" not in line:
                    if len(line.strip()) > 10:
                        overflow_risks[rel].append(f"Unconstrained Text inside Row: {line.strip()[:60]}")

print(f"Screens with missing SafeArea on Scaffold: {len(missing_safe_area)}")
for s in missing_safe_area[:15]:
    print(f"  {s}")

print(f"\nTotal screens with potential Row/Column overflow risks: {len(overflow_risks)}")
for s, risks in sorted(overflow_risks.items(), key=lambda x: -len(x[1]))[:15]:
    print(f"  {s:50}: {len(risks)} potential overflow points")

