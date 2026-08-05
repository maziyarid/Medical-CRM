# دکتر شاهین باستانی‌نژاد — وب‌سایت کلینیک (React / Vite Design Reference)

> **Persian RTL rhinoplasty clinic** — React + Vite + Tailwind v4 + SCSS + Framer Motion design reference for WordPress theme development.

---

## 🚀 Quick Start

```bash
# Install dependencies
pnpm install

# Development server (with HMR)
pnpm dev
# → http://localhost:5173

# Production build
pnpm build

# Preview production build locally
pnpm preview
# → http://localhost:4173

# TypeScript check (no emit)
pnpm typecheck
```

---

## 📁 Project Structure

```
src/
├── App.tsx                    # Route definitions (React Router v7)
├── main.tsx                   # Entry point
│
├── components/
│   ├── RootLayout.tsx         # Layout shell (nav + footer + chaty)
│   ├── SiteNav.tsx            # Sticky nav + mega-menu (animated)
│   ├── SiteFooter.tsx         # Footer with legal links
│   ├── ChatyWidget.tsx        # Floating multi-channel widget (animated, dynamic)
│   ├── Breadcrumb.tsx         # Breadcrumb trail
│   ├── ScrollToTop.tsx        # Scroll-to-top on navigation
│   ├── BlogPostTemplate.tsx   # Single post template (all 11 blog posts)
│   ├── ServiceDetailTemplate.tsx  # Single service template (all 11 services)
│   ├── ArticleAtoms.tsx       # Shared UI atoms (Badge, FAQSection, AuthorBio, etc.)
│   ├── JsonLd.tsx             # JSON-LD SEO schema injection component
│   ├── ServiceIcon.tsx        # Medical SVG icons per service slug
│   └── useMotion.ts           # Centralised Framer Motion variants
│
├── pages/
│   ├── HomePage.tsx           # Landing page (front-page.php)
│   ├── AboutPage.tsx          # About doctor (page-about.php)
│   ├── ServicesPage.tsx       # Services overview (page-services.php)
│   ├── GalleryPage.tsx        # Before/After gallery (page-gallery.php)
│   ├── BlogListPage.tsx       # Blog archive (archive.php)
│   ├── FAQPage.tsx            # FAQ page (page-faq.php)
│   ├── ContactPage.tsx        # Contact + map (page-contact.php)
│   ├── BookingPage.tsx        # Booking page (page-booking.php)
│   ├── NotFoundPage.tsx       # 404 (404.php) — search + services + CTA
│   ├── CategoryArchivePage.tsx # Category archive (category.php)
│   ├── TagArchivePage.tsx     # Tag Hub + archive (tag.php)
│   ├── SitemapPage.tsx        # Visual sitemap with search
│   ├── blog/
│   │   ├── BlogRhinoplasty.tsx
│   │   ├── BlogRhinoplastyRevision.tsx
│   │   └── BlogOtherPosts.tsx  (9 posts)
│   ├── services/
│   │   └── AllServicePages.tsx (11 service pages)
│   └── legal/
│       ├── PrivacyPage.tsx    # /legal/privacy
│       ├── TermsPage.tsx      # /legal/terms
│       └── CancellationPage.tsx # /legal/cancellation
│
├── data/
│   └── site.ts                # All shared data + WP field annotations
│
└── styles/
    ├── index.css              # Entry: SCSS → fonts → Tailwind → theme
    ├── fonts.css              # Irancell @font-face declarations
    ├── tailwind.css           # @import tailwindcss
    ├── theme.css              # CSS custom properties + Tailwind @theme
    └── scss/
        ├── main.scss          # SCSS entry (imports all partials)
        ├── _variables.scss    # Brand design tokens (primary #28722C, etc.)
        ├── _mixins.scss       # SCSS mixins (responsive, motion, btn, etc.)
        ├── _base.scss         # Reset + base element styles
        ├── _rtl.scss          # RTL-specific logical property overrides
        └── _components.scss   # Component SCSS (nav, chaty, sitemap, legal, etc.)
```

---

## 🎨 SCSS Architecture

| File | Purpose |
|------|---------|
| `_variables.scss` | Single source of truth for all design tokens: `$color-primary: #28722C`, `$color-bg: #F9F6F1`, spacing scale, radii, shadows, transitions, breakpoints |
| `_mixins.scss` | Reusable mixins: `container`, `card`, `btn-primary`, `btn-outline`, `focus-ring`, `reduced-motion`, `fluid-text`, `green-badge`, responsive helpers |
| `_base.scss` | HTML reset, fluid heading scale with `clamp()`, global `prefers-reduced-motion`, RTL body defaults |
| `_rtl.scss` | RTL logical property helpers, custom scrollbar, arrow-reversal class |
| `_components.scss` | Per-component rules: `.site-nav`, `.read-progress`, `.chaty`, `.service-icon`, `.key-takeaways`, `.legal-page`, `.sitemap`, `.archive`, `.not-found` |

**Tailwind v4 is kept as a utility companion** — no colours hard-coded into Tailwind classes. All brand colours come from CSS custom properties via `theme.css`.

---

## ✨ New Pages (Added in This Version)

| Route | Component | WP Template |
|-------|-----------|-------------|
| `/category/:slug` | `CategoryArchivePage` | `category.php` |
| `/tag` | `TagArchivePage` | `tag.php` (Tag Hub) |
| `/tag/:slug` | `TagArchivePage` | `tag.php` |
| `/sitemap` | `SitemapPage` | `page-sitemap.php` |
| `/legal/privacy` | `PrivacyPage` | `page-privacy.php` |
| `/legal/terms` | `TermsPage` | `page-terms.php` |
| `/legal/cancellation` | `CancellationPage` | `page-cancellation.php` |
| `*` (catch-all) | `NotFoundPage` | `404.php` |

> **Home page is always at `/`** — the catch-all now renders a proper 404 with search, popular services, and contact CTA.

---

## 🔧 Custom Fields / CPTs / Taxonomies / Options

The following must be registered in the **WordPress theme functions.php** (no ACF plugin):

### Options Page (Theme Customizer or custom settings)

```php
// Register in theme functions.php with register_setting() / add_settings_field()
// Or via WordPress Customizer with add_setting() / add_control()

'doctor_name'              // text   — دکتر شاهین باستانی‌نژاد
'site_logo'                // image  — logo URL
'clinic_phone_1'           // text   — 02186087250
'clinic_phone_2'           // text   — 02188205606
'clinic_whatsapp'          // url    — wa.me link
'clinic_telegram'          // url    — t.me link
'clinic_instagram'         // url    — instagram URL
'clinic_youtube'           // url    — youtube URL
'clinic_aparat'            // url    — aparat URL
'clinic_address'           // textarea
'clinic_address_short'     // text
'clinic_hours'             // text   — شنبه و سه‌شنبه · ۱۵:۰۰–۱۹:۰۰
'booking_url'              // url    — app.drbastaninejad.com
'enamad_url'               // url
'iranent_url'              // url

// Stats (repeater-like, use individual fields or serialized)
'clinic_years_experience'  // number — +18
'clinic_surgeries_count'   // number — +2000
'clinic_gallery_count'     // number — +178
'clinic_satisfaction_rate' // text   — 98%
```

### ChatyWidget — Dynamic Channels Repeater

```php
// In functions.php, register a serialized theme option:
register_setting('theme_options', 'chaty_channels');

// Each channel row:
//   chaty_channel_type   (text)   — identifier slug
//   chaty_channel_label  (text)   — display label (Persian)
//   chaty_channel_href   (url)    — link href
//   chaty_channel_color  (select) — green|sky|primary|booking

// PHP output into wp_footer:
add_action('wp_footer', function() {
    $channels = get_option('chaty_channels', []);
    echo '<script>window.__chatChannels = ' . json_encode($channels) . ';</script>';
});
```

> **Adding a new channel requires only WP Admin changes** — no code deploy needed.

### Custom Post Types (register in functions.php)

```php
register_post_type('service', [
    'labels'      => [ 'name' => 'خدمات', 'singular_name' => 'خدمت' ],
    'public'      => true,
    'has_archive' => true,
    'rewrite'     => [ 'slug' => 'services' ],
    'supports'    => ['title', 'editor', 'thumbnail', 'custom-fields'],
]);

register_post_type('case_study', [
    'labels'      => [ 'name' => 'نمونه کارها' ],
    'public'      => true,
    'rewrite'     => [ 'slug' => 'gallery' ],
    'supports'    => ['title', 'custom-fields'],
]);
```

### Custom Per-Post Fields (register_meta or custom settings form)

**Service posts:**
```
service_category_label      text    — e.g. "جراحی زیبایی"
service_hero_lead           textarea
service_highlights          serialized — [ {icon, title, body} ]
service_quick_facts         serialized — [ {label, val} ]
service_key_takeaways       textarea (one per line)
service_procedure_steps     serialized
service_related_services    comma-separated post IDs
service_faq_items           serialized — [ {question, answer} ]
```

**Blog posts:**
```
post_subtitle               text
post_read_time              number   — minutes
post_difficulty             select   — آموزشی|متوسط|تخصصی
last_medical_review_date    date     — YMYL/E-E-A-T critical
key_takeaways_list          textarea (one per line)
faq_items                   serialized — [ {question, answer} ]
```

### Built-in Taxonomies
```
category   — built-in (post categories)
post_tag   — built-in (post tags)
```

---

## 🏗️ JSON-LD Schema (SEO)

The `JsonLd` component injects structured data into `<head>`:

| Schema | Page | Builder |
|--------|------|---------|
| `MedicalBusiness` + `GeoCoordinates` | All pages | `LOCAL_BUSINESS_SCHEMA` |
| `Person` (doctor) | About, Blog posts | `DOCTOR_SCHEMA` |
| `FAQPage` | FAQ, Blog posts | `buildFaqSchema(faqs)` |
| `BreadcrumbList` | All pages | `buildBreadcrumbSchema(items)` |
| `MedicalWebPage` + `Speakable` | Blog posts | `buildArticleSchema(opts)` |

---

## ⚡ Performance (Core Web Vitals)

| Feature | Implementation |
|---------|---------------|
| Code splitting | `manualChunks` in vite.config.ts — vendor-react, vendor-motion, vendor-lucide, vendor-radix, chunk-blog, chunk-services, chunk-legal |
| Asset inlining | `assetsInlineLimit: 4096` (< 4 KB → base64) |
| Font loading | `font-display: swap` for all Irancell weights |
| Scroll listener | `{ passive: true }` on all scroll events |
| Images | `loading="lazy"` on all non-critical images |
| Motion | `useReducedMotion()` respected on all animations — GPU-only props (x/y/scale/opacity) |

---

## 🎭 Motion / Framer Motion

All animations live in `src/components/useMotion.ts`:

| Hook | Used in | Effect |
|------|---------|--------|
| `useFadeScaleVariants` | Chaty panel, Mega menu | Fade + scale (0.95→1) |
| `useStaggerVariants` | Mobile menu, Chaty channels | Stagger children |
| `useCardVariants` | Mobile nav items | Fade + slide up |
| `useChatyItem` | Chaty channels | Slide in from left |
| `useSlideVariants` | Available for future use | Slide from right |

All hooks return `{}` empty variants when `prefers-reduced-motion` is active.

---

## 🌐 WordPress Production Checklist

- [ ] Create WordPress theme directory under `wp-content/themes/drbastaninejad/`
- [ ] Register all custom fields from the list above in `functions.php` (no ACF plugin)
- [ ] Register `chaty_channels` as serialized theme option with WP Settings API
- [ ] Inject `window.__chatChannels` via `wp_footer` action
- [ ] Register `service` and `case_study` CPTs
- [ ] Add `get_template_directory_uri()` for logo + font paths
- [ ] Set up `wp_nav_menu()` locations: `primary`, `footer-quick`, `footer-services`, `footer-legal`
- [ ] Implement `wp_head()` / `wp_footer()` for JSON-LD injection
- [ ] Configure WordPress Customizer for all options listed above
- [ ] Set front page to static page (not latest posts)
- [ ] Configure `.htaccess` for SPA routing (all paths → index.php)

---

## 📝 Brand Signature

Footer credit: designed by **MA**Z ([maziyarid.com](https://maziyarid.com))
