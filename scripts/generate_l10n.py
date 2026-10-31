import json
import sys
import os

sys.stdout.reconfigure(encoding='utf-8')

en_arb_path = 'Eduex Flutter/lib/l10n/app_en.arb'
am_arb_path = 'Eduex Flutter/lib/l10n/app_am.arb'

with open(en_arb_path, 'r', encoding='utf-8') as f:
    en_arb = json.load(f)

with open(am_arb_path, 'r', encoding='utf-8') as f:
    am_arb = json.load(f)

# Fix corrupted strings
corrections_en = {
    'assignmentSubmittedSuccessMessage': "Your assignment has been submitted successfully. You will receive feedback once it's reviewed.",
    'didntReceiveCode': "Didn't receive code?",
    'otpVerificationSubtitle': "We've sent a 6-digit verification code to your phone / email.",
    'resetPasswordSubtitle': "Don't worry! Enter your registered email or phone to reset your password.",
}

corrections_am = {
    'assignmentSubmittedSuccessMessage': "የቤት ስራዎ በተሳካ ሁኔታ ገብቷል። እንደተገመገመ አስተያየት ይደርስዎታል።",
    'didntReceiveCode': "ኮድ አልደረሰዎትም?",
    'otpVerificationSubtitle': "ወደ ስልክዎ ወይም ኢሜይልዎ ባለ 6-አሃዝ የማረጋገጫ ኮድ ልከናል",
    'resetPasswordSubtitle': "አይጨነቁ! የይለፍ ቃልዎን እንደገና ለማስተካከል የተመዘገቡበትን ኢሜይል ወይም ስልክ ያስገቡ።",
    'assignment': "የቤት ስራ",
    'assignmentDescription': "የቤት ስራ መግለጫ",
    'assignmentDetails': "የቤት ስራ ዝርዝሮች",
    'assignments': "የቤት ስራዎች",
    'attachFiles': "ፋይሎችን አያይዝ",
    'attendees': "ተሳታፊዎች",
    'authenticCertificate': "ትክክለኛ እና የተረጋገጠ ሰርተፍኬት",
    'badges': "ባጆች",
    'basedOnYourPreferences': "በምርጫዎ መሰረት የተዘጋጁ",
}

for k, v in corrections_en.items():
    en_arb[k] = v

for k, v in corrections_am.items():
    am_arb[k] = v

# Ensure all keys in en are in am and vice versa
all_keys = sorted(set(list(en_arb.keys()) + list(am_arb.keys())))
for k in all_keys:
    if k.startswith('@'):
        continue
    if k not in en_arb:
        en_arb[k] = k
    if k not in am_arb:
        am_arb[k] = en_arb[k]

with open(en_arb_path, 'w', encoding='utf-8') as f:
    json.dump(en_arb, f, ensure_ascii=False, indent=2)

with open(am_arb_path, 'w', encoding='utf-8') as f:
    json.dump(am_arb, f, ensure_ascii=False, indent=2)

print(f"ARB files updated. Total keys: {len(all_keys)}")

# Generate lang_en.dart
lang_en_content = """// English localization dictionary
const Map<String, String> kLangEn = {
"""
for k in sorted(en_arb.keys()):
    if k.startswith('@'):
        continue
    val_json = json.dumps(en_arb[k], ensure_ascii=False)
    lang_en_content += f"  '{k}': {val_json},\n"
lang_en_content += """};

const Map<String, String> langEn = kLangEn;
"""

with open('Eduex Flutter/lib/l10n/lang_en.dart', 'w', encoding='utf-8') as f:
    f.write(lang_en_content)

# Generate lang_am.dart
lang_am_content = """// Amharic (አማርኛ) localization dictionary
const Map<String, String> kLangAm = {
"""
for k in sorted(am_arb.keys()):
    if k.startswith('@'):
        continue
    val_json = json.dumps(am_arb[k], ensure_ascii=False)
    lang_am_content += f"  '{k}': {val_json},\n"
lang_am_content += """};

const Map<String, String> langAm = kLangAm;
"""

with open('Eduex Flutter/lib/l10n/lang_am.dart', 'w', encoding='utf-8') as f:
    f.write(lang_am_content)

print("Generated lang_en.dart and lang_am.dart successfully.")
