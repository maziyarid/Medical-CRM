# drbastaninejad.com — WordPress Theme (React Design Reference)

## Overview

This is the **complete React + Vite + Tailwind CSS** design reference for the WordPress theme of `drbastaninejad.com`.

All pages are pixel-perfect implementations of the Figma designs exported from:
- `Iranian Rhinoplasty Landing Page/` — homepage + general pages design system
- `Iranian Rhinoplasty Blog Post Page/` — blog post + service detail design system

---

## Project Structure

```
src/
├── App.tsx                  # Router — maps URLs to page components
├── main.tsx                 # Entry point
├── styles/
│   ├── index.css            # Imports all styles
│   ├── theme.css            # CSS custom properties (colors, radius, etc.)
│   ├── tailwind.css         # Tailwind v4 import
│   └── fonts.css            # Vazirmatn Google Font
├── data/
│   └── site.ts              # All site-wide constants (replace with WP/ACF in PHP)
├── components/
│   ├── RootLayout.tsx        # Wrapper: SiteNav + <Outlet/> + SiteFooter + widgets
│   ├── SiteNav.tsx           # Global navigation (mega menu, mobile)
│   ├── SiteFooter.tsx        # Global footer (4-column, social bar)
│   ├── ChatyWidget.tsx       # Floating multi-channel contact widget
│   ├── ScrollToTop.tsx       # Back-to-top button
│   ├── Breadcrumb.tsx        # Breadcrumb nav (Yoast-compatible structure)
│   ├── ArticleAtoms.tsx      # Shared atoms: Badge, FAQSection, AuthorBio, etc.
│   ├── BlogPostTemplate.tsx  # Reusable blog post shell (all 11 posts use this)
│   └── ServiceDetailTemplate.tsx  # Reusable service page shell (all 11 services)
└── pages/
    ├── HomePage.tsx          # / → front-page.php
    ├── AboutPage.tsx         # /about → page-about.php
    ├── ServicesPage.tsx      # /services → page-services.php
    ├── GalleryPage.tsx       # /gallery → page-gallery.php
    ├── BlogListPage.tsx      # /blog → archive.php
    ├── FAQPage.tsx           # /faq → page-faq.php
    ├── ContactPage.tsx       # /contact → page-contact.php
    ├── BookingPage.tsx       # /booking → page-booking.php
    ├── blog/
    │   ├── BlogRhinoplasty.tsx         # /blog/rhinoplasty
    │   ├── BlogRhinoplastyRevision.tsx # /blog/rhinoplasty-revision
    │   └── BlogOtherPosts.tsx          # remaining 9 blog posts (exported functions)
    └── services/
        └── AllServicePages.tsx         # all 11 service pages (exported functions)
```

---

## WordPress Conversion Guide

### Template Hierarchy Mapping

| React Route | WP Template File |
|---|---|
| `/` | `front-page.php` |
| `/about` | `page-about.php` |
| `/services` | `page-services.php` |
| `/services/:slug` | `single-service.php` (CPT: `service`) |
| `/blog` | `archive.php` |
| `/blog/:slug` | `single-post.php` |
| `/gallery` | `page-gallery.php` |
| `/faq` | `page-faq.php` |
| `/contact` | `page-contact.php` |
| `/booking` | `page-booking.php` |

### Data Sources in WordPress

| React constant / prop | WordPress source |
|---|---|
| `DOCTOR_NAME`, `PHONES`, `ADDRESS` | ACF Options Page |
| `INSTAGRAM`, `YOUTUBE`, `TELEGRAM` | ACF Options Page: social links |
| `COSMETIC_SERVICES`, `FUNCTIONAL_SERVICES` | CPT: `service` with ACF |
| `BLOG_POSTS` | Standard WP posts |
| `GALLERY_ITEMS` | CPT: `case-study` or ACF gallery |
| `HOME_FAQS` | CPT: `faq` or ACF Options repeater |
| `AUTHOR` | WP user meta + ACF user fields |
| Post `meta` (title, date, tags…) | `get_the_title()`, `get_the_date()`, WP tags |
| FAQs per post | ACF repeater: `faq_items` |
| Testimonials | ACF repeater or CPT: `review` |

### Sections Marked `{ BACKEND }`

Search for `{ BACKEND }` comments throughout the codebase to find every location where static mock data needs to be replaced with dynamic WordPress/ACF calls.

### Contact Forms

The contact forms in `ContactPage.tsx`, `ContactCTA.tsx`, and `HomePage.tsx` are frontend-only. In WordPress, replace with:
- **Contact Form 7**: `do_shortcode('[contact-form-7 id="..." title="..."]')`
- **Gravity Forms**: `gravity_form(1, true)`
- **Custom WP AJAX**: `admin_url('admin-ajax.php')` + `wp_nonce_field()`

### Booking System

`BookingPage.tsx` links to `https://app.drbastaninejad.com/`. In WP, you can embed:
- An iframe pointing to the booking system
- A custom booking plugin shortcode

---

## Development

```bash
# Install dependencies
pnpm install   # or npm install

# Start dev server
pnpm dev

# Build for production
pnpm build
```

### Design Tokens (Primary Colors)

```css
--primary: #28722C    /* Evergreen — main brand color */
--background: #F9F6F1 /* Warm off-white */
--foreground: #1C1C1C /* Near-black text */
```

All colors are defined in `src/styles/theme.css` as CSS custom properties.

---

## Notes

- All RTL direction is set at root level (`dir="rtl" lang="fa-IR"`)
- Font: **Vazirmatn** (Google Fonts) — loaded via `<link>` preconnect in `index.html` (not `fonts.css`, to avoid double requests)
- Tailwind v4 is configured via `@tailwindcss/vite` plugin in `vite.config.ts` — the `postcss.config.mjs` is intentionally empty
- Sections that need backend integration in WP are clearly commented with `{ BACKEND }` markers
- The demo page-switcher in `BlogPostTemplate` is removed in this version — WP routing handles that automatically
