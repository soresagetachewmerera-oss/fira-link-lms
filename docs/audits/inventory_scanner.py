import os
import re
from pathlib import Path

FLUTTER_SCREENS_DIR = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib\screens")
ROUTER_FILE = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib\router\app_router.dart")
BLADE_VIEWS_DIR = Path(r"D:\.gradle\fira-link-lms\firalink web\firalink\resources\views")
WEB_ROUTES_DIR = Path(r"D:\.gradle\fira-link-lms\firalink web\firalink\routes")

# 1. Scan Flutter screens
flutter_screens = []
for p in FLUTTER_SCREENS_DIR.rglob("*.dart"):
    # If it looks like a screen (ends with _screen.dart or is under screens/ directly/subfolder)
    if p.name.endswith("_screen.dart") or "screen" in p.name.lower():
        flutter_screens.append(p)

print(f"Found {len(flutter_screens)} Flutter screen files.")

# Read router file to see routes
router_text = ROUTER_FILE.read_text(encoding="utf-8") if ROUTER_FILE.exists() else ""

screen_data = []
for s in sorted(flutter_screens):
    rel = s.relative_to(FLUTTER_SCREENS_DIR).as_posix()
    content = s.read_text(encoding="utf-8", errors="replace")
    
    # Check if mentioned in app_router.dart or if navigated to
    name_without_ext = s.stem
    # Find class name
    classes = re.findall(r'class\s+([A-Za-z0-9_]+)\s+extends\s+(?:StatefulWidget|StatelessWidget)', content)
    class_name = classes[0] if classes else name_without_ext
    
    in_router = class_name in router_text or name_without_ext in router_text
    
    # Check states: loading, empty, error, offline, success
    has_loading = bool(re.search(r'CircularProgressIndicator|shimmer|Skeleton|isLoading|_loading|loading', content, re.IGNORECASE))
    has_empty = bool(re.search(r'empty|no data|no \w+ found|empty state|isEmpty', content, re.IGNORECASE))
    has_error = bool(re.search(r'error|isError|_error|hasError|catch|failure', content, re.IGNORECASE))
    has_offline = bool(re.search(r'offline|connectivity|no internet', content, re.IGNORECASE))
    has_success = bool(re.search(r'success|completed|toast|snack|SnackBar', content, re.IGNORECASE))
    
    screen_data.append({
        "path": rel,
        "class": class_name,
        "in_router": in_router,
        "loading": has_loading,
        "empty": has_empty,
        "error": has_error,
        "offline": has_offline,
        "success": has_success,
    })

# Output summary of Flutter screens
print("\n--- SAMPLE FLUTTER SCREENS ---")
for sd in screen_data[:10]:
    print(sd)

# 2. Scan Blade views
blade_views = []
for p in BLADE_VIEWS_DIR.rglob("*.blade.php"):
    rel = p.relative_to(BLADE_VIEWS_DIR).as_posix()
    blade_views.append((p, rel))

print(f"\nFound {len(blade_views)} Blade view files.")

# Categorize Blade views
categories = {"public": [], "marketplace": [], "student": [], "instructor": [], "admin": [], "other": []}
for p, rel in blade_views:
    if rel.startswith("admin/"):
        categories["admin"].append(rel)
    elif rel.startswith("marketplace/"):
        categories["marketplace"].append(rel)
    elif rel.startswith("instructor/"):
        categories["instructor"].append(rel)
    elif rel.startswith("student/"):
        categories["student"].append(rel)
    elif rel.startswith("front/") or rel.startswith("layouts/app") or "public" in rel:
        categories["public"].append(rel)
    else:
        categories["other"].append(rel)

for cat, lst in categories.items():
    print(f"Blade {cat}: {len(lst)} views")

