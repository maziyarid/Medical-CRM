/**
 * Blog Posts: Pre-Op Steps, Post-Op Care, Fleshy Rhinoplasty, Choose Surgeon,
 *             Revision Guide, Complications, Male Rhinoplasty, Nutrition, ATL Removal
 * PHP: single-post.php | { BACKEND } ACF calls
 *
 * These posts use a simplified body — each has its own unique meta/content.
 * Full content can be expanded as needed; the structure is identical to BlogRhinoplasty.
 */
import { CheckCircle, AlertCircle, Info } from "lucide-react";
import BlogPostTemplate, { SHARED_TESTIMONIALS, SHARED_RELATED, AUTHOR } from "@/components/BlogPostTemplate";
import { MedicalDisclaimer } from "@/components/ArticleAtoms";
import type { PostMeta, FAQItem } from "@/components/ArticleAtoms";

/* ─── Helpers ─── */
const Section = ({ id, num, title, children }: { id: string; num: string; title: string; children: React.ReactNode }) => (
  <section id={id}>
    <h2 className="text-xl font-bold text-[#25272C] mb-4 flex items-center gap-2">
      <span className="w-8 h-8 rounded-lg bg-[#E4F0E4] text-[#28722C] text-sm font-bold flex items-center justify-center flex-shrink-0">{num}</span>
      {title}
    </h2>
    {children}
  </section>
);

const BulletList = ({ items, variant = "check" }: { items: string[]; variant?: "check" | "alert" }) => (
  <div className="space-y-2 mb-4">
    {items.map((item, i) => (
      <div key={i} className="flex items-center gap-2 text-sm text-[#25272C] bg-white rounded-xl p-3 border border-[#DDE2DD]">
        {variant === "check"
          ? <CheckCircle size={14} className="text-[#28722C] flex-shrink-0" />
          : <AlertCircle size={14} className="text-amber-500 flex-shrink-0" />}
        {item}
      </div>
    ))}
  </div>
);

/* ================================================================
   POST: اقدامات ضروری قبل از جراحی بینی
   ================================================================ */
const preOpMeta: PostMeta = {
  title: "اقدامات ضروری قبل از جراحی بینی — چک‌لیست کامل",
  subtitle: "آمادگی صحیح قبل از عمل نقش مهمی در موفقیت جراحی و سرعت بهبودی دارد",
  category: "راهنمای بیمار", categorySlug: "patient-guide",
  difficultyLevel: "آموزشی", readTimeMin: 7, viewCount: 3200,
  publishDate: "۵ مهر ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["قبل از جراحی", "آمادگی عمل بینی", "چک‌لیست جراحی"],
  featuredImage: { url: "/images/Blog/Blog-103.webp", alt: "آمادگی قبل از جراحی بینی" },
  seoTitle: "اقدامات قبل از جراحی بینی | دکتر باستانی‌نژاد",
  seoDescription: "چک‌لیست کامل آمادگی قبل از عمل بینی.",
  canonicalUrl: "https://drbastaninejad.com/blog/pre-op-steps",
};
const preOpFaqs: FAQItem[] = [
  { question: "چند روز قبل از عمل باید داروهایم را قطع کنم؟", answer: "داروهای رقیق‌کننده خون (آسپیرین، ایبوپروفن) باید ۱۰–۱۴ روز قبل از عمل قطع شوند. داروهای دیگر طبق نظر پزشک." },
  { question: "آیا باید قبل از عمل ناشتا باشم؟", answer: "بله. از ۸ ساعت قبل از عمل هیچ چیزی نخورید و ننوشید. این قانون برای ایمنی بیهوشی ضروری است." },
];

export function BlogPreOpSteps() {
  return (
    <BlogPostTemplate meta={preOpMeta} faqs={preOpFaqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["آزمایشات لازم", "داروهایی که باید قطع شوند", "آمادگی روز عمل", "موارد مهم"]}>
      <Section id="section-0" num="۱" title="آزمایشات لازم قبل از عمل">
        <BulletList items={["آزمایش خون کامل (CBC)", "تست انعقاد خون (PT/PTT)", "الکتروکاردیوگرام (ECG)", "عکس رادیولوژی قفسه سینه", "آزمایش‌های کلیوی و کبدی"]} />
      </Section>
      <Section id="section-1" num="۲" title="داروهایی که باید قطع شوند">
        <BulletList variant="alert" items={["آسپیرین — ۱۴ روز قبل", "ایبوپروفن — ۱۰ روز قبل", "داروهای ضدانعقاد (وارفارین و مشابه)", "مکمل‌های ویتامین E و امگا ۳", "داروهای گیاهی رقیق‌کننده"]} />
        <MedicalDisclaimer />
      </Section>
      <Section id="section-2" num="۳" title="آمادگی روز عمل">
        <BulletList items={["ناشتا از ۸ ساعت قبل از عمل", "عدم استفاده از آرایش و کرم", "پوشیدن لباس گشاد و دکمه‌دار", "همراه داشتن همراه مطمئن", "آوردن مدارک پزشکی"]} />
      </Section>
    </BlogPostTemplate>
  );
}

/* ================================================================
   POST: مراقبت‌های بعد از جراحی بینی
   ================================================================ */
const postOpMeta: PostMeta = {
  title: "مراقبت‌های بعد از جراحی بینی — راهنمای جامع دوره ریکاوری",
  subtitle: "رعایت این نکات نقش مستقیمی در کیفیت نتیجه نهایی جراحی شما دارد",
  category: "مراقبت‌ها", categorySlug: "care",
  difficultyLevel: "آموزشی", readTimeMin: 8, viewCount: 5100,
  publishDate: "۱۸ شهریور ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["مراقبت بعد از عمل", "ریکاوری بینی", "دوره بهبودی"],
  featuredImage: { url: "/images/Blog/Blog-104.webp", alt: "مراقبت بعد از جراحی بینی" },
  seoTitle: "مراقبت‌های بعد از جراحی بینی | دکتر باستانی‌نژاد",
  seoDescription: "راهنمای کامل مراقبت در دوره بهبودی بینی.",
  canonicalUrl: "https://drbastaninejad.com/blog/post-op-care",
};
const postOpFaqs: FAQItem[] = [
  { question: "چه زمانی می‌توانم عینک بزنم؟", answer: "معمولاً حداقل ۶ هفته پس از عمل. فشار عینک می‌تواند به ساختار در حال بهبود آسیب بزند." },
  { question: "چه مدت باید سرم را بالا نگه دارم؟", answer: "در ۲ هفته اول، سرتان را حتی هنگام خواب ۳۰–۴۵ درجه بالاتر از بدن نگه دارید تا ورم کمتر شود." },
];

export function BlogPostOpCare() {
  return (
    <BlogPostTemplate meta={postOpMeta} faqs={postOpFaqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["هفته اول", "هفته دوم تا سوم", "ماه اول تا سوم", "موارد ممنوع"]}>
      <Section id="section-0" num="۱" title="هفته اول — مراقبت‌های حیاتی">
        <BulletList items={["استراحت کامل، سر بالاتر از بدن", "استفاده منظم از داروهای تجویزی", "عدم فوت کردن بینی", "پرهیز از تماس با آب روی بینی", "ممنوعیت کامل سیگار"]} />
      </Section>
      <Section id="section-1" num="۲" title="هفته دوم تا سوم">
        <BulletList items={["بازگشت تدریجی به فعالیت سبک", "عدم فعالیت بدنی شدید", "پرهیز از آفتاب مستقیم", "استفاده از ضدآفتاب SPF 50+"]} />
      </Section>
      <Section id="section-2" num="۳" title="موارد ممنوع (۶ هفته اول)">
        <BulletList variant="alert" items={["استفاده از عینک طبی یا آفتابی", "ورزش سنگین و فعالیت هوازی", "شنا و جکوزی", "ماساژ ناحیه بینی", "تماس مستقیم با گرما"]} />
        <MedicalDisclaimer />
      </Section>
    </BlogPostTemplate>
  );
}

/* ================================================================
   POST: جراحی بینی گوشتی چیست؟
   ================================================================ */
const fleshyMeta: PostMeta = {
  title: "جراحی بینی گوشتی چیست؟ راهنمای تخصصی",
  subtitle: "بینی‌های گوشتی ویژگی‌های خاصی دارند که تکنیک‌های متفاوتی می‌طلبند",
  category: "جراحی بینی", categorySlug: "rhinoplasty",
  difficultyLevel: "متوسط", readTimeMin: 9, viewCount: 3780,
  publishDate: "۱ شهریور ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["بینی گوشتی", "جراحی بینی", "پوست ضخیم"],
  featuredImage: { url: "/images/Blog/Blog-105.webp", alt: "جراحی بینی گوشتی" },
  seoTitle: "جراحی بینی گوشتی | دکتر باستانی‌نژاد",
  seoDescription: "راهنمای تخصصی جراحی بینی‌های گوشتی و پوست ضخیم.",
  canonicalUrl: "https://drbastaninejad.com/blog/rhinoplasty-fleshy",
};
const fleshyFaqs: FAQItem[] = [
  { question: "بینی گوشتی چه تعریفی دارد؟", answer: "بینی گوشتی (Thick-Skinned Nose) بینی‌ای است که پوست ضخیم‌تر، چربی زیرپوستی بیشتر و بافت نرم قوی‌تری نسبت به بینی‌های استخوانی دارد." },
  { question: "نتیجه نهایی کِی مشخص می‌شود؟", answer: "به دلیل ضخامت پوست، ورم دیرتر فروکش می‌کند. نتیجه نهایی ممکن است ۱۲ تا ۱۸ ماه طول بکشد." },
];

export function BlogFleshyRhinoplasty() {
  return (
    <BlogPostTemplate meta={fleshyMeta} faqs={fleshyFaqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["بینی گوشتی چیست؟", "چالش‌های جراحی", "تکنیک‌های خاص", "انتظارات واقع‌بینانه"]}>
      <Section id="section-0" num="۱" title="بینی گوشتی چیست؟">
        <p className="text-[#25272C] leading-[2] mb-4">
          بینی گوشتی به بینی‌هایی گفته می‌شود که پوست ضخیم‌تری دارند. این ضخامت باعث می‌شود که جزئیات اصلاحات جراح از زیر پوست کمتر مشخص باشند و جراحی به دقت و تجربه بیشتری نیاز داشته باشد.
        </p>
      </Section>
      <Section id="section-1" num="۲" title="چالش‌های اصلی جراحی بینی گوشتی">
        <BulletList variant="alert" items={["پنهان شدن تغییرات ظریف زیر پوست ضخیم", "احتمال بیشتر ورم طولانی‌مدت", "محدودیت بیشتر در ایجاد ظرافت", "نیاز به تکنیک‌های تخصصی‌تر"]} />
      </Section>
      <Section id="section-2" num="۳" title="انتظارات واقع‌بینانه">
        <div className="bg-[#E4F0E4] rounded-xl p-4 mb-4">
          <p className="text-sm text-[#25272C] leading-relaxed">
            بینی گوشتی می‌تواند نتایج بسیار زیبایی داشته باشد، اما نتیجه نهایی کندتر از بینی‌های استخوانی مشخص می‌شود. صبر و پیگیری منظم ضروری است.
          </p>
        </div>
        <MedicalDisclaimer />
      </Section>
    </BlogPostTemplate>
  );
}

/* ================================================================
   POST: ۱۰ نکته مهم در انتخاب جراح بینی
   ================================================================ */
const chooseMeta: PostMeta = {
  title: "۱۰ نکته مهم در انتخاب جراح بینی",
  subtitle: "انتخاب درست جراح، مهم‌ترین گام در مسیر جراحی بینی موفق است",
  category: "راهنمای بیمار", categorySlug: "patient-guide",
  difficultyLevel: "آموزشی", readTimeMin: 6, viewCount: 4300,
  publishDate: "۱۲ مرداد ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["انتخاب جراح", "جراح بینی", "راهنمای بیمار"],
  featuredImage: { url: "/images/Blog/Blog-106.webp", alt: "انتخاب جراح بینی" },
  seoTitle: "نحوه انتخاب جراح بینی | دکتر باستانی‌نژاد",
  seoDescription: "معیارهای انتخاب جراح بینی مناسب.",
  canonicalUrl: "https://drbastaninejad.com/blog/choose-surgeon",
};
const chooseFaqs: FAQItem[] = [
  { question: "تفاوت جراح ENT و جراح پلاستیک در رینوپلاستی چیست؟", answer: "متخصص گوش، حلق و بینی (ENT) آشنایی عمیق‌تری با عملکرد تنفسی دارد. جراح پلاستیک تخصص بیشتری در زیبایی دارد. بهترین جراح بینی کسی است که فلوشیپ رینوپلاستی داشته باشد." },
];

export function BlogChooseSurgeon() {
  return (
    <BlogPostTemplate meta={chooseMeta} faqs={chooseFaqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["مدرک و بورد تخصصی", "تجربه و تعداد عمل", "نمونه کارها", "مشاوره اولیه", "شفافیت اطلاعات"]}>
      <Section id="section-0" num="۱" title="چک‌لیست انتخاب جراح بینی">
        <BulletList items={[
          "دارای بورد تخصصی گوش، حلق و بینی یا جراحی پلاستیک معتبر",
          "فلوشیپ رینوپلاستی یا تجربه تخصصی مستند",
          "حداقل ۵ سال تجربه انحصاری در جراحی بینی",
          "نمونه‌کارهای واقعی و قابل بررسی قبل و بعد",
          "توضیح شفاف روش جراحی، خطرات و انتظارات",
          "عضویت در انجمن‌های معتبر پزشکی",
          "بیمارستان یا مرکز جراحی مجاز",
          "پاسخگویی به سؤالات بدون فشار",
          "مشاوره اولیه بدون تعهد و رایگان",
          "نداشتن وعده‌های غیرواقعی",
        ]} />
      </Section>
      <Section id="section-1" num="۲" title="علائم هشدار">
        <BulletList variant="alert" items={["وعده نتیجه ۱۰۰٪ تضمینی", "فشار برای تصمیم‌گیری فوری", "قیمت غیرمعمول پایین", "عدم ارائه نمونه‌کار واقعی", "عدم توضیح کامل خطرات"]} />
      </Section>
    </BlogPostTemplate>
  );
}

/* ================================================================
   POST: رینوپلاستی ترمیمی راهنمای جامع (revision guide)
   ================================================================ */
const revisionMeta: PostMeta = {
  title: "رینوپلاستی ترمیمی: راهنمای جامع برای اصلاح نتیجه قبلی",
  subtitle: "وقتی نتیجه عمل اول رضایت‌بخش نیست — گزینه‌ها، چالش‌ها و انتظارات",
  category: "جراحی بینی", categorySlug: "rhinoplasty",
  difficultyLevel: "تخصصی", readTimeMin: 10, viewCount: 2100,
  publishDate: "۱ مرداد ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["رینوپلاستی ترمیمی", "عمل دوم بینی"],
  featuredImage: { url: "/images/Blog/Blog-101.webp", alt: "رینوپلاستی ترمیمی" },
  seoTitle: "رینوپلاستی ترمیمی جامع | دکتر باستانی‌نژاد",
  seoDescription: "راهنمای جامع جراحی ترمیمی بینی.",
  canonicalUrl: "https://drbastaninejad.com/blog/revision-rhinoplasty",
};
const revisionFaqs: FAQItem[] = [
  { question: "چرا باید ۱۲ ماه صبر کرد؟", answer: "بافت بینی ۱۲ ماه طول می‌کشد تا کاملاً بهبود یابد. تصمیم‌گیری زودتر می‌تواند بر اساس تورم موجود باشد، نه شکل واقعی نهایی." },
];

export function BlogRevisionGuide() {
  return (
    <BlogPostTemplate meta={revisionMeta} faqs={revisionFaqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["چرا به ترمیمی نیاز است؟", "زمان مناسب", "پیچیدگی‌های جراحی", "انتظارات"]}>
      <Section id="section-0" num="۱" title="چرا به جراحی ترمیمی نیاز می‌شود؟">
        <p className="text-[#25272C] leading-[2] mb-4">
          علل اصلی نیاز به رینوپلاستی ترمیمی شامل: نتیجه زیبایی غیررضایت‌بخش، ایجاد مشکل تنفسی پس از عمل، آسیمتری و عدم تقارن، یا حوادث پس از جراحی می‌شود.
        </p>
        <MedicalDisclaimer />
      </Section>
      <Section id="section-1" num="۲" title="پیچیدگی‌های جراحی ترمیمی">
        <BulletList variant="alert" items={["وجود بافت اسکار از عمل قبلی", "کمبود غضروف اضافه‌برداشته‌شده", "تغییر ساختارهای آناتومیک", "نیاز احتمالی به پیوند غضروف از دنده یا گوش"]} />
      </Section>
    </BlogPostTemplate>
  );
}

/* ================================================================
   POST: عوارض احتمالی جراحی بینی
   ================================================================ */
const compMeta: PostMeta = {
  title: "عوارض احتمالی جراحی بینی و راه‌های پیشگیری",
  subtitle: "آگاهی از عوارض، بخشی از آمادگی هوشمندانه برای جراحی بینی است",
  category: "آموزشی", categorySlug: "educational",
  difficultyLevel: "متوسط", readTimeMin: 8, viewCount: 2650,
  publishDate: "۲۰ تیر ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["عوارض جراحی بینی", "خطرات رینوپلاستی"],
  featuredImage: { url: "/images/Blog/Blog-102.webp", alt: "عوارض جراحی بینی" },
  seoTitle: "عوارض جراحی بینی | دکتر باستانی‌نژاد",
  seoDescription: "عوارض احتمالی جراحی بینی و پیشگیری از آن‌ها.",
  canonicalUrl: "https://drbastaninejad.com/blog/rhinoplasty-complications",
};
const compFaqs: FAQItem[] = [
  { question: "شایع‌ترین عارضه جراحی بینی چیست؟", answer: "ورم و کبودی شایع‌ترین و طبیعی‌ترین عوارض هستند. عفونت، خونریزی و اختلال تنفسی موقت نیز ممکن است اما با مراقبت مناسب کنترل می‌شوند." },
];

export function BlogComplications() {
  return (
    <BlogPostTemplate meta={compMeta} faqs={compFaqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["عوارض شایع", "عوارض نادر", "پیشگیری", "نشانه‌های خطر"]}>
      <Section id="section-0" num="۱" title="عوارض شایع (طبیعی)">
        <BulletList items={["ورم و کبودی (۷–۲۱ روز)", "احساس بی‌حسی موقت", "خشکی یا آبریزش بینی", "تغییر موقت در حس بویایی"]} />
      </Section>
      <Section id="section-1" num="۲" title="نشانه‌های خطر — فوری به پزشک مراجعه کنید">
        <BulletList variant="alert" items={["خونریزی شدید و مداوم", "تب بالای ۳۸.۵ درجه", "درد شدید و غیرقابل کنترل", "تنفس بسیار دشوار", "قرمزی، گرمی و تورم غیرمعمول"]} />
        <MedicalDisclaimer />
      </Section>
    </BlogPostTemplate>
  );
}

/* ================================================================
   POST: جراحی بینی در مردان
   ================================================================ */
const maleMeta: PostMeta = {
  title: "جراحی بینی در مردان — تفاوت‌ها و ملاحظات ویژه",
  subtitle: "رینوپلاستی در مردان اصول و ملاحظات خاص خود را دارد",
  category: "جراحی بینی", categorySlug: "rhinoplasty",
  difficultyLevel: "متوسط", readTimeMin: 7, viewCount: 1980,
  publishDate: "۵ تیر ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["بینی مردانه", "رینوپلاستی مردان"],
  featuredImage: { url: "/images/Blog/Blog-103.webp", alt: "جراحی بینی مردانه" },
  seoTitle: "جراحی بینی مردانه | دکتر باستانی‌نژاد",
  seoDescription: "نکات و تفاوت‌های جراحی بینی در مردان.",
  canonicalUrl: "https://drbastaninejad.com/blog/male-rhinoplasty",
};
const maleFaqs: FAQItem[] = [
  { question: "آیا نتیجه بینی مردانه باید کاملاً متفاوت با زنانه باشد؟", answer: "بله. بینی مردانه اصول زیبایی خاص خود را دارد: پل بینی صاف‌تر، زاویه نوک کمتر سربالا، و عرض کمی بیشتر. هدف طبیعی‌بودن و متناسب با ساختار مردانه چهره است." },
];

export function BlogMaleRhinoplasty() {
  return (
    <BlogPostTemplate meta={maleMeta} faqs={maleFaqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["تفاوت بینی مردانه", "اصول زیبایی مردانه", "نکات مهم"]}>
      <Section id="section-0" num="۱" title="تفاوت‌های بینی مردانه و زنانه">
        <div className="overflow-x-auto rounded-xl border border-[#DDE2DD] mb-4">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-[#E4F0E4]">
                {["ویژگی", "بینی مردانه", "بینی زنانه"].map(h => (
                  <th key={h} className="text-right py-3 px-4 font-bold text-[#25272C]">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {[
                ["زاویه نوک بینی",   "۹۰–۱۰۰ درجه",   "۱۰۰–۱۱۵ درجه"],
                ["پل بینی",          "صاف‌تر یا با قوز ملایم", "صاف یا کمی فرو رفته"],
                ["عرض بینی",         "نسبتاً عریض‌تر", "ظریف‌تر"],
              ].map(([f, m, w], i) => (
                <tr key={i} className={`border-t border-[#DDE2DD] ${i % 2 === 0 ? "bg-white" : "bg-[#F7F8F6]"}`}>
                  <td className="py-3 px-4 font-semibold text-[#28722C]">{f}</td>
                  <td className="py-3 px-4 text-[#25272C]">{m}</td>
                  <td className="py-3 px-4 text-[#545B64]">{w}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Section>
    </BlogPostTemplate>
  );
}

/* ================================================================
   POST: تغذیه قبل و بعد از جراحی بینی
   ================================================================ */
const nutritionMeta: PostMeta = {
  title: "تغذیه مناسب قبل و بعد از جراحی بینی",
  subtitle: "تغذیه صحیح تأثیر مستقیمی بر سرعت بهبودی و کیفیت نتیجه دارد",
  category: "مراقبت‌ها", categorySlug: "care",
  difficultyLevel: "آموزشی", readTimeMin: 6, viewCount: 2200,
  publishDate: "۱۸ خرداد ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["تغذیه جراحی بینی", "ریکاوری"],
  featuredImage: { url: "/images/Blog/Blog-104.webp", alt: "تغذیه قبل از جراحی بینی" },
  seoTitle: "تغذیه مناسب جراحی بینی | دکتر باستانی‌نژاد",
  seoDescription: "راهنمای تغذیه قبل و بعد از عمل بینی.",
  canonicalUrl: "https://drbastaninejad.com/blog/nutrition-rhinoplasty",
};
const nutritionFaqs: FAQItem[] = [
  { question: "چه مکمل‌هایی برای ریکاوری مفید هستند؟", answer: "ویتامین C برای سنتز کلاژن، روی (Zinc) برای ترمیم زخم، و ویتامین A مفید هستند. قبل از مصرف هر مکملی با پزشکتان مشورت کنید." },
];

export function BlogNutrition() {
  return (
    <BlogPostTemplate meta={nutritionMeta} faqs={nutritionFaqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["قبل از عمل", "بعد از عمل", "غذاهای مفید", "موارد ممنوع"]}>
      <Section id="section-0" num="۱" title="قبل از جراحی — چه بخوریم؟">
        <BulletList items={["پروتئین کافی (گوشت، ماهی، تخم‌مرغ)", "میوه و سبزیجات سرشار از آنتی‌اکسیدان", "غلات کامل برای انرژی پایدار", "مقدار زیاد آب"]} />
      </Section>
      <Section id="section-1" num="۲" title="بعد از جراحی — غذاهای مفید">
        <BulletList items={["غذاهای نرم (سوپ، پوره، ماست)", "میوه‌های سرشار از ویتامین C", "پروتئین‌های ساده‌هضم", "مایعات فراوان"]} />
      </Section>
      <Section id="section-2" num="۳" title="موارد ممنوع در دوره ریکاوری">
        <BulletList variant="alert" items={["الکل (رقیق‌کننده خون)", "غذاهای سخت که نیاز به جویدن شدید دارند", "خوراکی‌های خیلی داغ", "نمک زیاد (افزایش ورم)", "مکمل‌های رقیق‌کننده بدون مشورت"]} />
        <MedicalDisclaimer />
      </Section>
    </BlogPostTemplate>
  );
}

/* ================================================================
   POST: آتل بینی
   ================================================================ */
const atlMeta: PostMeta = {
  title: "آتل بینی: همه چیز درباره مراقبت و زمان برداشتن",
  subtitle: "آتل بینی بخش مهمی از دوره بهبودی است — نحوه مراقبت و انتظارات",
  category: "آموزشی", categorySlug: "educational",
  difficultyLevel: "آموزشی", readTimeMin: 5, viewCount: 3100,
  publishDate: "۱ خرداد ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["آتل بینی", "اسپلینت بینی", "مراقبت"],
  featuredImage: { url: "/images/Blog/Blog-105.webp", alt: "آتل بینی" },
  seoTitle: "آتل بینی — مراقبت و برداشتن | دکتر باستانی‌نژاد",
  seoDescription: "راهنمای کامل مراقبت از آتل بینی و زمان برداشتن.",
  canonicalUrl: "https://drbastaninejad.com/blog/atl-removal",
};
const atlFaqs: FAQItem[] = [
  { question: "برداشتن آتل بینی دردناک است؟", answer: "نه، برداشتن آتل بینی توسط پزشک معمولاً دردناک نیست. ممکن است کمی احساس ناراحتی داشته باشید اما درد شدیدی نخواهید داشت." },
  { question: "بعد از برداشتن آتل بینی چه ظاهری خواهم داشت؟", answer: "بینی هنوز ورم دارد. شکل کلی مشخص می‌شود اما نتیجه نهایی را باید تا ۶–۱۲ ماه بعد دید." },
];

export function BlogATLRemoval() {
  return (
    <BlogPostTemplate meta={atlMeta} faqs={atlFaqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["آتل بینی چیست؟", "مراقبت از آتل", "زمان برداشتن", "بعد از برداشتن"]}>
      <Section id="section-0" num="۱" title="آتل بینی چیست؟">
        <p className="text-[#25272C] leading-[2] mb-4">
          آتل (اسپلینت) بینی یک محافظ سفت است که بلافاصله پس از جراحی برای نگهداری ساختار اصلاح‌شده بینی روی آن گذاشته می‌شود. معمولاً ۷–۱۰ روز پس از عمل توسط جراح برداشته می‌شود.
        </p>
      </Section>
      <Section id="section-1" num="۲" title="مراقبت از آتل بینی">
        <BulletList items={["از خیس شدن آتل جلوگیری کنید", "از دست زدن یا تنظیم خودسرانه خودداری کنید", "هنگام خواب سرتان را بالا نگه دارید", "در برابر ضربه محافظت کنید"]} />
      </Section>
      <Section id="section-2" num="۳" title="بعد از برداشتن آتل">
        <BulletList items={["ادامه دادن به استفاده از کرم‌های تجویزی", "پرهیز از فشار روی بینی (عینک ممنوع ۶ هفته)", "ماساژ ملایم طبق دستور پزشک", "پیگیری منظم ویزیت‌های کنترل"]} />
        <MedicalDisclaimer />
      </Section>
    </BlogPostTemplate>
  );
}
