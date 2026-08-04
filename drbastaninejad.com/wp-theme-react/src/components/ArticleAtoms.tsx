/**
 * Shared article-level UI atoms — used across blog posts and service pages.
 *
 * WP mapping:
 *   Badge          → post category / ACF field label
 *   StarRating     → ACF: rating (1–5)
 *   DifficultyBadge→ ACF: post_difficulty select
 *   FAQSection     → ACF repeater: faq_items
 *   AuthorBio      → get_the_author_meta() + ACF user fields
 *   Testimonials   → ACF repeater: testimonials
 *   RelatedPosts   → WP_Query related by category
 *   ContactCTA     → ACF options: cta_heading, booking_url
 *   ArticleSidebar → Sidebar template with sticky TOC
 *   MedicalDisclaimer → Static YMYL notice
 */
import { useState } from "react";
import { Link } from "react-router";
import {
  ChevronDown, CheckCircle, AlertCircle, BookOpen, Shield,
  Award, GraduationCap, Star, Clock, Phone, MapPin, Send,
  ArrowLeft, BarChart2, Stethoscope,
} from "lucide-react";
import { AUTHOR as DEFAULT_AUTHOR, BOOKING_URL } from "@/data/site";

/* ── Types — mirror as ACF field groups in WordPress ── */
export interface FAQItem {
  question: string;  // ACF: faq_question
  answer:   string;  // ACF: faq_answer
}

export interface TestimonialItem {
  name:      string;   // ACF: patient_name (anonymized)
  age:       string;   // ACF: patient_age_range
  rating:    number;   // ACF: rating (1–5)
  text:      string;   // ACF: review_text
  date:      string;   // ACF: review_date (Jalali)
  verified:  boolean;  // ACF: is_verified_patient
  procedure: string;   // ACF: procedure_performed
}

export interface RelatedPostItem {
  title:    string;  // WP: post_title
  slug:     string;  // WP: post_name
  category: string;  // WP: category name
  readTime: string;  // ACF: post_read_time
  imgId:    string;  // Unsplash ID (replace with WP attachment)
  date:     string;  // WP: post_date (Jalali)
  excerpt:  string;  // WP: post_excerpt
}

export interface AuthorData {
  name:            string;
  title:           string;
  specialty:       string;
  hospital:        string;
  bio:             string;
  stats:           { label: string; value: string }[];
  certifications:  string[];
  avatarInitials:  string;
}

export interface PostMeta {
  title:          string;
  subtitle:       string;
  category:       string;
  categorySlug:   string;
  difficultyLevel: "آموزشی" | "متوسط" | "تخصصی";
  readTimeMin:    number;
  viewCount:      number;
  publishDate:    string;
  lastReviewed:   string;
  author:         AuthorData;
  tags:           string[];
  featuredImage:  { url: string; alt: string };
  seoTitle:       string;
  seoDescription: string;
  canonicalUrl:   string;
}

/* ── Badge ── */
export function Badge({ children, variant = "primary" }: { children: React.ReactNode; variant?: "primary" | "accent" | "info" | "muted" | "warn" }) {
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

/* ── StarRating ── */
export function StarRating({ rating }: { rating: number }) {
  return (
    <div className="flex gap-0.5" aria-label={`${rating} از ۵ ستاره`}>
      {[1,2,3,4,5].map(i => (
        <Star key={i} size={13} className={i <= rating ? "fill-amber-400 text-amber-400" : "text-gray-300 fill-gray-100"} />
      ))}
    </div>
  );
}

/* ── DifficultyBadge ── */
export function DifficultyBadge({ level }: { level: string }) {
  const map: Record<string, { cls: string; icon: React.ReactNode }> = {
    "آموزشی": { cls: "bg-green-50 text-green-800 border-green-200",   icon: <BookOpen size={10} /> },
    "متوسط":  { cls: "bg-blue-50 text-blue-800 border-blue-200",       icon: <BarChart2 size={10} /> },
    "تخصصی": { cls: "bg-purple-50 text-purple-800 border-purple-200", icon: <Stethoscope size={10} /> },
  };
  const s = map[level] || map["آموزشی"];
  return (
    <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border ${s.cls}`}>
      {s.icon} سطح: {level}
    </span>
  );
}

/* ── FAQSection ── */
export function FAQSection({ faqs, heading = "پرسش‌های متداول" }: { faqs: FAQItem[]; heading?: string }) {
  const [open, setOpen] = useState<number | null>(null);
  return (
    <section aria-labelledby="faq-heading">
      <h2 id="faq-heading" className="text-xl sm:text-2xl font-bold text-[#25272C] mb-5">{heading}</h2>
      {/* PHP: foreach (get_field('faq_items') as $item) */}
      <div className="space-y-2.5">
        {faqs.map((faq, i) => (
          <div key={i} className="bg-white rounded-xl border border-[#DDE2DD] overflow-hidden">
            <button
              onClick={() => setOpen(open === i ? null : i)}
              className="w-full flex items-center justify-between gap-3 p-4 text-right hover:bg-[#F7F8F6] transition-colors"
              aria-expanded={open === i}
            >
              <span className="text-sm font-semibold text-[#25272C] text-right">{faq.question}</span>
              <ChevronDown size={16} className={`text-[#28722C] flex-shrink-0 transition-transform duration-200 ${open === i ? "rotate-180" : ""}`} />
            </button>
            {open === i && (
              <div className="px-4 pb-4 border-t border-[#DDE2DD] pt-3">
                {/* PHP: echo wp_kses_post($item['faq_answer']); */}
                <p className="text-sm text-[#6A7078] leading-[2]">{faq.answer}</p>
              </div>
            )}
          </div>
        ))}
      </div>
    </section>
  );
}

/* ── AuthorBio ── */
export function AuthorBio({ author = DEFAULT_AUTHOR }: { author?: AuthorData }) {
  return (
    <aside className="bg-white rounded-2xl border border-[#DDE2DD] p-5 sm:p-6" aria-label="درباره نویسنده">
      <p className="text-xs font-bold text-[#6A7078] uppercase tracking-widest mb-4">نوشته و بازبینی‌شده توسط</p>
      <div className="flex items-start gap-4 mb-4">
        {/* Production: <img src="<?= get_avatar_url($author_id) ?>" ... /> */}
        <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#28722C] to-[#1a4e1d] flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
          {author.avatarInitials}
        </div>
        <div>
          <p className="font-bold text-[#25272C] text-base">{author.name}</p>
          <p className="text-sm text-[#28722C] font-medium">{author.title}</p>
          <p className="text-xs text-[#6A7078]">{author.hospital}</p>
        </div>
      </div>
      <p className="text-sm text-[#6A7078] leading-relaxed mb-5">{author.bio}</p>
      <div className="grid grid-cols-3 gap-2 mb-5 p-3 bg-[#F7F8F6] rounded-xl">
        {author.stats.map(s => (
          <div key={s.label} className="text-center">
            <p className="text-base font-bold text-[#28722C]">{s.value}</p>
            <p className="text-[10px] text-[#6A7078]">{s.label}</p>
          </div>
        ))}
      </div>
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

/* ── Testimonials ── */
export function Testimonials({ items }: { items: TestimonialItem[] }) {
  return (
    <section aria-labelledby="testimonials-heading">
      <div className="flex items-center justify-between mb-5">
        <h2 id="testimonials-heading" className="text-xl font-bold text-[#25272C]">نظر بیماران</h2>
        {/* WP: link to /testimonials archive */}
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
                <p className="font-semibold text-[#25272C] text-sm">{t.name}</p>
                <p className="text-xs text-[#6A7078]">{t.age} · {t.procedure}</p>
              </div>
              {t.verified && (
                <Badge variant="primary"><CheckCircle size={10} />تأیید شده</Badge>
              )}
            </div>
            <StarRating rating={t.rating} />
            <p className="text-sm text-[#6A7078] leading-relaxed flex-1">"{t.text}"</p>
            <p className="text-xs text-[#6A7078] border-t border-[#DDE2DD] pt-2">{t.date}</p>
          </div>
        ))}
      </div>
    </section>
  );
}

/* ── RelatedPosts ── */
export function RelatedPosts({ posts }: { posts: RelatedPostItem[] }) {
  return (
    <section aria-labelledby="related-heading">
      <div className="flex items-center justify-between mb-5">
        <h2 id="related-heading" className="text-xl font-bold text-[#25272C]">مقالات مرتبط</h2>
        <Link to="/blog" className="text-sm text-[#28722C] font-medium flex items-center gap-1 hover:gap-2 transition-all">
          همه مقالات <ArrowLeft size={13} />
        </Link>
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {/* PHP: foreach ($related_posts as $post) */}
        {posts.map((p, i) => (
          <article key={i} className="bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden group hover:shadow-md transition-shadow">
            <div className="bg-[#DDE2DD] overflow-hidden">
              <img
                src={p.imgId}
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
              <h3 className="font-semibold text-[#25272C] text-sm leading-relaxed mb-1 group-hover:text-[#28722C] transition-colors">{p.title}</h3>
              <p className="text-xs text-[#6A7078] line-clamp-2 mb-2">{p.excerpt}</p>
              <p className="text-xs text-[#6A7078]">{p.date}</p>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}

/* ── ContactCTA ── */
export function ContactCTA() {
  const [sent, setSent] = useState(false);
  return (
    <section id="contact" aria-labelledby="contact-heading">
      <div className="bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden">
        <div className="grid grid-cols-1 lg:grid-cols-2">
          {/* Info panel */}
          <div className="bg-gradient-to-br from-[#28722C] to-[#1a4e1d] p-8 text-white">
            <h2 id="contact-heading" className="text-2xl font-bold mb-2">مشاوره رایگان</h2>
            <p className="text-white/80 text-sm leading-relaxed mb-6">تیم ما آماده پاسخگویی به سوالات شما و هماهنگی وقت مشاوره حضوری است.</p>
            <div className="space-y-4">
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
            <div className="mt-6 pt-6 border-t border-white/20 flex flex-wrap gap-3">
              {["مشاوره رایگان", "بدون تعهد", "محرمانه"].map(t => (
                <span key={t} className="flex items-center gap-1 text-xs text-white/70">
                  <CheckCircle size={11} className="text-green-300" />{t}
                </span>
              ))}
            </div>
          </div>

          {/* Form — PHP: Contact Form 7 / Gravity Forms or WP AJAX */}
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
                {/* PHP: do_action('wpcf7_contact_form') or echo do_shortcode('[contact-form-7 ...]') */}
                <form className="space-y-4" onSubmit={e => { e.preventDefault(); setSent(true); }}>
                  <div className="grid grid-cols-2 gap-3">
                    {["نام", "نام خانوادگی"].map(label => (
                      <div key={label}>
                        <label className="text-xs text-[#6A7078] mb-1 block">{label}</label>
                        <input type="text" placeholder={label} required
                          className="w-full px-3 py-2.5 text-sm rounded-lg border border-[#DDE2DD] bg-[#F7F8F6] focus:outline-none focus:border-[#28722C] focus:ring-2 focus:ring-[#28722C]/10 transition-all" />
                      </div>
                    ))}
                  </div>
                  <div>
                    <label className="text-xs text-[#6A7078] mb-1 block">شماره تماس</label>
                    <input type="tel" placeholder="۰۹۱۲..." dir="ltr" required
                      className="w-full px-3 py-2.5 text-sm rounded-lg border border-[#DDE2DD] bg-[#F7F8F6] focus:outline-none focus:border-[#28722C] transition-all text-right" />
                  </div>
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

/* ── ArticleSidebar ── */
export function ArticleSidebar({ meta, tocItems }: { meta: PostMeta; tocItems: string[] }) {
  return (
    <aside className="space-y-5" aria-label="نوار کناری مقاله">
      {/* TOC */}
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
        <a href={BOOKING_URL} rel="noopener"
          className="flex items-center justify-center gap-2 w-full py-2.5 bg-white text-[#28722C] rounded-xl text-sm font-bold hover:bg-[#E4F0E4] transition-colors">
          <Phone size={13} />تشکیل پرونده
        </a>
        <div className="flex items-center justify-center gap-1.5 mt-3 text-xs text-white/70">
          <Shield size={11} />بدون تعهد · رایگان
        </div>
      </div>

      {/* Author mini */}
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
            { Icon: Shield,      label: "مجوز وزارت بهداشت ایران" },
            { Icon: Award,       label: "بورد تخصصی معتبر ISAPS" },
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

      {/* Tags */}
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

/* ServiceSidebar removed — ServiceDetailTemplate has its own sidebar */

/* ── MedicalDisclaimer ── */
export function MedicalDisclaimer() {
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
