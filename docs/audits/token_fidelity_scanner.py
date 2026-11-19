import os
import re
from pathlib import Path
from collections import defaultdict

FLUTTER_LIB = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib")

# Files to exclude from "outside theme" if any (e.g. fira_colors.dart, app_theme.dart)
THEME_FILES = {"fira_colors.dart", "app_theme.dart", "app_colors.dart"}

color_regex = re.compile(r'\b(?:Colors\.[a-zA-Z]+|Color\s*\(\s*0x[0-9a-fA-F]+\s*\))')
fontsize_regex = re.compile(r'fontSize:\s*([0-9.]+)')
radius_regex = re.compile(r'BorderRadius\.(?:circular\s*\(\s*([0-9.]+)\s*\)|all\s*\(\s*Radius\.circular\s*\(\s*([0-9.]+)\s*\)\s*\))')
padding_regex = re.compile(r'EdgeInsets\.(?:all|symmetric|only)\s*\([^)]+\)')
shadow_regex = re.compile(r'BoxShadow\s*\(')

# Metrics per file
file_metrics = defaultdict(lambda: {
    "colors": 0, "color_samples": [],
    "fonts": 0, "font_samples": [],
    "radii": 0, "radius_samples": [],
    "paddings": 0,
    "shadows": 0,
    "total": 0
})

font_sizes_found = defaultdict(int)
radii_found = defaultdict(int)
all_colors_found = defaultdict(int)

for p in FLUTTER_LIB.rglob("*.dart"):
    if p.name in THEME_FILES:
        continue
    content = p.read_text(encoding="utf-8", errors="replace")
    rel = p.relative_to(FLUTTER_LIB).as_posix()
    
    # 1. Colors
    for m in color_regex.finditer(content):
        c = m.group(0)
        file_metrics[rel]["colors"] += 1
        all_colors_found[c] += 1
        if len(file_metrics[rel]["color_samples"]) < 5:
            file_metrics[rel]["color_samples"].append(c)
            
    # 2. Font sizes
    for m in fontsize_regex.finditer(content):
        fs = float(m.group(1))
        file_metrics[rel]["fonts"] += 1
        font_sizes_found[fs] += 1
        if len(file_metrics[rel]["font_samples"]) < 5:
            file_metrics[rel]["font_samples"].append(fs)
            
    # 3. Radii
    for m in radius_regex.finditer(content):
        r = float(m.group(1) or m.group(2))
        file_metrics[rel]["radii"] += 1
        radii_found[r] += 1
        if len(file_metrics[rel]["radius_samples"]) < 5:
            file_metrics[rel]["radius_samples"].append(r)
            
    # 4. Shadows
    for m in shadow_regex.finditer(content):
        file_metrics[rel]["shadows"] += 1
        
    # Total violations
    file_metrics[rel]["total"] = (file_metrics[rel]["colors"] + 
                                  file_metrics[rel]["fonts"] + 
                                  file_metrics[rel]["radii"] + 
                                  file_metrics[rel]["shadows"])

print("=== TOP 20 FILES WITH TOKEN VIOLATIONS (COLORS, FONTS, RADII, SHADOWS) ===")
sorted_files = sorted(file_metrics.items(), key=lambda x: -x[1]["total"])
for rel, m in sorted_files[:20]:
    print(f"{rel:60} | Total: {m['total']:4} (Colors: {m['colors']:3}, Fonts: {m['fonts']:3}, Radii: {m['radii']:3}, Shadows: {m['shadows']:2})")
    print(f"    Color samples: {m['color_samples'][:3]}")
    print(f"    Font samples:  {m['font_samples'][:3]}")
    print(f"    Radius samples:{m['radius_samples'][:3]}")

print("\n=== DISTINCT FONT SIZES FOUND ===")
print(f"Total distinct font sizes: {len(font_sizes_found)}")
for fs, cnt in sorted(font_sizes_found.items(), key=lambda x: -x[1])[:20]:
    print(f"  {fs} pt: {cnt} times")

print("\n=== DISTINCT RADII FOUND (BRAND TOKENS: 12, 16, 22, 28) ===")
print(f"Total distinct radii: {len(radii_found)}")
for r, cnt in sorted(radii_found.items(), key=lambda x: -x[1])[:15]:
    status = "BRAND MATCH" if r in (12.0, 16.0, 22.0, 28.0) else "OFF-TOKEN"
    print(f"  {r} px: {cnt} times [{status}]")

print("\n=== TOP 25 HARD-CODED COLORS OUTSIDE THEME ===")
for c, cnt in sorted(all_colors_found.items(), key=lambda x: -x[1])[:25]:
    print(f"  {c:30}: {cnt} occurrences")

