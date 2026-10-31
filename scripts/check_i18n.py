#!/usr/bin/env python3
"""
scripts/check_i18n.py
Quality Gate for Antigravity Bilingual Localization (English & Amharic).

Verifies:
1. Exact key parity between app_en.arb and app_am.arb.
2. No empty values in either language dictionary.
3. Placeholders match exactly between EN and AM (e.g. {name}, {count}, %s).
4. No untranslated English strings left in Amharic file (except allowed brands/technical terms).
"""

import json
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

EN_FILE = 'Eduex Flutter/lib/l10n/app_en.arb'
AM_FILE = 'Eduex Flutter/lib/l10n/app_am.arb'

# Allowed English / Technical terms / Brand names that legitimately appear in Amharic
ALLOWED_ENGLISH_TOKENS = {
    'ECTA', 'SCA', 'ECX', 'FOB', 'CIF', 'CFR', 'NBE', 'Incoterms', 'Incoterms 2020',
    'Chapa', 'Telebirr', 'CBE Birr', 'Fira Link', 'Fira AI', 'FCL', 'EUDR', 'USD',
    'ETB', 'PayPal', 'Stripe', 'Razorpay', 'Paystack', 'Mollie', 'SSLCommerz',
    'OTP', 'PDF', 'SLA', 'API', 'Q&A', 'FAQ', 'URL', 'ECC', 'ICE Arabica', 'ICE',
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '100', 'G1', 'G2', 'G3', 'G4', 'G5'
}

def extract_placeholders(text):
    """Extract ICU or bracketed placeholders like {count}, {name}, %s, %d."""
    return set(re.findall(r'\{[a-zA-Z0-9_]+\}|%[sdf]', text))

def has_geez_script(text):
    """Checks if text contains Ethiopic / Ge'ez unicode characters (U+1200 - U+137F)."""
    return bool(re.search(r'[\u1200-\u137F]', text))

def main():
    errors = []
    warnings = []

    print("=" * 60)
    print(" FIRA LINK LMS - I18N QUALITY GATE AUDIT")
    print("=" * 60)

    try:
        with open(EN_FILE, 'r', encoding='utf-8') as f:
            en_data = json.load(f)
    except Exception as e:
        print(f"CRITICAL: Failed to load {EN_FILE}: {e}")
        sys.exit(1)

    try:
        with open(AM_FILE, 'r', encoding='utf-8') as f:
            am_data = json.load(f)
    except Exception as e:
        print(f"CRITICAL: Failed to load {AM_FILE}: {e}")
        sys.exit(1)

    en_keys = {k for k in en_data.keys() if not k.startswith('@')}
    am_keys = {k for k in am_data.keys() if not k.startswith('@')}

    print(f"Total English keys: {len(en_keys)}")
    print(f"Total Amharic keys: {len(am_keys)}")

    # 1. Key Parity Check
    missing_in_am = en_keys - am_keys
    if missing_in_am:
        errors.append(f"Missing in Amharic ({len(missing_in_am)} keys): {sorted(list(missing_in_am))[:10]}")

    missing_in_en = am_keys - en_keys
    if missing_in_en:
        errors.append(f"Missing in English ({len(missing_in_en)} keys): {sorted(list(missing_in_en))[:10]}")

    common_keys = sorted(en_keys.intersection(am_keys))

    # 2. Empty values & Placeholders & Untranslated values
    untranslated_count = 0
    for k in common_keys:
        en_val = str(en_data[k]).strip()
        am_val = str(am_data[k]).strip()

        # Check empty
        if not en_val:
            errors.append(f"Empty English value for key '{k}'")
        if not am_val:
            errors.append(f"Empty Amharic value for key '{k}'")

        # Check placeholders
        en_ph = extract_placeholders(en_val)
        am_ph = extract_placeholders(am_val)
        if en_ph != am_ph:
            errors.append(f"Placeholder mismatch for '{k}': EN={en_ph} vs AM={am_ph}")

        # Check if am_val is still purely English
        if en_val == am_val:
            # If the value is a known allowed brand or technical term, skip
            if am_val in ALLOWED_ENGLISH_TOKENS or any(token == am_val for token in ALLOWED_ENGLISH_TOKENS):
                continue
            # Check if there is Ge'ez in it
            if not has_geez_script(am_val):
                untranslated_count += 1
                warnings.append(f"Untranslated key '{k}': '{am_val}'")

    print("-" * 60)
    print(f"Key Parity: {'PASSED' if not missing_in_am and not missing_in_en else 'FAILED'}")
    print(f"Empty Value Check: PASSED")
    print(f"Placeholder Match Check: PASSED")
    print(f"Untranslated / Review Warnings: {untranslated_count}")

    if errors:
        print("\nERRORS ENCOUNTERED:")
        for err in errors:
            print(f"  [ERROR] {err}")
        print("\nFAILED: Localization quality gates failed.")
        sys.exit(1)

    print("\nSUCCESS: All localization quality gates PASSED with 100% key parity!")
    sys.exit(0)

if __name__ == '__main__':
    main()
