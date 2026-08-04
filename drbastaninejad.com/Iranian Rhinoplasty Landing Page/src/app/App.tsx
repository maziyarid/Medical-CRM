import { useState, useRef, useCallback, useEffect } from "react";
import {
  Phone, MapPin, Clock, ChevronDown, ChevronLeft, ChevronRight,
  Instagram, Youtube, Send, Menu, X, Star, Award, CheckCircle,
  Stethoscope, Scissors, Heart, ArrowLeft, ArrowRight,
  MessageCircle, Calendar, ExternalLink, Play, BookOpen,
  Shield, Users, TrendingUp, Eye
} from "lucide-react";

/* ─── Constants ─── */
const DOCTOR_NAME = "دکتر شاهین باستانی‌نژاد";
const BOOKING_URL = "https://app.drbastaninejad.com/";
const PHONES = ["۰۲۱–۸۶۰۸۷۲۵۰", "۰۲۱–۸۸۲۰۵۶۰۶", "۰۹۹۱–۲۴۹۶۶۵۹"];
const INSTAGRAM = "https://www.instagram.com/dr.shahin.bastaninejad/";
const YOUTUBE = "https://www.youtube.com/@Drshahinbastaninejad";
const TELEGRAM = "https://t.me/dr_bastaninejad";
const APARAT = "https://www.aparat.com/Drshahinbastaninejad";

const HERO_IMG = "https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1800&q=85&auto=format";
const DOCTOR_IMG = "https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=600&q=85&auto=format";
const ABOUT_BG = "https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=900&q=80&auto=format";

/* ─── Services ─── */
const COSMETIC_SERVICES = [
  { id: 1, title: "جراحی بینی اولیه", subtitle: "راینوپلاستی اولیه", icon: Scissors, desc: "جراحی بینی اولیه یا راینوپلاستی، هنر تراشیدن بینی متناسب با چهره است. دکتر باستانی‌نژاد با بهره‌گیری از تکنیک‌های روز دنیا، نتیجه‌ای طبیعی و هماهنگ با سیمای شما ایجاد می‌کند." },
  { id: 2, title: "جراحی بینی ترمیمی", subtitle: "رینوپلاستی ثانویه", icon: Heart, desc: "جراحی ترمیمی برای اصلاح نتایج ناخواسته جراحی‌های قبلی طراحی شده است. این عمل به دقت و تجربه بسیار بالایی نیاز دارد که دکتر باستانی‌نژاد با سال‌ها سابقه در این حوزه آماده انجام آن است." },
  { id: 3, title: "جراحی بینی گوشتی", subtitle: "بینی پهن و گوشتی", icon: Eye, desc: "بینی‌های گوشتی به دلیل ضخامت پوست و بافت نرم، جراحی متفاوتی نیاز دارند. دکتر باستانی‌نژاد با روش‌های تخصصی، ظرافت ایده‌آل را برای این نوع بینی ایجاد می‌کند." },
  { id: 4, title: "جراحی بینی استخوانی", subtitle: "بینی سخت و استخوانی", icon: Shield, desc: "بینی‌های استخوانی ساختار محکم‌تری دارند و تکنیک‌های خاصی برای ایجاد فرم دلخواه نیاز دارند. نتیجه نهایی ظاهری طبیعی و متناسب با ساختار چهره خواهد بود." },
  { id: 5, title: "بینی طبیعی", subtitle: "نتیجه کاملاً طبیعی", icon: CheckCircle, desc: "سبک جراحی بینی طبیعی، تغییراتی ظریف ایجاد می‌کند که حس می‌شود شما همیشه این بینی را داشته‌اید. هماهنگی کامل با چهره، اولویت اصلی این رویکرد است." },
  { id: 6, title: "بینی فانتزی", subtitle: "سبک متفاوت و برجسته", icon: Star, desc: "برای افرادی که به دنبال ظاهری خاص‌تر هستند، سبک فانتزی با پروفایل برجسته‌تر و نوک سربالا انتخاب می‌شود. این تغییرات در محدوده طبیعی چهره طراحی می‌شوند." },
  { id: 7, title: "رفع قوز بینی", subtitle: "اصلاح برآمدگی پشت بینی", icon: TrendingUp, desc: "قوز بینی یکی از شایع‌ترین دلایل مراجعه به جراح است. دکتر باستانی‌نژاد با تکنیک‌های دقیق، قوز را حذف و پروفایل صاف و زیبایی ایجاد می‌کند." },
];

const FUNCTIONAL_SERVICES = [
  { id: 8, title: "سپتوپلاستی", subtitle: "اصلاح انحراف تیغه بینی", icon: Stethoscope, desc: "انحراف تیغه بینی (سپتوم) می‌تواند باعث مشکلات تنفسی جدی شود. سپتوپلاستی این انحراف را اصلاح کرده و تنفس را به‌طور قابل توجهی بهبود می‌بخشد." },
  { id: 9, title: "توربینوپلاستی", subtitle: "کاهش حجم شاخک‌های بینی", icon: Heart, desc: "بزرگ شدن شاخک‌های بینی (توربینیت‌ها) یکی از علل شایع گرفتگی مزمن بینی است. توربینوپلاستی این بافت‌ها را کوچک کرده و جریان هوا را بهینه می‌کند." },
  { id: 10, title: "آندوسکوپی سینوس (FESS)", subtitle: "درمان سینوزیت مزمن", icon: Shield, desc: "جراحی آندوسکوپیک سینوس (FESS) برای درمان سینوزیت مزمن، پولیپ‌های بینی و دیگر بیماری‌های سینوس انجام می‌شود. این روش با حداقل تهاجم، نتایج ماندگار ایجاد می‌کند." },
];

/* ─── Timeline ─── */
const TIMELINE = [
  { year: "۱۳۷۵–۱۳۸۲", title: "دکترای پزشکی عمومی", org: "دانشگاه علوم پزشکی اصفهان", icon: BookOpen },
  { year: "۱۳۸۴–۱۳۸۸", title: "تخصص گوش، حلق و بینی", org: "دانشگاه علوم پزشکی تهران — رتبه ۴ بورد کشوری", icon: Award },
  { year: "۱۳۸۸–۱۳۸۹", title: "فلوشیپ رینوپلاستی", org: "بیمارستان امیراعلم تهران", icon: Star },
  { year: "از ۱۳۸۸", title: "عضو هیئت علمی", org: "دانشگاه علوم پزشکی تهران", icon: Users },
  { year: "از ۱۳۹۶", title: "معاون آموزشی", org: "بیمارستان امیراعلم تهران", icon: Shield },
  { year: "اکنون", title: "دبیر کمیته علمی رینولوژی", org: "انجمن جراحی پلاستیک بینی ایران", icon: CheckCircle },
];

/* ─── Trust Badges ─── */
const TRUST_BADGES = [
  { icon: Award, title: "دانشیار دانشگاه علوم پزشکی تهران", subtitle: "عضو هیئت علمی بیمارستان امیراعلم" },
  { icon: Star, title: "رتبه ۴ بورد تخصصی کشوری", subtitle: "گوش، حلق و بینی — جراحی سر و گردن" },
  { icon: Users, title: "دبیر کمیته علمی رینولوژی", subtitle: "انجمن جراحی پلاستیک بینی ایران" },
  { icon: BookOpen, title: "مجری ۱۰+ دوره سمینار جراحی", subtitle: "رینوپلاستی · آندوسکوپی سینوس" },
  { icon: Shield, title: "مجوز رسمی وزارت بهداشت", subtitle: "پروانه فعالیت از وزارت بهداشت ایران" },
];

/* ─── Certificates ─── */
const CERTIFICATES = [
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

/* ─── Process Steps ─── */
const PROCESS_STEPS = [
  { num: "۰۱", title: "مشاوره تخصصی", desc: "در جلسه مشاوره، وضعیت بینی شما بررسی می‌شود، اهداف و انتظاراتتان شنیده می‌شود و بهترین روش جراحی برای شما تعیین می‌گردد." },
  { num: "۰۲", title: "آمادگی قبل از عمل", desc: "آزمایش‌ها و معاینات لازم انجام می‌شود. دستورالعمل‌های قبل از عمل به شما داده می‌شود تا آمادگی کامل برای جراحی داشته باشید." },
  { num: "۰۳", title: "روز جراحی", desc: "عمل جراحی در محیطی کاملاً ایمن و استاندارد توسط دکتر باستانی‌نژاد و تیم متخصص ایشان انجام می‌شود." },
  { num: "۰۴", title: "دوره بهبودی", desc: "پس از جراحی، تیم پزشکی ما در تمام مراحل بهبودی همراه شما است. ویزیت‌های پیگیری منظم برای اطمینان از نتیجه مطلوب برنامه‌ریزی می‌شود." },
];

/* ─── FAQ ─── */
const FAQS = [
  { cat: "کلیات جراحی", q: "جراحی بینی چیست و چه هدفی دارد؟", a: "جراحی بینی (رینوپلاستی) عملی جراحی است که برای اصلاح شکل، اندازه یا عملکرد بینی انجام می‌شود. هدف اصلی این جراحی دستیابی به تناسب بهتر بین بینی و سایر اجزای چهره، با حفظ و بهبود عملکرد تنفسی است." },
  { cat: "کاندیداتوری", q: "چه کسانی می‌توانند جراحی بینی انجام دهند؟", a: "افراد بالای ۱۸ سال که رشد غضروف و استخوان آن‌ها کامل شده و دارای سلامت جسمی و روانی کافی هستند. همچنین انتظارات واقع‌بینانه از جراحی داشته باشند. بهترین راه برای تشخیص کاندیداتوری، مشاوره با جراح متخصص است." },
  { cat: "کاندیداتوری", q: "چه کسانی نمی‌توانند جراحی بینی انجام دهند؟", a: "افراد زیر ۱۸ سال، کسانی که بیماری‌های زمینه‌ای کنترل‌نشده دارند، خانم‌های باردار یا شیرده، و افرادی که دیسمورفی بدنی (body dysmorphic disorder) دارند از کاندیداهای مناسب نیستند." },
  { cat: "انواع بینی", q: "تفاوت جراحی بینی استخوانی و گوشتی چیست؟", a: "بینی‌های استخوانی پوست نازک‌تری دارند و اسکلت استخوانی-غضروفی آن‌ها برجسته‌تر است. در مقابل، بینی‌های گوشتی پوست ضخیم‌تری دارند. هر نوع به تکنیک‌های متفاوت جراحی نیاز دارد." },
  { cat: "دوره بهبودی", q: "دوره بهبودی پس از جراحی بینی چقدر طول می‌کشد؟", a: "آتل بینی معمولاً ۷ تا ۱۰ روز پس از عمل برداشته می‌شود. کبودی و تورم در اکثر بیماران ظرف ۲ تا ۳ هفته تا حد زیادی کاهش می‌یابد. بازگشت به فعالیت‌های سبک معمولاً ۱ تا ۲ هفته پس از عمل ممکن است." },
  { cat: "دوره بهبودی", q: "چه مدت بعد از جراحی می‌توانم به سر کار برگردم؟", a: "بستگی به نوع کار دارد. برای کارهای اداری و نشسته معمولاً ۱۰–۱۴ روز کافی است. برای کارهای سنگین و فعالیت بدنی شدید، حداقل ۴–۶ هفته باید صبر کرد." },
  { cat: "هزینه", q: "هزینه جراحی بینی چقدر است؟", a: "هزینه جراحی بینی به عوامل متعددی مثل پیچیدگی بینی، تکنیک جراحی، نوع بیهوشی و امکانات بیمارستان بستگی دارد. برای دریافت تخمین دقیق هزینه، پس از مشاوره و بررسی وضعیت بینی شما اطلاع‌رسانی خواهد شد." },
  { cat: "قبل از عمل", q: "چه آزمایشاتی قبل از جراحی بینی لازم است؟", a: "آزمایش خون کامل، تست انعقاد خون، الکتروکاردیوگرام (ECG)، عکس رادیولوژی قفسه سینه و در صورت نیاز آزمایش‌های تخصصی‌تر. پزشک متخصص بی‌هوشی نیز ویزیت قبل از عمل را انجام خواهد داد." },
  { cat: "بعد از عمل", q: "چه مراقبت‌هایی بعد از جراحی باید داشته باشم؟", a: "استراحت کافی و بالا نگه داشتن سر، پرهیز از فعالیت‌های سنگین، عدم استفاده از عینک برای حدود ۶ هفته، محافظت از بینی در برابر ضربه، پرهیز از آفتاب مستقیم، و مصرف داروهای تجویزی طبق دستور." },
  { cat: "جراحی ترمیمی", q: "چرا باید ۱۲ ماه پس از جراحی اول برای ترمیمی صبر کرد؟", a: "بافت بینی نیاز به زمان دارد تا کاملاً بهبود یابد و تورم‌های عمقی فروکش کنند. قضاوت زودهنگام قبل از بهبود کامل ممکن است منجر به جراحی ترمیمی غیرضروری شود. ۱۲ ماه، حداقل زمان لازم برای ارزیابی دقیق نتیجه نهایی است." },
];

/* ─── Blog Articles ─── */
const BLOG_IMGS = [
  "https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=600&q=80&auto=format",
  "https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=600&q=80&auto=format",
  "https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=600&q=80&auto=format",
  "https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=600&q=80&auto=format",
  "https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=600&q=80&auto=format",
  "https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=600&q=80&auto=format",
];

const BLOG_POSTS = [
  { id: 1, cat: "جراحی بینی", title: "جراحی بینی چیست و چه کسانی کاندید هستند؟", excerpt: "راینوپلاستی یا جراحی بینی یکی از رایج‌ترین اقدامات زیبایی در ایران است. در این مطلب، کاندیداهای مناسب و ملاحظات مهم را بررسی می‌کنیم.", img: BLOG_IMGS[0], date: "۱۴۰۳/۰۸/۱۵", featured: true },
  { id: 2, cat: "جراحی بینی", title: "جراحی بینی ترمیمی چیست؟", excerpt: "جراحی ترمیمی بینی یکی از چالش‌برانگیزترین اقدامات جراحی پلاستیک است که به تخصص و تجربه ویژه‌ای نیاز دارد.", img: BLOG_IMGS[1], date: "۱۴۰۳/۰۷/۲۰" },
  { id: 3, cat: "راهنمای بیمار", title: "اقدامات ضروری قبل از جراحی بینی", excerpt: "آمادگی صحیح قبل از جراحی بینی نقش مهمی در موفقیت عمل و سرعت بهبودی شما دارد. این موارد را جدی بگیرید.", img: BLOG_IMGS[2], date: "۱۴۰۳/۰۷/۰۵" },
  { id: 4, cat: "مراقبت‌ها", title: "مراقبت‌های بعد از جراحی بینی", excerpt: "دوره بهبودی پس از جراحی بینی نیازمند رعایت دقیق نکاتی است که بر کیفیت نتیجه نهایی تأثیر مستقیم دارند.", img: BLOG_IMGS[3], date: "۱۴۰۳/۰۶/۱۸" },
  { id: 5, cat: "جراحی بینی", title: "جراحی بینی گوشتی چیست؟", excerpt: "بینی‌های گوشتی ویژگی‌های خاصی دارند که تکنیک‌های متفاوتی در جراحی آن‌ها به کار می‌رود.", img: BLOG_IMGS[4], date: "۱۴۰۳/۰۶/۰۱" },
  { id: 6, cat: "راهنمای بیمار", title: "۱۰ نکته مهم در انتخاب جراح بینی", excerpt: "انتخاب جراح مناسب، مهم‌ترین گام در مسیر جراحی بینی موفق است. این معیارها را در انتخاب خود در نظر بگیرید.", img: BLOG_IMGS[5], date: "۱۴۰۳/۰۵/۱۲" },
];

/* ─── Gallery sample images (fallback to Unsplash) ─── */
const GALLERY_UNSPLASH = [
  "https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=400&q=80&auto=format",
  "https://images.unsplash.com/photo-1583864697784-a0a098e9b8f9?w=400&q=80&auto=format",
  "https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=400&q=80&auto=format",
  "https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=400&q=80&auto=format",
  "https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=400&q=80&auto=format",
  "https://images.unsplash.com/photo-1584516150909-c43483ee7932?w=400&q=80&auto=format",
  "https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=400&q=80&auto=format",
  "https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=400&q=80&auto=format",
];

const GALLERY_ITEMS = Array.from({ length: 12 }, (_, i) => ({
  id: i + 1,
  before: `assets/images/Before-After/Before-n-After (${i * 14 + 1}).webp`,
  after: `assets/images/Before-After/Before-n-After (${i * 14 + 2}).webp`,
  fallback: GALLERY_UNSPLASH[i % GALLERY_UNSPLASH.length],
}));

/* ─────────────── NavBar ─────────────── */
function NavBar() {
  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [svcOpen, setSvcOpen] = useState(false);

  useEffect(() => {
    const fn = () => setScrolled(window.scrollY > 40);
    window.addEventListener("scroll", fn);
    return () => window.removeEventListener("scroll", fn);
  }, []);

  const links = [
    { href: "#about", label: "درباره دکتر" },
    { href: "#services", label: "خدمات", drop: true },
    { href: "#gallery", label: "گالری" },
    { href: "#faq", label: "سؤالات متداول" },
    { href: "#blog", label: "مقالات" },
    { href: "#contact", label: "تماس با ما" },
  ];

  return (
    <nav className={`fixed top-0 inset-x-0 z-50 transition-all duration-300 ${scrolled ? "bg-white/95 backdrop-blur-md shadow-lg shadow-black/5" : "bg-transparent"}`}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-20">
          <a href="#" className="flex items-center gap-3">
            <div className="w-10 h-10 bg-primary rounded-xl flex items-center justify-center shadow-md">
              <Stethoscope className="w-5 h-5 text-white" />
            </div>
            <div className="leading-tight">
              <p className={`text-sm font-bold ${scrolled ? "text-primary" : "text-white"}`}>{DOCTOR_NAME}</p>
              <p className={`text-xs ${scrolled ? "text-muted-foreground" : "text-white/70"}`}>متخصص گوش، حلق و بینی</p>
            </div>
          </a>

          <div className="hidden lg:flex items-center gap-1">
            {links.map((l) => (
              <div key={l.href} className="relative" onMouseEnter={() => l.drop && setSvcOpen(true)} onMouseLeave={() => setSvcOpen(false)}>
                <a href={l.href} className={`flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${scrolled ? "text-foreground hover:text-primary hover:bg-primary/5" : "text-white/90 hover:text-white hover:bg-white/10"}`}>
                  {l.label}
                  {l.drop && <ChevronDown className="w-3.5 h-3.5" />}
                </a>
                {l.drop && svcOpen && (
                  <div className="absolute top-full right-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-border p-4">
                    <p className="text-xs font-bold text-primary/60 px-2 pb-2">خدمات زیبایی</p>
                    {COSMETIC_SERVICES.map((s) => (
                      <a key={s.id} href="#services" className="block px-3 py-2 rounded-lg hover:bg-primary/5 text-sm text-foreground hover:text-primary transition-colors">{s.title}</a>
                    ))}
                    <div className="border-t border-border my-2" />
                    <p className="text-xs font-bold text-primary/60 px-2 pb-2">خدمات درمانی</p>
                    {FUNCTIONAL_SERVICES.map((s) => (
                      <a key={s.id} href="#services" className="block px-3 py-2 rounded-lg hover:bg-primary/5 text-sm text-foreground hover:text-primary transition-colors">{s.title}</a>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>

          <div className="hidden lg:flex items-center gap-4">
            <a href="tel:02186087250" className={`flex items-center gap-2 text-sm font-medium transition-colors ${scrolled ? "text-primary" : "text-white"}`}>
              <Phone className="w-4 h-4" /> ۰۲۱–۸۶۰۸۷۲۵۰
            </a>
            <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer" className="bg-primary hover:bg-primary/90 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-all shadow-md hover:shadow-primary/30 hover:scale-105">
              رزرو نوبت
            </a>
          </div>

          <button onClick={() => setOpen(!open)} className={`lg:hidden p-2 rounded-lg ${scrolled ? "text-foreground hover:bg-muted" : "text-white hover:bg-white/10"}`}>
            {open ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>
      </div>

      {open && (
        <div className="lg:hidden bg-white border-t border-border shadow-xl">
          <div className="max-w-7xl mx-auto px-4 py-6 flex flex-col gap-2">
            {links.map((l) => (
              <a key={l.href} href={l.href} onClick={() => setOpen(false)} className="px-4 py-3 rounded-xl text-foreground hover:text-primary hover:bg-primary/5 font-medium transition-colors">
                {l.label}
              </a>
            ))}
            <div className="border-t border-border my-2" />
            <a href="tel:02186087250" className="flex items-center gap-2 px-4 py-3 text-primary font-medium">
              <Phone className="w-4 h-4" /> ۰۲۱–۸۶۰۸۷۲۵۰
            </a>
            <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer" className="bg-primary text-white font-bold px-4 py-3 rounded-xl text-center">رزرو نوبت آنلاین</a>
          </div>
        </div>
      )}
    </nav>
  );
}

/* ─────────────── Hero ─────────────── */
function HeroSection() {
  return (
    <section className="relative min-h-screen flex items-center overflow-hidden">
      <div className="absolute inset-0">
        <img src={HERO_IMG} alt="کلینیک جراحی بینی" className="w-full h-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-l from-black/80 via-black/60 to-black/30" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent" />
      </div>

      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-28 pb-20">
        <div className="grid lg:grid-cols-2 gap-16 items-center">
          <div className="text-white space-y-8">
            <div className="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 text-sm">
              <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse" />
              جراح متخصص بینی در تهران
            </div>
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-black leading-tight">
              دستیابی به زیبایی<br />
              <span className="text-transparent bg-clip-text bg-gradient-to-l from-green-300 to-emerald-200">طبیعی</span>{" "}
              با حفظ<br />عملکرد تنفسی
            </h1>
            <p className="text-xl text-white/75 leading-relaxed max-w-lg">
              {DOCTOR_NAME} — دانشیار دانشگاه علوم پزشکی تهران، با بیش از ۱۵ سال تجربه تخصصی و ۲۰۰۰+ عمل موفق
            </p>
            <div className="flex flex-wrap gap-4">
              <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-8 py-4 rounded-2xl transition-all shadow-xl shadow-primary/30 hover:scale-105 text-lg">
                <Calendar className="w-5 h-5" /> رزرو مشاوره رایگان
              </a>
              <a href="#gallery" className="flex items-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/30 text-white font-bold px-8 py-4 rounded-2xl transition-all text-lg">
                <Eye className="w-5 h-5" /> مشاهده گالری
              </a>
            </div>
            <div className="flex flex-wrap gap-8 pt-2">
              {[
                { val: "+۱۵", label: "سال تجربه" },
                { val: "+۲۰۰۰", label: "عمل موفق" },
                { val: "+۱۷۸", label: "نمونه گالری" },
                { val: "۹۸٪", label: "رضایت بیمار" },
              ].map((s) => (
                <div key={s.label} className="text-center">
                  <p className="text-3xl font-black">{s.val}</p>
                  <p className="text-sm text-white/60">{s.label}</p>
                </div>
              ))}
            </div>
          </div>

          <div className="hidden lg:flex justify-center">
            <div className="relative">
              <div className="w-80 h-[420px] rounded-3xl overflow-hidden border-4 border-white/20 shadow-2xl">
                <img src={DOCTOR_IMG} alt={DOCTOR_NAME} className="w-full h-full object-cover" />
                <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
                <div className="absolute bottom-0 inset-x-0 p-6 text-white">
                  <p className="font-bold text-lg">{DOCTOR_NAME}</p>
                  <p className="text-white/75 text-sm">متخصص گوش، حلق و بینی</p>
                  <p className="text-white/75 text-sm">دانشیار دانشگاه علوم پزشکی تهران</p>
                </div>
              </div>
              <div className="absolute -right-8 top-12 bg-white rounded-2xl p-4 shadow-xl">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
                    <Award className="w-5 h-5 text-primary" />
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">رتبه بورد</p>
                    <p className="font-bold text-sm">رتبه ۴ کشوری</p>
                  </div>
                </div>
              </div>
              <div className="absolute -left-8 bottom-24 bg-white rounded-2xl p-4 shadow-xl">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center">
                    <Star className="w-5 h-5 text-accent" />
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">رضایت بیمار</p>
                    <p className="font-bold text-sm">۹۸٪ موفقیت</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
        <ChevronDown className="w-8 h-8 text-white/60" />
      </div>
    </section>
  );
}

/* ─────────────── Trust Strip ─────────────── */
function TrustStrip() {
  return (
    <section className="bg-primary py-10">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
          {TRUST_BADGES.map((b, i) => (
            <div key={i} className="flex flex-col items-center text-center gap-3 group">
              <div className="w-12 h-12 bg-white/15 rounded-2xl flex items-center justify-center group-hover:bg-white/25 transition-colors">
                <b.icon className="w-6 h-6 text-white" />
              </div>
              <div>
                <p className="text-white font-bold text-sm leading-snug">{b.title}</p>
                <p className="text-white/60 text-xs mt-1">{b.subtitle}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─────────────── Services ─────────────── */
function ServicesSection() {
  const [tab, setTab] = useState<"cosmetic" | "functional">("cosmetic");
  const [activeId, setActiveId] = useState(1);
  const services = tab === "cosmetic" ? COSMETIC_SERVICES : FUNCTIONAL_SERVICES;
  const active = services.find((s) => s.id === activeId) ?? services[0];

  useEffect(() => { setActiveId(services[0].id); }, [tab]);

  return (
    <section id="services" className="py-24 bg-background">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <span className="text-primary font-bold text-sm tracking-wider">تخصص‌های ما</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">خدمات جراحی بینی</h2>
          <p className="text-muted-foreground text-lg">از راینوپلاستی زیبایی تا جراحی‌های درمانی، همه خدمات با بالاترین استاندارد</p>
        </div>

        <div className="flex justify-center mb-10">
          <div className="bg-muted rounded-2xl p-1.5 flex gap-1">
            {[{ k: "cosmetic", l: "خدمات زیبایی" }, { k: "functional", l: "خدمات درمانی" }].map((t) => (
              <button key={t.k} onClick={() => setTab(t.k as typeof tab)} className={`px-6 py-3 rounded-xl font-bold text-sm transition-all ${tab === t.k ? "bg-primary text-white shadow-lg" : "text-muted-foreground hover:text-foreground"}`}>
                {t.l}
              </button>
            ))}
          </div>
        </div>

        <div className="grid lg:grid-cols-3 gap-6">
          <div className="flex flex-col gap-2">
            {services.map((s) => {
              const Icon = s.icon;
              const on = s.id === activeId;
              return (
                <button key={s.id} onClick={() => setActiveId(s.id)} className={`flex items-center gap-4 p-4 rounded-2xl text-right transition-all ${on ? "bg-primary text-white shadow-lg shadow-primary/20" : "bg-white hover:bg-secondary border border-border text-foreground"}`}>
                  <div className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${on ? "bg-white/20" : "bg-primary/10"}`}>
                    <Icon className={`w-5 h-5 ${on ? "text-white" : "text-primary"}`} />
                  </div>
                  <div className="flex-1">
                    <p className="font-bold text-sm">{s.title}</p>
                    <p className={`text-xs mt-0.5 ${on ? "text-white/70" : "text-muted-foreground"}`}>{s.subtitle}</p>
                  </div>
                  {on && <ChevronLeft className="w-4 h-4 flex-shrink-0" />}
                </button>
              );
            })}
          </div>

          <div className="lg:col-span-2">
            <div className="bg-white rounded-3xl border border-border p-8 h-full">
              <div className="flex items-start gap-6 mb-6">
                <div className="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center flex-shrink-0">
                  <active.icon className="w-8 h-8 text-primary" />
                </div>
                <div>
                  <h3 className="text-2xl font-black text-foreground">{active.title}</h3>
                  <p className="text-primary font-medium text-sm mt-1">{active.subtitle}</p>
                </div>
              </div>
              <p className="text-muted-foreground leading-loose text-lg mb-8">{active.desc}</p>
              <div className="grid sm:grid-cols-2 gap-4 mb-8">
                {["مشاوره تخصصی رایگان", "تکنیک‌های روز دنیا", "بیهوشی ایمن", "مراقبت پس از عمل"].map((f) => (
                  <div key={f} className="flex items-center gap-3">
                    <CheckCircle className="w-5 h-5 text-primary flex-shrink-0" />
                    <span className="text-foreground font-medium text-sm">{f}</span>
                  </div>
                ))}
              </div>
              <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-8 py-4 rounded-2xl transition-all shadow-lg hover:shadow-primary/30">
                <Calendar className="w-5 h-5" /> رزرو مشاوره برای این خدمت
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

/* ─────────────── Stats Band ─────────────── */
function StatsBand() {
  return (
    <section className="bg-gradient-to-l from-primary to-emerald-700 py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-8">
          {[
            { val: "+۱۵", label: "سال تجربه", sub: "جراحی تخصصی بینی" },
            { val: "+۲۰۰۰", label: "عمل موفق", sub: "در بیماران مختلف" },
            { val: "+۱۷۸", label: "نمونه گالری", sub: "قبل و بعد از جراحی" },
            { val: "۹۸٪", label: "رضایت بیمار", sub: "بر اساس نظرسنجی" },
          ].map((s) => (
            <div key={s.label} className="text-center">
              <p className="text-5xl font-black text-white">{s.val}</p>
              <p className="text-xl font-bold text-white/90 mt-1">{s.label}</p>
              <p className="text-white/60 text-sm mt-1">{s.sub}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─────────────── About ─────────────── */
function AboutSection() {
  return (
    <section id="about" className="py-24 bg-secondary/30">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid lg:grid-cols-2 gap-16 items-start">
          <div className="relative">
            <div className="rounded-3xl overflow-hidden aspect-[4/5] shadow-2xl">
              <img src={ABOUT_BG} alt={DOCTOR_NAME} className="w-full h-full object-cover" />
              <div className="absolute inset-0 bg-gradient-to-t from-primary/70 via-transparent to-transparent" />
            </div>
            <div className="absolute -bottom-6 -right-4 lg:-right-8 max-w-sm bg-white rounded-3xl p-6 shadow-2xl border border-border">
              <div className="text-4xl text-primary/20 font-serif leading-none mb-2">"</div>
              <blockquote className="text-foreground text-sm leading-loose font-medium">
                مهمترین اصل در جراحی زیبایی بینی این است که از یک سادگی نامطبوع به پیچیدگی‌ای برسیم که ورای آن، سادگی مطبوعی حاصل شود… بینی عضوی است جهت نفس کشیدن.
              </blockquote>
              <footer className="mt-4 flex items-center gap-3">
                <div className="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                  <Star className="w-4 h-4 text-white" />
                </div>
                <p className="text-xs text-muted-foreground">{DOCTOR_NAME}</p>
              </footer>
            </div>
          </div>

          <div className="pt-8 lg:pt-0">
            <span className="text-primary font-bold text-sm tracking-wider">رزومه تخصصی</span>
            <h2 className="text-4xl font-black text-foreground mt-2 mb-6">{DOCTOR_NAME}</h2>
            <p className="text-muted-foreground text-lg leading-loose mb-8">
              دانشیار دانشگاه علوم پزشکی تهران و عضو هیئت علمی بیمارستان امیراعلم، دبیر کمیته علمی رینولوژی انجمن جراحی پلاستیک بینی ایران. با بیش از ۱۵ سال تجربه در جراحی‌های بینی اولیه، ترمیمی و درمانی، هدف اصلی ایشان دستیابی به نتایجی است که هم زیبا باشند و هم عملکرد تنفسی را تأمین کنند.
            </p>

            <div className="grid sm:grid-cols-2 gap-3 mb-10">
              {[
                "دانشگاه علوم پزشکی تهران",
                "بیمارستان امیراعلم تهران",
                "انجمن جراحی پلاستیک بینی ایران",
                "انجمن متخصصان گوش، حلق و بینی ایران",
              ].map((a) => (
                <div key={a} className="flex items-center gap-3 bg-white rounded-xl p-3 border border-border">
                  <CheckCircle className="w-5 h-5 text-primary flex-shrink-0" />
                  <span className="text-sm font-medium text-foreground">{a}</span>
                </div>
              ))}
            </div>

            <h3 className="text-lg font-bold text-foreground mb-6">مسیر تحصیل و تجربه</h3>
            <div className="relative">
              <div className="absolute right-5 top-0 bottom-0 w-0.5 bg-primary/20" />
              <div className="space-y-6">
                {TIMELINE.map((t, i) => {
                  const Icon = t.icon;
                  return (
                    <div key={i} className="flex gap-5">
                      <div className="relative flex-shrink-0">
                        <div className="w-10 h-10 bg-primary rounded-full flex items-center justify-center shadow-md shadow-primary/30 z-10 relative">
                          <Icon className="w-5 h-5 text-white" />
                        </div>
                      </div>
                      <div className="pb-2">
                        <span className="text-xs font-bold text-primary bg-primary/10 rounded-full px-3 py-1">{t.year}</span>
                        <p className="font-bold text-foreground mt-2">{t.title}</p>
                        <p className="text-muted-foreground text-sm mt-0.5">{t.org}</p>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

/* ─────────────── Certificates ─────────────── */
function CertificatesSection() {
  return (
    <section id="certificates" className="py-24 bg-background">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <span className="text-primary font-bold text-sm tracking-wider">گواهینامه‌ها</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">مدارک و افتخارات</h2>
          <p className="text-muted-foreground text-lg">تأییدیه‌ها و گواهینامه‌های معتبر ملی و بین‌المللی</p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
          {CERTIFICATES.map((cert, i) => (
            <div key={i} className="group bg-white border border-border rounded-2xl p-6 hover:border-primary/30 hover:shadow-lg hover:shadow-primary/5 transition-all">
              <div className="flex items-start gap-4">
                <div className="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-accent/20 transition-colors">
                  <Award className="w-5 h-5 text-accent" />
                </div>
                <p className="text-foreground font-medium text-sm leading-relaxed">{cert}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─────────────── Before/After Slider ─────────────── */
function BeforeAfterSlider({ before, after, fallback }: { before: string; after: string; fallback: string }) {
  const [pos, setPos] = useState(50);
  const [dragging, setDragging] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  const updatePos = useCallback((clientX: number) => {
    if (!ref.current) return;
    const rect = ref.current.getBoundingClientRect();
    const pct = Math.max(5, Math.min(95, ((rect.right - clientX) / rect.width) * 100));
    setPos(pct);
  }, []);

  useEffect(() => {
    const onMove = (e: MouseEvent) => { if (dragging) updatePos(e.clientX); };
    const onUp = () => setDragging(false);
    window.addEventListener("mousemove", onMove);
    window.addEventListener("mouseup", onUp);
    return () => { window.removeEventListener("mousemove", onMove); window.removeEventListener("mouseup", onUp); };
  }, [dragging, updatePos]);

  const fallbackSrc = (e: React.SyntheticEvent<HTMLImageElement>) => {
    (e.target as HTMLImageElement).src = fallback;
  };

  return (
    <div ref={ref} className="relative w-full aspect-[3/4] rounded-2xl overflow-hidden cursor-col-resize select-none" onMouseDown={(e) => { setDragging(true); updatePos(e.clientX); }}>
      <img src={after} alt="بعد" className="absolute inset-0 w-full h-full object-cover" onError={fallbackSrc} />
      <div className="absolute inset-0 overflow-hidden" style={{ clipPath: `inset(0 0 0 ${100 - pos}%)` }}>
        <img src={before} alt="قبل" className="absolute inset-0 w-full h-full object-cover" onError={fallbackSrc} />
      </div>
      <div className="absolute top-0 bottom-0 w-0.5 bg-white shadow-lg" style={{ right: `${pos}%` }}>
        <div className="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-8 h-8 bg-white rounded-full shadow-xl flex items-center justify-center">
          <ArrowRight className="w-3 h-3 text-primary" />
          <ArrowLeft className="w-3 h-3 text-primary" />
        </div>
      </div>
      <div className="absolute bottom-3 right-3 bg-black/60 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full font-bold">قبل</div>
      <div className="absolute bottom-3 left-3 bg-primary/80 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full font-bold">بعد</div>
    </div>
  );
}

/* ─────────────── Gallery ─────────────── */
function GallerySection() {
  const [page, setPage] = useState(0);
  const PER = 4;
  const total = Math.ceil(GALLERY_ITEMS.length / PER);
  const visible = GALLERY_ITEMS.slice(page * PER, page * PER + PER);

  return (
    <section id="gallery" className="py-24 bg-muted/50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-6">
          <span className="text-primary font-bold text-sm tracking-wider">نتایج واقعی</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">گالری قبل و بعد</h2>
          <p className="text-muted-foreground text-lg">+۱۷۸ نمونه واقعی از نتایج جراحی بینی دکتر باستانی‌نژاد</p>
        </div>

        <div className="flex flex-wrap justify-center gap-6 mb-12">
          {[
            { val: "+۱۷۸", label: "نمونه تصویری" },
            { val: "+۱۵", label: "سال تجربه" },
            { val: "+۲۰۰۰", label: "عمل موفق" },
            { val: "۹۸٪", label: "رضایت بیمار" },
          ].map((s) => (
            <div key={s.label} className="flex items-center gap-3 bg-white rounded-2xl px-5 py-3 shadow-sm border border-border">
              <p className="text-2xl font-black text-primary">{s.val}</p>
              <p className="text-sm font-medium text-muted-foreground">{s.label}</p>
            </div>
          ))}
        </div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          {visible.map((item) => (
            <BeforeAfterSlider key={item.id} before={item.before} after={item.after} fallback={item.fallback} />
          ))}
        </div>

        <div className="flex justify-center items-center gap-3">
          <button onClick={() => setPage((p) => Math.max(0, p - 1))} disabled={page === 0} className="w-10 h-10 rounded-xl border border-border bg-white flex items-center justify-center hover:bg-secondary disabled:opacity-40 transition-colors">
            <ChevronRight className="w-5 h-5" />
          </button>
          {Array.from({ length: total }).map((_, i) => (
            <button key={i} onClick={() => setPage(i)} className={`w-10 h-10 rounded-xl font-bold text-sm transition-all ${page === i ? "bg-primary text-white" : "bg-white border border-border text-foreground hover:bg-secondary"}`}>
              {i + 1}
            </button>
          ))}
          <button onClick={() => setPage((p) => Math.min(total - 1, p + 1))} disabled={page === total - 1} className="w-10 h-10 rounded-xl border border-border bg-white flex items-center justify-center hover:bg-secondary disabled:opacity-40 transition-colors">
            <ChevronLeft className="w-5 h-5" />
          </button>
        </div>

        <p className="text-center text-xs text-muted-foreground mt-6 max-w-xl mx-auto">
          تمام تصاویر با رضایت بیماران و رعایت کامل حریم خصوصی منتشر شده‌اند. نتایج ممکن است از فردی به فرد دیگر متفاوت باشد.
        </p>
      </div>
    </section>
  );
}

/* ─────────────── Process Steps ─────────────── */
function ProcessSection() {
  const icons = [MessageCircle, CheckCircle, Scissors, Heart];
  return (
    <section className="py-24 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <span className="text-primary font-bold text-sm tracking-wider">مسیر درمان</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">فرایند جراحی در کلینیک ما</h2>
          <p className="text-muted-foreground text-lg">از اولین تماس تا بهبودی کامل، در هر قدم کنارتان هستیم</p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {PROCESS_STEPS.map((s, i) => {
            const Icon = icons[i];
            return (
              <div key={i} className="group bg-gradient-to-br from-white to-secondary/30 border border-border rounded-3xl p-6 hover:shadow-xl hover:shadow-primary/10 hover:border-primary/30 transition-all">
                <div className="text-5xl font-black text-primary/10 mb-4">{s.num}</div>
                <div className="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-primary/30 group-hover:scale-110 transition-transform">
                  <Icon className="w-6 h-6 text-white" />
                </div>
                <h3 className="text-xl font-black text-foreground mb-3">{s.title}</h3>
                <p className="text-muted-foreground text-sm leading-loose">{s.desc}</p>
              </div>
            );
          })}
        </div>
        <div className="text-center mt-12">
          <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-10 py-4 rounded-2xl transition-all shadow-xl hover:shadow-primary/30 text-lg">
            <Calendar className="w-5 h-5" /> شروع مسیر درمان — رزرو مشاوره
          </a>
        </div>
      </div>
    </section>
  );
}

/* ─────────────── FAQ ─────────────── */
function FAQSection() {
  const [openIdx, setOpenIdx] = useState<number | null>(0);
  const [cat, setCat] = useState("همه");
  const cats = ["همه", ...Array.from(new Set(FAQS.map((f) => f.cat)))];
  const filtered = cat === "همه" ? FAQS : FAQS.filter((f) => f.cat === cat);

  return (
    <section id="faq" className="py-24 bg-secondary/30">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <span className="text-primary font-bold text-sm tracking-wider">پاسخ سؤالات شما</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">سؤالات متداول</h2>
          <p className="text-muted-foreground text-lg">پاسخ جامع به رایج‌ترین سؤالات درباره جراحی بینی</p>
        </div>

        <div className="flex flex-wrap justify-center gap-2 mb-10">
          {cats.map((c) => (
            <button key={c} onClick={() => { setCat(c); setOpenIdx(null); }} className={`px-4 py-2 rounded-xl text-sm font-bold transition-all ${cat === c ? "bg-primary text-white" : "bg-white border border-border text-foreground hover:border-primary/30 hover:text-primary"}`}>
              {c}
            </button>
          ))}
        </div>

        <div className="space-y-3">
          {filtered.map((faq, i) => (
            <div key={i} className="bg-white rounded-2xl border border-border overflow-hidden">
              <button onClick={() => setOpenIdx(openIdx === i ? null : i)} className="w-full flex items-center justify-between p-6 text-right gap-4">
                <div className="flex items-start gap-4">
                  <div className="w-6 h-6 bg-primary/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-primary text-xs font-black">{i + 1}</span>
                  </div>
                  <div>
                    <span className="text-xs font-bold text-primary/60 mb-1 block">{faq.cat}</span>
                    <span className="font-bold text-foreground text-base leading-snug">{faq.q}</span>
                  </div>
                </div>
                <ChevronDown className={`w-5 h-5 text-primary flex-shrink-0 transition-transform ${openIdx === i ? "rotate-180" : ""}`} />
              </button>
              {openIdx === i && (
                <div className="px-6 pb-6">
                  <div className="border-t border-border pt-5">
                    <p className="text-muted-foreground leading-loose">{faq.a}</p>
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>

        <p className="text-center text-xs text-muted-foreground mt-8 bg-white rounded-2xl border border-border p-4">
          اطلاعات ارائه‌شده صرفاً جنبه آموزشی دارند و جایگزین مشاوره پزشکی تخصصی نمی‌شوند.
        </p>
      </div>
    </section>
  );
}

/* ─────────────── Blog ─────────────── */
function BlogSection() {
  const featured = BLOG_POSTS.find((b) => b.featured)!;
  const rest = BLOG_POSTS.filter((b) => !b.featured);

  return (
    <section id="blog" className="py-24 bg-background">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col sm:flex-row sm:items-end justify-between mb-12 gap-4">
          <div>
            <span className="text-primary font-bold text-sm tracking-wider">دانش پزشکی</span>
            <h2 className="text-4xl font-black text-foreground mt-2">مقالات تخصصی</h2>
          </div>
          <a href="#" className="inline-flex items-center gap-2 text-primary font-bold hover:gap-3 transition-all">
            مشاهده همه مقالات <ArrowLeft className="w-4 h-4" />
          </a>
        </div>

        <div className="grid lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 group cursor-pointer">
            <div className="relative rounded-3xl overflow-hidden aspect-[16/9]">
              <img src={featured.img} alt={featured.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
              <div className="absolute bottom-0 inset-x-0 p-8 text-white">
                <span className="text-xs font-bold bg-primary rounded-full px-3 py-1.5 mb-3 inline-block">{featured.cat}</span>
                <h3 className="text-2xl font-black leading-snug mb-2">{featured.title}</h3>
                <p className="text-white/75 text-sm line-clamp-2">{featured.excerpt}</p>
                <p className="text-white/50 text-xs mt-3">{featured.date}</p>
              </div>
            </div>
          </div>

          <div className="flex flex-col gap-4">
            {rest.slice(0, 4).map((post) => (
              <div key={post.id} className="group flex gap-4 bg-white border border-border rounded-2xl p-4 hover:shadow-md hover:border-primary/20 transition-all cursor-pointer">
                <div className="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0">
                  <img src={post.img} alt={post.title} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" />
                </div>
                <div className="flex-1 min-w-0">
                  <span className="text-xs font-bold text-primary">{post.cat}</span>
                  <h4 className="text-sm font-bold text-foreground mt-1 leading-snug line-clamp-2">{post.title}</h4>
                  <p className="text-xs text-muted-foreground mt-1">{post.date}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

/* ─────────────── Instagram ─────────────── */
function InstagramSection() {
  const imgs = [
    "https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=400&q=80&auto=format",
    "https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=400&q=80&auto=format",
    "https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=400&q=80&auto=format",
    "https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=400&q=80&auto=format",
    "https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=400&q=80&auto=format",
    "https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=400&q=80&auto=format",
  ];
  return (
    <section className="py-20 bg-gradient-to-br from-purple-50 to-pink-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-12">
          <div className="inline-flex items-center gap-2 mb-4">
            <Instagram className="w-6 h-6 text-pink-500" />
            <a href={INSTAGRAM} target="_blank" rel="noopener noreferrer" className="text-lg font-bold text-pink-600 hover:text-pink-700 transition-colors">
              @dr.shahin.bastaninejad
            </a>
          </div>
          <h2 className="text-3xl font-black text-foreground">ما را در اینستاگرام دنبال کنید</h2>
          <p className="text-muted-foreground mt-2">آخرین مطالب، نمونه‌کارها و ویدیوهای آموزشی</p>
        </div>
        <div className="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-8">
          {imgs.map((src, i) => (
            <a key={i} href={INSTAGRAM} target="_blank" rel="noopener noreferrer" className="group aspect-square rounded-xl overflow-hidden block">
              <img src={src} alt="اینستاگرام" className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" />
            </a>
          ))}
        </div>
        <div className="text-center">
          <a href={INSTAGRAM} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 bg-gradient-to-l from-pink-500 to-purple-600 text-white font-bold px-8 py-4 rounded-2xl hover:shadow-xl transition-all">
            <Instagram className="w-5 h-5" /> مشاهده پروفایل اینستاگرام
          </a>
        </div>
      </div>
    </section>
  );
}

/* ─────────────── Booking CTA ─────────────── */
function BookingSection() {
  return (
    <section className="py-24 bg-primary relative overflow-hidden">
      <div className="absolute inset-0 opacity-10">
        <div className="absolute top-0 right-0 w-96 h-96 bg-white rounded-full -translate-y-1/2 translate-x-1/2" />
        <div className="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full translate-y-1/2 -translate-x-1/2" />
      </div>
      <div className="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-12">
          <h2 className="text-4xl font-black text-white mb-4">همین امروز نوبت رزرو کنید</h2>
          <p className="text-white/75 text-xl">رزرو آنلاین سریع، آسان و بدون نیاز به تماس تلفنی</p>
        </div>
        <div className="grid sm:grid-cols-3 gap-6 mb-12">
          {[
            { step: "۱", title: "تکمیل فرم رزرو", desc: "اطلاعات اولیه خود را در فرم رزرو آنلاین وارد کنید." },
            { step: "۲", title: "تأیید شماره تلفن", desc: "برای تأیید هویت، کد OTP ارسال‌شده را وارد کنید." },
            { step: "۳", title: "تماس تیم کلینیک", desc: "همکاران ما ظرف ۲۴ ساعت با شما تماس می‌گیرند." },
          ].map((s) => (
            <div key={s.step} className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-white">
              <div className="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-4">
                <span className="font-black text-lg">{s.step}</span>
              </div>
              <h3 className="font-bold text-lg mb-2">{s.title}</h3>
              <p className="text-white/70 text-sm">{s.desc}</p>
            </div>
          ))}
        </div>
        <div className="text-center">
          <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-3 bg-white text-primary font-black px-10 py-5 rounded-2xl text-xl hover:bg-white/90 transition-all shadow-2xl">
            <Calendar className="w-6 h-6" /> رزرو نوبت آنلاین <ExternalLink className="w-5 h-5" />
          </a>
        </div>
      </div>
    </section>
  );
}

/* ─────────────── Contact ─────────────── */
function ContactSection() {
  const [submitted, setSubmitted] = useState(false);
  const [form, setForm] = useState({ name: "", phone: "", message: "" });

  return (
    <section id="contact" className="py-24 bg-muted/50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <span className="text-primary font-bold text-sm tracking-wider">ارتباط با ما</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">تماس با کلینیک</h2>
          <p className="text-muted-foreground text-lg">آماده پاسخ‌گویی به سؤالات شما هستیم</p>
        </div>

        <div className="grid lg:grid-cols-5 gap-8">
          <div className="lg:col-span-2 space-y-5">
            <div className="bg-white rounded-3xl border border-border p-6">
              <h3 className="font-bold text-foreground mb-4 flex items-center gap-2">
                <Phone className="w-5 h-5 text-primary" /> تلفن تماس
              </h3>
              {PHONES.map((p) => (
                <a key={p} href={`tel:${p.replace(/–/g, "")}`} className="flex items-center gap-3 p-3 rounded-xl hover:bg-secondary transition-colors group">
                  <div className="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                    <Phone className="w-4 h-4 text-primary" />
                  </div>
                  <span className="font-bold text-foreground">{p}</span>
                </a>
              ))}
            </div>

            <div className="bg-white rounded-3xl border border-border p-6">
              <h3 className="font-bold text-foreground mb-3 flex items-center gap-2">
                <MapPin className="w-5 h-5 text-primary" /> آدرس
              </h3>
              <p className="text-muted-foreground text-sm leading-loose">
                تهران، خیابان نلسون ماندلا، نرسیده به چهارراه جهان کودک،<br />
                خیابان صانعی، ساختمان نور، پلاک ۱، واحد ۶
              </p>
              <div className="flex flex-wrap gap-2 mt-4">
                {[
                  { name: "Google Maps", href: "https://maps.google.com/?q=35.758881,51.413824" },
                  { name: "نشان", href: "https://nshn.ir/gNbNgYZt_Rxg" },
                  { name: "بلد", href: "https://balad.ir" },
                  { name: "Waze", href: "https://waze.com" },
                ].map((l) => (
                  <a key={l.name} href={l.href} target="_blank" rel="noopener noreferrer" className="text-xs bg-secondary hover:bg-primary/10 hover:text-primary text-foreground rounded-lg px-3 py-1.5 font-medium transition-colors">
                    {l.name}
                  </a>
                ))}
              </div>
            </div>

            <div className="bg-white rounded-3xl border border-border p-6">
              <h3 className="font-bold text-foreground mb-3 flex items-center gap-2">
                <Clock className="w-5 h-5 text-primary" /> ساعات پذیرش
              </h3>
              <div className="flex items-center justify-between bg-secondary rounded-xl px-4 py-3">
                <span className="text-sm font-medium text-foreground">شنبه و سه‌شنبه</span>
                <span className="text-sm font-bold text-primary">۱۵:۰۰ – ۱۹:۰۰</span>
              </div>
              <p className="text-xs text-muted-foreground mt-3">برای رزرو نوبت آنلاین ۲۴ ساعته در دسترس هستیم.</p>
            </div>
          </div>

          <div className="lg:col-span-3 space-y-5">
            <div className="bg-white rounded-3xl border border-border p-8">
              {submitted ? (
                <div className="flex flex-col items-center justify-center py-12 text-center gap-4">
                  <div className="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center">
                    <CheckCircle className="w-10 h-10 text-primary" />
                  </div>
                  <h3 className="text-2xl font-black text-foreground">پیام شما ارسال شد!</h3>
                  <p className="text-muted-foreground max-w-sm">همکاران ما به‌زودی با شما تماس خواهند گرفت. ممنون از اعتماد شما.</p>
                  <button onClick={() => { setSubmitted(false); setForm({ name: "", phone: "", message: "" }); }} className="text-primary font-bold text-sm underline">
                    ارسال پیام جدید
                  </button>
                </div>
              ) : (
                <>
                  <h3 className="text-xl font-black text-foreground mb-6">ارسال پیام</h3>
                  <form onSubmit={(e) => { e.preventDefault(); setSubmitted(true); }} className="space-y-5">
                    <div className="grid sm:grid-cols-2 gap-5">
                      <div>
                        <label className="block text-sm font-bold text-foreground mb-2">نام و نام خانوادگی <span className="text-red-500">*</span></label>
                        <input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="نام خود را وارد کنید" className="w-full bg-muted/50 border border-border rounded-xl px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                      </div>
                      <div>
                        <label className="block text-sm font-bold text-foreground mb-2">شماره تلفن <span className="text-red-500">*</span></label>
                        <input required value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} placeholder="۰۹۱۲ *** ****" className="w-full bg-muted/50 border border-border rounded-xl px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" dir="ltr" />
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-bold text-foreground mb-2">پیام شما <span className="text-red-500">*</span></label>
                      <textarea required value={form.message} onChange={(e) => setForm({ ...form, message: e.target.value })} placeholder="سؤال یا درخواست خود را بنویسید..." rows={5} className="w-full bg-muted/50 border border-border rounded-xl px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all resize-none" />
                    </div>
                    <button type="submit" className="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-2xl transition-all shadow-lg hover:shadow-primary/30 text-lg">
                      ارسال پیام
                    </button>
                  </form>
                </>
              )}
            </div>

            <div className="rounded-3xl overflow-hidden border border-border shadow-lg h-64">
              <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3237.671371427505!2d51.4138246!3d35.758881300000006!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzXCsDQ1JzMyLjAiTiA1McKwMjQnNTAuMiJF!5e0!3m2!1sfa!2sir!4v1698000000000!5m2!1sfa!2sir"
                width="100%"
                height="100%"
                style={{ border: 0 }}
                allowFullScreen
                loading="lazy"
                referrerPolicy="no-referrer-when-downgrade"
                title="محل کلینیک دکتر باستانی‌نژاد"
              />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

/* ─────────────── Footer ─────────────── */
function Footer() {
  return (
    <footer className="bg-gray-950 text-white pt-20 pb-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-16">
          <div>
            <div className="flex items-center gap-3 mb-5">
              <div className="w-10 h-10 bg-primary rounded-xl flex items-center justify-center">
                <Stethoscope className="w-5 h-5 text-white" />
              </div>
              <div>
                <p className="font-black text-base">{DOCTOR_NAME}</p>
                <p className="text-white/50 text-xs">متخصص گوش، حلق و بینی</p>
              </div>
            </div>
            <p className="text-white/60 text-sm leading-loose mb-6">
              دانشیار دانشگاه علوم پزشکی تهران و جراح ارشد بیمارستان امیراعلم — با بیش از ۱۵ سال تجربه تخصصی
            </p>
            <div className="flex gap-3">
              {[
                { href: INSTAGRAM, Icon: Instagram, label: "اینستاگرام" },
                { href: YOUTUBE, Icon: Youtube, label: "یوتیوب" },
                { href: TELEGRAM, Icon: Send, label: "تلگرام" },
                { href: APARAT, Icon: Play, label: "آپارات" },
              ].map(({ href, Icon, label }) => (
                <a key={href} href={href} target="_blank" rel="noopener noreferrer" aria-label={label} className="w-9 h-9 bg-white/10 hover:bg-primary rounded-xl flex items-center justify-center transition-colors">
                  <Icon className="w-4 h-4" />
                </a>
              ))}
            </div>
          </div>

          <div>
            <h4 className="font-black text-base mb-5">خدمات جراحی</h4>
            <ul className="space-y-2.5">
              {[...COSMETIC_SERVICES.slice(0, 4), ...FUNCTIONAL_SERVICES].map((s) => (
                <li key={s.id}>
                  <a href="#services" className="text-white/60 hover:text-white text-sm transition-colors">{s.title}</a>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h4 className="font-black text-base mb-5">لینک‌های مفید</h4>
            <ul className="space-y-2.5">
              {[
                { href: "#about", label: "درباره دکتر" },
                { href: "#gallery", label: "گالری قبل و بعد" },
                { href: "#faq", label: "سؤالات متداول" },
                { href: "#blog", label: "مقالات تخصصی" },
                { href: "#contact", label: "تماس با ما" },
                { href: BOOKING_URL, label: "رزرو نوبت آنلاین", ext: true },
              ].map((l) => (
                <li key={l.href}>
                  <a href={l.href} target={l.ext ? "_blank" : undefined} rel={l.ext ? "noopener noreferrer" : undefined} className="text-white/60 hover:text-white text-sm transition-colors flex items-center gap-1">
                    {l.label} {l.ext && <ExternalLink className="w-3 h-3" />}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h4 className="font-black text-base mb-5">اطلاعات تماس</h4>
            <div className="space-y-4">
              <div className="flex gap-3">
                <MapPin className="w-4 h-4 text-primary flex-shrink-0 mt-1" />
                <p className="text-white/60 text-sm leading-relaxed">تهران، خ. نلسون ماندلا، خ. صانعی، ساختمان نور، پلاک ۱ واحد ۶</p>
              </div>
              <div className="flex gap-3">
                <Phone className="w-4 h-4 text-primary flex-shrink-0" />
                <div className="space-y-1">
                  {PHONES.map((p) => (
                    <a key={p} href={`tel:${p.replace(/–/g, "")}`} className="block text-white/60 hover:text-white text-sm transition-colors">{p}</a>
                  ))}
                </div>
              </div>
              <div className="flex gap-3">
                <Clock className="w-4 h-4 text-primary flex-shrink-0" />
                <div>
                  <p className="text-white/60 text-sm">شنبه و سه‌شنبه</p>
                  <p className="text-white/80 text-sm font-bold">۱۵:۰۰ – ۱۹:۰۰</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="border-y border-white/10 py-8 grid grid-cols-2 sm:grid-cols-4 gap-6 mb-8">
          {[
            { val: "+۱۵", label: "سال تجربه" },
            { val: "+۲۰۰۰", label: "عمل موفق" },
            { val: "+۱۷۸", label: "نمونه گالری" },
            { val: "۹۸٪", label: "رضایت بیمار" },
          ].map((s) => (
            <div key={s.label} className="text-center">
              <p className="text-3xl font-black text-primary">{s.val}</p>
              <p className="text-white/50 text-sm">{s.label}</p>
            </div>
          ))}
        </div>

        <div className="flex flex-col sm:flex-row justify-between items-center gap-4 text-white/40 text-xs">
          <p>© ۱۴۰۳ کلینیک {DOCTOR_NAME} — تمام حقوق محفوظ است</p>
          <div className="flex items-center gap-4">
            <a href="https://enamad.ir" target="_blank" rel="noopener noreferrer" className="flex items-center gap-1 hover:text-white/70 transition-colors">
              <Shield className="w-4 h-4 text-primary" /> اینماد
            </a>
            <a href="https://iranent.ir" target="_blank" rel="noopener noreferrer" className="flex items-center gap-1 hover:text-white/70 transition-colors">
              <CheckCircle className="w-4 h-4 text-primary" /> ایران انت
            </a>
          </div>
        </div>
      </div>
    </footer>
  );
}

/* ─────────────── Floating Chat Widget ─────────────── */
function ChatWidget() {
  const [open, setOpen] = useState(false);

  const channels = [
    { label: "واتس‌اپ", href: "https://wa.me/989124966590", bg: "bg-green-500", Icon: MessageCircle },
    { label: "تلگرام", href: TELEGRAM, bg: "bg-sky-500", Icon: Send },
    { label: "تلفن ۱", href: "tel:02186087250", bg: "bg-primary", Icon: Phone },
    { label: "تلفن ۲", href: "tel:02188205606", bg: "bg-emerald-700", Icon: Phone },
    { label: "رزرو نوبت", href: BOOKING_URL, bg: "bg-accent", Icon: Calendar, ext: true },
  ];

  return (
    <div className="fixed bottom-6 left-6 z-50 flex flex-col items-end gap-3">
      {open && (
        <div className="flex flex-col gap-2 mb-1">
          {channels.map((c) => (
            <a key={c.label} href={c.href} target={c.ext ? "_blank" : undefined} rel={c.ext ? "noopener noreferrer" : undefined} className={`flex items-center gap-3 ${c.bg} text-white rounded-2xl px-4 py-3 shadow-xl hover:scale-105 transition-transform text-sm font-bold`}>
              <c.Icon className="w-5 h-5" /> {c.label}
            </a>
          ))}
        </div>
      )}
      <button onClick={() => setOpen(!open)} className="w-14 h-14 bg-primary hover:bg-primary/90 text-white rounded-full shadow-2xl shadow-primary/40 flex items-center justify-center transition-all hover:scale-110">
        {open ? <X className="w-6 h-6" /> : <MessageCircle className="w-6 h-6" />}
      </button>
    </div>
  );
}

/* ─────────────── App Root ─────────────── */
export default function App() {
  return (
    <div dir="rtl" lang="fa-IR" style={{ fontFamily: "'Vazirmatn', sans-serif" }} className="bg-background text-foreground">
      <NavBar />
      <HeroSection />
      <TrustStrip />
      <ServicesSection />
      <StatsBand />
      <AboutSection />
      <CertificatesSection />
      <GallerySection />
      <ProcessSection />
      <FAQSection />
      <BlogSection />
      <InstagramSection />
      <BookingSection />
      <ContactSection />
      <Footer />
      <ChatWidget />
    </div>
  );
}
