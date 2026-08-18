/**
 * Blog Post: جراحی بینی ترمیمی چیست؟
 * PHP: single-post.php | { BACKEND } ACF calls
 */
import { CheckCircle, AlertCircle } from "lucide-react";
import BlogPostTemplate, { SHARED_TESTIMONIALS, SHARED_RELATED, AUTHOR } from "@/components/BlogPostTemplate";
import { MedicalDisclaimer } from "@/components/ArticleAtoms";
import type { PostMeta, FAQItem } from "@/components/ArticleAtoms";

const meta: PostMeta = {
  title: "جراحی بینی ترمیمی چیست؟ راهنمای کامل رینوپلاستی ثانویه",
  subtitle: "زمانی که نتیجه عمل اول رضایت‌بخش نیست — همه چیز درباره جراحی ترمیمی بینی",
  category: "جراحی بینی", categorySlug: "rhinoplasty",
  difficultyLevel: "تخصصی", readTimeMin: 10, viewCount: 2840,
  publishDate: "۲۰ مهر ۱۴۰۳", lastReviewed: "مرداد ۱۴۰۴",
  author: AUTHOR,
  tags: ["رینوپلاستی ترمیمی", "جراحی بینی", "عمل دوم بینی"],
  featuredImage: { url: "/images/Blog/Blog-102.webp", alt: "جراحی بینی ترمیمی" },
  seoTitle: "جراحی بینی ترمیمی — راهنمای کامل | دکتر باستانی‌نژاد",
  seoDescription: "همه چیز درباره جراحی ترمیمی بینی، دلایل نیاز و انتظارات واقع‌بینانه.",
  canonicalUrl: "https://drbastaninejad.com/blog/rhinoplasty-revision",
};

const faqs: FAQItem[] = [
  { question: "حداقل فاصله زمانی بین عمل اول و ترمیمی چقدر است؟", answer: "حداقل ۱۲ ماه پس از عمل اول. بافت باید کاملاً بهبود یابد و تورم‌های عمقی فروکش کنند تا ارزیابی دقیق امکان‌پذیر باشد." },
  { question: "جراحی ترمیمی سخت‌تر از اولیه است؟", answer: "بله. به دلیل وجود بافت اسکار، کمبود غضروف و تغییرات قبلی، رینوپلاستی ترمیمی به مراتب پیچیده‌تر و نیازمند تخصص و تجربه بیشتری است." },
  { question: "آیا ریکاوری ترمیمی متفاوت است؟", answer: "معمولاً ورم بیشتر و طولانی‌تری نسبت به عمل اول وجود دارد. نتیجه نهایی ممکن است ۱۲ تا ۱۸ ماه طول بکشد." },
];

export default function BlogRevisionRhinoplasty() {
  return (
    <BlogPostTemplate meta={meta} faqs={faqs} testimonials={SHARED_TESTIMONIALS} related={SHARED_RELATED}
      tocItems={["جراحی ترمیمی چیست؟", "دلایل نیاز", "معیارهای کاندیداتوری", "فرایند جراحی", "انتظارات"]}>

      <section id="section-0">
        <h2 className="text-xl font-bold text-[#25272C] mb-4">جراحی بینی ترمیمی چیست؟</h2>
        <p className="text-[#25272C] leading-[2] mb-4">
          رینوپلاستی ترمیمی (Revision Rhinoplasty) به جراحی‌هایی گفته می‌شود که برای اصلاح نتایج ناخواسته یا عوارض عمل‌های قبلی بینی انجام می‌شوند. این نوع جراحی یکی از چالش‌برانگیزترین اقدامات در جراحی پلاستیک است.
        </p>
      </section>

      <section id="section-1">
        <h2 className="text-xl font-bold text-[#25272C] mb-4">دلایل رایج نیاز به جراحی ترمیمی</h2>
        <div className="space-y-2 mb-4">
          {["نتیجه غیر زیبایی و عدم رضایت از شکل", "نقص تنفسی ایجادشده پس از جراحی", "آسیمتری یا عدم تقارن بینی", "پینچ ناخواسته نوک بینی", "کمبود حجم غضروف"].map((item, i) => (
            <div key={i} className="flex items-center gap-2 text-sm text-[#25272C] bg-white rounded-xl p-3 border border-[#DDE2DD]">
              <AlertCircle size={14} className="text-amber-500 flex-shrink-0" />{item}
            </div>
          ))}
        </div>
        <MedicalDisclaimer />
      </section>

      <section id="section-2">
        <h2 className="text-xl font-bold text-[#25272C] mb-4">شرایط کاندیداتوری</h2>
        <div className="grid sm:grid-cols-2 gap-4">
          <div className="bg-white rounded-xl border border-[#DDE2DD] p-4">
            <p className="font-bold text-[#28722C] mb-3">پیش‌نیازها</p>
            {["حداقل ۱۲ ماه پس از آخرین جراحی", "بهبودی کامل بافت بینی", "سلامت عمومی مناسب", "انتظارات واقع‌بینانه"].map((item, i) => (
              <div key={i} className="flex items-center gap-2 text-sm text-[#25272C] py-1.5 border-b border-[#F7F8F6] last:border-0">
                <CheckCircle size={12} className="text-[#28722C] flex-shrink-0" />{item}
              </div>
            ))}
          </div>
          <div className="bg-[#E4F0E4] rounded-xl p-4">
            <p className="font-bold text-[#28722C] mb-3">نکته مهم</p>
            <p className="text-sm text-[#25272C] leading-relaxed">
              تا زمانی که ۱۲ ماه از عمل اول نگذشته، ورم‌های عمقی ممکن است هنوز وجود داشته باشند. قضاوت زودهنگام می‌تواند منجر به جراحی غیرضروری شود.
            </p>
          </div>
        </div>
      </section>
    </BlogPostTemplate>
  );
}
