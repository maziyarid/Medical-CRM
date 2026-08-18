/**
 * ============================================================
 *  MAZ//ID · drbastaninejad.com — Blog & Service Pages
 *  React design reference — all sections commented for WP/PHP
 *  Developer: Maziyar | Primary green: #28722C
 * ============================================================
 *
 *  DEMO SWITCHER (top bar) — remove in production
 *  Three page types:
 *   1. BlogPost     — standard long-form educational article
 *   2. ServicePage  — service detail (sidebar + highlights)
 *   3. ComparePost  — comparison / educational with rich media
 * ============================================================
 */

import { useState, useEffect, useRef } from "react";
import {
  ChevronDown, ChevronLeft, ChevronUp, Phone, Calendar, Clock, Eye,
  CheckCircle, AlertCircle, Info, BookOpen, Tag, Share2, Bookmark,
  ThumbsUp, MessageCircle, ArrowLeft, Star, Award, Shield, GraduationCap,
  MapPin, Send, X, Menu, Search, ExternalLink, BarChart2, Stethoscope,
  FileText, Layers, Zap, Heart, TrendingUp, Users,
} from "lucide-react";

// ─────────────────────────────────────────────────────────────────────────────
// TYPES — mirror these as PHP/ACF field groups in WordPress
// ─────────────────────────────────────────────────────────────────────────────

interface PostMeta {
  title: string;
  subtitle: string;
  category: string;
  categorySlug: string;
  difficultyLevel: "آموزشی" | "متوسط" | "تخصصی";  // ACF: post_difficulty
  readTimeMin: number;                              // ACF: post_read_time (auto-calc in PHP)
  viewCount: number;                                // ACF / post meta: post_views
  publishDate: string;                              // WP: post_date
  lastReviewed: string;                             // ACF: last_medical_review_date
  author: AuthorData;
  tags: string[];                                   // WP: post_tags taxonomy
  featuredImage: { url: string; alt: string };      // WP: _thumbnail_id
  seoTitle: string;                                 // Yoast/RankMath: _yoast_wpseo_title
  seoDescription: string;                           // Yoast/RankMath: _yoast_wpseo_metadesc
  canonicalUrl: string;
}

interface AuthorData {
  name: string;              // WP: user_display_name
  title: string;             // ACF (user meta): author_medical_title
  specialty: string;         // ACF: author_specialty
  hospital: string;          // ACF: author_hospital
  bio: string;               // WP: user_description
  stats: { label: string; value: string }[];  // ACF repeater: author_stats
  certifications: string[];  // ACF repeater: author_certifications
  avatarInitials: string;    // derived from name
}

interface FAQItem {
  question: string;  // ACF repeater: faq_items → faq_question
  answer: string;    // ACF repeater: faq_items → faq_answer
}

interface TestimonialItem {
  name: string;      // ACF repeater: testimonials → patient_name (anonymized)
  age: string;       // ACF: patient_age_range
  rating: number;    // ACF: rating (1–5)
  text: string;      // ACF: review_text
  date: string;      // ACF: review_date (Jalali)
  verified: boolean; // ACF: is_verified_patient
  procedure: string; // ACF: procedure_performed
}

interface RelatedPost {
  title: string;     // WP: post_title
  slug: string;      // WP: post_name
  category: string;  // WP: category name
  readTime: string;  // ACF: post_read_time
  imgId: string;     // Unsplash ID (replace with WP attachment ID)
  date: string;      // WP: post_date (Jalali-formatted)
  excerpt: string;   // WP: post_excerpt
}

interface HighlightCard {
  icon: string;   // ACF: highlight_icon_name (maps to Lucide icon)
  title: string;  // ACF: highlight_title
  body: string;   // ACF: highlight_body
}

interface CompareRow {
  feature: string;   // ACF repeater: compare_rows → feature_label
  optionA: string;   // ACF: option_a_value
  optionB: string;   // ACF: option_b_value
  winner?: "a" | "b" | "both";  // ACF: compare_winner
}

// ─────────────────────────────────────────────────────────────────────────────
// MOCK DATA — replace all with WP get_field() / get_the_* calls in PHP
// ─────────────────────────────────────────────────────────────────────────────

/* === SHARED AUTHOR (ACF user-meta fields) === */
const AUTHOR: AuthorData = {
  name: "دکتر شاهین باستانی‌نژاد",
  title: "فوق‌تخصص جراحی پلاستیک بینی",
  specialty: "گوش، گلو و بینی · جراحی پلاستیک",
  hospital: "بیمارستان میلاد — تهران",
  bio: "دکتر شاهین باستانی‌نژاد با بیش از یک دهه تجربه تخصصی در جراحی پلاستیک بینی، از پیشگامان استفاده از روش‌های نوین رینوپلاستی در ایران است. هدف ایشان دستیابی به نتایجی طبیعی، پایدار و کاملاً هماهنگ با ساختار چهره هر بیمار است.",
  stats: [
    { label: "سال تجربه", value: "+۱۵" },
    { label: "عمل موفق", value: "+۲۰۰۰" },
    { label: "رضایت بیمار", value: "۴.۹" },
  ],
  certifications: [
    "بورد تخصصی گوش، گلو و بینی ایران",
    "عضو ISAPS (انجمن بین‌المللی جراحی پلاستیک)",
    "فلوشیپ تخصصی جراحی پلاستیک صورت",
    "عضو انجمن جراحان گوش، گلو و بینی ایران",
  ],
  avatarInitials: "دب",
};

/* === PAGE 1: FULL EDUCATIONAL BLOG POST (rhinoplasty guide) === */
/* PHP template: single-post.php or template-blog-post.php         */
const POST1_META: PostMeta = {
  title: "رینوپلاستی: راهنمای کامل جراحی بینی از مشاوره تا نتیجه نهایی",
  subtitle: "هر آنچه قبل، حین و بعد از جراحی بینی باید بدانید — علمی، جامع و تخصصی",
  category: "راهنمای بیمار",
  categorySlug: "patient-guide",
  difficultyLevel: "متوسط",
  readTimeMin: 12,
  viewCount: 4821,
  publishDate: "۱۵ تیر ۱۴۰۳",
  lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["رینوپلاستی", "جراحی بینی", "بینی طبیعی", "بینی فانتزی", "سپتوپلاستی", "ریکاوری"],
  featuredImage: { url: "photo-1579684385127-1ef15d508118", alt: "محیط کلینیک جراحی پلاستیک مدرن" },
  seoTitle: "رینوپلاستی ۱۴۰۴ — راهنمای کامل | دکتر شاهین باستانی‌نژاد",
  seoDescription: "راهنمای جامع جراحی بینی شامل انواع روش‌ها، معیار کاندیداتوری، مراحل عمل و دوره بهبودی. تخصصی، علمی و مبتنی بر تجربه بالینی.",
  canonicalUrl: "https://drbastaninejad.com/blog/rhinoplasty-complete-guide",
};

const POST1_FAQS: FAQItem[] = [
  { question: "رینوپلاستی چیست و چه افرادی کاندیدای مناسبی هستند؟", answer: "رینوپلاستی یا جراحی بینی مداخله‌ای جراحی بر روی ساختارهای استخوانی، غضروفی و پوستی بینی است. کاندیداهای مناسب افراد بالغ بالای ۱۸ سال با رشد استخوانی کامل، سلامت عمومی مناسب و انتظارات واقع‌بینانه هستند." },
  { question: "دوره بهبودی چقدر طول می‌کشد؟", answer: "ورم و کبودی اولیه ظرف ۷–۱۰ روز کاهش می‌یابد. اسپلینت پس از هفته اول برداشته می‌شود. بازگشت به کار ۱۰–۱۴ روز و نتیجه نهایی ۶–۱۲ ماه پس از عمل." },
  { question: "تفاوت رینوپلاستی باز و بسته چیست؟", answer: "در روش باز، برشی کوچک روی ستون بینی ایجاد می‌شود که دسترسی کامل به ساختار را فراهم می‌کند — مناسب موارد پیچیده. در روش بسته، تمام برش‌ها درون سوراخ بینی است و هیچ ندبه خارجی ندارد." },
  { question: "آیا رینوپلاستی دردناک است؟", answer: "عمل زیر بیهوشی عمومی انجام می‌شود. پس از عمل درد خفیف تا متوسط با داروهای تجویزی کنترل می‌شود. اکثر بیماران آن را در حد ناراحتی — نه درد شدید — توصیف می‌کنند." },
  { question: "هزینه جراحی بینی چقدر است؟", answer: "هزینه بستگی به پیچیدگی عمل، نوع بیمارستان و تکنیک جراحی دارد. مشاوره اولیه رایگان است و برآورد دقیق پس از معاینه ارائه می‌شود." },
  { question: "آیا بیمه هزینه را پوشش می‌دهد؟", answer: "جراحی زیبایی معمولاً تحت پوشش نیست. اما سپتوپلاستی (اصلاح انحراف بینی) که هدف درمانی دارد ممکن است توسط برخی بیمه‌ها پوشش داده شود." },
];

const POST1_TESTIMONIALS: TestimonialItem[] = [
  { name: "سارا م.", age: "۲۸ ساله", rating: 5, text: "از نتیجه عمل بینی‌ام بسیار راضی هستم. دکتر باستانی‌نژاد با دقت و حوصله همه چیز را توضیح داد. بینی‌ام کاملاً طبیعی به نظر می‌رسد.", date: "مرداد ۱۴۰۳", verified: true, procedure: "رینوپلاستی طبیعی" },
  { name: "نیلوفر ک.", age: "۳۵ ساله", rating: 5, text: "بعد از سال‌ها تردید بالاخره تصمیم گرفتم. تیم پزشکی فوق‌العاده حرفه‌ای بودند و ریکاوری راحت‌تر از آنچه انتظار داشتم بود.", date: "خرداد ۱۴۰۳", verified: true, procedure: "رینوپلاستی فانتزی" },
  { name: "محمد ر.", age: "۳۲ ساله", rating: 5, text: "انحراف تیغه بینی مشکل تنفسی داشتم. بعد از عمل هم تنفسم بهتر شده هم ظاهرم. یک سنگ، دو پرنده!", date: "اردیبهشت ۱۴۰۳", verified: true, procedure: "سپتورینوپلاستی" },
];

const POST1_RELATED: RelatedPost[] = [
  { title: "انواع بینی و روش‌های جراحی مناسب هر نوع", slug: "nose-types-surgery", category: "آموزشی", readTime: "۸ دقیقه", imgId: "photo-1576091160550-2173dba999ef", date: "۱۵ تیر ۱۴۰۳", excerpt: "راهنمای انتخاب بهترین روش جراحی بر اساس نوع بافت و ساختار بینی شما." },
  { title: "مراقبت‌های بعد از عمل بینی — راهنمای جامع", slug: "post-op-care-rhinoplasty", category: "مراقبت", readTime: "۶ دقیقه", imgId: "photo-1559757148-5c350d0d3c56", date: "۲ خرداد ۱۴۰۳", excerpt: "تمام نکاتی که برای مراقبت از بینی در دوره ریکاوری باید بدانید." },
  { title: "رینوپلاستی ترمیمی: وقتی نتیجه رضایت‌بخش نیست", slug: "revision-rhinoplasty", category: "تخصصی", readTime: "۱۰ دقیقه", imgId: "photo-1582750433449-648ed127bb54", date: "۱ اردیبهشت ۱۴۰۳", excerpt: "همه چیز درباره جراحی ترمیمی بینی، دلایل نیاز به آن و انتظارات واقع‌بینانه." },
];

/* === PAGE 2: SERVICE DETAIL PAGE (hump removal) === */
/* PHP template: single-service.php or template-service-detail.php */
const PAGE2_HIGHLIGHTS: HighlightCard[] = [
  { icon: "BarChart2", title: "رایج‌ترین درخواست", body: "رفع قوز یکی از شایع‌ترین دلایل مراجعه برای جراحی بینی است." },
  { icon: "Layers", title: "قابل ترکیب", body: "می‌توان همزمان با سایر اصلاحات بینی ترکیب کرد." },
  { icon: "Eye", title: "نتیجه قابل مشاهده", body: "پروفایل صاف از اولین هفته‌های بهبودی مشخص می‌شود." },
  { icon: "Zap", title: "بدون تأثیر بر تنفس", body: "جراحی صحیح تأثیری بر عملکرد تنفسی بینی ندارد." },
];

const PAGE2_FAQS: FAQItem[] = [
  { question: "آیا رفع قوز به تنهایی انجام می‌شود؟", answer: "بله، اما گاهی اصلاحات تکمیلی برای تناسب کلی صورت لازم است. این موضوع در معاینه اولیه با تصویربرداری بررسی می‌شود." },
  { question: "آیا رفع قوز بر تنفس تأثیر دارد؟", answer: "خیر، اگر جراحی به درستی انجام شود. اگر قوز همراه با انحراف تیغه باشد، اصلاح همزمان می‌تواند تنفس را بهبود دهد." },
  { question: "هزینه رفع قوز چقدر است؟", answer: "هزینه بستگی به میزان و نوع قوز دارد. مشاوره رایگان ما برآورد دقیقی ارائه می‌دهد." },
];

/* === PAGE 3: COMPARISON / EDUCATIONAL POST === */
/* PHP template: single-post.php with post_type='compare-post' or ACF layout='comparison' */
const POST3_COMPARE: CompareRow[] = [
  { feature: "هدف اصلی", optionA: "زیبایی و اصلاح فرم بینی", optionB: "بهبود عملکرد تنفسی", winner: "both" },
  { feature: "مدت جراحی", optionA: "۱.۵ – ۳ ساعت", optionB: "۱ – ۲ ساعت", winner: "b" },
  { feature: "پوشش بیمه", optionA: "معمولاً پوشش نمی‌دهد", optionB: "در بسیاری موارد پوشش دارد", winner: "b" },
  { feature: "دوره ریکاوری", optionA: "۱۰ روز – ۱۲ ماه", optionB: "۱ – ۴ هفته", winner: "b" },
  { feature: "ندبه خارجی", optionA: "ممکن است (روش باز)", optionB: "معمولاً ندارد", winner: "b" },
  { feature: "قابل ترکیب با یکدیگر؟", optionA: "بله (سپتورینوپلاستی)", optionB: "بله (سپتورینوپلاستی)", winner: "both" },
];

const POST3_FAQS: FAQItem[] = [
  { question: "آیا می‌توان رینوپلاستی و سپتوپلاستی را همزمان انجام داد؟", answer: "بله. این ترکیب را «سپتورینوپلاستی» می‌نامند. انجام هم‌زمان هر دو عمل هم ریکاوری را کوتاه‌تر می‌کند هم نتیجه زیبایی و عملکردی بهتری دارد." },
  { question: "چطور بفهمم به رینوپلاستی نیاز دارم یا سپتوپلاستی؟", answer: "اگر مشکل اصلی شما تنفس است، سپتوپلاستی اولویت دارد. اگر نگرانی اصلی ظاهری است، رینوپلاستی مناسب‌تر است. اگر هر دو مشکل را دارید، سپتورینوپلاستی ترکیبی بهترین گزینه است." },
  { question: "آیا سپتوپلاستی ظاهر بینی را تغییر می‌دهد؟", answer: "سپتوپلاستی خالص معمولاً تغییر ظاهری قابل‌توجهی ایجاد نمی‌کند. اما اگر انحراف سبب کج‌شدن بینی شده باشد، اصلاح آن تأثیر ظاهری خفیفی دارد." },
];

// ─────────────────────────────────────────────────────────────────────────────
// SHARED STATIC DATA
// ─────────────────────────────────────────────────────────────────────────────

/* NAV links — in WP: wp_nav_menu() with 'primary' location */
const NAV_MEGA_ITEMS = [
  {
    group: "بر اساس بافت",
    items: [
      { label: "جراحی بینی اولیه", sub: "راینوپلاستی برای اولین بار", href: "/services/rhinoplasty-primary", icon: FileText },
      { label: "بینی استخوانی", sub: "اصلاح قوز و ساختار سخت", href: "/services/rhinoplasty-bony", icon: Layers },
      { label: "بینی گوشتی", sub: "پوست ضخیم — چالش‌برانگیزترین", href: "/services/rhinoplasty-fleshy", icon: Heart },
    ],
  },
  {
    group: "بر اساس سبک",
    items: [
      { label: "بینی طبیعی", sub: "نتیجه هماهنگ با چهره", href: "/services/rhinoplasty-natural", icon: CheckCircle },
      { label: "بینی فانتزی", sub: "طرح‌های خاص شخصی‌سازی‌شده", href: "/services/rhinoplasty-fantasy", icon: Star },
      { label: "جراحی ترمیمی", sub: "اصلاح نتایج عمل قبلی", href: "/services/rhinoplasty-revision", icon: TrendingUp },
    ],
  },
  {
    group: "درمانی",
    items: [
      { label: "انحراف بینی (سپتوپلاستی)", sub: "بهبود تنفس و اصلاح انحراف", href: "/services/septoplasty", icon: Zap },
      { label: "شاخک‌های بینی", sub: "توربینوپلاستی برای تنفس بهتر", href: "/services/turbinoplasty", icon: TrendingUp },
      { label: "آندوسکوپی سینوس", sub: "درمان سینوزیت مزمن", href: "/services/sinus-endoscopy", icon: Search },
    ],
  },
];

/* Footer links — in WP: wp_nav_menu() with 'footer-quick', 'footer-services' locations */
const FOOTER_QUICK = [
  { label: "خانه", href: "/" },
  { label: "درباره دکتر", href: "/about" },
  { label: "همه خدمات", href: "/services" },
  { label: "نمونه کارها", href: "/gallery" },
  { label: "مقالات", href: "/blog" },
  { label: "سوالات متداول", href: "/faq" },
  { label: "تماس و آدرس", href: "/contact" },
  { label: "نوبت‌گیری", href: "/booking" },
];

const FOOTER_SERVICES = [
  { label: "جراحی بینی اولیه", href: "/services/rhinoplasty-primary" },
  { label: "جراحی ترمیمی", href: "/services/rhinoplasty-revision" },
  { label: "بینی گوشتی", href: "/services/rhinoplasty-fleshy" },
  { label: "بینی استخوانی", href: "/services/rhinoplasty-bony" },
  { label: "بینی طبیعی", href: "/services/rhinoplasty-natural" },
  { label: "بینی فانتزی", href: "/services/rhinoplasty-fantasy" },
  { label: "رفع قوز بینی", href: "/services/hump-removal" },
  { label: "انحراف بینی", href: "/services/septoplasty" },
];

// ─────────────────────────────────────────────────────────────────────────────
// REUSABLE ATOMS
// ─────────────────────────────────────────────────────────────────────────────

function Badge({ children, variant = "primary" }: { children: React.ReactNode; variant?: "primary" | "accent" | "info" | "muted" | "warn" }) {
  const cls: Record<string, string> = {
    primary: "bg-[#E4F0E4] text-[#1a4e1d] border border-[#28722C]/20",
    accent:  "bg-amber-50 text-amber-800 border border-amber-200",
    info:    "bg-blue-50 text-blue-800 border border-blue-200",
    muted:   "bg-gray-100 text-gray-600 border border-gray-200",
    warn:    "bg-orange-50 text-orange-800 border border-orange-200",
  };
  return (
    <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${cls[variant]}`}>
      {children}
    </span>
  );
}

function StarRating({ rating }: { rating: number }) {
  return (
    <div className="flex gap-0.5" aria-label={`${rating} از ۵ ستاره`}>
      {[1,2,3,4,5].map(i => (
        <Star key={i} size={13} className={i <= rating ? "fill-amber-400 text-amber-400" : "text-gray-300 fill-gray-100"} />
      ))}
    </div>
  );
}

/* Difficulty pill — maps ACF post_difficulty field */
function DifficultyBadge({ level }: { level: string }) {
  const map: Record<string, { cls: string; icon: React.ReactNode }> = {
    "آموزشی":  { cls: "bg-green-50 text-green-800 border-green-200", icon: <BookOpen size={10} /> },
    "متوسط":   { cls: "bg-blue-50 text-blue-800 border-blue-200",  icon: <BarChart2 size={10} /> },
    "تخصصی":  { cls: "bg-purple-50 text-purple-800 border-purple-200", icon: <Stethoscope size={10} /> },
  };
  const s = map[level] || map["آموزشی"];
  return (
    <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border ${s.cls}`}>
      {s.icon} سطح: {level}
    </span>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED NAV — faithful to nav.html mega-menu pattern
// PHP: include get_template_part('components/nav');
// ─────────────────────────────────────────────────────────────────────────────
function SiteNav({ activePage }: { activePage: string }) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [megaOpen, setMegaOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const megaRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 50);
    window.addEventListener("scroll", onScroll);
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (megaRef.current && !megaRef.current.contains(e.target as Node)) setMegaOpen(false);
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  return (
    <nav
      role="navigation"
      aria-label="ناوبری اصلی"
      className={`site-nav sticky top-0 z-50 transition-all duration-200 border-b ${
        scrolled ? "bg-white/97 backdrop-blur-md shadow-sm border-[#DDE2DD]" : "bg-white border-[#DDE2DD]"
      }`}
    >
      {/* ── Read-progress bar — driven by JS scroll listener in production */}
      <div className="absolute bottom-0 left-0 h-[2px] bg-[#28722C]" style={{ width: "45%" }} aria-hidden />

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 flex items-center justify-between h-16">

        {/* Brand — WP: bloginfo('url') + custom_logo */}
        <a href="/" aria-label="صفحه اصلی — دکتر شاهین باستانی‌نژاد" className="flex items-center gap-3 group flex-shrink-0">
          {/* In production: <img src="assets/images/logo.svg" ... /> */}
          <div className="w-9 h-9 rounded-xl bg-[#28722C] flex items-center justify-center shadow-sm group-hover:bg-[#246b28] transition-colors">
            <Stethoscope size={18} className="text-white" />
          </div>
          <div className="leading-tight">
            <div className="text-sm font-bold text-[#25272C]">دکتر شاهین باستانی‌نژاد</div>
            <div className="text-[10px] text-[#6A7078]">جراح پلاستیک بینی · تهران</div>
          </div>
        </a>

        {/* Desktop links — WP: wp_nav_menu() */}
        <div className="hidden lg:flex items-center gap-0.5" ref={megaRef}>
          {[
            { label: "خانه", href: "/" },
            { label: "درباره دکتر", href: "/about" },
          ].map(l => (
            <a key={l.label} href={l.href}
              className="px-3 py-2 rounded-lg text-sm text-[#6A7078] hover:text-[#28722C] hover:bg-[#E4F0E4] transition-all font-medium">
              {l.label}
            </a>
          ))}

          {/* Mega dropdown */}
          <div className="relative">
            <button
              onClick={() => setMegaOpen(!megaOpen)}
              className={`flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all ${megaOpen ? "bg-[#E4F0E4] text-[#28722C]" : "text-[#6A7078] hover:text-[#28722C] hover:bg-[#E4F0E4]"}`}
              aria-expanded={megaOpen}
            >
              جراحی بینی <ChevronDown size={14} className={`transition-transform ${megaOpen ? "rotate-180" : ""}`} />
            </button>
            {megaOpen && (
              <div className="absolute top-full right-0 mt-1 w-[600px] bg-white border border-[#DDE2DD] rounded-2xl shadow-xl p-4 grid grid-cols-3 gap-4" role="menu">
                {NAV_MEGA_ITEMS.map(col => (
                  <div key={col.group}>
                    <p className="text-[10px] font-bold text-[#6A7078] uppercase tracking-widest mb-2 px-2">{col.group}</p>
                    {col.items.map(item => {
                      const Icon = item.icon;
                      return (
                        <a key={item.href} href={item.href} role="menuitem"
                          className="flex items-start gap-2.5 p-2 rounded-xl hover:bg-[#E4F0E4] transition-colors group"
                          onClick={() => setMegaOpen(false)}>
                          <span className="w-7 h-7 rounded-lg bg-[#E4F0E4] flex items-center justify-center flex-shrink-0 group-hover:bg-[#28722C] transition-colors">
                            <Icon size={13} className="text-[#28722C] group-hover:text-white transition-colors" />
                          </span>
                          <span className="min-w-0">
                            <span className="text-xs font-semibold text-[#25272C] block leading-tight">{item.label}</span>
                            <span className="text-[10px] text-[#6A7078] leading-tight">{item.sub}</span>
                          </span>
                        </a>
                      );
                    })}
                  </div>
                ))}
                <div className="col-span-3 border-t border-[#DDE2DD] pt-3 mt-1 flex gap-3">
                  <a href="/services" className="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-semibold text-[#28722C] bg-[#E4F0E4] rounded-lg hover:bg-[#28722C] hover:text-white transition-colors">
                    همه خدمات
                  </a>
                  <a href="https://app.drbastaninejad.com/" rel="noopener"
                    className="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-semibold text-white bg-[#28722C] rounded-lg hover:bg-[#246b28] transition-colors">
                    نوبت‌گیری آنلاین
                  </a>
                </div>
              </div>
            )}
          </div>

          {[
            { label: "نمونه کارها", href: "/gallery" },
            { label: "مقالات", href: "/blog" },
            { label: "سوالات", href: "/faq" },
            { label: "تماس", href: "/contact" },
          ].map(l => (
            <a key={l.label} href={l.href}
              className="px-3 py-2 rounded-lg text-sm text-[#6A7078] hover:text-[#28722C] hover:bg-[#E4F0E4] transition-all font-medium">
              {l.label}
            </a>
          ))}
        </div>

        {/* CTA */}
        <div className="flex items-center gap-2">
          <a href="tel:02186087250"
            className="hidden md:flex items-center gap-1.5 text-sm text-[#28722C] font-semibold hover:text-[#246b28] transition-colors"
            aria-label="تماس تلفنی">
            <Phone size={13} />۰۲۱۸۶۰۸۷۲۵۰
          </a>
          <a href="https://app.drbastaninejad.com/" rel="noopener"
            className="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 bg-[#28722C] text-white text-sm font-bold rounded-lg hover:bg-[#246b28] transition-colors">
            نوبت‌گیری
          </a>
          <button onClick={() => setMobileOpen(!mobileOpen)} className="lg:hidden p-2 rounded-lg hover:bg-[#E4F0E4] transition-colors" aria-label="منو" aria-expanded={mobileOpen}>
            {mobileOpen ? <X size={20} /> : <Menu size={20} />}
          </button>
        </div>
      </div>

      {/* Mobile panel — WP: same nav structure rendered conditionally */}
      {mobileOpen && (
        <div className="lg:hidden bg-white border-t border-[#DDE2DD] max-h-[80vh] overflow-y-auto" role="dialog" aria-modal="true" aria-label="منوی موبایل">
          <div className="p-4 space-y-1">
            <a href="/" className="flex items-center gap-3 py-3 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm font-medium text-[#25272C]">خانه</a>
            <a href="/about" className="flex items-center gap-3 py-3 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm font-medium text-[#25272C]">درباره دکتر</a>
            <div className="text-xs font-bold text-[#6A7078] px-3 pt-3 pb-1 uppercase tracking-widest">جراحی بینی</div>
            {NAV_MEGA_ITEMS.flatMap(g => g.items).map(item => (
              <a key={item.href} href={item.href} className="flex items-center gap-3 py-2.5 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm text-[#25272C]">
                <span className="w-6 h-6 rounded-lg bg-[#E4F0E4] flex items-center justify-center"><item.icon size={12} className="text-[#28722C]" /></span>
                {item.label}
              </a>
            ))}
            <div className="text-xs font-bold text-[#6A7078] px-3 pt-3 pb-1 uppercase tracking-widest">صفحات</div>
            {["/gallery:نمونه کارها", "/blog:مقالات", "/faq:سوالات", "/contact:تماس"].map(l => {
              const [href, label] = l.split(":");
              return <a key={href} href={href} className="flex py-2.5 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm text-[#25272C]">{label}</a>;
            })}
          </div>
          <div className="p-4 grid grid-cols-2 gap-2 border-t border-[#DDE2DD]">
            <a href="/booking" className="flex items-center justify-center gap-1.5 py-3 border-2 border-[#28722C] text-[#28722C] rounded-xl text-sm font-bold">نوبت‌گیری</a>
            <a href="https://app.drbastaninejad.com/" rel="noopener" className="flex items-center justify-center gap-1.5 py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold">تشکیل پرونده</a>
          </div>
        </div>
      )}
    </nav>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED FOOTER — faithful to footer.html
// PHP: include get_template_part('components/footer');
// ─────────────────────────────────────────────────────────────────────────────
function SiteFooter() {
  return (
    <footer className="bg-[#1a2318] text-white/80" role="contentinfo">
      {/* Social bar — WP: ACF options page: social_instagram, social_youtube, etc. */}
      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 pt-8">
        <div className="flex flex-wrap justify-center gap-5 pb-6 border-b border-white/10 mb-6">
          {[
            { label: "اینستاگرام", href: "https://www.instagram.com/dr.shahin.bastaninejad/", color: "hover:text-pink-400" },
            { label: "یوتیوب", href: "https://www.youtube.com/@Drshahinbastaninejad", color: "hover:text-red-400" },
            { label: "آپارات", href: "https://www.aparat.com/drbasataninejad", color: "hover:text-red-500" },
            { label: "تلگرام", href: "https://t.me/dr_bastaninejad", color: "hover:text-sky-400" },
          ].map(s => (
            <a key={s.label} href={s.href} target="_blank" rel="noopener noreferrer"
              className={`text-sm flex items-center gap-1.5 text-white/70 transition-colors ${s.color}`}>
              <ExternalLink size={13} aria-hidden /> {s.label}
            </a>
          ))}
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 pb-8">
          {/* Brand col */}
          <div>
            {/* Production: <img src="assets/images/logo-monochrome.svg" alt="..." /> */}
            <div className="flex items-center gap-2 mb-3">
              <div className="w-9 h-9 rounded-xl bg-[#28722C] flex items-center justify-center">
                <Stethoscope size={17} className="text-white" />
              </div>
              <span className="font-bold text-sm text-white">دکتر شاهین باستانی‌نژاد</span>
            </div>
            <p className="text-xs leading-relaxed mb-3">جراح و متخصص گوش، گلو و بینی<br />جراح پلاستیک بینی — تهران</p>

            {/* Stats — WP: ACF options: clinic_surgeries_count, clinic_years */}
            <div className="grid grid-cols-3 gap-2 mb-4">
              {[{ n: "+۱۵", l: "سال تجربه" }, { n: "+۲۰۰۰", l: "عمل موفق" }, { n: "+۱۷۸", l: "نمونه گالری" }].map(s => (
                <div key={s.l} className="text-center">
                  <div className="text-sm font-bold text-[#28722C]">{s.n}</div>
                  <div className="text-[10px] text-white/50">{s.l}</div>
                </div>
              ))}
            </div>

            {/* Trust seals — Production: <img src="enamad.webp" ...> linked to seal URLs */}
            <div className="flex gap-2">
              <a href="https://trustseal.enamad.ir/?id=533172&Code=N0dO6V3ic509Xoj2zIAFDG27eDa4Inlv"
                target="_blank" rel="noopener" aria-label="نماد اعتماد الکترونیکی"
                className="w-12 h-12 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center hover:bg-white/20 transition-colors text-[10px] text-center leading-tight text-white/60 p-1">
                نماد اعتماد
              </a>
              <a href="https://iranent.com/doctors/925/profile/"
                target="_blank" rel="noopener" aria-label="انجمن جراحان گوش، گلو و بینی"
                className="w-12 h-12 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center hover:bg-white/20 transition-colors text-[10px] text-center leading-tight text-white/60 p-1">
                GBN انجمن
              </a>
            </div>
          </div>

          {/* Quick links — WP: wp_nav_menu('footer-quick') */}
          <nav aria-label="لینک‌های سریع">
            <h4 className="text-sm font-bold text-white mb-4">لینک‌های سریع</h4>
            <ul className="space-y-2">
              {FOOTER_QUICK.map(l => (
                <li key={l.href}><a href={l.href} className="text-xs text-white/60 hover:text-[#28722C] transition-colors">{l.label}</a></li>
              ))}
            </ul>
          </nav>

          {/* Services — WP: wp_nav_menu('footer-services') */}
          <nav aria-label="خدمات">
            <h4 className="text-sm font-bold text-white mb-4">خدمات</h4>
            <ul className="space-y-2">
              {FOOTER_SERVICES.map(l => (
                <li key={l.href}><a href={l.href} className="text-xs text-white/60 hover:text-[#28722C] transition-colors">{l.label}</a></li>
              ))}
            </ul>
          </nav>

          {/* Contact — WP: ACF options: clinic_phone_1, clinic_phone_2, clinic_address, clinic_hours */}
          <div>
            <h4 className="text-sm font-bold text-white mb-4">تماس با ما</h4>
            <div className="space-y-4">
              <div className="flex items-start gap-3">
                <div className="w-7 h-7 rounded-lg bg-[#28722C]/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <Phone size={12} className="text-[#28722C]" />
                </div>
                <div>
                  <p className="text-[10px] text-white/50 mb-0.5">تلفن کلینیک</p>
                  <a href="tel:02186087250" className="text-xs text-white/80 hover:text-[#28722C] block transition-colors" dir="ltr">۰۲۱۸۶۰۸۷۲۵۰</a>
                  <a href="tel:02188205606" className="text-xs text-white/80 hover:text-[#28722C] block transition-colors" dir="ltr">۰۲۱۸۸۲۰۵۶۰۶</a>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-7 h-7 rounded-lg bg-[#28722C]/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <Clock size={12} className="text-[#28722C]" />
                </div>
                <div>
                  <p className="text-[10px] text-white/50 mb-0.5">ساعت پذیرش</p>
                  <p className="text-xs text-white/80">شنبه و سه‌شنبه<br />۱۵:۰۰ تا ۱۹:۰۰</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-7 h-7 rounded-lg bg-[#28722C]/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <MapPin size={12} className="text-[#28722C]" />
                </div>
                <div>
                  <p className="text-[10px] text-white/50 mb-0.5">آدرس</p>
                  <p className="text-xs text-white/70 leading-relaxed">تهران، خ. نلسون ماندلا، خ. صانعی، ساختمان نور، پلاک ۱ واحد ۶</p>
                  {/* Map links — ACF options: google_maps_url, neshan_url, balad_url, waze_url */}
                  <div className="flex gap-2 mt-2">
                    {["Google Maps", "نشان", "بلد"].map(m => (
                      <a key={m} href="#" target="_blank" rel="noopener"
                        className="px-1.5 py-0.5 text-[10px] bg-white/10 rounded border border-white/20 text-white/60 hover:bg-[#28722C]/30 transition-colors">{m}</a>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Bottom bar */}
        <div className="py-4 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-white/40">
          {/* WP: echo date('Y') + bloginfo('name') */}
          <span>© ۱۴۰۵ کلینیک دکتر شاهین باستانی نژاد — تمامی حقوق محفوظ است.</span>
          <span>طراحی توسط <a href="https://maziyarid.com" target="_blank" rel="noopener" className="text-[#28722C] hover:text-white transition-colors">MA<span className="font-black">Z</span></a></span>
        </div>
      </div>
    </footer>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED CHATY WIDGET — faithful to chaty-wrap pattern
// PHP: include get_template_part('components/chaty');
// ACF options: whatsapp_number, telegram_username, phone_1, phone_2, booking_url
// ─────────────────────────────────────────────────────────────────────────────
function ChatyWidget() {
  const [open, setOpen] = useState(false);
  return (
    <div className="fixed bottom-6 left-6 z-50 flex flex-col items-end gap-2" aria-label="ارتباط سریع">
      {open && (
        <div className="bg-white rounded-2xl shadow-2xl border border-[#DDE2DD] w-60 overflow-hidden mb-1">
          <div className="p-3 border-b border-[#DDE2DD]">
            <p className="text-xs font-bold text-[#25272C]">ارتباط سریع</p>
            <p className="text-[10px] text-[#6A7078]">سوالی داشتین در خدمتیم</p>
          </div>
          <div className="p-2 space-y-1.5">
            {[
              { label: "واتس‌اپ", href: "https://wa.me/989124966590", color: "bg-green-50 border-green-200 text-green-800 hover:bg-green-100" },
              { label: "تلگرام", href: "https://t.me/dr_bastaninejad", color: "bg-sky-50 border-sky-200 text-sky-800 hover:bg-sky-100" },
              { label: "۰۲۱۸۶۰۸۷۲۵۰", href: "tel:02186087250", color: "bg-[#E4F0E4] border-[#28722C]/20 text-[#28722C] hover:bg-[#28722C]/10" },
              { label: "۰۲۱۸۸۲۰۵۶۰۶", href: "tel:02188205606", color: "bg-[#E4F0E4] border-[#28722C]/20 text-[#28722C] hover:bg-[#28722C]/10" },
              { label: "نوبت‌گیری آنلاین", href: "https://app.drbastaninejad.com/", color: "bg-[#28722C] border-[#28722C] text-white hover:bg-[#246b28]" },
            ].map(ch => (
              <a key={ch.label} href={ch.href} target={ch.href.startsWith("http") ? "_blank" : undefined}
                rel={ch.href.startsWith("http") ? "noopener" : undefined}
                className={`flex items-center gap-2 px-3 py-2 rounded-xl border text-xs font-semibold transition-colors ${ch.color}`}>
                <Phone size={12} aria-hidden />
                {ch.label}
              </a>
            ))}
          </div>
        </div>
      )}
      <button
        onClick={() => setOpen(!open)}
        className="w-14 h-14 bg-gradient-to-br from-[#28722C] to-[#1a4e1d] rounded-2xl shadow-xl flex items-center justify-center hover:scale-105 transition-transform relative"
        aria-label={open ? "بستن گزینه‌های تماس" : "باز کردن گزینه‌های تماس"}
        aria-expanded={open}
      >
        {open ? <X size={20} className="text-white" /> : <MessageCircle size={22} className="text-white" />}
        {!open && (
          <span className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-[10px] text-white flex items-center justify-center font-bold" aria-label="۱ پیام جدید">۱</span>
        )}
      </button>
    </div>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED BREADCRUMB — WP: Yoast SEO breadcrumb or custom function
// PHP: <?php if (function_exists('yoast_breadcrumb')) yoast_breadcrumb(); ?>
// Schema: BreadcrumbList JSON-LD injected in <head>
// ─────────────────────────────────────────────────────────────────────────────
function Breadcrumb({ items }: { items: { label: string; href?: string }[] }) {
  return (
    <nav className="bg-white border-b border-[#DDE2DD] px-4 sm:px-6 py-2.5" aria-label="مسیر صفحه">
      <div className="max-w-[1200px] mx-auto">
        <ol className="flex flex-wrap items-center gap-1.5 text-xs text-[#6A7078]">
          {items.map((b, i) => (
            <li key={b.label} className="flex items-center gap-1.5">
              {i > 0 && <ChevronLeft size={11} className="text-[#DDE2DD]" />}
              {b.href && i < items.length - 1
                ? <a href={b.href} className="hover:text-[#28722C] transition-colors">{b.label}</a>
                : <span className="text-[#28722C] font-semibold" aria-current={i === items.length - 1 ? "page" : undefined}>{b.label}</span>
              }
            </li>
          ))}
        </ol>
      </div>
    </nav>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED FAQ — ACF repeater field: faq_items (question + answer)
// Schema: FAQPage JSON-LD — generate in PHP via loop of get_field('faq_items')
// ─────────────────────────────────────────────────────────────────────────────
function FAQSection({ faqs, heading = "پرسش‌های متداول" }: { faqs: FAQItem[]; heading?: string }) {
  const [open, setOpen] = useState<number | null>(null);
  return (
    <section aria-labelledby="faq-heading">
      <h2 id="faq-heading" className="text-xl sm:text-2xl font-bold text-[#25272C] mb-5">{heading}</h2>
      {/* FAQ items — PHP: foreach (get_field('faq_items') as $item) */}
      <div className="space-y-2.5">
        {faqs.map((faq, i) => (
          <div key={i} className="bg-white rounded-xl border border-[#DDE2DD] overflow-hidden">
            {/* ACF: faq_question */}
            <button
              onClick={() => setOpen(open === i ? null : i)}
              className="w-full flex items-center justify-between gap-3 p-4 text-right hover:bg-[#F7F8F6] transition-colors"
              aria-expanded={open === i}
            >
              <span className="text-sm font-semibold text-[#25272C] text-right">{faq.question}</span>
              <ChevronDown size={16} className={`text-[#28722C] flex-shrink-0 transition-transform duration-200 ${open === i ? "rotate-180" : ""}`} />
            </button>
            {/* ACF: faq_answer — rendered with wp_kses_post() in production */}
            {open === i && (
              <div className="px-4 pb-4 border-t border-[#DDE2DD] pt-3">
                <p className="text-sm text-[#6A7078] leading-[2]">{faq.answer}</p>
              </div>
            )}
          </div>
        ))}
      </div>
    </section>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED AUTHOR BIO — WP: get_the_author_meta() + ACF user fields
// PHP: get_field('author_certifications', 'user_' . get_the_author_meta('ID'))
// ─────────────────────────────────────────────────────────────────────────────
function AuthorBio({ author }: { author: AuthorData }) {
  return (
    <aside className="bg-white rounded-2xl border border-[#DDE2DD] p-5 sm:p-6" aria-label="درباره نویسنده">
      <p className="text-xs font-bold text-[#6A7078] uppercase tracking-widest mb-4">نوشته و بازبینی‌شده توسط</p>
      <div className="flex items-start gap-4 mb-4">
        {/* Production: <img src="<?= get_avatar_url($author_id) ?>" ... /> */}
        <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#28722C] to-[#1a4e1d] flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
          {author.avatarInitials}
        </div>
        <div>
          {/* WP: the_author_meta('display_name') */}
          <p className="font-bold text-[#25272C] text-base">{author.name}</p>
          {/* ACF user meta: author_medical_title */}
          <p className="text-sm text-[#28722C] font-medium">{author.title}</p>
          {/* ACF user meta: author_hospital */}
          <p className="text-xs text-[#6A7078]">{author.hospital}</p>
        </div>
      </div>
      {/* WP: user_description */}
      <p className="text-sm text-[#6A7078] leading-relaxed mb-5">{author.bio}</p>

      {/* Stats — ACF repeater: author_stats */}
      <div className="grid grid-cols-3 gap-2 mb-5 p-3 bg-[#F7F8F6] rounded-xl">
        {author.stats.map(s => (
          <div key={s.label} className="text-center">
            <p className="text-base font-bold text-[#28722C]">{s.value}</p>
            <p className="text-[10px] text-[#6A7078]">{s.label}</p>
          </div>
        ))}
      </div>

      {/* Board certifications — ACF repeater: author_certifications */}
      <div className="space-y-2">
        <p className="text-xs font-bold text-[#25272C] mb-2">مدارک و گواهینامه‌ها</p>
        {author.certifications.map((cert, i) => (
          <div key={i} className="flex items-center gap-2 text-xs text-[#25272C]">
            <Award size={12} className="text-[#B6905E] flex-shrink-0" />
            {cert}
          </div>
        ))}
      </div>
    </aside>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED TESTIMONIALS — ACF repeater: testimonials (or CPT: review)
// Production: WP_Query with post_type='review', meta_query for procedure
// ─────────────────────────────────────────────────────────────────────────────
function Testimonials({ items }: { items: TestimonialItem[] }) {
  return (
    <section aria-labelledby="testimonials-heading">
      <div className="flex items-center justify-between mb-5">
        <h2 id="testimonials-heading" className="text-xl font-bold text-[#25272C]">نظر بیماران</h2>
        {/* WP: link to /testimonials or reviews CPT archive */}
        <a href="/testimonials" className="text-sm text-[#28722C] font-medium flex items-center gap-1 hover:gap-2 transition-all">
          همه نظرات <ArrowLeft size={13} />
        </a>
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {/* PHP: foreach ($testimonials as $t) */}
        {items.map((t, i) => (
          <div key={i} className="bg-white rounded-2xl border border-[#DDE2DD] p-4 flex flex-col gap-3">
            <div className="flex items-start justify-between">
              <div>
                {/* ACF: patient_name (anonymized per GDPR) */}
                <p className="font-semibold text-[#25272C] text-sm">{t.name}</p>
                <p className="text-xs text-[#6A7078]">{t.age} · {t.procedure}</p>
              </div>
              {/* ACF: is_verified_patient */}
              {t.verified && (
                <Badge variant="primary"><CheckCircle size={10} />تأیید شده</Badge>
              )}
            </div>
            <StarRating rating={t.rating} />
            {/* ACF: review_text */}
            <p className="text-sm text-[#6A7078] leading-relaxed flex-1">"{t.text}"</p>
            {/* ACF: review_date (Jalali) */}
            <p className="text-xs text-[#6A7078] border-t border-[#DDE2DD] pt-2">{t.date}</p>
          </div>
        ))}
      </div>
    </section>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED RELATED POSTS — WP_Query: related by category + tags
// PHP: query_posts(['category_name' => $cat, 'posts_per_page' => 3, 'post__not_in' => [get_the_ID()]])
// ─────────────────────────────────────────────────────────────────────────────
function RelatedPosts({ posts }: { posts: RelatedPost[] }) {
  return (
    <section aria-labelledby="related-heading">
      <div className="flex items-center justify-between mb-5">
        <h2 id="related-heading" className="text-xl font-bold text-[#25272C]">مقالات مرتبط</h2>
        <a href="/blog" className="text-sm text-[#28722C] font-medium flex items-center gap-1 hover:gap-2 transition-all">
          همه مقالات <ArrowLeft size={13} />
        </a>
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {/* PHP: foreach ($related_posts as $post) */}
        {posts.map((p, i) => (
          <article key={i} className="bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden group hover:shadow-md transition-shadow">
            {/* WP: get_the_post_thumbnail_url($id, 'medium') */}
            <div className="bg-[#DDE2DD] overflow-hidden">
              <img
                src={`https://images.unsplash.com/${p.imgId}?w=400&h=220&fit=crop&auto=format`}
                alt={p.title}
                className="w-full h-44 object-cover group-hover:scale-105 transition-transform duration-500"
                loading="lazy"
              />
            </div>
            <div className="p-4">
              <div className="flex items-center gap-2 mb-2">
                <Badge variant="primary">{p.category}</Badge>
                <span className="text-xs text-[#6A7078] flex items-center gap-1"><Clock size={10} />{p.readTime}</span>
              </div>
              {/* WP: the_title() */}
              <h3 className="font-semibold text-[#25272C] text-sm leading-relaxed mb-1 group-hover:text-[#28722C] transition-colors">{p.title}</h3>
              <p className="text-xs text-[#6A7078] line-clamp-2 mb-2">{p.excerpt}</p>
              {/* WP: get_the_date('j F Y', $post) — Jalali via plugin */}
              <p className="text-xs text-[#6A7078]">{p.date}</p>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED CONTACT CTA SECTION
// PHP: include get_template_part('sections/contact-cta');
// ACF options: cta_heading, cta_subheading, booking_url
// ─────────────────────────────────────────────────────────────────────────────
function ContactCTA() {
  const [sent, setSent] = useState(false);
  return (
    <section id="contact" aria-labelledby="contact-heading">
      <div className="bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden">
        <div className="grid grid-cols-1 lg:grid-cols-2">
          {/* Info panel */}
          <div className="bg-gradient-to-br from-[#28722C] to-[#1a4e1d] p-8 text-white">
            {/* ACF options: contact_cta_heading */}
            <h2 id="contact-heading" className="text-2xl font-bold mb-2">مشاوره رایگان</h2>
            <p className="text-white/80 text-sm leading-relaxed mb-6">تیم ما آماده پاسخگویی به سوالات شما و هماهنگی وقت مشاوره حضوری است.</p>
            <div className="space-y-4">
              {/* ACF options: clinic_phone_1, clinic_phone_2, clinic_hours, clinic_address */}
              {[
                { Icon: Phone, label: "تلفن", val: "۰۲۱۸۶۰۸۷۲۵۰  ·  ۰۲۱۸۸۲۰۵۶۰۶" },
                { Icon: MapPin, label: "آدرس", val: "تهران، خ. نلسون ماندلا، خ. صانعی، ساختمان نور، پلاک ۱ واحد ۶" },
                { Icon: Clock, label: "ساعت پذیرش", val: "شنبه و سه‌شنبه — ۱۵:۰۰ تا ۱۹:۰۰" },
              ].map(({ Icon, label, val }) => (
                <div key={label} className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                    <Icon size={14} className="text-white" />
                  </div>
                  <div>
                    <p className="text-xs text-white/60 mb-0.5">{label}</p>
                    <p className="text-sm font-medium text-white">{val}</p>
                  </div>
                </div>
              ))}
            </div>
            {/* Trust signals */}
            <div className="mt-6 pt-6 border-t border-white/20 flex flex-wrap gap-3">
              {["مشاوره رایگان", "بدون تعهد", "محرمانه"].map(t => (
                <span key={t} className="flex items-center gap-1 text-xs text-white/70">
                  <CheckCircle size={11} className="text-green-300" />{t}
                </span>
              ))}
            </div>
          </div>

          {/* Form — PHP: process via WP AJAX or Contact Form 7 / Gravity Forms */}
          <div className="p-8">
            {sent ? (
              <div className="h-full flex flex-col items-center justify-center text-center gap-3">
                <div className="w-16 h-16 rounded-full bg-[#E4F0E4] flex items-center justify-center">
                  <CheckCircle size={28} className="text-[#28722C]" />
                </div>
                <p className="text-lg font-bold text-[#25272C]">پیام دریافت شد!</p>
                <p className="text-sm text-[#6A7078]">تیم ما در اسرع وقت با شما تماس می‌گیرد.</p>
              </div>
            ) : (
              <>
                <h3 className="font-bold text-[#25272C] mb-5">فرم درخواست مشاوره</h3>
                <form className="space-y-4" onSubmit={e => { e.preventDefault(); setSent(true); }}
                  /* PHP: action="<?= admin_url('admin-ajax.php') ?>" method="POST" */
                >
                  {/* WP nonce: wp_nonce_field('contact_form_nonce') */}
                  <div className="grid grid-cols-2 gap-3">
                    {/* ACF/CF7: first_name, last_name */}
                    {["نام", "نام خانوادگی"].map(label => (
                      <div key={label}>
                        <label className="text-xs text-[#6A7078] mb-1 block">{label}</label>
                        <input type="text" placeholder={label} required
                          className="w-full px-3 py-2.5 text-sm rounded-lg border border-[#DDE2DD] bg-[#F7F8F6] focus:outline-none focus:border-[#28722C] focus:ring-2 focus:ring-[#28722C]/10 transition-all" />
                      </div>
                    ))}
                  </div>
                  {/* phone */}
                  <div>
                    <label className="text-xs text-[#6A7078] mb-1 block">شماره تماس</label>
                    <input type="tel" placeholder="۰۹۱۲..." dir="ltr" required
                      className="w-full px-3 py-2.5 text-sm rounded-lg border border-[#DDE2DD] bg-[#F7F8F6] focus:outline-none focus:border-[#28722C] transition-all text-right" />
                  </div>
                  {/* ACF/CF7: service_interest — populated from ACF options or custom taxonomy */}
                  <div>
                    <label className="text-xs text-[#6A7078] mb-1 block">موضوع مشاوره</label>
                    <select className="w-full px-3 py-2.5 text-sm rounded-lg border border-[#DDE2DD] bg-[#F7F8F6] focus:outline-none focus:border-[#28722C] transition-all text-[#25272C]">
                      <option>رینوپلاستی (جراحی بینی اولیه)</option>
                      <option>جراحی بینی ترمیمی</option>
                      <option>رفع قوز بینی</option>
                      <option>انحراف بینی (سپتوپلاستی)</option>
                      <option>سایر</option>
                    </select>
                  </div>
                  {/* message */}
                  <div>
                    <label className="text-xs text-[#6A7078] mb-1 block">توضیحات</label>
                    <textarea rows={3} placeholder="سوال یا توضیح..."
                      className="w-full px-3 py-2.5 text-sm rounded-lg border border-[#DDE2DD] bg-[#F7F8F6] focus:outline-none focus:border-[#28722C] focus:ring-2 focus:ring-[#28722C]/10 transition-all resize-none" />
                  </div>
                  <button type="submit"
                    className="w-full py-3 bg-[#28722C] text-white text-sm font-bold rounded-xl hover:bg-[#246b28] transition-colors flex items-center justify-center gap-2">
                    <Send size={15} />ارسال درخواست مشاوره
                  </button>
                  <p className="text-xs text-[#6A7078] text-center flex items-center justify-center gap-1">
                    <Shield size={11} />اطلاعات شما محرمانه و امن است
                  </p>
                </form>
              </>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED SIDEBAR — PHP: get_sidebar('post') or get_sidebar('service')
// ─────────────────────────────────────────────────────────────────────────────
function ArticleSidebar({ meta, tocItems }: { meta: PostMeta; tocItems: string[] }) {
  return (
    <aside className="space-y-5" aria-label="نوار کناری مقاله">

      {/* TOC — ACF: generated from H2/H3 headings via JS in production */}
      <div className="bg-white rounded-2xl border border-[#DDE2DD] p-5 sticky top-20">
        <p className="text-sm font-bold text-[#25272C] mb-4 flex items-center gap-2">
          <BookOpen size={14} className="text-[#28722C]" />فهرست مطالب
        </p>
        <nav aria-label="فهرست مطالب مقاله">
          <ol className="space-y-1.5">
            {tocItems.map((item, i) => (
              <li key={i}>
                <a href={`#section-${i}`}
                  className="flex items-center gap-2 py-1 text-xs text-[#6A7078] hover:text-[#28722C] transition-colors group">
                  <span className="w-5 h-5 rounded-full bg-[#E4F0E4] text-[#28722C] text-[10px] font-bold flex items-center justify-center flex-shrink-0 group-hover:bg-[#28722C] group-hover:text-white transition-colors">
                    {(i + 1).toLocaleString("fa-IR")}
                  </span>
                  {item}
                </a>
              </li>
            ))}
          </ol>
        </nav>
      </div>

      {/* CTA card */}
      <div className="bg-gradient-to-br from-[#28722C] to-[#1a4e1d] rounded-2xl p-5 text-white">
        <p className="font-bold mb-1.5">مشاوره رایگان</p>
        <p className="text-sm text-white/80 leading-relaxed mb-4">پرونده اولیه خود را تکمیل کنید — تیم ما با شما تماس می‌گیرد.</p>
        <a href="https://app.drbastaninejad.com/" rel="noopener"
          className="flex items-center justify-center gap-2 w-full py-2.5 bg-white text-[#28722C] rounded-xl text-sm font-bold hover:bg-[#E4F0E4] transition-colors">
          <Phone size={13} />تشکیل پرونده
        </a>
        <div className="flex items-center justify-center gap-1.5 mt-3 text-xs text-white/70">
          <Shield size={11} />بدون تعهد · رایگان
        </div>
      </div>

      {/* Author mini card */}
      <div className="bg-white rounded-2xl border border-[#DDE2DD] p-4">
        <p className="text-xs font-bold text-[#6A7078] mb-3 uppercase tracking-widest">نویسنده</p>
        <div className="flex items-center gap-3 mb-3">
          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-[#28722C] to-[#1a4e1d] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
            {meta.author.avatarInitials}
          </div>
          <div>
            <p className="font-semibold text-[#25272C] text-xs">{meta.author.name}</p>
            <p className="text-[10px] text-[#6A7078]">{meta.author.title}</p>
          </div>
        </div>
        <div className="flex items-center gap-1.5 text-xs text-[#28722C] font-medium">
          <CheckCircle size={11} />
          <span>بازبینی: {meta.lastReviewed}</span>
        </div>
      </div>

      {/* Trust signals */}
      <div className="bg-white rounded-2xl border border-[#DDE2DD] p-4">
        <p className="text-xs font-bold text-[#6A7078] mb-3 uppercase tracking-widest">اعتبار و مجوزها</p>
        <div className="space-y-2">
          {[
            { Icon: Shield, label: "مجوز وزارت بهداشت ایران" },
            { Icon: Award, label: "بورد تخصصی معتبر ISAPS" },
            { Icon: GraduationCap, label: "فلوشیپ جراحی پلاستیک صورت" },
            { Icon: CheckCircle, label: "بیمه مسئولیت پزشکی" },
          ].map(({ Icon, label }) => (
            <div key={label} className="flex items-center gap-2 text-xs text-[#25272C]">
              <div className="w-6 h-6 rounded-lg bg-[#E4F0E4] flex items-center justify-center flex-shrink-0">
                <Icon size={12} className="text-[#28722C]" />
              </div>
              {label}
            </div>
          ))}
        </div>
      </div>

      {/* Tags — WP: get_the_tags() */}
      <div className="bg-white rounded-2xl border border-[#DDE2DD] p-4">
        <p className="text-xs font-bold text-[#6A7078] mb-3 uppercase tracking-widest">برچسب‌ها</p>
        <div className="flex flex-wrap gap-1.5">
          {meta.tags.map(tag => (
            <a key={tag} href={`/tag/${tag}`}
              className="text-xs px-2.5 py-1 bg-[#F7F8F6] border border-[#DDE2DD] rounded-full text-[#6A7078] hover:bg-[#E4F0E4] hover:text-[#28722C] hover:border-[#28722C]/30 transition-all">
              #{tag}
            </a>
          ))}
        </div>
      </div>
    </aside>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED MEDICAL DISCLAIMER — static, always visible on YMYL pages
// PHP: include get_template_part('components/medical-disclaimer');
// ─────────────────────────────────────────────────────────────────────────────
function MedicalDisclaimer() {
  return (
    <div className="bg-amber-50 border border-amber-200 rounded-xl p-4" role="note" aria-label="یادداشت پزشکی">
      <div className="flex items-start gap-2.5">
        <AlertCircle size={16} className="text-amber-600 flex-shrink-0 mt-0.5" />
        <p className="text-xs text-amber-900 leading-relaxed">
          <strong>یادداشت پزشکی (YMYL):</strong> محتوای این صفحه صرفاً جنبه آموزشی دارد و جایگزین مشاوره، تشخیص یا درمان پزشکی نیست.
          برای هر تصمیم پزشکی با جراح متخصص مجاز مشورت کنید.
        </p>
      </div>
    </div>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// PAGE 1 — FULL EDUCATIONAL BLOG POST
// PHP template: single-post.php | WP post type: post | category: راهنمای بیمار
// Custom fields (ACF field group: Blog Post Details):
//   • post_read_time (number)     • post_difficulty (select)
//   • last_medical_review_date (date_picker)  • key_takeaways (textarea)
//   • faq_items (repeater)        • testimonials (relationship)
// ─────────────────────────────────────────────────────────────────────────────
function BlogPostPage() {
  const [liked, setLiked] = useState(false);
  const [likeCount, setLikeCount] = useState(247);
  const [bookmarked, setBookmarked] = useState(false);
  const [activeBeforeAfter, setActiveBeforeAfter] = useState<"before" | "after">("before");
  const [readProgress, setReadProgress] = useState(0);

  useEffect(() => {
    const onScroll = () => {
      const doc = document.documentElement;
      const pct = (window.scrollY / (doc.scrollHeight - doc.clientHeight)) * 100;
      setReadProgress(Math.min(100, pct));
    };
    window.addEventListener("scroll", onScroll);
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  const meta = POST1_META;

  return (
    <>
      {/* Read progress — JS: driven by scroll event */}
      <div className="fixed top-[65px] left-0 right-0 z-40 h-[3px] bg-[#DDE2DD]" aria-hidden>
        <div className="h-full bg-[#28722C] transition-all duration-100" style={{ width: `${readProgress}%` }} />
      </div>

      <Breadcrumb items={[
        { label: "خانه", href: "/" },
        { label: "وبلاگ", href: "/blog" },
        { label: meta.category, href: `/category/${meta.categorySlug}` },
        { label: meta.title },
      ]} />

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-8">
        <div className="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-8">

          {/* ── MAIN ARTICLE ─────────────────────────────────────────────── */}
          <main id="main-content">
            <article>

              {/* ── POST HEADER ─────────────────────────────────────────── */}
              {/* PHP: get_the_title(), get_post_meta(), get_field() calls */}
              <header className="mb-6">
                {/* Badges — ACF: post_category, post_difficulty, is_expert_reviewed */}
                <div className="flex flex-wrap items-center gap-2 mb-4">
                  <Badge variant="primary"><Tag size={10} />{meta.category}</Badge>
                  <DifficultyBadge level={meta.difficultyLevel} />
                  <Badge variant="info"><Shield size={10} />تأیید پزشک متخصص</Badge>
                  <Badge variant="accent"><Award size={10} />مبتنی بر شواهد</Badge>
                </div>

                {/* H1 — WP: the_title() */}
                <h1 className="text-2xl sm:text-3xl lg:text-[2rem] font-bold text-[#25272C] leading-snug mb-3">
                  {meta.title}
                </h1>
                {/* Subtitle — ACF: post_subtitle */}
                <p className="text-lg text-[#6A7078] leading-relaxed mb-5">{meta.subtitle}</p>

                {/* Meta row — WP: get_the_date(), get_post_meta views, etc. */}
                <div className="flex flex-wrap items-center gap-4 text-sm text-[#6A7078] pb-5 border-b border-[#DDE2DD]">
                  <div className="flex items-center gap-2">
                    {/* Production: get_avatar(get_the_author_meta('ID'), 32) */}
                    <div className="w-8 h-8 rounded-full bg-[#28722C] flex items-center justify-center text-white text-xs font-bold">{meta.author.avatarInitials}</div>
                    <span>
                      <span className="font-semibold text-[#25272C]">{meta.author.name}</span>
                      <span className="text-[#6A7078]"> — {meta.author.title}</span>
                    </span>
                  </div>
                  <div className="flex items-center gap-1"><Calendar size={12} />{meta.publishDate}</div>
                  <div className="flex items-center gap-1"><Clock size={12} />{meta.readTimeMin} دقیقه مطالعه</div>
                  <div className="flex items-center gap-1"><Eye size={12} />{meta.viewCount.toLocaleString("fa-IR")} بازدید</div>
                  {/* ACF: last_medical_review_date — critical for YMYL/EEAT */}
                  <div className="flex items-center gap-1 text-[#28722C] font-medium mr-auto">
                    <CheckCircle size={12} />بازبینی پزشکی: {meta.lastReviewed}
                  </div>
                </div>

                {/* Action buttons */}
                <div className="flex flex-wrap items-center gap-2 pt-4">
                  <button onClick={() => { setLiked(!liked); setLikeCount(c => liked ? c - 1 : c + 1); }}
                    className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition-all ${liked ? "bg-[#28722C] text-white border-[#28722C]" : "bg-white text-[#6A7078] border-[#DDE2DD] hover:border-[#28722C] hover:text-[#28722C]"}`}>
                    <ThumbsUp size={13} />{likeCount.toLocaleString("fa-IR")}
                  </button>
                  <button onClick={() => setBookmarked(!bookmarked)}
                    className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition-all ${bookmarked ? "bg-amber-50 text-amber-700 border-amber-300" : "bg-white text-[#6A7078] border-[#DDE2DD] hover:border-amber-300 hover:text-amber-700"}`}>
                    <Bookmark size={13} />ذخیره
                  </button>
                  <button className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border border-[#DDE2DD] bg-white text-[#6A7078] hover:border-[#28722C] hover:text-[#28722C] transition-all">
                    <Share2 size={13} />اشتراک‌گذاری
                  </button>
                </div>
              </header>

              {/* ── FEATURED IMAGE — WP: the_post_thumbnail('full') */}
              <div className="relative rounded-2xl overflow-hidden mb-8 bg-[#DDE2DD]">
                <img
                  src={`https://images.unsplash.com/${meta.featuredImage.url}?w=860&h=440&fit=crop&auto=format`}
                  alt={meta.featuredImage.alt}
                  className="w-full h-64 sm:h-80 object-cover"
                  loading="eager"
                  fetchPriority="high"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-[#25272C]/50 via-transparent to-transparent" />
                <div className="absolute bottom-4 right-4 left-4">
                  {/* ACF: featured_image_caption */}
                  <p className="text-white text-sm font-medium drop-shadow-sm">
                    کلینیک دکتر شاهین باستانی‌نژاد — مجهز به تکنولوژی روز جراحی پلاستیک
                  </p>
                </div>
              </div>

              {/* ── KEY TAKEAWAYS — ACF: key_takeaways (textarea or wysiwyg) */}
              <div className="bg-gradient-to-br from-[#28722C] to-[#246b28] rounded-2xl p-6 mb-8 text-white shadow-lg">
                <div className="flex items-center gap-2 mb-4">
                  <div className="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                    <CheckCircle size={16} />
                  </div>
                  <h2 className="text-lg font-bold">نکات کلیدی این مقاله</h2>
                </div>
                <ul className="space-y-2.5">
                  {/* PHP: foreach (get_field('key_takeaways_list') as $item) */}
                  {[
                    "رینوپلاستی نیازمند برنامه‌ریزی دقیق و انتخاب جراح بورد‌سرتیفاید است",
                    "دوره ریکاوری کامل ۶ تا ۱۲ ماه است و نتیجه نهایی تدریجی دیده می‌شود",
                    "انتخاب روش باز یا بسته بر اساس پیچیدگی عمل و نظر جراح تعیین می‌شود",
                    "داشتن انتظارات واقع‌بینانه مهم‌ترین عامل رضایت بیمار است",
                    "ایران با +۲۰۰٬۰۰۰ عمل سالانه، مرکز جهانی رینوپلاستی است",
                  ].map((item, i) => (
                    <li key={i} className="flex items-start gap-2.5 text-sm leading-relaxed">
                      <CheckCircle size={14} className="text-white/70 flex-shrink-0 mt-0.5" />
                      {item}
                    </li>
                  ))}
                </ul>
              </div>

              {/* ── ARTICLE BODY — WP: the_content() or ACF wysiwyg fields per section */}
              <div className="space-y-10">

                {/* Section 1 — ACF: section_1_content or standard WP block */}
                <section id="section-0">
                  <h2 className="text-xl sm:text-2xl font-bold text-[#25272C] mb-4 flex items-center gap-2">
                    <span className="w-8 h-8 rounded-lg bg-[#E4F0E4] text-[#28722C] text-sm font-bold flex items-center justify-center flex-shrink-0">۱</span>
                    رینوپلاستی چیست؟
                  </h2>
                  <p className="text-[#25272C] leading-[2] mb-4">
                    <strong className="text-[#28722C]">رینوپلاستی</strong> (Rhinoplasty) یا جراحی بینی، یکی از پیچیده‌ترین و تخصصی‌ترین اقدامات جراحی زیبایی در جهان است.
                    این جراحی با هدف اصلاح فرم، اندازه، تناسب یا عملکرد بینی انجام می‌شود. ایران با بیش از
                    <strong> ۲۰۰٬۰۰۰ عمل سالانه</strong>، رتبه اول جهان را در تعداد رینوپلاستی دارد.
                  </p>

                  {/* Definition box — ACF: definition_term, definition_body */}
                  <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                    <div className="flex items-start gap-2.5">
                      <Info size={16} className="text-blue-600 flex-shrink-0 mt-0.5" />
                      <div>
                        <p className="text-sm font-bold text-blue-900 mb-1">تعریف پزشکی</p>
                        <p className="text-sm text-blue-800 leading-relaxed">
                          <strong>رینوپلاستی (Rhinoplasty):</strong> مداخله جراحی بر ساختارهای استخوانی، غضروفی و نرم‌افزاری بینی با هدف اصلاح زیبایی یا عملکردی. کد ICD-10: Q30 · Z41.1
                        </p>
                      </div>
                    </div>
                  </div>
                </section>

                {/* Section 2 — Comparison table — ACF: comparison_table (repeater) */}
                <section id="section-1">
                  <h2 className="text-xl sm:text-2xl font-bold text-[#25272C] mb-4 flex items-center gap-2">
                    <span className="w-8 h-8 rounded-lg bg-[#E4F0E4] text-[#28722C] text-sm font-bold flex items-center justify-center flex-shrink-0">۲</span>
                    انواع روش‌های رینوپلاستی
                  </h2>
                  <div className="overflow-x-auto rounded-xl border border-[#DDE2DD] mb-4">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="bg-[#E4F0E4]">
                          {["روش", "ویژگی اصلی", "مناسب برای", "ندبه"].map(h => (
                            <th key={h} className="text-right py-3 px-4 font-bold text-[#25272C]">{h}</th>
                          ))}
                        </tr>
                      </thead>
                      <tbody>
                        {/* PHP: foreach (get_field('procedure_types') as $row) */}
                        {[
                          ["رینوپلاستی باز", "دید مستقیم کامل", "موارد پیچیده، ترمیمی", "برش کوچک ستون بینی"],
                          ["رینوپلاستی بسته", "برش داخل بینی", "اصلاح‌های محدود", "بدون ندبه خارجی"],
                          ["رینوپلاستی تزریقی", "فیلر، غیرجراحی", "اصلاح‌های موقت کوچک", "هیچ"],
                          ["سپتوپلاستی", "اصلاح تیغه میانی", "انحراف و مشکل تنفسی", "داخلی"],
                        ].map(([method, feat, suitable, scar], i) => (
                          <tr key={i} className={`border-t border-[#DDE2DD] ${i % 2 === 0 ? "bg-white" : "bg-[#F7F8F6]"}`}>
                            <td className="py-3 px-4 font-semibold text-[#28722C]">{method}</td>
                            <td className="py-3 px-4 text-[#25272C]">{feat}</td>
                            <td className="py-3 px-4 text-[#6A7078]">{suitable}</td>
                            <td className="py-3 px-4 text-[#6A7078]">{scar}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </section>

                {/* Section 3 — Candidacy — ACF: candidacy_checklist (repeater) */}
                <section id="section-2">
                  <h2 className="text-xl sm:text-2xl font-bold text-[#25272C] mb-4 flex items-center gap-2">
                    <span className="w-8 h-8 rounded-lg bg-[#E4F0E4] text-[#28722C] text-sm font-bold flex items-center justify-center flex-shrink-0">۳</span>
                    معیارهای کاندیداتوری
                  </h2>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                    <div className="bg-white rounded-xl border border-[#DDE2DD] p-4">
                      <p className="font-bold text-[#28722C] mb-3 flex items-center gap-2"><CheckCircle size={15} />کاندیداهای مناسب</p>
                      {/* ACF repeater: candidacy_positive */}
                      {["بالای ۱۸ سال (رشد کامل)", "سلامت عمومی مناسب", "انتظارات واقع‌بینانه", "غیرسیگاری یا آماده ترک", "بدون اختلال روانپزشکی فعال"].map((item, i) => (
                        <div key={i} className="flex items-center gap-2 text-sm text-[#25272C] py-1.5 border-b border-[#F7F8F6] last:border-0">
                          <CheckCircle size={12} className="text-[#28722C] flex-shrink-0" />{item}
                        </div>
                      ))}
                    </div>
                    <div className="bg-white rounded-xl border border-[#DDE2DD] p-4">
                      <p className="font-bold text-red-600 mb-3 flex items-center gap-2"><AlertCircle size={15} />موارد منع نسبی</p>
                      {/* ACF repeater: candidacy_contraindications */}
                      {["دیابت کنترل‌نشده", "بیماری‌های قلبی–عروقی شدید", "اختلالات انعقادی", "بارداری یا شیردهی", "مصرف داروهای رقیق‌کننده"].map((item, i) => (
                        <div key={i} className="flex items-center gap-2 text-sm text-[#25272C] py-1.5 border-b border-[#F7F8F6] last:border-0">
                          <AlertCircle size={12} className="text-red-400 flex-shrink-0" />{item}
                        </div>
                      ))}
                    </div>
                  </div>
                  <MedicalDisclaimer />
                </section>

                {/* Section 4 — Steps — ACF: procedure_steps (repeater) */}
                <section id="section-3">
                  <h2 className="text-xl sm:text-2xl font-bold text-[#25272C] mb-5 flex items-center gap-2">
                    <span className="w-8 h-8 rounded-lg bg-[#E4F0E4] text-[#28722C] text-sm font-bold flex items-center justify-center flex-shrink-0">۴</span>
                    مراحل جراحی
                  </h2>
                  {/* PHP: foreach (get_field('procedure_steps') as $i => $step) */}
                  <div className="space-y-3">
                    {[
                      { step: "مشاوره اولیه", desc: "معاینه، عکاسی ۳D و برنامه‌ریزی شخصی", time: "جلسه اول" },
                      { step: "آزمایشات پیش از عمل", desc: "آزمایش خون، EKG، مشاوره بیهوشی", time: "۱–۲ هفته قبل" },
                      { step: "جراحی", desc: "بیهوشی عمومی، ۱.۵ تا ۳ ساعت", time: "روز عمل" },
                      { step: "ریکاوری اولیه", desc: "اسپلینت ۷ روز، برگشت به فعالیت عادی", time: "هفته اول" },
                      { step: "نتیجه نهایی", desc: "کاهش تدریجی ورم — نتیجه کامل ۶–۱۲ ماه", time: "۶–۱۲ ماه" },
                    ].map((s, i) => (
                      <div key={i} className="flex gap-4">
                        <div className="flex flex-col items-center flex-shrink-0">
                          <div className="w-8 h-8 rounded-full bg-[#28722C] text-white text-xs font-bold flex items-center justify-center">{(i+1).toLocaleString("fa-IR")}</div>
                          {i < 4 && <div className="w-px flex-1 bg-[#DDE2DD] my-1" />}
                        </div>
                        <div className="bg-white rounded-xl border border-[#DDE2DD] p-4 flex-1 flex items-center justify-between gap-3 mb-0">
                          <div>
                            <p className="font-semibold text-[#25272C] text-sm">{s.step}</p>
                            <p className="text-sm text-[#6A7078]">{s.desc}</p>
                          </div>
                          <Badge variant="muted">{s.time}</Badge>
                        </div>
                      </div>
                    ))}
                  </div>
                </section>

                {/* Section 5 — Recovery table */}
                <section id="section-4">
                  <h2 className="text-xl sm:text-2xl font-bold text-[#25272C] mb-4 flex items-center gap-2">
                    <span className="w-8 h-8 rounded-lg bg-[#E4F0E4] text-[#28722C] text-sm font-bold flex items-center justify-center flex-shrink-0">۵</span>
                    دوره بهبودی
                  </h2>
                  <div className="overflow-x-auto rounded-xl border border-[#DDE2DD] mb-4">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="bg-[#E4F0E4]">
                          {["زمان", "وضعیت بیمار", "اقدام لازم"].map(h => (
                            <th key={h} className="text-right py-3 px-4 font-bold text-[#25272C]">{h}</th>
                          ))}
                        </tr>
                      </thead>
                      <tbody>
                        {[
                          ["روز ۱–۳", "ورم و کبودی شدید", "استراحت کامل، داروها"],
                          ["روز ۷", "برداشتن اسپلینت", "ویزیت کنترل پزشک"],
                          ["هفته ۲–۳", "کاهش ورم ۶۰٪", "بازگشت به کار (اداری)"],
                          ["ماه ۱–۳", "شکل تقریبی نهایی", "پرهیز از ورزش سنگین"],
                          ["ماه ۶–۱۲", "نتیجه کامل و دائمی", "عکاسی نتیجه نهایی"],
                        ].map(([t, s, a], i) => (
                          <tr key={i} className={`border-t border-[#DDE2DD] ${i % 2 === 0 ? "bg-white" : "bg-[#F7F8F6]"}`}>
                            <td className="py-3 px-4 font-semibold text-[#28722C]">{t}</td>
                            <td className="py-3 px-4 text-[#25272C]">{s}</td>
                            <td className="py-3 px-4 text-[#6A7078]">{a}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </section>

                {/* Section 6 — Before/After gallery — ACF: gallery (gallery field) or CPT: case-study */}
                <section id="section-5">
                  <h2 className="text-xl sm:text-2xl font-bold text-[#25272C] mb-4 flex items-center gap-2">
                    <span className="w-8 h-8 rounded-lg bg-[#E4F0E4] text-[#28722C] text-sm font-bold flex items-center justify-center flex-shrink-0">۶</span>
                    نمونه‌های قبل و بعد
                  </h2>
                  <div className="bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden">
                    {/* Tab switch — PHP: controlled by URL param or JS */}
                    <div className="flex border-b border-[#DDE2DD]">
                      {(["before", "after"] as const).map(tab => (
                        <button key={tab} onClick={() => setActiveBeforeAfter(tab)}
                          className={`flex-1 py-3 text-sm font-semibold transition-colors ${activeBeforeAfter === tab ? "bg-[#28722C] text-white" : "text-[#6A7078] hover:bg-[#F7F8F6]"}`}>
                          {tab === "before" ? "قبل از عمل" : "بعد از عمل"}
                        </button>
                      ))}
                    </div>
                    <div className="p-4 grid grid-cols-3 gap-3">
                      {/* ACF gallery: before_gallery / after_gallery */}
                      {[
                        "photo-1559757148-5c350d0d3c56",
                        "photo-1576091160550-2173dba999ef",
                        "photo-1582750433449-648ed127bb54",
                      ].map((id, i) => (
                        <div key={i} className="relative rounded-xl overflow-hidden bg-[#DDE2DD]">
                          <img
                            src={`https://images.unsplash.com/${id}?w=280&h=320&fit=crop&auto=format`}
                            alt={`نمونه ${i+1} — ${activeBeforeAfter === "before" ? "قبل" : "بعد"} از عمل`}
                            className="w-full h-44 object-cover"
                            loading="lazy"
                          />
                          <div className="absolute bottom-2 right-2">
                            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${activeBeforeAfter === "before" ? "bg-gray-800/80 text-white" : "bg-[#28722C]/90 text-white"}`}>
                              {activeBeforeAfter === "before" ? "قبل" : "بعد"}
                            </span>
                          </div>
                        </div>
                      ))}
                    </div>
                    <p className="text-xs text-[#6A7078] text-center pb-4">تصاویر با رضایت کامل بیماران منتشر شده‌اند. نتایج فردی متفاوت است.</p>
                  </div>
                </section>

                {/* FAQ section */}
                <section id="section-6">
                  <FAQSection faqs={POST1_FAQS} heading="پرسش‌های متداول — رینوپلاستی" />
                </section>

                {/* Mid-article CTA — ACF: mid_cta_text, mid_cta_url */}
                <div className="bg-[#E4F0E4] border border-[#28722C]/20 rounded-2xl p-6 flex flex-col sm:flex-row items-center gap-4">
                  <div className="flex-1">
                    <p className="font-bold text-[#25272C] mb-1">می‌خواهید بدانید رینوپلاستی برای شما مناسب است؟</p>
                    <p className="text-sm text-[#6A7078]">پرونده اولیه خود را تکمیل کنید تا دکتر باستانی‌نژاد وضعیت بینی شما را بررسی کنند.</p>
                  </div>
                  <a href="https://app.drbastaninejad.com/" rel="noopener"
                    className="flex-shrink-0 flex items-center gap-2 px-5 py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
                    <Phone size={14} />تشکیل پرونده
                  </a>
                </div>

              </div>{/* /article body */}

              {/* Testimonials */}
              <div className="mt-10">
                <Testimonials items={POST1_TESTIMONIALS} />
              </div>

              {/* Author bio */}
              <div className="mt-10">
                <AuthorBio author={meta.author} />
              </div>

            </article>

            {/* Related posts */}
            <div className="mt-10">
              <RelatedPosts posts={POST1_RELATED} />
            </div>

            {/* Contact CTA */}
            <div className="mt-10">
              <ContactCTA />
            </div>
          </main>

          {/* Sidebar — xl only */}
          <div className="hidden xl:block">
            <ArticleSidebar meta={meta} tocItems={["رینوپلاستی چیست؟", "انواع روش‌ها", "معیارهای کاندیداتوری", "مراحل جراحی", "دوره بهبودی", "قبل و بعد", "سوالات متداول"]} />
          </div>
        </div>
      </div>
    </>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// PAGE 2 — SERVICE DETAIL PAGE (hump removal pattern)
// PHP template: single-service.php | WP post type: service
// ACF field group: Service Details
//   • service_highlights (repeater: icon, title, body)
//   • service_procedure_steps (repeater)
//   • key_takeaways (wysiwyg)
//   • faq_items (repeater)
//   • related_services (relationship)
// ─────────────────────────────────────────────────────────────────────────────
function ServiceDetailPage() {
  return (
    <>
      <Breadcrumb items={[
        { label: "خانه", href: "/" },
        { label: "خدمات", href: "/services" },
        { label: "رفع قوز بینی" },
      ]} />

      {/* Service Hero — ACF: hero_label, page_title (post_title), hero_lead */}
      <header className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto">
          <div className="inline-block text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full mb-4 border border-[#28722C]/30">
            جراحی بینی
          </div>
          {/* WP: the_title() */}
          <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">رفع قوز بینی</h1>
          {/* ACF: hero_lead */}
          <p className="text-white/75 text-lg leading-relaxed max-w-xl">
            رفع قوز بینی یکی از رایج‌ترین دلایل مراجعه برای جراحی بینی است. با برداشت بخش اضافی استخوان یا غضروف، پروفایل صاف و طبیعی ایجاد می‌شود.
          </p>
          <div className="flex flex-wrap gap-3 mt-6">
            <a href="https://app.drbastaninejad.com/" rel="noopener"
              className="flex items-center gap-2 px-5 py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
              <Phone size={14} />تشکیل پرونده اولیه
            </a>
            <a href="#faq"
              className="flex items-center gap-2 px-5 py-3 border border-white/30 text-white rounded-xl text-sm font-semibold hover:bg-white/10 transition-colors">
              سوالات متداول
            </a>
          </div>
        </div>
      </header>

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-10">
        <div className="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-8">
          <main id="main-content">

            {/* Service highlights — ACF repeater: service_highlights */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10">
              {PAGE2_HIGHLIGHTS.map((h, i) => {
                const icons: Record<string, React.ReactNode> = {
                  BarChart2: <BarChart2 size={20} className="text-[#28722C]" />,
                  Layers: <Layers size={20} className="text-[#28722C]" />,
                  Eye: <Eye size={20} className="text-[#28722C]" />,
                  Zap: <Zap size={20} className="text-[#28722C]" />,
                };
                return (
                  <div key={i} className="bg-white rounded-2xl border border-[#DDE2DD] p-4 text-center">
                    {/* ACF: highlight_icon → mapped to Lucide */}
                    <div className="w-10 h-10 rounded-xl bg-[#E4F0E4] flex items-center justify-center mx-auto mb-3">
                      {icons[h.icon] || <CheckCircle size={20} className="text-[#28722C]" />}
                    </div>
                    {/* ACF: highlight_title */}
                    <p className="text-sm font-bold text-[#25272C] mb-1">{h.title}</p>
                    {/* ACF: highlight_body */}
                    <p className="text-xs text-[#6A7078] leading-relaxed">{h.body}</p>
                  </div>
                );
              })}
            </div>

            {/* Article body — WP: the_content() or ACF content sections */}
            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6 sm:p-8 mb-8">
              {/* ACF: section_what_is */}
              <h2 className="text-xl font-bold text-[#25272C] mb-4">قوز بینی چیست؟</h2>
              <p className="text-[#25272C] leading-[2] mb-4">
                قوز بینی برجستگی استخوانی یا غضروفی در پروفایل جانبی بینی است که در نیمرخ به‌وضوح مشخص می‌شود. این برجستگی معمولاً در ناحیه اتصال استخوان‌های بینی به غضروف‌های فوقانی شکل می‌گیرد.
              </p>

              <h2 className="text-xl font-bold text-[#25272C] mb-4 mt-6">روش جراحی</h2>
              <p className="text-[#25272C] leading-[2] mb-4">
                جراحی از طریق برش داخل بینی (روش بسته) یا روی ستون بینی (روش باز) انجام می‌شود. جراح با ابزارهای ظریف بخش اضافی را برمی‌دارد و سپس با تکنیک اوستئوتومی دیواره‌های جانبی را به هم نزدیک می‌کند.
              </p>
              {/* ACF: procedure_bullet_points (repeater) */}
              <ul className="space-y-2 mb-6">
                {["برش‌ها کاملاً داخل بینی یا در کمترین نقطه قابل‌رویت", "مدت جراحی ۱.۵ تا ۲.۵ ساعت", "بیهوشی عمومی در مرکز جراحی مجاز", "ترخیص در همان روز در اغلب موارد"].map((item, i) => (
                  <li key={i} className="flex items-center gap-2.5 text-sm text-[#25272C]">
                    <CheckCircle size={14} className="text-[#28722C] flex-shrink-0" />{item}
                  </li>
                ))}
              </ul>

              {/* Key Takeaways aside — ACF: key_takeaways_list */}
              <aside className="bg-[#E4F0E4] border border-[#28722C]/20 rounded-2xl p-5 mb-6" aria-label="نکات کلیدی">
                <div className="flex items-center gap-2 mb-3">
                  <CheckCircle size={16} className="text-[#28722C]" />
                  <h3 className="font-bold text-[#28722C]">نکات کلیدی رفع قوز</h3>
                </div>
                <ul className="space-y-2">
                  {["بهترین کاندید: بینی‌های استخوانی با قوز واضح در نیمرخ", "نتیجه نهایی: ۶–۱۲ ماه پس از جراحی", "بازگشت به کار: ۷–۱۰ روز", "ورزش سبک: پس از ۴–۶ هفته", "ندبه خارجی: ندارد یا بسیار ناچیز"].map((item, i) => (
                    <li key={i} className="flex items-start gap-2 text-sm text-[#25272C]">
                      <CheckCircle size={12} className="text-[#28722C] flex-shrink-0 mt-1" />{item}
                    </li>
                  ))}
                </ul>
              </aside>

              {/* Mid article CTA — ACF: mid_cta_content */}
              <div className="flex flex-col sm:flex-row items-center gap-4 bg-[#25272C] rounded-2xl p-5 text-white">
                <div className="flex-1">
                  <p className="font-bold text-sm mb-1">می‌خواهید بدانید رفع قوز برای شما مناسب است؟</p>
                  <p className="text-white/70 text-xs">پرونده اولیه خود را تکمیل کنید تا دکتر وضعیت بینی شما را بررسی کند.</p>
                </div>
                <a href="https://app.drbastaninejad.com/" rel="noopener"
                  className="flex-shrink-0 flex items-center gap-1.5 px-4 py-2.5 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
                  تشکیل پرونده
                </a>
              </div>
            </div>

            {/* FAQ section — ACF: faq_items */}
            <div id="faq" className="mb-8">
              <FAQSection faqs={PAGE2_FAQS} heading="سوالات متداول — رفع قوز بینی" />
            </div>

            {/* Medical disclaimer */}
            <MedicalDisclaimer />

            {/* Testimonials */}
            <div className="mt-10">
              <Testimonials items={POST1_TESTIMONIALS.slice(0, 2)} />
            </div>

            {/* Author */}
            <div className="mt-10">
              <AuthorBio author={AUTHOR} />
            </div>
          </main>

          {/* Service sidebar */}
          <aside className="space-y-5">
            {/* Booking card */}
            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-5 sticky top-20">
              <h4 className="font-bold text-[#25272C] mb-2">مشاوره رایگان</h4>
              <p className="text-xs text-[#6A7078] mb-4 leading-relaxed">برای اطلاعات بیشتر و بررسی وضعیت بینی خود، پرونده اولیه تکمیل کنید.</p>
              <a href="https://app.drbastaninejad.com/" rel="noopener"
                className="flex items-center justify-center gap-2 w-full py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
                <Phone size={14} />تشکیل پرونده اولیه
              </a>
              <a href="tel:02186087250"
                className="flex items-center justify-center gap-2 w-full py-2.5 mt-2 border border-[#DDE2DD] text-[#25272C] rounded-xl text-sm font-medium hover:border-[#28722C] hover:text-[#28722C] transition-colors">
                ۰۲۱۸۶۰۸۷۲۵۰
              </a>
            </div>

            {/* Related services — ACF: related_services (relationship field) */}
            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-5">
              <h4 className="font-bold text-[#25272C] mb-4">خدمات مرتبط</h4>
              <div className="space-y-2">
                {FOOTER_SERVICES.slice(0, 5).map(s => (
                  <a key={s.href} href={s.href}
                    className="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-[#E4F0E4] transition-colors group">
                    <span className="text-sm text-[#25272C] group-hover:text-[#28722C]">{s.label}</span>
                    <ChevronLeft size={13} className="text-[#DDE2DD] group-hover:text-[#28722C]" />
                  </a>
                ))}
              </div>
            </div>

            {/* Quick info box — ACF: service_quick_facts */}
            <div className="bg-[#E4F0E4] rounded-2xl border border-[#28722C]/20 p-5">
              <h4 className="font-bold text-[#28722C] mb-4">اطلاعات سریع</h4>
              {[
                { label: "مدت جراحی", val: "۱.۵–۲.۵ ساعت" },
                { label: "بیهوشی", val: "عمومی" },
                { label: "بستری", val: "سرپایی / ۱ شب" },
                { label: "بازگشت به کار", val: "۷–۱۰ روز" },
                { label: "نتیجه نهایی", val: "۶–۱۲ ماه" },
              ].map(({ label, val }) => (
                <div key={label} className="flex justify-between items-center py-2 border-b border-[#28722C]/10 last:border-0 text-sm">
                  <span className="text-[#6A7078]">{label}</span>
                  <span className="font-semibold text-[#25272C]">{val}</span>
                </div>
              ))}
            </div>
          </aside>
        </div>

        {/* Contact CTA */}
        <div className="mt-10">
          <ContactCTA />
        </div>
      </div>
    </>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// PAGE 3 — COMPARISON / EDUCATIONAL POST
// PHP template: single-post.php | ACF Layout: 'comparison'
// ACF fields (in addition to standard post fields):
//   • compare_option_a_title (text)
//   • compare_option_b_title (text)
//   • compare_rows (repeater: feature_label, option_a, option_b, winner)
//   • comparison_verdict (wysiwyg)
//   • interactive_chart (boolean)  — renders recharts in production
// ─────────────────────────────────────────────────────────────────────────────
function ComparisonPostPage() {
  const [openRow, setOpenRow] = useState<number | null>(null);

  const POST3_META: PostMeta = {
    title: "رینوپلاستی یا سپتوپلاستی؟ — راهنمای انتخاب بر اساس نیاز شما",
    subtitle: "تفاوت‌های کلیدی، موارد کاربرد و نحوه ترکیب دو روش توضیح داده شده‌اند",
    category: "تخصصی",
    categorySlug: "specialized",
    difficultyLevel: "تخصصی",
    readTimeMin: 10,
    viewCount: 3215,
    publishDate: "۲ مرداد ۱۴۰۳",
    lastReviewed: "مرداد ۱۴۰۴",
    author: AUTHOR,
    tags: ["رینوپلاستی", "سپتوپلاستی", "مقایسه", "انحراف بینی", "تنفس"],
    featuredImage: { url: "photo-1576091160550-2173dba999ef", alt: "مقایسه رینوپلاستی و سپتوپلاستی" },
    seoTitle: "رینوپلاستی یا سپتوپلاستی؟ مقایسه کامل | دکتر باستانی‌نژاد",
    seoDescription: "تفاوت رینوپلاستی و سپتوپلاستی، موارد مناسب هر کدام و امکان ترکیب هم‌زمان — راهنمای تخصصی دکتر شاهین باستانی‌نژاد.",
    canonicalUrl: "https://drbastaninejad.com/blog/rhinoplasty-vs-septoplasty",
  };

  return (
    <>
      <Breadcrumb items={[
        { label: "خانه", href: "/" },
        { label: "وبلاگ", href: "/blog" },
        { label: "تخصصی", href: "/category/specialized" },
        { label: "رینوپلاستی یا سپتوپلاستی؟" },
      ]} />

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-8">
        <div className="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-8">
          <main id="main-content">
            <article>

              {/* Post header */}
              <header className="mb-8">
                <div className="flex flex-wrap gap-2 mb-4">
                  <Badge variant="muted"><Tag size={10} />{POST3_META.category}</Badge>
                  <DifficultyBadge level={POST3_META.difficultyLevel} />
                  <Badge variant="info"><Shield size={10} />بازبینی پزشکی</Badge>
                </div>
                <h1 className="text-2xl sm:text-3xl font-bold text-[#25272C] leading-snug mb-3">
                  {POST3_META.title}
                </h1>
                <p className="text-lg text-[#6A7078] leading-relaxed mb-5">{POST3_META.subtitle}</p>
                <div className="flex flex-wrap gap-4 text-sm text-[#6A7078] pb-5 border-b border-[#DDE2DD]">
                  <div className="flex items-center gap-2">
                    <div className="w-7 h-7 rounded-full bg-[#28722C] flex items-center justify-center text-white text-xs font-bold">{AUTHOR.avatarInitials}</div>
                    <span className="font-semibold text-[#25272C]">{AUTHOR.name}</span>
                  </div>
                  <div className="flex items-center gap-1"><Calendar size={12} />{POST3_META.publishDate}</div>
                  <div className="flex items-center gap-1"><Clock size={12} />{POST3_META.readTimeMin} دقیقه</div>
                  <div className="flex items-center gap-1 text-[#28722C] font-medium mr-auto">
                    <CheckCircle size={12} />بازبینی: {POST3_META.lastReviewed}
                  </div>
                </div>
              </header>

              {/* Featured image */}
              <div className="relative rounded-2xl overflow-hidden mb-8 bg-[#DDE2DD]">
                <img
                  src={`https://images.unsplash.com/${POST3_META.featuredImage.url}?w=860&h=400&fit=crop&auto=format`}
                  alt={POST3_META.featuredImage.alt}
                  className="w-full h-56 sm:h-72 object-cover"
                  loading="eager"
                />
              </div>

              {/* Quick answer box — ACF: quick_answer (speakable schema candidate) */}
              <div className="bg-gradient-to-br from-[#28722C] to-[#246b28] text-white rounded-2xl p-6 mb-8">
                <div className="flex items-center gap-2 mb-3">
                  <Zap size={18} className="text-white" />
                  <h2 className="text-lg font-bold">پاسخ سریع</h2>
                </div>
                <p className="text-white/90 text-sm leading-[2]">
                  <strong>رینوپلاستی</strong> برای اصلاح ظاهر بینی مناسب است. <strong>سپتوپلاستی</strong> برای بهبود تنفس و اصلاح انحراف تیغه میانی انجام می‌شود.
                  اگر هر دو مشکل دارید، <strong>سپتورینوپلاستی</strong> هر دو را همزمان اصلاح می‌کند.
                </p>
              </div>

              {/* Two-column intro cards — ACF: compare_option_a/b */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-10">
                {[
                  { title: "رینوپلاستی", sub: "Rhinoplasty", goal: "اصلاح ظاهری", color: "border-[#28722C] bg-[#E4F0E4]", iconColor: "text-[#28722C]", desc: "هدف اصلی بهبود فرم، اندازه، تناسب یا نیمرخ بینی است. بر اساس درخواست بیمار و تشخیص جراح انجام می‌شود.", Icon: Star },
                  { title: "سپتوپلاستی", sub: "Septoplasty", goal: "بهبود تنفسی", color: "border-blue-400 bg-blue-50", iconColor: "text-blue-600", desc: "هدف اصلی اصلاح انحراف تیغه میانی بینی برای بهبود جریان هوا و تنفس است. جنبه درمانی دارد.", Icon: Zap },
                ].map(c => (
                  <div key={c.title} className={`rounded-2xl border-2 p-5 ${c.color}`}>
                    <div className="flex items-center gap-3 mb-3">
                      <div className="w-10 h-10 rounded-xl bg-white flex items-center justify-center">
                        <c.Icon size={20} className={c.iconColor} />
                      </div>
                      <div>
                        {/* ACF: compare_option_a_title */}
                        <p className="font-bold text-[#25272C]">{c.title}</p>
                        <p className="text-xs text-[#6A7078]">{c.sub} · {c.goal}</p>
                      </div>
                    </div>
                    {/* ACF: compare_option_a_description */}
                    <p className="text-sm text-[#25272C] leading-relaxed">{c.desc}</p>
                  </div>
                ))}
              </div>

              {/* Comparison table — ACF repeater: compare_rows */}
              <section className="mb-10">
                <h2 className="text-xl font-bold text-[#25272C] mb-4">مقایسه جامع</h2>
                <div className="bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden">
                  {/* Table header */}
                  <div className="grid grid-cols-[1fr_1fr_1fr] bg-[#25272C] text-white text-sm font-bold">
                    <div className="py-3 px-4">ویژگی</div>
                    <div className="py-3 px-4 text-center border-x border-white/10">رینوپلاستی</div>
                    <div className="py-3 px-4 text-center">سپتوپلاستی</div>
                  </div>
                  {/* PHP: foreach (get_field('compare_rows') as $row) */}
                  {POST3_COMPARE.map((row, i) => (
                    <div key={i} className={`grid grid-cols-[1fr_1fr_1fr] border-t border-[#DDE2DD] ${i % 2 === 0 ? "bg-white" : "bg-[#F7F8F6]"}`}>
                      <div className="py-3 px-4 text-sm font-semibold text-[#25272C]">{row.feature}</div>
                      {/* ACF: option_a_value, option_b_value, winner */}
                      <div className={`py-3 px-4 text-sm text-center border-x border-[#DDE2DD] ${row.winner === "a" || row.winner === "both" ? "text-[#28722C] font-semibold" : "text-[#6A7078]"}`}>
                        {(row.winner === "a" || row.winner === "both") && <CheckCircle size={12} className="inline ml-1 text-[#28722C]" />}
                        {row.optionA}
                      </div>
                      <div className={`py-3 px-4 text-sm text-center ${row.winner === "b" || row.winner === "both" ? "text-[#28722C] font-semibold" : "text-[#6A7078]"}`}>
                        {(row.winner === "b" || row.winner === "both") && <CheckCircle size={12} className="inline ml-1 text-[#28722C]" />}
                        {row.optionB}
                      </div>
                    </div>
                  ))}
                </div>
              </section>

              {/* Combo option — ACF: show_combo_section (boolean) */}
              <section className="mb-10">
                <h2 className="text-xl font-bold text-[#25272C] mb-4">ترکیب دو روش: سپتورینوپلاستی</h2>
                <div className="bg-white rounded-2xl border-2 border-[#28722C]/20 p-6">
                  <div className="flex items-start gap-4">
                    <div className="w-12 h-12 rounded-2xl bg-[#E4F0E4] flex items-center justify-center flex-shrink-0">
                      <Users size={22} className="text-[#28722C]" />
                    </div>
                    <div>
                      <h3 className="font-bold text-[#25272C] mb-2">سپتورینوپلاستی — بهترین هر دو دنیا</h3>
                      {/* ACF: combo_description */}
                      <p className="text-sm text-[#6A7078] leading-[2] mb-4">
                        اگر هم مشکل تنفسی دارید و هم از ظاهر بینی ناراضی هستید، جراحی ترکیبی بهترین گزینه است.
                        با یک بیهوشی و یک دوره ریکاوری، هر دو مشکل اصلاح می‌شود.
                      </p>
                      <div className="grid grid-cols-3 gap-3">
                        {[{ n: "۱", l: "بیهوشی" }, { n: "۱", l: "دوره ریکاوری" }, { n: "۲", l: "مشکل حل‌شده" }].map(s => (
                          <div key={s.l} className="text-center bg-[#E4F0E4] rounded-xl py-3">
                            <p className="text-xl font-bold text-[#28722C]">{s.n}</p>
                            <p className="text-xs text-[#6A7078]">{s.l}</p>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              {/* Decision guide — ACF: decision_guide_items (repeater) */}
              <section className="mb-10">
                <h2 className="text-xl font-bold text-[#25272C] mb-4">کدام روش برای شما مناسب است؟</h2>
                <div className="space-y-3">
                  {[
                    { q: "مشکل اصلی من ظاهری است", answer: "رینوپلاستی", color: "border-[#28722C] bg-[#E4F0E4] text-[#28722C]" },
                    { q: "مشکل اصلی من تنفسی / انحراف تیغه است", answer: "سپتوپلاستی", color: "border-blue-400 bg-blue-50 text-blue-700" },
                    { q: "هر دو مشکل را دارم", answer: "سپتورینوپلاستی", color: "border-amber-400 bg-amber-50 text-amber-700" },
                    { q: "قبلاً عمل کرده‌ام و از نتیجه راضی نیستم", answer: "رینوپلاستی ترمیمی", color: "border-purple-400 bg-purple-50 text-purple-700" },
                  ].map((item, i) => (
                    <div key={i} className={`flex items-center justify-between p-4 rounded-xl border-2 ${item.color}`}>
                      <span className="text-sm text-[#25272C] font-medium">{item.q}</span>
                      <span className={`text-xs font-bold px-3 py-1 rounded-full bg-white border ${item.color.split(" ")[0]}`}>{item.answer} ←</span>
                    </div>
                  ))}
                </div>
              </section>

              {/* FAQ */}
              <FAQSection faqs={POST3_FAQS} heading="سوالات متداول" />

              <div className="mt-8">
                <MedicalDisclaimer />
              </div>

              {/* Verdict / Conclusion — ACF: conclusion_text */}
              <div className="mt-8 bg-[#25272C] text-white rounded-2xl p-6">
                <h2 className="text-lg font-bold mb-3">جمع‌بندی متخصص</h2>
                <p className="text-white/80 text-sm leading-[2]">
                  انتخاب بین رینوپلاستی، سپتوپلاستی یا ترکیب آن‌ها کاملاً به وضعیت آناتومیک، هدف بیمار و ارزیابی جراح بستگی دارد.
                  هیچ پاسخ یک‌اندازه‌ای وجود ندارد — مشاوره حضوری با جراح متخصص تنها راه تصمیم‌گیری صحیح است.
                </p>
                <a href="https://app.drbastaninejad.com/" rel="noopener"
                  className="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
                  <Phone size={13} />تشکیل پرونده رایگان
                </a>
              </div>

              <div className="mt-10">
                <Testimonials items={POST1_TESTIMONIALS} />
              </div>
              <div className="mt-10">
                <AuthorBio author={AUTHOR} />
              </div>
            </article>

            <div className="mt-10">
              <RelatedPosts posts={POST1_RELATED} />
            </div>
            <div className="mt-10">
              <ContactCTA />
            </div>
          </main>

          {/* Sidebar */}
          <div className="hidden xl:block">
            <ArticleSidebar
              meta={POST3_META}
              tocItems={["پاسخ سریع", "مقایسه جامع", "سپتورینوپلاستی", "کدام برای شما؟", "سوالات متداول", "جمع‌بندی"]}
            />
          </div>
        </div>
      </div>
    </>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// ROOT APP — Demo page switcher (remove in production, use WP routing)
// In production: WordPress handles routing via template hierarchy
// ─────────────────────────────────────────────────────────────────────────────
type PageType = "blog-post" | "service-page" | "comparison-post";

export default function App() {
  const [activePage, setActivePage] = useState<PageType>("blog-post");

  const pages: { id: PageType; label: string; desc: string }[] = [
    { id: "blog-post", label: "پست مقاله", desc: "single-post.php · راهنمای کامل رینوپلاستی" },
    { id: "service-page", label: "صفحه خدمت", desc: "single-service.php · رفع قوز بینی" },
    { id: "comparison-post", label: "پست مقایسه‌ای", desc: "single-post.php (layout=compare) · رینوپلاستی vs سپتوپلاستی" },
  ];

  return (
    <div dir="rtl" lang="fa-IR" className="min-h-screen bg-[#F7F8F6] text-[#25272C]"
      style={{ fontFamily: "'Vazirmatn', 'Irancell', Tahoma, Arial, system-ui, sans-serif" }}>

      {/* ── DEMO SWITCHER — remove this entire block in production ─────── */}
      {/* In WordPress: template selection is automatic via template hierarchy */}
      <div className="bg-[#25272C] border-b border-white/10 px-4 py-2.5">
        <div className="max-w-[1200px] mx-auto flex flex-col sm:flex-row items-start sm:items-center gap-3">
          <span className="text-xs font-bold text-white/50 whitespace-nowrap">نوع صفحه:</span>
          <div className="flex flex-wrap gap-2">
            {pages.map(p => (
              <button key={p.id} onClick={() => setActivePage(p.id)}
                className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border ${
                  activePage === p.id
                    ? "bg-[#28722C] text-white border-[#28722C]"
                    : "bg-white/10 text-white/70 border-white/20 hover:bg-white/20"
                }`}>
                {p.label}
                <span className="hidden sm:inline text-white/40 font-normal mr-1">— {p.desc}</span>
              </button>
            ))}
          </div>
        </div>
      </div>
      {/* ── END DEMO SWITCHER ─────────────────────────────────────────── */}

      {/* Shared nav */}
      <SiteNav activePage={activePage} />

      {/* Page content — WP: determined by template hierarchy */}
      {activePage === "blog-post" && <BlogPostPage />}
      {activePage === "service-page" && <ServiceDetailPage />}
      {activePage === "comparison-post" && <ComparisonPostPage />}

      {/* Shared footer */}
      <SiteFooter />

      {/* Chaty floating contact — always present */}
      <ChatyWidget />

      {/* Scroll to top */}
      <ScrollToTop />
    </div>
  );
}

function ScrollToTop() {
  const [show, setShow] = useState(false);
  useEffect(() => {
    const h = () => setShow(window.scrollY > 400);
    window.addEventListener("scroll", h);
    return () => window.removeEventListener("scroll", h);
  }, []);
  if (!show) return null;
  return (
    <button
      onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
      className="fixed bottom-24 right-4 sm:bottom-6 sm:right-6 z-40 w-10 h-10 bg-white border border-[#DDE2DD] rounded-xl shadow-md flex items-center justify-center hover:bg-[#E4F0E4] hover:border-[#28722C] transition-all"
      aria-label="بازگشت به بالا"
    >
      <ChevronUp size={16} className="text-[#28722C]" />
    </button>
  );
}
