# Localization Handoff V2 — START HERE

**Fixed issue in V1:** a leftover empty file `01_pages_fa_source.json` (2 bytes) confused tools/GitHub.  
**Canonical pages file:** `01_pages_fa_SOURCE.json` (**~93 KB**, 10 pages).

## Quick verify
```bash
wc -c 01_pages_fa_SOURCE.json   # expect ~90000+
python3 -c "import json; d=json.load(open('01_pages_fa_SOURCE.json',encoding='utf-8')); print(len(d), d[0]['title_fa'])"
# expect: 10 سیاست حفظ حریم خصوصی
```

## For the translating AI
1. Read `00_TECHNICAL_SPEC_AND_ROADMAP.md`
2. Use prompt in `07_PROMPT_FOR_TRANSLATING_AI.md`
3. Start Phase A with `04_chrome_ui_keys_fa.json`
4. Phase B with `01_pages_fa_SOURCE.json` + services + FAQs

## File list
See `00_MANIFEST.json` for exact byte sizes of every file in this package.
