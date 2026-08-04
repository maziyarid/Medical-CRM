/**
 * ============================================================
 *  SITE-WIDE CONSTANTS & SHARED DATA
 *  drbastaninejad.com — WordPress Theme (React Design Reference)
 *
 *  WP NOTE: In production these constants come from:
 *    • ACF Options Page (doctor info, socials, phones)
 *    • wp_nav_menu() for navigation items
 *    • WP post/taxonomy queries for services/blog data
 * ============================================================
 */

import {
  Scissors, Heart, Eye, Shield, CheckCircle, Star,
  TrendingUp, Stethoscope, BookOpen, Award, Users,
  FileText, Layers, Zap, Search,
} from "lucide-react";

/* ── Clinic / Doctor identity — ACF Options Page ── */
export const DOCTOR_NAME     = "دکتر شاهین باستانی‌نژاد";
export const BOOKING_URL     = "https://app.drbastaninejad.com/";
export const PHONES          = ["۰۲۱–۸۶۰۸۷۲۵۰", "۰۲۱–۸۸۲۰۵۶۰۶", "۰۹۹۱–۲۴۹۶۶۵۹"];
export const INSTAGRAM       = "https://www.instagram.com/dr.shahin.bastaninejad/";
export const YOUTUBE         = "https://www.youtube.com/@Drshahinbastaninejad";
export const TELEGRAM        = "https://t.me/dr_bastaninejad";
export const APARAT          = "https://www.aparat.com/Drshahinbastaninejad";
export const WHATSAPP        = "https://wa.me/989124966590";
export const ADDRESS         = "تهران، خیابان نلسون ماندلا، نرسیده به چهارراه جهان کودک، خیابان صانعی، ساختمان نور، پلاک ۱، واحد ۶";
export const ADDRESS_SHORT   = "تهران، خ. نلسون ماندلا، خ. صانعی، ساختمان نور، پلاک ۱ واحد ۶";
export const CLINIC_HOURS    = "شنبه و سه‌شنبه · ۱۵:۰۰ – ۱۹:۰۰";
export const ENAMAD_URL      = "https://trustseal.enamad.ir/?id=533172&Code=N0dO6V3ic509Xoj2zIAFDG27eDa4Inlv";
export const IRANENT_URL     = "https://iranent.com/doctors/925/profile/";

/* ── Stats — ACF Options Page ── */
export const STATS = [
  { val: "+۱۸", label: "سال تجربه",    sub: "جراحی تخصصی بینی" },
  { val: "+۲۰۰۰", label: "عمل موفق",  sub: "در بیماران مختلف" },
  { val: "+۱۷۸", label: "نمونه گالری", sub: "قبل و بعد از جراحی" },
  { val: "۹۸٪", label: "رضایت بیمار", sub: "بر اساس نظرسنجی" },
];

/* ── Trust Badges — ACF Options Page ── */
export const TRUST_BADGES = [
  { icon: Award,    title: "دانشیار دانشگاه علوم پزشکی تهران", subtitle: "عضو هیئت علمی بیمارستان امیراعلم" },
  { icon: Star,     title: "رتبه ۴ بورد تخصصی کشوری",          subtitle: "گوش، حلق و بینی — جراحی سر و گردن" },
  { icon: Users,    title: "دبیر کمیته علمی رینولوژی",          subtitle: "انجمن جراحی پلاستیک بینی ایران" },
  { icon: BookOpen, title: "مجری ۱۰+ دوره سمینار جراحی",       subtitle: "رینوپلاستی · آندوسکوپی سینوس" },
  { icon: Shield,   title: "مجوز رسمی وزارت بهداشت",           subtitle: "پروانه فعالیت از وزارت بهداشت ایران" },
];

/* ── Cosmetic Services — WP CPT: service ── */
export const COSMETIC_SERVICES = [
  { id: 1, slug: "rhinoplasty-primary",  title: "جراحی بینی اولیه",   subtitle: "راینوپلاستی اولیه",         icon: Scissors,    desc: "جراحی بینی اولیه یا راینوپلاستی، هنر تراشیدن بینی متناسب با چهره است. دکتر باستانی‌نژاد با بهره‌گیری از تکنیک‌های روز دنیا، نتیجه‌ای طبیعی و هماهنگ با سیمای شما ایجاد می‌کند." },
  { id: 2, slug: "rhinoplasty-revision", title: "جراحی بینی ترمیمی",  subtitle: "رینوپلاستی ثانویه",         icon: Heart,       desc: "جراحی ترمیمی برای اصلاح نتایج ناخواسته جراحی‌های قبلی طراحی شده است. این عمل به دقت و تجربه بسیار بالایی نیاز دارد که دکتر باستانی‌نژاد با سال‌ها سابقه در این حوزه آماده انجام آن است." },
  { id: 3, slug: "rhinoplasty-fleshy",   title: "جراحی بینی گوشتی",   subtitle: "بینی پهن و گوشتی",          icon: Eye,         desc: "بینی‌های گوشتی به دلیل ضخامت پوست و بافت نرم، جراحی متفاوتی نیاز دارند. دکتر باستانی‌نژاد با روش‌های تخصصی، ظرافت ایده‌آل را برای این نوع بینی ایجاد می‌کند." },
  { id: 4, slug: "rhinoplasty-bony",     title: "جراحی بینی استخوانی", subtitle: "بینی سخت و استخوانی",      icon: Shield,      desc: "بینی‌های استخوانی ساختار محکم‌تری دارند و تکنیک‌های خاصی برای ایجاد فرم دلخواه نیاز دارند. نتیجه نهایی ظاهری طبیعی و متناسب با ساختار چهره خواهد بود." },
  { id: 5, slug: "rhinoplasty-natural",  title: "بینی طبیعی",          subtitle: "نتیجه کاملاً طبیعی",       icon: CheckCircle, desc: "سبک جراحی بینی طبیعی، تغییراتی ظریف ایجاد می‌کند که حس می‌شود شما همیشه این بینی را داشته‌اید. هماهنگی کامل با چهره، اولویت اصلی این رویکرد است." },
  { id: 6, slug: "rhinoplasty-fantasy",  title: "بینی فانتزی",          subtitle: "سبک متفاوت و برجسته",      icon: Star,        desc: "برای افرادی که به دنبال ظاهری خاص‌تر هستند، سبک فانتزی با پروفایل برجسته‌تر و نوک سربالا انتخاب می‌شود. این تغییرات در محدوده طبیعی چهره طراحی می‌شوند." },
  { id: 7, slug: "hump-removal",         title: "رفع قوز بینی",         subtitle: "اصلاح برآمدگی پشت بینی",  icon: TrendingUp,  desc: "قوز بینی یکی از شایع‌ترین دلایل مراجعه به جراح است. دکتر باستانی‌نژاد با تکنیک‌های دقیق، قوز را حذف و پروفایل صاف و زیبایی ایجاد می‌کند." },
];

/* ── Functional Services — WP CPT: service ── */
export const FUNCTIONAL_SERVICES = [
  { id: 8, slug: "septoplasty",     title: "سپتوپلاستی",             subtitle: "اصلاح انحراف تیغه بینی",    icon: Stethoscope, desc: "انحراف تیغه بینی (سپتوم) می‌تواند باعث مشکلات تنفسی جدی شود. سپتوپلاستی این انحراف را اصلاح کرده و تنفس را به‌طور قابل توجهی بهبود می‌بخشد." },
  { id: 9, slug: "turbinoplasty",   title: "توربینوپلاستی",           subtitle: "کاهش حجم شاخک‌های بینی",   icon: Heart,       desc: "بزرگ شدن شاخک‌های بینی (توربینیت‌ها) یکی از علل شایع گرفتگی مزمن بینی است. توربینوپلاستی این بافت‌ها را کوچک کرده و جریان هوا را بهینه می‌کند." },
  { id: 10, slug: "sinus-endoscopy", title: "آندوسکوپی سینوس (FESS)", subtitle: "درمان سینوزیت مزمن",       icon: Shield,      desc: "جراحی آندوسکوپیک سینوس (FESS) برای درمان سینوزیت مزمن، پولیپ‌های بینی و دیگر بیماری‌های سینوس انجام می‌شود. این روش با حداقل تهاجم، نتایج ماندگار ایجاد می‌کند." },
];

/* ── Nav mega-menu items — WP: wp_nav_menu() ── */
export const NAV_MEGA_ITEMS = [
  {
    group: "بر اساس بافت",
    items: [
      { label: "جراحی بینی اولیه", sub: "راینوپلاستی برای اولین بار",     href: "/services/rhinoplasty-primary",  icon: FileText },
      { label: "بینی استخوانی",     sub: "اصلاح قوز و ساختار سخت",         href: "/services/rhinoplasty-bony",     icon: Layers },
      { label: "بینی گوشتی",        sub: "پوست ضخیم — چالش‌برانگیزترین", href: "/services/rhinoplasty-fleshy",   icon: Heart },
    ],
  },
  {
    group: "بر اساس سبک",
    items: [
      { label: "بینی طبیعی",    sub: "نتیجه هماهنگ با چهره",         href: "/services/rhinoplasty-natural",  icon: CheckCircle },
      { label: "بینی فانتزی",   sub: "طرح‌های خاص شخصی‌سازی‌شده",  href: "/services/rhinoplasty-fantasy",  icon: Star },
      { label: "جراحی ترمیمی", sub: "اصلاح نتایج عمل قبلی",         href: "/services/rhinoplasty-revision", icon: TrendingUp },
    ],
  },
  {
    group: "درمانی",
    items: [
      { label: "انحراف بینی (سپتوپلاستی)", sub: "بهبود تنفس و اصلاح انحراف",    href: "/services/septoplasty",      icon: Zap },
      { label: "شاخک‌های بینی",            sub: "توربینوپلاستی برای تنفس بهتر", href: "/services/turbinoplasty",    icon: TrendingUp },
      { label: "آندوسکوپی سینوس",          sub: "درمان سینوزیت مزمن",           href: "/services/sinus-endoscopy",  icon: Search },
    ],
  },
];

/* ── Footer quick links — WP: wp_nav_menu('footer-quick') ── */
export const FOOTER_QUICK = [
  { label: "خانه",         href: "/" },
  { label: "درباره دکتر", href: "/about" },
  { label: "همه خدمات",   href: "/services" },
  { label: "نمونه کارها", href: "/gallery" },
  { label: "مقالات",      href: "/blog" },
  { label: "سوالات متداول", href: "/faq" },
  { label: "تماس و آدرس", href: "/contact" },
  { label: "نوبت‌گیری",  href: "/booking" },
];

/* ── Footer service links — WP: wp_nav_menu('footer-services') ── */
export const FOOTER_SERVICES = [
  { label: "جراحی بینی اولیه", href: "/services/rhinoplasty-primary" },
  { label: "جراحی ترمیمی",     href: "/services/rhinoplasty-revision" },
  { label: "بینی گوشتی",       href: "/services/rhinoplasty-fleshy" },
  { label: "بینی استخوانی",    href: "/services/rhinoplasty-bony" },
  { label: "بینی طبیعی",       href: "/services/rhinoplasty-natural" },
  { label: "بینی فانتزی",      href: "/services/rhinoplasty-fantasy" },
  { label: "رفع قوز بینی",     href: "/services/hump-removal" },
  { label: "انحراف بینی",      href: "/services/septoplasty" },
];

/* ── Timeline — ACF: cv_timeline (repeater) ── */
export const TIMELINE = [
  { year: "۱۳۷۵–۱۳۸۲", title: "دکترای پزشکی عمومی",    org: "دانشگاه علوم پزشکی اصفهان",                          icon: BookOpen },
  { year: "۱۳۸۴–۱۳۸۸", title: "تخصص گوش، حلق و بینی", org: "دانشگاه علوم پزشکی تهران — رتبه ۴ بورد کشوری",     icon: Award },
  { year: "۱۳۸۸–۱۳۸۹", title: "فلوشیپ رینوپلاستی",     org: "بیمارستان امیراعلم تهران",                           icon: Star },
  { year: "از ۱۳۸۸",   title: "عضو هیئت علمی",          org: "دانشگاه علوم پزشکی تهران",                          icon: Users },
  { year: "از ۱۳۹۶",   title: "معاون آموزشی",            org: "بیمارستان امیراعلم تهران",                          icon: Shield },
  { year: "اکنون",     title: "دبیر کمیته علمی رینولوژی",org: "انجمن جراحی پلاستیک بینی ایران",                   icon: CheckCircle },
];

/* ── Certificates — ACF repeater: certificates ── */
export const CERTIFICATES = [
  "هفتمین کنگره بین‌المللی رینوپلاستی و جراحی پلاستیک صورت",
  "دوره مستر دیسکشن رینولوژی و جراحی پلاستیک بازسازی",
  "هشتمین کنگره بین‌المللی انجمن رینولوژی ایران",
  "سومین دوره بین‌المللی رینولوژی شیراز (SRIC)",
  "گواهی بازسازی دریچه بینی — دانشگاه علوم پزشکی تهران",
  "گواهی‌نامه بین‌المللی — جمهوری تاجیکستان",
  "چهارمین کنفرانس بین‌المللی گوش، حلق و بینی کردستان عراق",
  "پنجمین کنفرانس و نمایشگاه بین‌المللی — کردستان عراق",
  "مجوز مراکز درمانی و زیبایی از وزارت بهداشت",
];

/* ── Process Steps — ACF repeater: process_steps ── */
export const PROCESS_STEPS = [
  { num: "۰۱", title: "مشاوره تخصصی",   desc: "در جلسه مشاوره، وضعیت بینی شما بررسی می‌شود، اهداف و انتظاراتتان شنیده می‌شود و بهترین روش جراحی برای شما تعیین می‌گردد." },
  { num: "۰۲", title: "آمادگی قبل از عمل", desc: "آزمایش‌ها و معاینات لازم انجام می‌شود. دستورالعمل‌های قبل از عمل به شما داده می‌شود تا آمادگی کامل برای جراحی داشته باشید." },
  { num: "۰۳", title: "روز جراحی",       desc: "عمل جراحی در محیطی کاملاً ایمن و استاندارد توسط دکتر باستانی‌نژاد و تیم متخصص ایشان انجام می‌شود." },
  { num: "۰۴", title: "دوره بهبودی",     desc: "پس از جراحی، تیم پزشکی ما در تمام مراحل بهبودی همراه شما است. ویزیت‌های پیگیری منظم برای اطمینان از نتیجه مطلوب برنامه‌ریزی می‌شود." },
];

/* ── Homepage FAQs — ACF repeater on front page ── */
export const HOME_FAQS = [
  { cat: "کلیات جراحی",    q: "جراحی بینی چیست و چه هدفی دارد؟",                    a: "جراحی بینی (رینوپلاستی) عملی جراحی است که برای اصلاح شکل، اندازه یا عملکرد بینی انجام می‌شود. هدف اصلی این جراحی دستیابی به تناسب بهتر بین بینی و سایر اجزای چهره، با حفظ و بهبود عملکرد تنفسی است." },
  { cat: "کاندیداتوری",   q: "چه کسانی می‌توانند جراحی بینی انجام دهند؟",            a: "افراد بالای ۱۸ سال که رشد غضروف و استخوان آن‌ها کامل شده و دارای سلامت جسمی و روانی کافی هستند. همچنین انتظارات واقع‌بینانه از جراحی داشته باشند." },
  { cat: "کاندیداتوری",   q: "چه کسانی نمی‌توانند جراحی بینی انجام دهند؟",          a: "افراد زیر ۱۸ سال، کسانی که بیماری‌های زمینه‌ای کنترل‌نشده دارند، خانم‌های باردار یا شیرده، و افرادی که دیسمورفی بدنی دارند از کاندیداهای مناسب نیستند." },
  { cat: "انواع بینی",    q: "تفاوت جراحی بینی استخوانی و گوشتی چیست؟",              a: "بینی‌های استخوانی پوست نازک‌تری دارند و اسکلت استخوانی-غضروفی آن‌ها برجسته‌تر است. در مقابل، بینی‌های گوشتی پوست ضخیم‌تری دارند. هر نوع به تکنیک‌های متفاوت جراحی نیاز دارد." },
  { cat: "دوره بهبودی",   q: "دوره بهبودی پس از جراحی بینی چقدر طول می‌کشد؟",       a: "آتل بینی معمولاً ۷ تا ۱۰ روز پس از عمل برداشته می‌شود. کبودی و تورم در اکثر بیماران ظرف ۲ تا ۳ هفته تا حد زیادی کاهش می‌یابد. بازگشت به فعالیت‌های سبک معمولاً ۱ تا ۲ هفته پس از عمل ممکن است." },
  { cat: "دوره بهبودی",   q: "چه مدت بعد از جراحی می‌توانم به سر کار برگردم؟",       a: "بستگی به نوع کار دارد. برای کارهای اداری و نشسته معمولاً ۱۰–۱۴ روز کافی است. برای کارهای سنگین و فعالیت بدنی شدید، حداقل ۴–۶ هفته باید صبر کرد." },
  { cat: "هزینه",          q: "هزینه جراحی بینی چقدر است؟",                           a: "هزینه جراحی بینی به عوامل متعددی مثل پیچیدگی بینی، تکنیک جراحی، نوع بیهوشی و امکانات بیمارستان بستگی دارد. برای دریافت تخمین دقیق هزینه، پس از مشاوره اطلاع‌رسانی خواهد شد." },
  { cat: "قبل از عمل",    q: "چه آزمایشاتی قبل از جراحی بینی لازم است؟",             a: "آزمایش خون کامل، تست انعقاد خون، الکتروکاردیوگرام (ECG)، عکس رادیولوژی قفسه سینه و در صورت نیاز آزمایش‌های تخصصی‌تر. پزشک متخصص بیهوشی نیز ویزیت قبل از عمل را انجام خواهد داد." },
  { cat: "بعد از عمل",    q: "چه مراقبت‌هایی بعد از جراحی باید داشته باشم؟",         a: "استراحت کافی و بالا نگه داشتن سر، پرهیز از فعالیت‌های سنگین، عدم استفاده از عینک برای حدود ۶ هفته، محافظت از بینی در برابر ضربه، پرهیز از آفتاب مستقیم، و مصرف داروهای تجویزی طبق دستور." },
  { cat: "جراحی ترمیمی", q: "چرا باید ۱۲ ماه پس از جراحی اول برای ترمیمی صبر کرد؟", a: "بافت بینی نیاز به زمان دارد تا کاملاً بهبود یابد و تورم‌های عمقی فروکش کنند. قضاوت زودهنگام قبل از بهبود کامل ممکن است منجر به جراحی ترمیمی غیرضروری شود. ۱۲ ماه، حداقل زمان لازم برای ارزیابی دقیق نتیجه نهایی است." },
];

/* ── Blog articles — WP: WP_Query post_type='post' ── */
export const BLOG_IMGS = [
  "/images/Blog/Blog-101.webp",
  "/images/Blog/Blog-102.webp",
  "/images/Blog/Blog-103.webp",
  "/images/Blog/Blog-104.webp",
  "/images/Blog/Blog-105.webp",
  "/images/Blog/Blog-106.webp",
];

export const BLOG_POSTS = [
  { id: 1,  slug: "rhinoplasty",             cat: "جراحی بینی",    title: "جراحی بینی چیست و چه کسانی کاندید هستند؟",          excerpt: "راینوپلاستی یا جراحی بینی یکی از رایج‌ترین اقدامات زیبایی در ایران است. در این مطلب، کاندیداهای مناسب و ملاحظات مهم را بررسی می‌کنیم.",  img: BLOG_IMGS[0], date: "۱۴۰۳/۰۸/۱۵", featured: true },
  { id: 2,  slug: "rhinoplasty-revision",    cat: "جراحی بینی",    title: "جراحی بینی ترمیمی چیست؟",                           excerpt: "جراحی ترمیمی بینی یکی از چالش‌برانگیزترین اقدامات جراحی پلاستیک است که به تخصص و تجربه ویژه‌ای نیاز دارد.",                            img: BLOG_IMGS[1], date: "۱۴۰۳/۰۷/۲۰" },
  { id: 3,  slug: "pre-op-steps",            cat: "راهنمای بیمار", title: "اقدامات ضروری قبل از جراحی بینی",                   excerpt: "آمادگی صحیح قبل از جراحی بینی نقش مهمی در موفقیت عمل و سرعت بهبودی شما دارد. این موارد را جدی بگیرید.",                               img: BLOG_IMGS[2], date: "۱۴۰۳/۰۷/۰۵" },
  { id: 4,  slug: "post-op-care",            cat: "مراقبت‌ها",     title: "مراقبت‌های بعد از جراحی بینی",                      excerpt: "دوره بهبودی پس از جراحی بینی نیازمند رعایت دقیق نکاتی است که بر کیفیت نتیجه نهایی تأثیر مستقیم دارند.",                              img: BLOG_IMGS[3], date: "۱۴۰۳/۰۶/۱۸" },
  { id: 5,  slug: "rhinoplasty-fleshy",      cat: "جراحی بینی",    title: "جراحی بینی گوشتی چیست؟",                            excerpt: "بینی‌های گوشتی ویژگی‌های خاصی دارند که تکنیک‌های متفاوتی در جراحی آن‌ها به کار می‌رود.",                                            img: BLOG_IMGS[4], date: "۱۴۰۳/۰۶/۰۱" },
  { id: 6,  slug: "choose-surgeon",          cat: "راهنمای بیمار", title: "۱۰ نکته مهم در انتخاب جراح بینی",                   excerpt: "انتخاب جراح مناسب، مهم‌ترین گام در مسیر جراحی بینی موفق است. این معیارها را در انتخاب خود در نظر بگیرید.",                              img: BLOG_IMGS[5], date: "۱۴۰۳/۰۵/۱۲" },
  { id: 7,  slug: "revision-rhinoplasty",    cat: "جراحی بینی",    title: "رینوپلاستی ترمیمی: راهنمای جامع",                   excerpt: "همه چیز درباره جراحی ترمیمی بینی، دلایل نیاز به آن و انتظارات واقع‌بینانه.",                                                          img: BLOG_IMGS[0], date: "۱۴۰۳/۰۵/۰۱" },
  { id: 8,  slug: "rhinoplasty-complications", cat: "آموزشی",      title: "عوارض احتمالی جراحی بینی و پیشگیری از آن‌ها",        excerpt: "آشنایی با عوارض احتمالی جراحی بینی و راه‌های پیشگیری — اطلاعاتی که هر کاندید باید بداند.",                                             img: BLOG_IMGS[1], date: "۱۴۰۳/۰۴/۲۰" },
  { id: 9,  slug: "male-rhinoplasty",        cat: "جراحی بینی",    title: "جراحی بینی در مردان — تفاوت‌ها و نکات",             excerpt: "جراحی بینی در مردان اصول و ملاحظات خاص خود را دارد. در این مقاله تفاوت‌های کلیدی را بررسی می‌کنیم.",                                   img: BLOG_IMGS[2], date: "۱۴۰۳/۰۴/۰۵" },
  { id: 10, slug: "nutrition-rhinoplasty",   cat: "مراقبت‌ها",     title: "تغذیه مناسب قبل و بعد از جراحی بینی",               excerpt: "تغذیه صحیح تأثیر مستقیمی بر سرعت بهبودی و نتیجه نهایی جراحی بینی دارد.",                                                            img: BLOG_IMGS[3], date: "۱۴۰۳/۰۳/۱۸" },
  { id: 11, slug: "atl-removal",             cat: "آموزشی",        title: "آتل بینی: همه چیز درباره مراقبت و برداشتن",          excerpt: "آتل بینی بخش مهمی از دوره بهبودی است. در این مطلب نحوه مراقبت و زمان برداشتن آن را توضیح می‌دهیم.",                                     img: BLOG_IMGS[4], date: "۱۴۰۳/۰۳/۰۱" },
];

/* ── Gallery items — WP: ACF gallery field or CPT: case-study ── */
// Pairs from the 178 real before/after images — every 14th image is a new patient pair
export const GALLERY_ITEMS = Array.from({ length: 12 }, (_, i) => ({
  id: i + 1,
  before: `/images/Before-After/Before-n-After (${i * 14 + 1}).webp`,
  after:  `/images/Before-After/Before-n-After (${i * 14 + 2}).webp`,
  // fallback to a blog image if the before/after pair is missing
  fallback: `/images/Blog/Blog-10${(i % 6) + 1}.webp`,
}));

/* ── Shared Author — WP: get_the_author_meta() + ACF user fields ── */
export const AUTHOR = {
  name:             "دکتر شاهین باستانی‌نژاد",
  title:            "فوق‌تخصص جراحی پلاستیک بینی",
  specialty:        "گوش، گلو و بینی · جراحی پلاستیک",
  hospital:         "بیمارستان میلاد — تهران",
  bio:              "دکتر شاهین باستانی‌نژاد با بیش از ۱۸ سال تجربه تخصصی در جراحی پلاستیک بینی، از پیشگامان استفاده از روش‌های نوین رینوپلاستی در ایران است. هدف ایشان دستیابی به نتایجی طبیعی، پایدار و کاملاً هماهنگ با ساختار چهره هر بیمار است.",
  stats:            [{ label: "سال تجربه", value: "+۱۸" }, { label: "عمل موفق", value: "+۲۰۰۰" }, { label: "رضایت بیمار", value: "۴.۹" }],
  certifications:   ["بورد تخصصی گوش، گلو و بینی ایران", "عضو ISAPS (انجمن بین‌المللی جراحی پلاستیک)", "فلوشیپ تخصصی جراحی پلاستیک صورت", "عضو انجمن جراحان گوش، گلو و بینی ایران"],
  avatarInitials:   "دب",
};
