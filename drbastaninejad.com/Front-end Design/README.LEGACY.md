<!-- MAZ//ID · Dr. Shahin Bastaninejad Medical Platform -->
# 🗄️ LEGACY — do not extend

**Status:** archived on 2026-07-27
**Superseded by:** [`drbst/Frontend/`](../Frontend/) (Next.js 16 + React 19)
**Recorded in:** [`Medical-CRM/REPOSITORY_AUDIT.md`](../../Medical-CRM/REPOSITORY_AUDIT.md) §4

This folder holds the earlier React + Vite marketing-site prototype. It is kept
for reference only. Do not add features, fix bugs, or import components from
here — the current marketing site lives in `drbst/Frontend/`.

## Rules for agents

1. Do **not** modify any file under `drbst/Front-end Design/`.
2. Do **not** import components, styles, or data from here into `drbst/Frontend/` or
   any other project.
3. If a good pattern lives only here, re-implement it inside `drbst/Frontend/` —
   do not add a cross-tree import.
4. Deletion of this folder is pending explicit approval from the product owner
   per `SPACE_COORDINATION_PROTOCOL.md`. Until that approval, this folder stays
   on disk unchanged.

## Stray files noted

- `src/backend/server.py` — a Python file inside a React project tree. Not part of the
  marketing site build; likely a leftover from the earlier prototype. Do not extend.

---

_M•Z — MAZ//ID_
