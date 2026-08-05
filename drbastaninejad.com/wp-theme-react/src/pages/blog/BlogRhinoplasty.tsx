/**
 * Blog Post: رینوپلاستی — راهنمای کامل جراحی بینی
 * PHP template: single-post.php
 * { BACKEND } Replace all static data with WP/ACF calls
 */
import { CheckCircle, Info, AlertCircle } from "lucide-react";
import BlogPostTemplate, { SHARED_TESTIMONIALS, SHARED_RELATED, AUTHOR } from "@/components/BlogPostTemplate";
import { MedicalDisclaimer } from "@/components/ArticleAtoms";
import type { PostMeta, FAQItem } from "@/components/ArticleAtoms";

const meta: PostMeta = {
  title: "رینوپلاستی: راهنمای کامل جراحی بینی از مشاوره تا نتیجه نهایی",
  subtitle: "هر آنچه قبل، حین و بعد از جراحی بینی باید بدانید — علمی، جامع و تخصصی",
  category: "راهنمای بیمار", categorySlug: "patient-guide",
  difficultyLevel: "متوسط", readTimeMin: 12, viewCount: 4821,
  publishDate: "۱۵ تیر ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["رینوپلاستی", "جراحی بینی", "بینی طبیعی", "بینی فانتزی", "سپتوپلاستی", "ریکاوری"],
  featuredImage: { url: "/images/Blog/Blog-101.webp", alt: "جراحی بینی — دکتر باستانی‌نژاد" },
  seoTitle: "رینوپلاستی ۱۴۰۴ — راهنمای کامل | دکتر شاهین باستانی‌نژاد",
  seoDescription: "راهنمای جامع جراحی بینی شامل انواع روش‌ها، معیار کاندیداتوری، مراحل عمل و دوره بهبودی.",
  canonicalUrl: "https://drbastaninejad.com/blog/rhinoplasty",
};

const faqs: FAQItem[] = [
  { question: "رینوپلاستی چیست و چه افرادی کاندیدای مناسبی هستند؟", answer: "رینوپلاستی یا جراحی بینی مداخله‌ای جراحی بر روی ساختارهای استخوانی، غضروفی و پوستی بینی است. کاندیداهای مناسب افراد بالغ بالای ۱۸ سال با رشد استخوانی کامل، سلامت عمومی مناسب و انتظارات واقع‌بینانه هستند." },
  { question: "دوره بهبودی چقدر طول می‌کشد؟", answer: "ورم و کبودی اولیه ظرف ۷–۱۰ روز کاهش می‌یابد. اسپلینت پس از هفته اول برداشته می‌شود. بازگشت به کار ۱۰–۱۴ روز و نتیجه نهایی ۶–۱۲ ماه پس از عمل." },
  { question: "تفاوت رینوپلاستی باز و بسته چیست؟", answer: "در روش باز، برشی کوچک روی ستون بینی ایجاد می‌شود که دسترسی کامل به ساختار را فراهم می‌کند — مناسب موارد پیچیده. در روش بسته، تمام برش‌ها درون سوراخ بینی است و هیچ ندبه خارجی ندارد." },
  { question: "آیا رینوپلاستی دردناک است؟", answer: "عمل زیر بیهوشی عمومی انجام می‌شود. پس از عمل درد خفیف تا متوسط با داروهای تجویزی کنترل می‌شود. اکثر بیماران آن را در حد ناراحتی — نه درد شدید — توصیف می‌کنند." },
  { question: "هزینه جراحی بینی چقدر است؟", answer: "هزینه بستگی به پیچیدگی عمل، نوع بیمارستان و تکنیک جراحی دارد. مشاوره اولیه رایگان است و برآورد دقیق پس از معاینه ارائه می‌شود." },
  { question: "آیا بیمه هزینه را پوشش می‌دهد؟", answer: "جراحی زیبایی معمولاً تحت پوشش نیست. اما سپتوپلاستی که هدف درمانی دارد ممکن است توسط برخی بیمه‌ها پوشش داده شود." },
];

export default function BlogRhinoplasty() {
  return (
    <BlogPostTemplate meta={meta} faqs={faqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["رینوپلاستی چیست؟", "انواع روش‌ها", "معیارهای کاندیداتوری", "مراحل جراحی", "دوره بهبودی", "نمونه‌های قبل و بعد"]}>

      {/* ── KEY TAKEAWAYS — { BACKEND } ACF: key_takeaways_list ── */}
      <div className="bg-gradient-to-br from-[#28722C] to-[#246b28] rounded-2xl p-6 mb-8 text-white shadow-lg">
        <div className="flex items-center gap-2 mb-4">
          <div className="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center"><CheckCircle size={16} /></div>
          <h2 className="text-lg font-bold">نکات کلیدی این مقاله</h2>
        </div>
        <ul className="space-y-2.5">
          {["رینوپلاستی نیازمند برنامه‌ریزی دقیق و انتخاب جراح بورد‌سرتیفاید است",
            "دوره ریکاوری کامل ۶ تا ۱۲ ماه است و نتیجه نهایی تدریجی دیده می‌شود",
            "انتخاب روش باز یا بسته بر اساس پیچیدگی عمل و نظر جراح تعیین می‌شود",
            "داشتن انتظارات واقع‌بینانه مهم‌ترین عامل رضایت بیمار است",
            "ایران با +۲۰۰٬۰۰۰ عمل سالانه، مرکز جهانی رینوپلاستی است",
          ].map((item, i) => (
            <li key={i} className="flex items-start gap-2.5 text-sm leading-relaxed">
              <CheckCircle size={14} className="text-white/70 flex-shrink-0 mt-0.5" />{item}
            </li>
          ))}
        </ul>
      </div>

      {/* ── SECTION 1: What is Rhinoplasty ── */}
      <section id="section-0">
        <h2 className="text-xl sm:text-2xl font-bold text-[#25272C] mb-4 flex items-center gap-2">
          <span className="w-8 h-8 rounded-lg bg-[#E4F0E4] text-[#28722C] text-sm font-bold flex items-center justify-center flex-shrink-0">۱</span>
          رینوپلاستی چیست؟
        </h2>
        <p className="text-[#25272C] leading-[2] mb-4">
          <strong className="text-[#28722C]">رینوپلاستی</strong> (Rhinoplasty) یا جراحی بینی، یکی از پیچیده‌ترین و تخصصی‌ترین اقدامات جراحی زیبایی در جهان است.
          این جراحی با هدف اصلاح فرم، اندازه، تناسب یا عملکرد بینی انجام می‌شود. ایران با بیش از <strong> ۲۰۰٬۰۰۰ عمل سالانه</strong>، رتبه اول جهان را در تعداد رینوپلاستی دارد.
        </p>
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

      {/* ── SECTION 2: Types ── */}
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
              {[
                ["رینوپلاستی باز",    "دید مستقیم کامل",      "موارد پیچیده، ترمیمی", "برش کوچک ستون بینی"],
                ["رینوپلاستی بسته",  "برش داخل بینی",         "اصلاح‌های محدود",     "بدون ندبه خارجی"],
                ["رینوپلاستی تزریقی","فیلر، غیرجراحی",        "اصلاح‌های موقت کوچک", "هیچ"],
                ["سپتوپلاستی",       "اصلاح تیغه میانی",      "انحراف و مشکل تنفسی", "داخلی"],
              ].map(([method, feat, suitable, scar], i) => (
                <tr key={i} className={`border-t border-[#DDE2DD] ${i % 2 === 0 ? "bg-white" : "bg-[#F7F8F6]"}`}>
                  <td className="py-3 px-4 font-semibold text-[#28722C]">{method}</td>
                  <td className="py-3 px-4 text-[#25272C]">{feat}</td>
                  <td className="py-3 px-4 text-[#545B64]">{suitable}</td>
                  <td className="py-3 px-4 text-[#545B64]">{scar}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      {/* ── SECTION 3: Candidacy ── */}
      <section id="section-2">
        <h2 className="text-xl sm:text-2xl font-bold text-[#25272C] mb-4 flex items-center gap-2">
          <span className="w-8 h-8 rounded-lg bg-[#E4F0E4] text-[#28722C] text-sm font-bold flex items-center justify-center flex-shrink-0">۳</span>
          معیارهای کاندیداتوری
        </h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
          <div className="bg-white rounded-xl border border-[#DDE2DD] p-4">
            <p className="font-bold text-[#28722C] mb-3 flex items-center gap-2"><CheckCircle size={15} />کاندیداهای مناسب</p>
            {["بالای ۱۸ سال (رشد کامل)", "سلامت عمومی مناسب", "انتظارات واقع‌بینانه", "غیرسیگاری یا آماده ترک", "بدون اختلال روانپزشکی فعال"].map((item, i) => (
              <div key={i} className="flex items-center gap-2 text-sm text-[#25272C] py-1.5 border-b border-[#F7F8F6] last:border-0">
                <CheckCircle size={12} className="text-[#28722C] flex-shrink-0" />{item}
              </div>
            ))}
          </div>
          <div className="bg-white rounded-xl border border-[#DDE2DD] p-4">
            <p className="font-bold text-red-600 mb-3 flex items-center gap-2"><AlertCircle size={15} />موارد منع نسبی</p>
            {["دیابت کنترل‌نشده", "بیماری‌های قلبی–عروقی شدید", "اختلالات انعقادی", "بارداری یا شیردهی", "مصرف داروهای رقیق‌کننده"].map((item, i) => (
              <div key={i} className="flex items-center gap-2 text-sm text-[#25272C] py-1.5 border-b border-[#F7F8F6] last:border-0">
                <AlertCircle size={12} className="text-red-400 flex-shrink-0" />{item}
              </div>
            ))}
          </div>
        </div>
        <MedicalDisclaimer />
      </section>

      {/* ── SECTION 4: Steps ── */}
      <section id="section-3">
        <h2 className="text-xl sm:text-2xl font-bold text-[#25272C] mb-5 flex items-center gap-2">
          <span className="w-8 h-8 rounded-lg bg-[#E4F0E4] text-[#28722C] text-sm font-bold flex items-center justify-center flex-shrink-0">۴</span>
          مراحل جراحی
        </h2>
        <div className="space-y-3">
          {[
            { step: "مشاوره اولیه",        desc: "معاینه، عکاسی ۳D و برنامه‌ریزی شخصی",           time: "جلسه اول" },
            { step: "آزمایشات پیش از عمل", desc: "آزمایش خون، EKG، مشاوره بیهوشی",                time: "۱–۲ هفته قبل" },
            { step: "جراحی",               desc: "بیهوشی عمومی، ۱.۵ تا ۳ ساعت",                  time: "روز عمل" },
            { step: "ریکاوری اولیه",       desc: "اسپلینت ۷ روز، برگشت به فعالیت عادی",            time: "هفته اول" },
            { step: "نتیجه نهایی",         desc: "کاهش تدریجی ورم — نتیجه کامل ۶–۱۲ ماه",         time: "۶–۱۲ ماه" },
          ].map((s, i) => (
            <div key={i} className="flex gap-4">
              <div className="flex flex-col items-center flex-shrink-0">
                <div className="w-8 h-8 rounded-full bg-[#28722C] text-white text-xs font-bold flex items-center justify-center">{(i+1).toLocaleString("fa-IR")}</div>
                {i < 4 && <div className="w-px flex-1 bg-[#DDE2DD] my-1" />}
              </div>
              <div className="bg-white rounded-xl border border-[#DDE2DD] p-4 flex-1 flex items-center justify-between gap-3 mb-0">
                <div>
                  <p className="font-semibold text-[#25272C] text-sm">{s.step}</p>
                  <p className="text-sm text-[#545B64]">{s.desc}</p>
                </div>
                <span className="text-xs bg-gray-100 text-gray-600 border border-gray-200 px-2.5 py-1 rounded-full font-semibold whitespace-nowrap">{s.time}</span>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* ── SECTION 5: Recovery ── */}
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
                ["روز ۱–۳",    "ورم و کبودی شدید",        "استراحت کامل، داروها"],
                ["روز ۷",      "برداشتن اسپلینت",          "ویزیت کنترل پزشک"],
                ["هفته ۲–۳",  "کاهش ورم ۶۰٪",             "بازگشت به کار (اداری)"],
                ["ماه ۱–۳",   "شکل تقریبی نهایی",          "پرهیز از ورزش سنگین"],
                ["ماه ۶–۱۲",  "نتیجه کامل و دائمی",        "عکاسی نتیجه نهایی"],
              ].map(([t, s, a], i) => (
                <tr key={i} className={`border-t border-[#DDE2DD] ${i % 2 === 0 ? "bg-white" : "bg-[#F7F8F6]"}`}>
                  <td className="py-3 px-4 font-semibold text-[#28722C]">{t}</td>
                  <td className="py-3 px-4 text-[#25272C]">{s}</td>
                  <td className="py-3 px-4 text-[#545B64]">{a}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </BlogPostTemplate>
  );
}
