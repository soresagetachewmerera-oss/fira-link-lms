import json
import sys

sys.stdout.reconfigure(encoding='utf-8')

with open('Eduex Flutter/lib/l10n/app_en.arb', 'r', encoding='utf-8') as f:
    en_arb = json.load(f)

with open('Eduex Flutter/lib/l10n/app_am.arb', 'r', encoding='utf-8') as f:
    am_arb = json.load(f)

for k, v in en_arb.items():
    if k.startswith('@'): continue
    if '\\' in v or v.endswith(('\\', 'Didn', 'Don', 'We', "it'")) or '\n' in v:
        print(f"EN: {k} -> {repr(v)}")

for k, v in am_arb.items():
    if k.startswith('@'): continue
    if '\\' in v or '\n' in v or v.endswith('\\'):
        print(f"AM: {k} -> {repr(v)}")
