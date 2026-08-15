# الگوی قطعی تصویر شاخص مقالات

## اندازه و رفتار در قالب

- فایل مادر: **1600 × 900 پیکسل (نسبت 16:9)**.
- خروجی نهایی: WebP، فضای رنگی sRGB، کیفیت 82 تا 86، حداکثر حدود 300 کیلوبایت در صورت حفظ کیفیت چهره و بافت.
- قالب از همین فایل، Hero مقاله را در 1600×900 و کارت آرشیو را در 720×405 می‌سازد. برش هر دو دقیقاً 16:9 است؛ بنابراین ترکیب‌بندی تغییر نمی‌کند.
- ناحیه امن: همه عناصر مهم داخل 80٪ میانی کادر باشند. 90 پیکسل از چهار لبه خالی بماند.
- متن عنوان مقاله داخل تصویر تولید نشود؛ عنوان به‌صورت HTML روی Hero نمایش داده می‌شود.
- لوگوی اصلی کلینیک باید از فایل `assets/dist6/logo-white.svg` به‌عنوان مرجع/لایه واقعی استفاده شود. تولید دوباره لوگو با هوش مصنوعی مجاز نیست.

## Prompt template (copy/paste)

```text
Create a premium editorial hero image for a Persian rhinoplasty and ENT medical article.

CANVAS: exactly 1600 × 900 px, 16:9, sRGB.
ARTICLE TOPIC: [TOPIC]
CORE VISUAL METAPHOR: [ONE CLEAR VISUAL IDEA]
OPTIONAL SUBJECT: [SUBJECT OR “no person”]

Art direction: calm, medically credible, refined and modern; aligned with a high-end specialist clinic rather than a beauty advertisement. Use the clinic palette: charcoal #25272C, deep forest #1A2318, signature green #28722C, warm ivory #F7F8F6, and restrained soft-gray highlights. Cinematic soft directional light, controlled contrast, clean negative space, subtle depth, realistic materials, no visual clutter.

Composition for a right-to-left Persian website: reserve the RIGHT 44% as quiet, darker negative space for the HTML headline; place the main subject or metaphor in the LEFT/CENTER-left area; keep all important details within the central 80% safe zone and at least 90 px away from every edge. The image must remain strong when viewed at 720 × 405 px.

Branding: place the EXACT supplied clinic logo asset (do not redraw, reinterpret, translate, or generate it) in the TOP-LEFT safe area, 150 px wide, monochrome white at approximately 88% opacity, with at least 64 px clearance. If the image tool cannot preserve the supplied logo exactly, leave that area empty and add the real SVG afterward as a separate layer.

Medical integrity: anatomically plausible, respectful, non-sensational, no surgery in progress, no blood, no bruising, no exaggerated “perfect” result, no before/after claim, no misleading device or anatomy. If a patient is shown, use a non-identifiable editorial model with natural skin texture and neutral expression; never imitate a real patient or the doctor.

Do not render any words, Persian/Arabic letters, numbers, badges, UI, watermarks, borders, or additional logos. No glossy cosmetic-ad look, plastic skin, neon green, oversaturation, stock-photo handshake, floating anatomy collage, warped facial features, extra nostrils, asymmetrical eyes, malformed hands, or text-like artifacts.

Output one finished 1600 × 900 image with a clean full-bleed edge and no frame.
```

## لایه‌گذاری لوگو و خروجی

1. تصویر بدون متن را بسازید.
2. اگر ابزار لوگو را دقیق نگه نداشت، خروجی بدون لوگو بگیرید و `logo-white.svg` را در بالا-چپ با عرض 150px، فاصله 64px و شفافیت 88٪ قرار دهید.
3. عنوان فارسی را هرگز روی فایل تصویر نچسبانید؛ قالب آن را خوانا، قابل دسترس و واکنش‌گرا روی Hero نمایش می‌دهد.
4. خروجی را با نام انگلیسی و خط تیره ذخیره کنید؛ نمونه: `anti-inflammatory-diet-rhinoplasty.webp`.
5. متن جایگزین را توصیفی بنویسید، نه تکرار عنوان؛ نمونه: «مواد غذایی تازه و کم‌التهاب در چیدمان آرام با رنگ‌های سبز کلینیک».

## نمونه پرشده برای مقاله رژیم ضدالتهابی

```text
ARTICLE TOPIC: anti-inflammatory nutrition before and after rhinoplasty
CORE VISUAL METAPHOR: a refined overhead arrangement of fresh low-inflammatory foods forming a subtle flowing recovery path, with gentle green botanical accents and warm ivory ceramics
OPTIONAL SUBJECT: no person
```
