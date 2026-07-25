# Contributing — MΛZ Medical CRM

_Repository is private. Contributions accepted only from authorised collaborators._

---

## Branching model

```
main         ← protected, production-ready only
└── develop  ← integration branch
    ├── feat/<short-name>   ← features
    ├── fix/<short-name>    ← bug fixes
    └── docs/<short-name>   ← doc-only changes
```

- Never push directly to `main` — always through a reviewed PR from `develop`.
- Rebase your feature branch on `develop` before opening the PR.

---

## Commit message convention

Adopted from the personal MAZ//ID brand conventions (see `Maziyar_ID.md`).

| Prefix        | Use for                                        |
|---------------|------------------------------------------------|
| `MAZ:`        | New feature or user-visible enhancement         |
| `MAZ:fix`     | Bug fix                                         |
| `MAZ:docs`    | Documentation only                              |
| `MAZ:style`   | Formatting / whitespace / non-behavioural       |
| `MAZ:refactor`| Internal refactor with no behaviour change      |
| `MAZ:perf`    | Performance improvement                         |
| `MAZ:test`    | Test-only changes                               |
| `MAZ:chore`   | Build / infra / deps                            |

Examples:

```
MAZ: add rhinoplasty EMR template + Copilot draft card
MAZ:fix intake OTP resend timer never firing on Safari 15
MAZ:docs SCHEMA.md — clarify EAV mirror write path
```

Each commit body is optional but should explain **why**, not what.

---

## Code style

### PHP (backend, when it lands)
- PHP 8.2+, `declare(strict_types=1);` at the top of every file.
- PSR-12 code style.
- PSR-4 autoload namespaces.
- No global state; inject dependencies through constructors.
- SQL parameters are always bound — never string-concatenated.

### JavaScript (frontend)
- **Intake page (`pages/intake/intake.html`) targets old browsers** — no `?.` / `??` / `async`/`await` / spread on strings. Rewrite as XMLHttpRequest + callbacks or promises with `.then()`.
- Other pages: modern ES2020+ is fine.
- Single quotes; 2-space indent; semicolons required.
- All new modules live under `assets/js/` and expose via a namespaced global (`window.MAZCRM.*`) — no globals leaking to `window` directly.

### CSS
- Only reference tokens from `assets/css/tokens.css` — never hard-code colors, radii, or shadow values in component CSS. If a token is missing, add it to `tokens.css` first, then use it.
- Prefer utility classes already in `base.css` before writing new selectors.

### HTML / accessibility
- `dir="rtl" lang="fa"` on `<html>` for every Persian page.
- Every interactive element ≥ 44 × 44 px.
- Every form field has a visible `<label>` — never placeholder-only.
- Focus rings visible (`:focus-visible` outline is defined globally — do not remove it).
- Errors associated with fields via `aria-describedby` / `aria-live="polite"` for toasts.

---

## Pull request checklist

Copy this into the PR description:

```markdown
### What changed
<short paragraph>

### Screenshots (if UI)
<before / after>

### Checklist
- [ ] Follows commit prefix convention (MAZ: / MAZ:fix / …)
- [ ] No hard-coded colors — used tokens only
- [ ] RTL verified visually (no flipped icons that shouldn't flip)
- [ ] Keyboard navigation still works (Tab / Enter / Esc)
- [ ] No console errors
- [ ] Docs updated if a public interface (API, token, schema) changed
- [ ] CHANGELOG entry added under "Unreleased"
```

---

## Signing off

Every file authored under this repo must carry the `MAZ//ID` header comment and copyright to _Dr. Shahin Bastaninejad_. If you add a new file type not covered by the existing templates in `assets/`, mirror the header format from a comparable file.

---

_M•Z_
