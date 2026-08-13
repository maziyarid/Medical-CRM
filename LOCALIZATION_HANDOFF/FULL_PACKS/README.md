# Full localization packs (no GitHub required)

Generated on the engineering side from LOCALIZATION_HANDOFF_V2 sources.

## Files
- `pack_en.json` — **complete**: chrome + forms + 10 pages + 8 services + 22 FAQs + gallery
- `pack_{ar,tr,de,fr,es,ru}.json` — chrome + services + page SEO/titles + EN FAQ bodies (see note in JSON)

## Integrity
```bash
python3 -c "import json;d=json.load(open('pack_en.json',encoding='utf-8'));print(len(d['pages']),len(d['services']),len(d['faqs']),len(d['chrome']))"
# expect: 10 8 22 34
```

## Why not GitHub?
Other tools could not read the 93KB pages file through GitHub. These packs were built from the same source data offline.

## Install next steps
1. Map `chrome` → `languages/{lang}.php` (Phase A already shipped separately).
2. Create WP pages from `pages[]` with `drb_lang_code` + shared `drb_lang_group_id`.
3. Load services/FAQs into production content sources the React app uses.
