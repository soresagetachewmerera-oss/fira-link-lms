import re
import math
from pathlib import Path
from collections import defaultdict

# 1. WCAG Contrast Calculation
def parse_hex_color(hex_str):
    hex_str = hex_str.strip().lstrip('#')
    if len(hex_str) == 8: # AARRGGBB
        hex_str = hex_str[2:]
    if len(hex_str) == 6:
        r = int(hex_str[0:2], 16) / 255.0
        g = int(hex_str[2:4], 16) / 255.0
        b = int(hex_str[4:6], 16) / 255.0
        return (r, g, b)
    return None

def sRGB_to_luminance(c):
    return c / 12.92 if c <= 0.03928 else math.pow((c + 0.055) / 1.055, 2.4)

def relative_luminance(rgb):
    r, g, b = rgb
    rl = sRGB_to_luminance(r)
    gl = sRGB_to_luminance(g)
    bl = sRGB_to_luminance(b)
    return 0.2126 * rl + 0.7152 * gl + 0.0722 * bl

def contrast_ratio(hex1, hex2):
    c1 = parse_hex_color(hex1)
    c2 = parse_hex_color(hex2)
    if not c1 or not c2:
        return 0.0
    l1 = relative_luminance(c1)
    l2 = relative_luminance(c2)
    lighter = max(l1, l2)
    darker = min(l1, l2)
    return (lighter + 0.05) / (darker + 0.05)

# Fira Link Brand Tokens
tokens = {
    "brandPrimary": "6D1A95",
    "brandDeep": "3B0764",
    "brandTint": "F3E8FF",
    "surface": "FFFFFF",
    "card": "FFFFFF",
    "onSurface": "1E1B4B",
    "mutedText": "6B7280",      # typical grey in fira_colors
    "mutedTextAlt": "9CA3AF",   # secondary muted grey
    "success": "10B981",        # green
    "pending": "6D1A95",        # purple on lavender
    "danger": "EF4444",         # red
    "chipSelectedBg": "6D1A95",
    "chipUnselectedBg": "F3F4F6",
    "chipUnselectedText": "4B5563",
    "lavender": "F3E8FF",
}

print("=== WCAG 2.1 CONTRAST RATIO CHECKS ===")
pairs = [
    ("onSurface on surface", tokens["onSurface"], tokens["surface"]),
    ("brandPrimary on surface", tokens["brandPrimary"], tokens["surface"]),
    ("brandPrimary on brandTint (Pending on Lavender)", tokens["pending"], tokens["lavender"]),
    ("mutedText (#6B7280) on surface", tokens["mutedText"], tokens["surface"]),
    ("mutedTextAlt (#9CA3AF) on surface", tokens["mutedTextAlt"], tokens["surface"]),
    ("mutedText on brandTint", tokens["mutedText"], tokens["brandTint"]),
    ("success (#10B981) on surface", tokens["success"], tokens["surface"]),
    ("danger (#EF4444) on surface", tokens["danger"], tokens["surface"]),
    ("white on brandPrimary (Button text)", "FFFFFF", tokens["brandPrimary"]),
    ("white on success (Success badge)", "FFFFFF", tokens["success"]),
    ("white on danger (Danger badge)", "FFFFFF", tokens["danger"]),
    ("chipUnselectedText on chipUnselectedBg", tokens["chipUnselectedText"], tokens["chipUnselectedBg"]),
]

for name, fg, bg in pairs:
    ratio = contrast_ratio(fg, bg)
    aa_normal = "PASS" if ratio >= 4.5 else "FAIL"
    aa_large = "PASS" if ratio >= 3.0 else "FAIL"
    print(f"{name:45} | Ratio: {ratio:5.2f}:1 | AA Normal (4.5): {aa_normal} | AA Large (3.0): {aa_large}")

# 2. IconButton missing tooltip / semantics
FLUTTER_LIB = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib")
icon_btn_pattern = re.compile(r'IconButton\s*\(')
tooltip_pattern = re.compile(r'tooltip:\s*')

icon_btns_total = 0
icon_btns_with_tooltip = 0
files_with_unlabeled_icon_btns = defaultdict(int)

for p in FLUTTER_LIB.rglob("*.dart"):
    content = p.read_text(encoding="utf-8", errors="replace")
    rel = p.relative_to(FLUTTER_LIB).as_posix()
    matches = list(icon_btn_pattern.finditer(content))
    for m in matches:
        icon_btns_total += 1
        # inspect the next 300 characters
        snippet = content[m.start():m.start() + 250]
        if "tooltip:" in snippet or "Semantics" in snippet:
            icon_btns_with_tooltip += 1
        else:
            files_with_unlabeled_icon_btns[rel] += 1

print(f"\n=== ICON BUTTON SEMANTICS ACCESSIBILITY ===")
print(f"Total IconButton widgets found: {icon_btns_total}")
print(f"IconButton widgets with tooltip or semantics: {icon_btns_with_tooltip}")
print(f"IconButton widgets UNLABELED (screen reader sees unannounced icon): {icon_btns_total - icon_btns_with_tooltip}")
print("Top 10 files with unlabeled IconButtons:")
for f, cnt in sorted(files_with_unlabeled_icon_btns.items(), key=lambda x: -x[1])[:10]:
    print(f"  {f}: {cnt} unlabeled")

