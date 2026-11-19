import os
import re
from pathlib import Path

FLUTTER_LIB = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib")

# Check all Navigator calls and routes
nav_calls = {}
for p in FLUTTER_LIB.rglob("*.dart"):
    content = p.read_text(encoding="utf-8", errors="replace")
    pushes = re.findall(r'Navigator\.(?:push|pushNamed|pushReplacement|pushReplacementNamed|pushAndRemoveUntil)\s*\(\s*[^,]+,\s*(?:MaterialPageRoute\s*\(\s*builder:\s*\(context\)\s*=>\s*([A-Za-z0-9_]+)|["\']([^"\']+)["\'])', content)
    named = re.findall(r'pushNamed(?:AndRemoveUntil)?\s*\(\s*[^,]+,\s*["\']([^"\']+)["\']', content)
    routes = re.findall(r'AppRouter\.([a-zA-Z0-9_]+)', content)
    
    # Check screen instantiations in routes or navigator
    screen_matches = re.findall(r'([A-Za-z0-9_]+Screen)\s*\(', content)
    
    for sm in screen_matches:
        nav_calls[sm] = nav_calls.get(sm, 0) + 1

print(f"Total distinct Screen classes instantiated in lib: {len(nav_calls)}")
for sm, count in sorted(nav_calls.items(), key=lambda x: -x[1])[:25]:
    print(f"  {sm}: {count} references")

