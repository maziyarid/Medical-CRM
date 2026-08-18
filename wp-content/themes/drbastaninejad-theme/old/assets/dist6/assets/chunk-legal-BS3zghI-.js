import { r as _, j as e } from "./vendor-react-CfDz6BDZ.js";
import {
  S as w,
  A as S,
  L as v,
  a as k,
  b as D,
  c as A,
  M,
  Z as F,
  W as B,
  d as I,
  e as O,
  f as L,
  U as T,
  B as P,
  g as u,
  C as R,
  h as U,
  H as E,
  P as f,
  i as H,
  F as $,
  j as C,
  k as q,
} from "./vendor-lucide-BPdX3gUv.js";
import { L as G } from "./vendor-router-YUMSJXsu.js";
const se = "دکتر شاهین باستانی‌نژاد",
  z = "https://app.drbastaninejad.com/",
  te = ["۰۲۱۸۶۰۸۷۲۵۰", "۰۲۱۸۸۲۰۵۶۰۶", "۰۹۹۱۲۴۹۶۶۵۹"],
  ae = "https://www.instagram.com/dr.shahin.bastaninejad/",
  le = "https://www.youtube.com/@Drshahinbastaninejad",
  ne = "https://t.me/dr_bastaninejad",
  re = "https://www.aparat.com/Drshahinbastaninejad",
  ie =
    "تهران، خیابان نلسون ماندلا، نرسیده به چهارراه جهان کودک، خیابان صانعی، ساختمان نور، پلاک ۱، واحد ۶",
  ce = "تهران، خ. نلسون ماندلا، خ. صانعی، ساختمان نور، پلاک ۱ واحد ۶",
  oe = "https://trustseal.enamad.ir/?id=533172&Code=N0dO6V3ic509Xoj2zIAFDG27eDa4Inlv",
  de = "https://iranent.com/doctors/925/profile/",
  xe = window.__DRB_WP__?.stats?.length
    ? window.__DRB_WP__.stats.map((s) => ({ val: s.value, label: s.label, sub: s.sub }))
    : [
        { val: "+۱۸", label: "سال تجربه", sub: "جراحی تخصصی بینی" },
        { val: "+۲۰۰۰", label: "عمل موفق", sub: "در بیماران مختلف" },
        { val: "۱۸–۴۵", label: "بازه سنی پذیرش", sub: "سال تمام" },
        { val: "۹۸٪", label: "رضایت بیمار", sub: "بر اساس نظرسنجی" },
      ],
  me = [
    {
      icon: O,
      title: "دانشیار دانشگاه علوم پزشکی تهران",
      subtitle: "عضو هیئت علمی بیمارستان امیراعلم",
    },
    { icon: L, title: "رتبه ۴ بورد تخصصی کشوری", subtitle: "گوش، حلق و بینی — جراحی سر و گردن" },
    { icon: T, title: "دبیر کمیته علمی رینولوژی", subtitle: "انجمن جراحی پلاستیک بینی ایران" },
    { icon: P, title: "مجری ۱۰+ دوره سمینار جراحی", subtitle: "رینوپلاستی · آندوسکوپی سینوس" },
    { icon: u, title: "مجوز رسمی وزارت بهداشت", subtitle: "پروانه فعالیت از وزارت بهداشت ایران" },
  ],
  he = [
    {
      id: 1,
      slug: "rhinoplasty-primary",
      title: "جراحی بینی اولیه",
      subtitle: "راینوپلاستی اولیه",
      icon: w,
      desc: "جراحی بینی اولیه یا راینوپلاستی، هنر تراشیدن بینی متناسب با چهره است. دکتر باستانی‌نژاد با بهره‌گیری از تکنیک‌های روز دنیا، نتیجه‌ای طبیعی و هماهنگ با سیمای شما ایجاد می‌کند.",
    },
    {
      id: 2,
      slug: "rhinoplasty-revision",
      title: "جراحی بینی ترمیمی",
      subtitle: "رینوپلاستی ثانویه",
      icon: S,
      desc: "جراحی ترمیمی برای اصلاح نتایج ناخواسته جراحی‌های قبلی طراحی شده است. این عمل به دقت و تجربه بسیار بالایی نیاز دارد که دکتر باستانی‌نژاد با سال‌ها سابقه در این حوزه آماده انجام آن است.",
    },
    {
      id: 4,
      slug: "rhinoplasty-bony",
      title: "جراحی بینی استخوانی",
      subtitle: "بینی سخت و استخوانی",
      icon: k,
      desc: "بینی‌های استخوانی ساختار محکم‌تری دارند و تکنیک‌های خاصی برای ایجاد فرم دلخواه نیاز دارند. نتیجه نهایی ظاهری طبیعی و متناسب با ساختار چهره خواهد بود.",
    },
    {
      id: 5,
      slug: "rhinoplasty-natural",
      title: "بینی طبیعی",
      subtitle: "نتیجه کاملاً طبیعی",
      icon: D,
      desc: "سبک جراحی بینی طبیعی، تغییراتی ظریف ایجاد می‌کند که حس می‌شود شما همیشه این بینی را داشته‌اید. هماهنگی کامل با چهره، اولویت اصلی این رویکرد است.",
    },
    {
      id: 7,
      slug: "hump-removal",
      title: "رفع قوز بینی",
      subtitle: "اصلاح برآمدگی پشت بینی",
      icon: M,
      desc: "قوز بینی یکی از شایع‌ترین دلایل مراجعه به جراح است. دکتر باستانی‌نژاد با تکنیک‌های دقیق، قوز را حذف و پروفایل صاف و زیبایی ایجاد می‌کند.",
    },
  ],
  pe = [
    {
      id: 8,
      slug: "septoplasty",
      title: "سپتوپلاستی",
      subtitle: "اصلاح انحراف تیغه بینی",
      icon: F,
      desc: "انحراف تیغه بینی (سپتوم) می‌تواند باعث مشکلات تنفسی جدی شود. سپتوپلاستی این انحراف را اصلاح کرده و تنفس را به‌طور قابل توجهی بهبود می‌بخشد.",
    },
    {
      id: 9,
      slug: "turbinoplasty",
      title: "توربینوپلاستی",
      subtitle: "کاهش حجم شاخک‌های بینی",
      icon: B,
      desc: "بزرگ شدن شاخک‌های بینی (توربینیت‌ها) یکی از علل شایع گرفتگی مزمن بینی است. توربینوپلاستی این بافت‌ها را کوچک کرده و جریان هوا را بهینه می‌کند.",
    },
    {
      id: 10,
      slug: "sinus-endoscopy",
      title: "آندوسکوپی سینوس (FESS)",
      subtitle: "درمان سینوزیت مزمن",
      icon: I,
      desc: "جراحی آندوسکوپیک سینوس (FESS) برای درمان سینوزیت مزمن، پولیپ‌های بینی و دیگر بیماری‌های سینوس انجام می‌شود. این روش با حداقل تهاجم، نتایج ماندگار ایجاد می‌کند.",
    },
  ],
  be = [
    {
      group: "بر اساس بافت",
      items: [
        {
          label: "جراحی بینی اولیه",
          sub: "راینوپلاستی برای اولین بار",
          href: "/services/rhinoplasty-primary",
          icon: w,
        },
        {
          label: "بینی استخوانی",
          sub: "اصلاح قوز و ساختار سخت",
          href: "/services/rhinoplasty-bony",
          icon: k,
        },
      ],
    },
    {
      group: "بر اساس سبک",
      items: [
        {
          label: "بینی طبیعی",
          sub: "نتیجه هماهنگ با چهره",
          href: "/services/rhinoplasty-natural",
          icon: D,
        },
        {
          label: "جراحی ترمیمی",
          sub: "اصلاح نتایج عمل قبلی",
          href: "/services/rhinoplasty-revision",
          icon: S,
        },
      ],
    },
    {
      group: "درمانی",
      items: [
        {
          label: "انحراف بینی (سپتوپلاستی)",
          sub: "بهبود تنفس و اصلاح انحراف",
          href: "/services/septoplasty",
          icon: F,
        },
        {
          label: "شاخک‌های بینی",
          sub: "توربینوپلاستی برای تنفس بهتر",
          href: "/services/turbinoplasty",
          icon: B,
        },
        {
          label: "آندوسکوپی سینوس",
          sub: "درمان سینوزیت مزمن",
          href: "/services/sinus-endoscopy",
          icon: I,
        },
      ],
    },
  ],
  fe = [
    { label: "خانه", href: "/" },
    { label: "درباره دکتر", href: "/about" },
    { label: "همه خدمات", href: "/services" },
    { label: "گالری", href: "/gallery" },
    { label: "مقالات", href: "/blog" },
    { label: "سوالات متداول", href: "/faq" },
    { label: "تماس و آدرس", href: "/contact" },
    { label: "رزرو نوبت", href: "/booking" },
  ],
  ue = [
    { label: "جراحی بینی اولیه", href: "/services/rhinoplasty-primary" },
    { label: "جراحی ترمیمی", href: "/services/rhinoplasty-revision" },
    { label: "بینی استخوانی", href: "/services/rhinoplasty-bony" },
    { label: "بینی طبیعی", href: "/services/rhinoplasty-natural" },
    { label: "رفع قوز بینی", href: "/services/hump-removal" },
    { label: "انحراف بینی", href: "/services/septoplasty" },
  ],
  je = [
    { year: "۱۳۷۵–۱۳۸۲", title: "دکترای پزشکی عمومی", org: "دانشگاه علوم پزشکی اصفهان", icon: P },
    {
      year: "۱۳۸۴–۱۳۸۸",
      title: "تخصص گوش، حلق و بینی",
      org: "دانشگاه علوم پزشکی تهران — رتبه ۴ بورد کشوری",
      icon: O,
    },
    { year: "۱۳۸۸–۱۳۸۹", title: "فلوشیپ رینوپلاستی", org: "بیمارستان امیراعلم تهران", icon: L },
    { year: "از ۱۳۸۸", title: "عضو هیئت علمی", org: "دانشگاه علوم پزشکی تهران", icon: T },
    { year: "از ۱۳۹۶", title: "معاون آموزشی", org: "بیمارستان امیراعلم تهران", icon: u },
    {
      year: "اکنون",
      title: "دبیر کمیته علمی رینولوژی",
      org: "انجمن جراحی پلاستیک بینی ایران",
      icon: R,
    },
  ],
  ge = [
    "هفتمین کنگره بین‌المللی رینوپلاستی و جراحی پلاستیک صورت",
    "دوره مستر دیسکشن رینولوژی و جراحی پلاستیک بازسازی",
    "هشتمین کنگره بین‌المللی انجمن رینولوژی ایران",
    "سومین دوره بین‌المللی رینولوژی شیراز (SRIC)",
    "گواهی بازسازی دریچه بینی — دانشگاه علوم پزشکی تهران",
    "گواهی‌نامه بین‌المللی — جمهوری تاجیکستان",
    "چهارمین کنفرانس بین‌المللی گوش، حلق و بینی کردستان عراق",
    "پنجمین کنفرانس و نمایشگاه بین‌المللی — کردستان عراق",
    "مجوز مراکز درمانی و زیبایی از وزارت بهداشت",
  ],
  Ne = [
    {
      num: "۰۱",
      title: "مشاوره تخصصی",
      desc: "در جلسه مشاوره، وضعیت بینی شما بررسی می‌شود، اهداف و انتظاراتتان شنیده می‌شود و بهترین روش جراحی برای شما تعیین می‌گردد.",
    },
    {
      num: "۰۲",
      title: "آمادگی قبل از عمل",
      desc: "آزمایش‌ها و معاینات لازم انجام می‌شود. دستورالعمل‌های قبل از عمل به شما داده می‌شود تا آمادگی کامل برای جراحی داشته باشید.",
    },
    {
      num: "۰۳",
      title: "روز جراحی",
      desc: "عمل جراحی در محیطی کاملاً ایمن و استاندارد توسط دکتر باستانی‌نژاد و تیم متخصص ایشان انجام می‌شود.",
    },
    {
      num: "۰۴",
      title: "دوره بهبودی",
      desc: "پس از جراحی، تیم پزشکی ما در تمام مراحل بهبودی همراه شما است. ویزیت‌های پیگیری منظم برای اطمینان از نتیجه مطلوب برنامه‌ریزی می‌شود.",
    },
  ],
  ye = [
    {
      cat: "کلیات جراحی",
      q: "جراحی بینی چیست و چه هدفی دارد؟",
      a: "جراحی بینی (رینوپلاستی) عملی جراحی است که برای اصلاح شکل، اندازه یا عملکرد بینی انجام می‌شود. هدف اصلی این جراحی دستیابی به تناسب بهتر بین بینی و سایر اجزای چهره، با حفظ و بهبود عملکرد تنفسی است.",
    },
    {
      cat: "کاندیداتوری",
      q: "چه کسانی می‌توانند جراحی بینی انجام دهند؟",
      a: "پذیرش فقط برای افراد ۱۸ تا ۴۵ سال انجام می‌شود و ثبت درخواست به معنی پذیرش قطعی نیست.",
    },
    {
      cat: "کاندیداتوری",
      q: "چه کسانی نمی‌توانند جراحی بینی انجام دهند؟",
      a: "افراد خارج از بازه ۱۸ تا ۴۵ سال، مبتلایان به دیابت یا فشار خون، بیماری زمینه‌ای یا خودایمنی کنترل‌نشده و افرادی که سنترال لب انجام داده‌اند پذیرش نمی‌شوند.",
    },
    { cat: "پذیرش", q: "سبک جراحی دکتر چیست؟", a: "رویکرد دکتر طبیعی و متناسب با اجزای صورت است؛ سبک فانتزی یا عروسکی انجام نمی‌شود و جراحی بینی گوشتی نیز در این مطب انجام نمی‌شود." },
    {
      cat: "دوره بهبودی",
      q: "دوره بهبودی پس از جراحی بینی چقدر طول می‌کشد؟",
      a: "آتل بینی معمولاً ۷ تا ۱۰ روز پس از عمل برداشته می‌شود. کبودی و تورم در اکثر بیماران ظرف ۲ تا ۳ هفته تا حد زیادی کاهش می‌یابد. بازگشت به فعالیت‌های سبک معمولاً ۱ تا ۲ هفته پس از عمل ممکن است.",
    },
    {
      cat: "دوره بهبودی",
      q: "چه مدت بعد از جراحی می‌توانم به سر کار برگردم؟",
      a: "بستگی به نوع کار دارد. برای کارهای اداری و نشسته معمولاً ۱۰–۱۴ روز کافی است. برای کارهای سنگین و فعالیت بدنی شدید، حداقل ۴–۶ هفته باید صبر کرد.",
    },
    {
      cat: "هزینه",
      q: "هزینه جراحی بینی چقدر است؟",
      a: "هزینه جراحی بینی به عوامل متعددی مثل پیچیدگی بینی، تکنیک جراحی، نوع بیهوشی و امکانات بیمارستان بستگی دارد. برای دریافت تخمین دقیق هزینه، پس از مشاوره اطلاع‌رسانی خواهد شد.",
    },
    {
      cat: "قبل از عمل",
      q: "چه آزمایشاتی قبل از جراحی بینی لازم است؟",
      a: "آزمایش خون کامل، تست انعقاد خون، الکتروکاردیوگرام (ECG)، عکس رادیولوژی قفسه سینه و در صورت نیاز آزمایش‌های تخصصی‌تر. پزشک متخصص بیهوشی نیز ویزیت قبل از عمل را انجام خواهد داد.",
    },
    {
      cat: "بعد از عمل",
      q: "چه مراقبت‌هایی بعد از جراحی باید داشته باشم؟",
      a: "فقط چک‌لیست تأییدشده و دستور اختصاصی جراح را رعایت کنید؛ از توصیه‌های عمومی یا محتوای قدیمی اینترنتی استفاده نشود.",
    },
    {
      cat: "جراحی ترمیمی",
      q: "چرا باید ۲۴ ماه پس از جراحی اول برای ترمیمی صبر کرد؟",
      a: "بررسی جراحی ترمیمی فقط پس از ۲۴ ماه کامل انجام می‌شود؛ برای کاهش خطر نکروز، عفونت و آسیب جدی بافت و پوست و چون فرم بینی، غضروف‌ها و اسکلت تا ۲۴ ماه همچنان تغییر می‌کنند.",
    },
    { cat: "بعد از عمل", q: "چه زمانی بعد از عمل امکان پرواز وجود دارد؟", a: "۱۰ روز پس از جراحی امکان پرواز وجود دارد. اگر تیم درمان برای پرونده شما دستور متفاوتی داده است، همان دستور ملاک خواهد بود." },
    { cat: "پذیرش", q: "ساعات کاری و آخرین زمان پذیرش چیست؟", a: "شنبه تا سه‌شنبه، از ساعت ۱۵:۰۰ تا ۱۸:۰۰. مراجعینی که بعد از ساعت ۱۸:۰۰ در مطب حضور یابند، به هیچ‌وجه پذیرش نخواهند شد." },
  ],
  l = [
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Blog/Blog-101.webp",
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Blog/Blog-102.webp",
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Blog/Blog-103.webp",
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Blog/Blog-104.webp",
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Blog/Blog-105.webp",
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Blog/Blog-106.webp",
  ],
  Ee = [
    {
      id: 1,
      slug: "rhinoplasty",
      cat: "جراحی بینی",
      title: "جراحی بینی چیست و چه کسانی کاندید هستند؟",
      excerpt:
        "راینوپلاستی یا جراحی بینی یکی از رایج‌ترین اقدامات زیبایی در ایران است. در این مطلب، کاندیداهای مناسب و ملاحظات مهم را بررسی می‌کنیم.",
      img: l[0],
      date: "۱۴۰۳/۰۸/۱۵",
      featured: !0,
    },
    {
      id: 2,
      slug: "rhinoplasty-revision",
      cat: "جراحی بینی",
      title: "جراحی بینی ترمیمی چیست؟",
      excerpt:
        "جراحی ترمیمی بینی یکی از چالش‌برانگیزترین اقدامات جراحی پلاستیک است که به تخصص و تجربه ویژه‌ای نیاز دارد.",
      img: l[1],
      date: "۱۴۰۳/۰۷/۲۰",
    },
    {
      id: 3,
      slug: "pre-op-steps",
      cat: "راهنمای بیمار",
      title: "اقدامات ضروری قبل از جراحی بینی",
      excerpt:
        "آمادگی صحیح قبل از جراحی بینی نقش مهمی در موفقیت عمل و سرعت بهبودی شما دارد. این موارد را جدی بگیرید.",
      img: l[2],
      date: "۱۴۰۳/۰۷/۰۵",
    },
    {
      id: 4,
      slug: "post-op-care",
      cat: "مراقبت‌ها",
      title: "مراقبت‌های بعد از جراحی بینی",
      excerpt:
        "دوره بهبودی پس از جراحی بینی نیازمند رعایت دقیق نکاتی است که بر کیفیت نتیجه نهایی تأثیر مستقیم دارند.",
      img: l[3],
      date: "۱۴۰۳/۰۶/۱۸",
    },
    {
      id: 6,
      slug: "choose-surgeon",
      cat: "راهنمای بیمار",
      title: "۱۰ نکته مهم در انتخاب جراح بینی",
      excerpt:
        "انتخاب جراح مناسب، مهم‌ترین گام در مسیر جراحی بینی موفق است. این معیارها را در انتخاب خود در نظر بگیرید.",
      img: l[5],
      date: "۱۴۰۳/۰۵/۱۲",
    },
    {
      id: 7,
      slug: "revision-rhinoplasty",
      cat: "جراحی بینی",
      title: "رینوپلاستی ترمیمی: راهنمای جامع",
      excerpt: "همه چیز درباره جراحی ترمیمی بینی، دلایل نیاز به آن و انتظارات واقع‌بینانه.",
      img: l[0],
      date: "۱۴۰۳/۰۵/۰۱",
    },
    {
      id: 8,
      slug: "rhinoplasty-complications",
      cat: "آموزشی",
      title: "عوارض احتمالی جراحی بینی و پیشگیری از آن‌ها",
      excerpt:
        "آشنایی با عوارض احتمالی جراحی بینی و راه‌های پیشگیری — اطلاعاتی که هر کاندید باید بداند.",
      img: l[1],
      date: "۱۴۰۳/۰۴/۲۰",
    },
    {
      id: 9,
      slug: "male-rhinoplasty",
      cat: "جراحی بینی",
      title: "جراحی بینی در مردان — تفاوت‌ها و نکات",
      excerpt:
        "جراحی بینی در مردان اصول و ملاحظات خاص خود را دارد. در این مقاله تفاوت‌های کلیدی را بررسی می‌کنیم.",
      img: l[2],
      date: "۱۴۰۳/۰۴/۰۵",
    },
    {
      id: 10,
      slug: "nutrition-rhinoplasty",
      cat: "مراقبت‌ها",
      title: "رژیم ضد التهابی",
      excerpt: "راهنمای اختصاصی کلینیک بر اساس PDF تأییدشده رژیم ضدالتهابی.",
      img: l[3],
      date: "۱۴۰۳/۰۳/۱۸",
    },
    {
      id: 11,
      slug: "atl-removal",
      cat: "آموزشی",
      title: "آتل بینی: همه چیز درباره مراقبت و برداشتن",
      excerpt:
        "آتل بینی بخش مهمی از دوره بهبودی است. در این مطلب نحوه مراقبت و زمان برداشتن آن را توضیح می‌دهیم.",
      img: l[4],
      date: "۱۴۰۳/۰۳/۰۱",
    },
  ],
  W = [
    [1, 2],
    [3, 4],
    [5, 6],
    [7, 8],
    [14, 15],
    [16, 17],
    [18, 19],
    [20, 21],
    [27, 28],
    [39, 40],
    [44, 45],
    [46, 47],
    [56, 57],
    [60, 61],
    [65, 66],
    [72, 74],
    [78, 79],
    [81, 82],
    [84, 85],
    [86, 87],
    [90, 91],
    [96, 98],
    [100, 101],
    [103, 104],
  ],
  // Never expose bundled patient examples. Only consent-approved WordPress cases are rendered.
  Ce = window.__DRB_WP__?.gallery || [],
  we = {
    name: "دکتر شاهین باستانی‌نژاد",
    title: "فوق‌تخصص جراحی پلاستیک بینی",
    specialty: "گوش، گلو و بینی · جراحی پلاستیک",
    hospital: "",
    bio: "دکتر شاهین باستانی‌نژاد با بیش از ۱۸ سال تجربه تخصصی در جراحی پلاستیک بینی، از پیشگامان استفاده از روش‌های نوین رینوپلاستی در ایران است. هدف ایشان دستیابی به نتایجی طبیعی، پایدار و کاملاً هماهنگ با ساختار چهره هر بیمار است.",
    stats: [
      { label: "سال تجربه", value: "+۱۸" },
      { label: "عمل موفق", value: "+۲۰۰۰" },
      { label: "رضایت بیمار", value: "۴.۹" },
    ],
    certifications: [
      "بورد تخصصی گوش، گلو و بینی ایران",
      "عضو ISAPS (انجمن بین‌المللی جراحی پلاستیک)",
      "فلوشیپ تخصصی جراحی پلاستیک صورت",
      "عضو انجمن جراحان گوش، گلو و بینی ایران",
    ],
    avatarImg:
      "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/Dr Shahin Bastani Nejad (3).webp",
    avatarInitials: "دب",
  };
function j({ data: s, id: t = "jsonld" }) {
  const a = JSON.stringify(Array.isArray(s) ? s : [s]);
  return (
    _.useEffect(() => {
      const c = document.getElementById(t);
      c && c.remove();
      const d = document.createElement("script");
      return (
        (d.id = t),
        (d.type = "application/ld+json"),
        (d.textContent = a),
        document.head.appendChild(d),
        () => {
          const y = document.getElementById(t);
          y && y.remove();
        }
      );
    }, [a, t]),
    null
  );
}
const Se = {
    "@context": "https://schema.org",
    "@type": "MedicalBusiness",
    "@id": "https://drbastaninejad.com/#clinic",
    name: "کلینیک دکتر شاهین باستانی‌نژاد",
    alternateName: "Dr. Shahin Bastaninejad Clinic",
    url: "https://drbastaninejad.com",
    telephone: ["+982186087250", "+982188205606"],
    priceRange: "$$",
    medicalSpecialty: "Plastic Surgery",
    image: "https://drbastaninejad.com/images/Doctor/DrShahinBastaninejadPortrait1.png",
    description:
      "کلینیک تخصصی جراحی پلاستیک بینی دکتر شاهین باستانی‌نژاد — رینوپلاستی، سپتوپلاستی، آندوسکوپی سینوس",
    address: {
      "@type": "PostalAddress",
      streetAddress:
        "خیابان نلسون ماندلا، نرسیده به چهارراه جهان کودک، خیابان صانعی، ساختمان نور، پلاک ۱، واحد ۶",
      addressLocality: "تهران",
      addressCountry: "IR",
      postalCode: "1514773119",
    },
    geo: { "@type": "GeoCoordinates", latitude: 35.758881, longitude: 51.413824 },
    openingHoursSpecification: ["Saturday", "Sunday", "Monday", "Tuesday"].map((dayOfWeek) => ({
      "@type": "OpeningHoursSpecification",
      dayOfWeek,
      opens: "15:00",
      closes: "18:00",
    })),
    sameAs: [
      "https://www.instagram.com/dr.shahin.bastaninejad/",
      "https://www.youtube.com/@Drshahinbastaninejad",
      "https://t.me/dr_bastaninejad",
    ],
  },
  ve = {
    "@context": "https://schema.org",
    "@type": "Person",
    "@id": "https://drbastaninejad.com/#doctor",
    name: "دکتر شاهین باستانی‌نژاد",
    alternateName: "Dr. Shahin Bastaninejad",
    jobTitle: "فوق‌تخصص جراحی پلاستیک بینی",
    medicalSpecialty: "Otolaryngology, Plastic Surgery",
    worksFor: { "@id": "https://drbastaninejad.com/#clinic" },
    url: "https://drbastaninejad.com/about",
    alumniOf: [
      { "@type": "CollegeOrUniversity", name: "دانشگاه علوم پزشکی اصفهان" },
      { "@type": "CollegeOrUniversity", name: "دانشگاه علوم پزشکی تهران" },
    ],
    memberOf: [
      { "@type": "MedicalOrganization", name: "انجمن جراحی پلاستیک بینی ایران" },
      {
        "@type": "MedicalOrganization",
        name: "ISAPS — International Society of Aesthetic Plastic Surgery",
      },
    ],
  };
function ke(s) {
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: s.map((t) => ({
      "@type": "Question",
      name: t.question,
      acceptedAnswer: { "@type": "Answer", text: t.answer },
    })),
  };
}
function g(s) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: s.map((t, a) => ({
      "@type": "ListItem",
      position: a + 1,
      name: t.label,
      ...(t.href ? { item: `https://drbastaninejad.com${t.href}` } : {}),
    })),
  };
}
function N({ items: s }) {
  return e.jsx("nav", {
    "aria-label": "مسیر صفحه",
    className: "bg-[#F7F8F6] border-b border-[#DDE2DD]",
    children: e.jsx("div", {
      className: "max-w-[1200px] mx-auto px-4 sm:px-6",
      children: e.jsx("ol", {
        className:
          "flex flex-row flex-nowrap items-center gap-1.5 h-11 text-sm overflow-x-auto whitespace-nowrap [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden",
        children: s.map((t, a) => {
          const c = a === s.length - 1;
          return e.jsxs(
            "li",
            {
              className: "inline-flex items-center gap-1.5 flex-shrink-0 leading-none",
              children: [
                a > 0 &&
                  e.jsx(U, {
                    className: "w-3.5 h-3.5 text-[#A0A8A0] flex-shrink-0",
                    "aria-hidden": !0,
                  }),
                t.href && !c
                  ? e.jsxs(G, {
                      to: t.href,
                      className:
                        "inline-flex items-center gap-1 text-[#545B64] hover:text-[#28722C] transition-colors leading-none",
                      children: [
                        a === 0 && e.jsx(E, { className: "w-3.5 h-3.5", "aria-hidden": !0 }),
                        e.jsx("span", { children: t.label }),
                      ],
                    })
                  : e.jsxs("span", {
                      className:
                        "inline-flex items-center gap-1 text-[#25272C] font-semibold leading-none",
                      "aria-current": "page",
                      children: [
                        a === 0 &&
                          !t.href &&
                          e.jsx(E, { className: "w-3.5 h-3.5", "aria-hidden": !0 }),
                        t.label,
                      ],
                    }),
              ],
            },
            a,
          );
        }),
      }),
    }),
  });
}
const V = "۱ فروردین ۱۴۰۴",
  i =
    "text-sm font-bold text-[#25272C] mt-8 mb-2 pb-2 border-b border-[#DDE2DD] flex items-center gap-2 border-r-4 border-r-[#28722C] pr-3",
  n = "text-sm text-[#545B64] leading-[2] mb-3",
  p = "list-none space-y-1.5 mb-4",
  b = "flex items-start gap-2 text-sm text-[#545B64]";
function De() {
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(j, {
        data: g([{ label: "خانه", href: "/" }, { label: "حریم خصوصی" }]),
        id: "privacy-schema",
      }),
      e.jsx(N, { items: [{ label: "خانه", href: "/" }, { label: "سیاست حریم خصوصی" }] }),
      e.jsx("header", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-12 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[760px] mx-auto",
          children: [
            e.jsxs("div", {
              className: "flex items-center gap-2 mb-4",
              children: [
                e.jsx(u, { size: 20, className: "text-[#28722C]" }),
                e.jsx("span", {
                  className:
                    "text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full border border-[#28722C]/30",
                  children: "حقوقی",
                }),
              ],
            }),
            e.jsx("h1", { className: "text-3xl font-bold mb-2", children: "سیاست حریم خصوصی" }),
            e.jsxs("p", {
              className: "text-white/60 text-sm",
              children: ["آخرین به‌روزرسانی: ", V],
            }),
          ],
        }),
      }),
      e.jsx("div", {
        className: "max-w-[760px] mx-auto px-4 sm:px-6 py-10",
        children: e.jsxs("div", {
          className: "bg-white rounded-2xl border border-[#DDE2DD] p-6 sm:p-8 space-y-0",
          children: [
            e.jsxs("h2", {
              className: i,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۱",
                }),
                "جمع‌آوری اطلاعات",
              ],
            }),
            e.jsx("p", {
              className: n,
              children:
                "کلینیک دکتر شاهین باستانی‌نژاد اطلاعاتی را که شما هنگام استفاده از این وب‌سایت، پر کردن فرم‌های تماس یا ثبت‌نام برای نوبت‌گیری ارائه می‌دهید، جمع‌آوری می‌کند. این اطلاعات ممکن است شامل نام، شماره تماس، آدرس ایمیل و اطلاعات مرتبط با وضعیت سلامتی برای اهداف مشاوره باشد.",
            }),
            e.jsx("p", {
              className: n,
              children:
                "همچنین هنگام بازدید از این وب‌سایت، اطلاعاتی مانند آدرس IP، نوع مرورگر، صفحات بازدید‌شده و مدت زمان حضور در سایت به‌صورت خودکار جمع‌آوری می‌شود.",
            }),
            e.jsxs("h2", {
              className: i,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۲",
                }),
                "استفاده از اطلاعات",
              ],
            }),
            e.jsx("p", {
              className: n,
              children: "اطلاعات جمع‌آوری‌شده برای اهداف زیر استفاده می‌شود:",
            }),
            e.jsx("ul", {
              className: p,
              children: [
                "هماهنگی وقت مشاوره و نوبت‌گیری پزشکی",
                "پاسخ به سوالات و درخواست‌های شما",
                "بهبود کیفیت خدمات وب‌سایت",
                "ارسال اطلاعیه‌های پزشکی مرتبط (با رضایت شما)",
              ].map((s) =>
                e.jsxs(
                  "li",
                  {
                    className: b,
                    children: [
                      e.jsx("span", {
                        className: "w-1.5 h-1.5 rounded-full bg-[#28722C] flex-shrink-0 mt-2",
                      }),
                      s,
                    ],
                  },
                  s,
                ),
              ),
            }),
            e.jsxs("h2", {
              className: i,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۳",
                }),
                "اشتراک‌گذاری اطلاعات",
              ],
            }),
            e.jsx("p", {
              className: n,
              children:
                "اطلاعات شخصی شما بدون رضایت صریح شما با اشخاص ثالث به اشتراک گذاشته نمی‌شود، مگر در موارد زیر:",
            }),
            e.jsx("ul", {
              className: p,
              children: [
                "الزامات قانونی یا حکم دادگاه",
                "حفاظت از حقوق، دارایی یا ایمنی کلینیک، بیماران یا دیگران",
                "ارائه‌دهندگان خدمات فنی مورد اعتماد که تحت قرارداد محرمانگی با ما کار می‌کنند",
              ].map((s) =>
                e.jsxs(
                  "li",
                  {
                    className: b,
                    children: [
                      e.jsx("span", {
                        className: "w-1.5 h-1.5 rounded-full bg-[#28722C] flex-shrink-0 mt-2",
                      }),
                      s,
                    ],
                  },
                  s,
                ),
              ),
            }),
            e.jsxs("h2", {
              className: i,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۴",
                }),
                "امنیت اطلاعات",
              ],
            }),
            e.jsx("p", {
              className: n,
              children:
                "ما از روش‌های استاندارد صنعت برای حفاظت از اطلاعات شخصی شما استفاده می‌کنیم. با این حال، هیچ روش انتقال اطلاعات از طریق اینترنت ۱۰۰٪ امن نیست. ما متعهد هستیم که از اطلاعات شما با بهترین روش‌های موجود محافظت کنیم.",
            }),
            e.jsxs("h2", {
              className: i,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۵",
                }),
                "کوکی‌ها",
              ],
            }),
            e.jsx("p", {
              className: n,
              children:
                "این وب‌سایت ممکن است از کوکی‌ها برای بهبود تجربه کاربری استفاده کند. شما می‌توانید کوکی‌ها را از طریق تنظیمات مرورگر خود مدیریت کنید. غیرفعال کردن کوکی‌ها ممکن است برخی عملکردهای سایت را تحت تأثیر قرار دهد.",
            }),
            e.jsxs("h2", {
              className: i,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۶",
                }),
                "حقوق شما",
              ],
            }),
            e.jsx("p", { className: n, children: "شما حق دارید:" }),
            e.jsx("ul", {
              className: p,
              children: [
                "به اطلاعات شخصی‌ای که از شما نگهداری می‌کنیم، دسترسی داشته باشید",
                "درخواست اصلاح اطلاعات نادرست دهید",
                "درخواست حذف اطلاعات خود را بدهید (در حدود الزامات قانونی)",
                "از پردازش اطلاعات خود برای اهداف بازاریابی انصراف دهید",
              ].map((s) =>
                e.jsxs(
                  "li",
                  {
                    className: b,
                    children: [
                      e.jsx("span", {
                        className: "w-1.5 h-1.5 rounded-full bg-[#28722C] flex-shrink-0 mt-2",
                      }),
                      s,
                    ],
                  },
                  s,
                ),
              ),
            }),
            e.jsxs("h2", {
              className: i,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۷",
                }),
                "تغییرات در این سیاست",
              ],
            }),
            e.jsx("p", {
              className: n,
              children:
                "ما ممکن است این سیاست حریم خصوصی را هر از چند گاهی به‌روز کنیم. تغییرات مهم از طریق وب‌سایت اطلاع‌رسانی می‌شود. ادامه استفاده از سایت پس از این تغییرات به منزله قبول سیاست جدید است.",
            }),
            e.jsxs("h2", {
              className: i,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۸",
                }),
                "تماس با ما",
              ],
            }),
            e.jsx("p", {
              className: n,
              children: "برای هرگونه سوال درباره این سیاست حریم خصوصی، با ما تماس بگیرید:",
            }),
            e.jsxs("div", {
              className:
                "flex flex-col sm:flex-row gap-3 p-4 bg-[#F7F8F6] rounded-xl border border-[#DDE2DD] mt-2",
              children: [
                e.jsxs("a", {
                  href: "tel:02186087250",
                  className:
                    "flex items-center gap-2 text-sm text-[#28722C] font-medium hover:text-[#246b28] transition-colors",
                  children: [e.jsx(f, { size: 14, className: "flex-shrink-0" }), "۰۲۱۸۶۰۸۷۲۵۰"],
                }),
                e.jsxs("a", {
                  href: "mailto:info@drbastaninejad.com",
                  className:
                    "flex items-center gap-2 text-sm text-[#28722C] font-medium hover:text-[#246b28] transition-colors",
                  children: [
                    e.jsx(H, { size: 14, className: "flex-shrink-0" }),
                    "info@drbastaninejad.com",
                  ],
                }),
              ],
            }),
          ],
        }),
      }),
    ],
  });
}
const Y = "۱ فروردین ۱۴۰۴",
  o =
    "text-sm font-bold text-[#25272C] mt-8 mb-2 pb-2 border-b border-[#DDE2DD] flex items-center gap-2 border-r-4 border-r-[#28722C] pr-3",
  r = "text-sm text-[#545B64] leading-[2] mb-3";
function Ae() {
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(j, {
        data: g([{ label: "خانه", href: "/" }, { label: "شرایط استفاده" }]),
        id: "terms-schema",
      }),
      e.jsx(N, { items: [{ label: "خانه", href: "/" }, { label: "شرایط استفاده" }] }),
      e.jsx("header", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-12 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[760px] mx-auto",
          children: [
            e.jsxs("div", {
              className: "flex items-center gap-2 mb-4",
              children: [
                e.jsx($, { size: 20, className: "text-[#28722C]" }),
                e.jsx("span", {
                  className:
                    "text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full border border-[#28722C]/30",
                  children: "حقوقی",
                }),
              ],
            }),
            e.jsx("h1", {
              className: "text-3xl font-bold mb-2",
              children: "شرایط و قوانین استفاده",
            }),
            e.jsxs("p", {
              className: "text-white/60 text-sm",
              children: ["آخرین به‌روزرسانی: ", Y],
            }),
          ],
        }),
      }),
      e.jsx("div", {
        className: "max-w-[760px] mx-auto px-4 sm:px-6 py-10",
        children: e.jsxs("div", {
          className: "bg-white rounded-2xl border border-[#DDE2DD] p-6 sm:p-8",
          children: [
            e.jsx("p", {
              className: r,
              children:
                "با استفاده از وب‌سایت کلینیک دکتر شاهین باستانی‌نژاد (drbastaninejad.com)، شما با این شرایط موافقت می‌کنید. لطفاً آن‌ها را با دقت مطالعه کنید.",
            }),
            e.jsxs("h2", {
              className: o,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۱",
                }),
                "استفاده از اطلاعات پزشکی",
              ],
            }),
            e.jsx("p", {
              className: r,
              children:
                "محتوای این وب‌سایت صرفاً برای اهداف اطلاع‌رسانی و آموزشی ارائه می‌شود. این اطلاعات جایگزین مشاوره، تشخیص یا درمان پزشکی حرفه‌ای نیست. برای هرگونه تصمیم پزشکی، با متخصص مجاز مشورت کنید.",
            }),
            e.jsx("p", {
              className: r,
              children:
                "کلینیک دکتر باستانی‌نژاد مسئولیتی در قبال اقدامات پزشکی انجام‌شده بر اساس اطلاعات این وب‌سایت بدون مشاوره مستقیم با پزشک نمی‌پذیرد.",
            }),
            e.jsxs("h2", {
              className: o,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۲",
                }),
                "مالکیت معنوی",
              ],
            }),
            e.jsx("p", {
              className: r,
              children:
                "تمام محتوا، تصاویر، نوشته‌ها، لوگو و طراحی این وب‌سایت تحت حمایت قوانین مالکیت معنوی ایران قرار دارند. هرگونه استفاده، تکثیر یا توزیع بدون اجازه کتبی ممنوع است.",
            }),
            e.jsxs("h2", {
              className: o,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۳",
                }),
                "تصاویر بیماران",
              ],
            }),
            e.jsx("p", {
              className: r,
              children:
                "تصاویر قبل و بعد از جراحی با رضایت کتبی بیماران منتشر شده‌اند. نتایج ممکن است برای هر فرد متفاوت باشد. این تصاویر تضمینی برای نتایج مشابه نیستند.",
            }),
            e.jsxs("h2", {
              className: o,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۴",
                }),
                "محدودیت مسئولیت",
              ],
            }),
            e.jsx("p", {
              className: r,
              children:
                'این وب‌سایت "به همان شکل که هست" (as-is) ارائه می‌شود. کلینیک هیچ ضمانتی در مورد دقت، کمال یا به‌روز بودن اطلاعات نمی‌دهد. در حداکثر حد مجاز قانونی، هیچ مسئولیتی برای خسارات مستقیم یا غیرمستقیم ناشی از استفاده از این وب‌سایت وجود ندارد.',
            }),
            e.jsxs("h2", {
              className: o,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۵",
                }),
                "لینک‌های خارجی",
              ],
            }),
            e.jsx("p", {
              className: r,
              children:
                "این وب‌سایت ممکن است شامل لینک به وب‌سایت‌های خارجی باشد. ما هیچ کنترلی بر محتوای آن‌ها نداریم و مسئولیتی در قبال آن‌ها نمی‌پذیریم.",
            }),
            e.jsxs("h2", {
              className: o,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۶",
                }),
                "تغییرات",
              ],
            }),
            e.jsx("p", {
              className: r,
              children:
                "ما حق داریم این شرایط را در هر زمانی تغییر دهیم. ادامه استفاده از وب‌سایت پس از تغییرات به منزله قبول شرایط جدید است.",
            }),
            e.jsxs("h2", {
              className: o,
              children: [
                e.jsx("span", {
                  className:
                    "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                  children: "۷",
                }),
                "قانون حاکم",
              ],
            }),
            e.jsx("p", {
              className: `${r} mb-0`,
              children:
                "این شرایط تحت قوانین جمهوری اسلامی ایران تفسیر و اجرا می‌شوند. هرگونه اختلاف در دادگاه‌های صالح تهران رسیدگی می‌شود.",
            }),
            e.jsxs("div", {
              className:
                "mt-6 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3",
              children: [
                e.jsx("span", { className: "text-amber-500 mt-0.5 flex-shrink-0", children: "⚠️" }),
                e.jsx("p", {
                  className: "text-xs text-amber-800 leading-relaxed",
                  children:
                    "محتوای پزشکی این سایت جایگزین مشاوره پزشکی نیست. همیشه با متخصص مجاز مشورت کنید.",
                }),
              ],
            }),
          ],
        }),
      }),
    ],
  });
}
const h = 48,
  J = "۱ فروردین ۱۴۰۴",
  x =
    "text-sm font-bold text-[#25272C] mt-8 mb-2 pb-2 border-b border-[#DDE2DD] flex items-center gap-2 border-r-4 border-r-[#28722C] pr-3",
  m = "text-sm text-[#545B64] leading-[2] mb-3",
  Q = "list-none space-y-1.5 mb-4",
  K = "flex items-start gap-2 text-sm text-[#545B64]";
function Fe() {
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(j, {
        data: g([{ label: "خانه", href: "/" }, { label: "سیاست لغو" }]),
        id: "cancel-schema",
      }),
      e.jsx(N, { items: [{ label: "خانه", href: "/" }, { label: "سیاست لغو و کنسلی" }] }),
      e.jsx("header", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-12 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[760px] mx-auto",
          children: [
            e.jsxs("div", {
              className: "flex items-center gap-2 mb-4",
              children: [
                e.jsx(C, { size: 20, className: "text-[#28722C]" }),
                e.jsx("span", {
                  className:
                    "text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full border border-[#28722C]/30",
                  children: "حقوقی",
                }),
              ],
            }),
            e.jsx("h1", {
              className: "text-3xl font-bold mb-2",
              children: "سیاست لغو و کنسلی وقت",
            }),
            e.jsxs("p", {
              className: "text-white/60 text-sm",
              children: ["آخرین به‌روزرسانی: ", J],
            }),
          ],
        }),
      }),
      e.jsxs("div", {
        className: "max-w-[760px] mx-auto px-4 sm:px-6 py-10",
        children: [
          e.jsx("div", {
            className: "grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8",
            children: [
              {
                Icon: R,
                bg: "bg-[#E4F0E4]",
                ic: "text-[#28722C]",
                title: `${h} ساعت قبل`,
                desc: "لغو رایگان",
              },
              {
                Icon: q,
                bg: "bg-amber-50",
                ic: "text-amber-600",
                title: "۲۴–۴۸ ساعت",
                desc: "۵۰٪ هزینه مشاوره",
              },
              {
                Icon: C,
                bg: "bg-red-50",
                ic: "text-red-600",
                title: "کمتر از ۲۴ ساعت",
                desc: "هزینه کامل",
              },
            ].map(({ Icon: s, bg: t, ic: a, title: c, desc: d }) =>
              e.jsxs(
                "div",
                {
                  className: "bg-white border border-[#DDE2DD] rounded-2xl p-5 text-center",
                  children: [
                    e.jsx("div", {
                      className: `w-10 h-10 rounded-xl ${t} flex items-center justify-center mx-auto mb-3`,
                      children: e.jsx(s, { size: 18, className: a }),
                    }),
                    e.jsx("p", { className: "font-bold text-[#25272C] text-sm mb-1", children: c }),
                    e.jsx("p", { className: "text-xs text-[#545B64]", children: d }),
                  ],
                },
                c,
              ),
            ),
          }),
          e.jsxs("div", {
            className: "bg-white rounded-2xl border border-[#DDE2DD] p-6 sm:p-8",
            children: [
              e.jsxs("h2", {
                className: x,
                children: [
                  e.jsx("span", {
                    className:
                      "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                    children: "۱",
                  }),
                  "مهلت لغو",
                ],
              }),
              e.jsxs("p", {
                className: m,
                children: [
                  "بیماران می‌توانند وقت مشاوره خود را حداقل ",
                  e.jsxs("strong", { className: "text-[#25272C]", children: [h, " ساعت"] }),
                  " قبل از زمان مقرر به‌صورت رایگان لغو کنند. برای لغو وقت، لطفاً از طریق تلفن یا سامانه آنلاین اقدام کنید.",
                ],
              }),
              e.jsxs("h2", {
                className: x,
                children: [
                  e.jsx("span", {
                    className:
                      "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                    children: "۲",
                  }),
                  "لغو با تأخیر",
                ],
              }),
              e.jsxs("p", {
                className: m,
                children: [
                  "در صورت لغو بین ۲۴ تا ",
                  h,
                  " ساعت قبل از وقت، ۵۰٪ هزینه ویزیت یا مشاوره دریافت می‌شود. این هزینه قابل کسر از هزینه خدمت بعدی می‌باشد.",
                ],
              }),
              e.jsxs("h2", {
                className: x,
                children: [
                  e.jsx("span", {
                    className:
                      "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                    children: "۳",
                  }),
                  "عدم حضور (No-Show)",
                ],
              }),
              e.jsx("p", {
                className: m,
                children:
                  "در صورت عدم حضور بدون اطلاع قبلی (کمتر از ۲۴ ساعت یا هیچ اطلاعی)، هزینه کامل ویزیت دریافت می‌شود. تکرار این موضوع ممکن است منجر به محدودیت در پذیرش شود.",
              }),
              e.jsxs("h2", {
                className: x,
                children: [
                  e.jsx("span", {
                    className:
                      "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                    children: "۴",
                  }),
                  "شرایط استثنائی",
                ],
              }),
              e.jsx("p", {
                className: m,
                children:
                  "در موارد اورژانس پزشکی، تصادف یا شرایط فورس‌ماژور، پس از ارائه مدارک لازم، هزینه کنسلی بخشوده می‌شود. لطفاً در اسرع وقت با کلینیک تماس بگیرید.",
              }),
              e.jsxs("h2", {
                className: x,
                children: [
                  e.jsx("span", {
                    className:
                      "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                    children: "۵",
                  }),
                  "تغییر وقت",
                ],
              }),
              e.jsxs("p", {
                className: m,
                children: [
                  "تغییر وقت حداقل ",
                  h,
                  " ساعت قبل از زمان مقرر رایگان است. برای تغییر وقت با تأخیر کمتر، بنا به موجودیت وقت و صلاحدید مجموعه عمل می‌شود.",
                ],
              }),
              e.jsxs("h2", {
                className: x,
                children: [
                  e.jsx("span", {
                    className:
                      "w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0",
                    children: "۶",
                  }),
                  "روش لغو / تغییر وقت",
                ],
              }),
              e.jsx("ul", {
                className: Q,
                children: [
                  "از طریق سامانه آنلاین: app.drbastaninejad.com",
                  "از طریق تلفن کلینیک: ۰۲۱۸۶۰۸۷۲۵۰",
                  "ساعات پذیرش: شنبه تا سه‌شنبه — ۱۵:۰۰ تا ۱۸:۰۰؛ مراجعه بعد از ۱۸:۰۰ پذیرش نمی‌شود.",
                ].map((s) =>
                  e.jsxs(
                    "li",
                    {
                      className: K,
                      children: [
                        e.jsx("span", {
                          className: "w-1.5 h-1.5 rounded-full bg-[#28722C] flex-shrink-0 mt-2",
                        }),
                        s,
                      ],
                    },
                    s,
                  ),
                ),
              }),
              e.jsxs("div", {
                className: "flex flex-col sm:flex-row gap-3 p-4 bg-[#E4F0E4] rounded-xl mt-4",
                children: [
                  e.jsxs("a", {
                    href: "tel:02186087250",
                    className:
                      "flex items-center gap-2 text-sm text-[#28722C] font-bold hover:text-[#246b28] transition-colors",
                    children: [e.jsx(f, { size: 14, className: "flex-shrink-0" }), "۰۲۱–۸۶۰۸۷۲۵۰"],
                  }),
                  e.jsxs("a", {
                    href: "tel:02188205606",
                    className:
                      "flex items-center gap-2 text-sm text-[#28722C] font-medium hover:text-[#246b28] transition-colors",
                    children: [e.jsx(f, { size: 14, className: "flex-shrink-0" }), "۰۲۱–۸۸۲۰۵۶۰۶"],
                  }),
                  e.jsx("a", {
                    href: z,
                    rel: "noopener",
                    className:
                      "flex items-center gap-2 text-sm text-[#28722C] font-medium hover:text-[#246b28] transition-colors mr-auto",
                    children: "رزرو نوبت ←",
                  }),
                ],
              }),
            ],
          }),
        ],
      }),
    ],
  });
}
export {
  we as A,
  N as B,
  he as C,
  se as D,
  oe as E,
  ue as F,
  Ce as G,
  ye as H,
  ae as I,
  j as J,
  Se as L,
  be as N,
  te as P,
  xe as S,
  ne as T,
  le as Y,
  re as a,
  de as b,
  fe as c,
  ce as d,
  ve as e,
  me as f,
  pe as g,
  je as h,
  ge as i,
  Ne as j,
  Ee as k,
  ie as l,
  g as m,
  ke as n,
  z as o,
  De as p,
  Ae as q,
  Fe as r,
};
