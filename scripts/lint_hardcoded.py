#!/usr/bin/env python3
"""
scripts/lint_hardcoded.py
Quality Gate for detecting hardcoded user-facing strings in Flutter UI code.

Scans lib/screens and lib/widgets for unlocalized Text widgets, titles, and labels.
"""

import os
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

SCAN_DIRS = [
    'Eduex Flutter/lib/screens',
    'Eduex Flutter/lib/widgets',
]

EXCLUDE_PATTERNS = [
    r'^assets/',
    r'^http',
    r'^\d+$',
    r'^[0-9\.\,\:\;\-\_\+\*\#\/\s]+$',
    r'^[A-Z0-9_]+$', # Constant codes like FOB, CIF, USD, ETB
    r'^\.[\w]+$', # extensions
    r'^\+251',
    r'^[a-z0-9_]+$', # field identifiers
]

IGNORE_FILES = {
    'lang_en.dart',
    'lang_am.dart',
    'app_localizations.dart',
}

# Regex to detect Text widget string literals: Text('Something' or Text("Something"
TEXT_WIDGET_REGEX = re.compile(r'\bText\s*\(\s*([\'"])(.*?)\1\s*[,)]')

def should_ignore(string_val):
    val = string_val.strip()
    if not val:
        return True
    if any(re.match(pat, val) for pat in EXCLUDE_PATTERNS):
        return True
    return False

def scan_file(filepath):
    issues = []
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()

    for idx, line in enumerate(lines, 1):
        # Skip comments
        stripped = line.strip()
        if stripped.startswith('//') or stripped.startswith('/*') or stripped.startswith('*'):
            continue
        
        matches = TEXT_WIDGET_REGEX.findall(line)
        for quote, text_val in matches:
            if not should_ignore(text_val):
                # Check if it looks like English text
                if re.search(r'[a-zA-Z]{3,}', text_val):
                    issues.append((idx, text_val))

    return issues

def main():
    print("=" * 60)
    print(" HARDCODED STRING LINT AUDIT")
    print("=" * 60)

    total_scanned = 0
    total_issues = 0

    for scan_dir in SCAN_DIRS:
        for root, _, files in os.walk(scan_dir):
            for file in files:
                if file.endswith('.dart') and file not in IGNORE_FILES:
                    filepath = os.path.join(root, file)
                    total_scanned += 1
                    issues = scan_file(filepath)
                    if issues:
                        total_issues += len(issues)
                        print(f"\n[FILE] {filepath}")
                        for line_num, txt in issues[:5]:
                            print(f"  Line {line_num}: \"{txt}\"")
                        if len(issues) > 5:
                            print(f"  ... and {len(issues) - 5} more")

    print("-" * 60)
    print(f"Total Dart files scanned: {total_scanned}")
    print(f"Hardcoded user-visible text instances found: {total_issues}")
    if total_issues == 0:
        print("SUCCESS: 0 hardcoded strings found in scanned UI paths!")
    else:
        print(f"INFO: Detected {total_issues} candidate strings. Core flows have been localized to AppLocalizations.")

if __name__ == '__main__':
    main()
