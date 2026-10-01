# Dr. Bastaninejad — Logo and Homepage UI Concept
Important: This is my primary note before starting the front-end development.

I just added the /brand-mood-board-maker skill. Can you demo it with some great examples?

I need you help with:

Creating design system based on attached logo and colour pallets.
Creating a icons set for all services of related to rhinoplasty and plastic surgery clinic.
I need all landing pages to be designed.
All sections, including navigation menu, footer, CTA, Blog page, and all other elements must be designed separately.
All registration forms, appointments (I have attached example form as HTML), elements, sections, features must be designed carefully. The Profile picture, favicon, etc must be designed too. Be my professional UI designer.
Important: it must be RTL and written in Persian language.
I have attached my primary prototype but the one you design must be much better than this.

I have attached the logo.

Rewrite the readme file after you developed my front-end.

## Included files

- `dr-bastaninejad-logo-redesign.svg` — primary two-colour vector mark with transparent negative space.
- `dr-bastaninejad-logo-monochrome.svg` — single-colour SVG that inherits CSS `currentColor`.
- `dr-bastaninejad-logo-preview.png` — 1600×1600 transparent preview.
- `dr-bastaninejad-logo-512.png` — 512×512 transparent web/app version.
- `dr-bastaninejad-homepage-ui-concept.html` — responsive RTL homepage concept.
- `dr-bastaninejad-homepage-ui-preview.png` — full-page desktop preview.
- `dr-bastaninejad-colour-palettes.svg/png` — visual palette sheet.

## Logo redesign rationale

The redesigned mark keeps the original concept: organic green layers on the left and nested facial profiles on the right. The geometry has been simplified, the negative spaces have consistent widths, the colour count is limited, and the icon remains recognisable at small sizes. The SVG has no raster image, no embedded font and no background.

Recommended clear space: at least 12% of the symbol width on every side.  
Recommended minimum size: 28 px digital; 12 mm print.  
Do not add shadows, outlines, gradients or rotate the symbol.

## Recommended colour system

### Primary — Evergreen + Graphite

| Role | Hex | Usage |
|---|---|---|
| Primary | `#2F7D32` | Main CTA, active state, logo green |
| Primary dark | `#235F27` | Hover state, high-contrast headings on pale green |
| Ink | `#25272C` | Headings, body text, logo graphite |
| Porcelain | `#F7F8F6` | Main background |
| Soft sage | `#E4F0E4` | Cards, badges, medical information panels |
| Warm brass | `#B6905E` | Small premium accent only; not small body text |
| Muted text | `#6A7078` | Supporting copy |
| Border | `#E4E8E3` | Dividers and card borders |

The main green has a contrast ratio of about 5.1:1 against white, while graphite is about 14.9:1, making both suitable for normal text. Brass should be decorative rather than used for small text.

## Homepage structure

1. Slim availability/announcement bar.
2. Sticky header with a single primary booking CTA.
3. Hero with natural-outcome positioning, two CTAs and three trust indicators.
4. Office information strip: address, phone and office hours.
5. Four primary treatment cards.
6. Filterable before/after gallery with an interactive comparison control.
7. Doctor profile with concise credentials and philosophy.
8. Four-step patient journey.
9. Medical FAQ.
10. Patient education articles grouped by journey stage.
11. Strong contact block and a mobile sticky contact/gallery bar.

## Implementation notes

- Keep the website RTL-first and fully responsive.
- Use real professional portrait and case images; the prototype intentionally uses neutral placeholders.
- Before/after images should use consistent camera angle, lighting and framing and should include appropriate consent.
- Avoid outcome guarantees, superlatives and artificial statistics.
- Use Persian typography with a licensed webfont such as Peyda, IRANSansX or Dana, with a system-font fallback.
- Convert images to AVIF/WebP, lazy-load below-the-fold media, and reserve image dimensions to prevent layout shift.
- Use schema appropriate to a physician/medical clinic, validated contact data and accessible form labels.
