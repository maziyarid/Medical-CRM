/**
 * BlogPostTemplate — single-post.php equivalent
 * Used by ALL 11 blog post pages. Each page passes its own PostMeta + content.
 *
 * WP: PHP template hierarchy → single-post.php
 * ACF field group: Blog Post Details
 *   • post_subtitle          • post_read_time
 *   • post_difficulty        • last_medical_review_date
 *   • key_takeaways_list     • faq_items (repeater)
 *   • testimonials (relationship CPT)
 *
 * BACKEND NOTE:
 *   Sections marked { BACKEND } should be replaced with:
 *   - PHP: get_field() / the_content() / get_the_author_meta() calls
 *   - Dynamic data from WP database (title, date, content, tags, etc.)
 *   The static mock data in each page file is ONLY a design reference.
 */
import { useState, useEffect } from "react";
import {
  Calendar, Clock, Eye, CheckCircle, Info, AlertCircle,
  Tag, Share2, Bookmark, ThumbsUp, Phone, Shield,
} from "lucide-react";
import Breadcrumb from "@/components/Breadcrumb";
import {
  Badge, DifficultyBadge, FAQSection, AuthorBio, Testimonials,
  RelatedPosts, ContactCTA, ArticleSidebar, MedicalDisclaimer,
  type PostMeta, type FAQItem, type TestimonialItem, type RelatedPostItem,
} from "@/components/ArticleAtoms";
import { AUTHOR } from "@/data/site";

/* ── Default shared testimonials for all posts ── */
export const SHARED_TESTIMONIALS: TestimonialItem[] = [
  { name: "سارا م.",    age: "۲۸ ساله", rating: 5, text: "از نتیجه عمل بینی‌ام بسیار راضی هستم. دکتر باستانی‌نژاد با دقت و حوصله همه چیز را توضیح داد. بینی‌ام کاملاً طبیعی به نظر می‌رسد.", date: "مرداد ۱۴۰۳", verified: true, procedure: "رینوپلاستی طبیعی" },
  { name: "نیلوفر ک.", age: "۳۵ ساله", rating: 5, text: "بعد از سال‌ها تردید بالاخره تصمیم گرفتم. تیم پزشکی فوق‌العاده حرفه‌ای بودند و ریکاوری راحت‌تر از آنچه انتظار داشتم بود.", date: "خرداد ۱۴۰۳", verified: true, procedure: "رینوپلاستی فانتزی" },
  { name: "محمد ر.",   age: "۳۲ ساله", rating: 5, text: "انحراف تیغه بینی مشکل تنفسی داشتم. بعد از عمل هم تنفسم بهتر شده هم ظاهرم. یک سنگ، دو پرنده!", date: "اردیبهشت ۱۴۰۳", verified: true, procedure: "سپتورینوپلاستی" },
];

/* ── Default related posts ── */
export const SHARED_RELATED: RelatedPostItem[] = [
  { title: "انواع بینی و روش‌های جراحی مناسب هر نوع", slug: "rhinoplasty", category: "آموزشی", readTime: "۸ دقیقه", imgId: "/images/Blog/Blog-101.webp", date: "۱۵ تیر ۱۴۰۳", excerpt: "راهنمای انتخاب بهترین روش جراحی بر اساس نوع بافت و ساختار بینی شما." },
  { title: "مراقبت‌های بعد از عمل بینی — راهنمای جامع", slug: "post-op-care", category: "مراقبت", readTime: "۶ دقیقه", imgId: "/images/Blog/Blog-102.webp", date: "۲ خرداد ۱۴۰۳", excerpt: "تمام نکاتی که برای مراقبت از بینی در دوره ریکاوری باید بدانید." },
  { title: "رینوپلاستی ترمیمی: وقتی نتیجه رضایت‌بخش نیست", slug: "revision-rhinoplasty", category: "تخصصی", readTime: "۱۰ دقیقه", imgId: "/images/Blog/Blog-103.webp", date: "۱ اردیبهشت ۱۴۰۳", excerpt: "همه چیز درباره جراحی ترمیمی بینی، دلایل نیاز به آن و انتظارات واقع‌بینانه." },
];

/* ── Props ── */
interface BlogPostTemplateProps {
  meta: PostMeta;
  faqs: FAQItem[];
  tocItems: string[];
  children: React.ReactNode;   // the actual article body sections
  testimonials?: TestimonialItem[];
  related?: RelatedPostItem[];
}

export default function BlogPostTemplate({
  meta, faqs, tocItems, children,
  testimonials = SHARED_TESTIMONIALS,
  related = SHARED_RELATED,
}: BlogPostTemplateProps) {
  const [liked, setLiked] = useState(false);
  const [likeCount, setLikeCount] = useState(meta.viewCount > 100 ? 247 : 42);
  const [bookmarked, setBookmarked] = useState(false);
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

  return (
    <>
      {/* Read progress bar — PHP: JS-driven scroll listener */}
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

          {/* ── MAIN ARTICLE ── */}
          <main id="main-content">
            <article>

              {/* ── POST HEADER — WP: get_the_title(), get_the_date(), ACF meta fields ── */}
              <header className="mb-6">
                {/* Badges — wrap gracefully on narrow screens */}
                <div className="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-3">
                  <Badge variant="primary"><Tag size={10} />{meta.category}</Badge>
                  <DifficultyBadge level={meta.difficultyLevel} />
                  <Badge variant="info"><Shield size={10} className="hidden sm:block" />تأیید پزشک</Badge>
                  <Badge variant="accent" ><Info size={10} className="hidden sm:block" />مبتنی بر شواهد</Badge>
                </div>
                {/* { BACKEND } WP: the_title() */}
                <h1 className="text-xl sm:text-2xl lg:text-[1.9rem] font-bold text-[#25272C] leading-snug mb-2">{meta.title}</h1>
                {/* { BACKEND } ACF: post_subtitle */}
                <p className="text-sm sm:text-base text-[#545B64] leading-relaxed mb-4">{meta.subtitle}</p>

                {/* Author row */}
                <div className="flex items-center gap-2 mb-3">
                  <div className="w-8 h-8 rounded-full bg-[#28722C] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">{meta.author.avatarInitials}</div>
                  <div className="min-w-0">
                    <span className="font-semibold text-[#25272C] text-xs sm:text-sm">{meta.author.name}</span>
                    <span className="text-[#545B64] text-xs"> — {meta.author.title}</span>
                  </div>
                </div>

                {/* Meta chips row — { BACKEND } WP: get_the_date(), get_post_meta views */}
                <div className="flex flex-wrap items-center gap-2 sm:gap-3 text-xs text-[#545B64] pb-4 border-b border-[#DDE2DD]">
                  <span className="flex items-center gap-1"><Calendar size={11} />{meta.publishDate}</span>
                  <span className="flex items-center gap-1"><Clock size={11} />{meta.readTimeMin} دقیقه</span>
                  <span className="flex items-center gap-1"><Eye size={11} />{meta.viewCount.toLocaleString("fa-IR")} بازدید</span>
                  {/* { BACKEND } ACF: last_medical_review_date — critical for YMYL/E-E-A-T */}
                  <span className="flex items-center gap-1 text-[#28722C] font-medium">
                    <CheckCircle size={11} />بازبینی: {meta.lastReviewed}
                  </span>
                </div>

                {/* Action buttons */}
                <div className="flex flex-wrap items-center gap-2 pt-4">
                  <button
                    onClick={() => { setLiked(!liked); setLikeCount(c => liked ? c - 1 : c + 1); }}
                    className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition-all ${liked ? "bg-[#28722C] text-white border-[#28722C]" : "bg-white text-[#545B64] border-[#DDE2DD] hover:border-[#28722C] hover:text-[#28722C]"}`}>
                    <ThumbsUp size={13} />{likeCount.toLocaleString("fa-IR")}
                  </button>
                  <button
                    onClick={() => setBookmarked(!bookmarked)}
                    className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition-all ${bookmarked ? "bg-amber-50 text-amber-700 border-amber-300" : "bg-white text-[#545B64] border-[#DDE2DD] hover:border-amber-300 hover:text-amber-700"}`}>
                    <Bookmark size={13} />ذخیره
                  </button>
                  <button className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border border-[#DDE2DD] bg-white text-[#545B64] hover:border-[#28722C] hover:text-[#28722C] transition-all">
                    <Share2 size={13} />اشتراک‌گذاری
                  </button>
                </div>
              </header>

              {/* ── FEATURED IMAGE — { BACKEND } WP: the_post_thumbnail('full') ── */}
              <div className="relative rounded-2xl overflow-hidden mb-8 bg-[#DDE2DD]">
                <img
                  src={meta.featuredImage.url}
                  alt={meta.featuredImage.alt}
                  className="w-full h-64 sm:h-80 object-cover"
                  loading="eager"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-[#25272C]/50 via-transparent to-transparent" />
                <div className="absolute bottom-4 right-4 left-4">
                  <p className="text-white text-sm font-medium drop-shadow-sm">
                    کلینیک دکتر شاهین باستانی‌نژاد — مجهز به تکنولوژی روز جراحی پلاستیک
                  </p>
                </div>
              </div>

              {/* ── ARTICLE BODY — provided by each individual page ── */}
              <div className="space-y-10">
                {children}

                {/* ── FAQ — { BACKEND } ACF repeater: faq_items ── */}
                <section id="section-faq">
                  <FAQSection faqs={faqs} heading="پرسش‌های متداول" />
                </section>

                {/* Mid CTA — { BACKEND } ACF: mid_cta_text, mid_cta_url */}
                <div className="bg-[#E4F0E4] border border-[#28722C]/20 rounded-2xl p-6 flex flex-col sm:flex-row items-center gap-4">
                  <div className="flex-1">
                    <p className="font-bold text-[#25272C] mb-1">می‌خواهید بدانید این روش برای شما مناسب است؟</p>
                    <p className="text-sm text-[#545B64]">پرونده اولیه خود را تکمیل کنید تا دکتر باستانی‌نژاد وضعیت بینی شما را بررسی کنند.</p>
                  </div>
                  <a href="https://app.drbastaninejad.com/" rel="noopener"
                    className="flex-shrink-0 flex items-center gap-2 px-5 py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
                    <Phone size={14} />تشکیل پرونده
                  </a>
                </div>
              </div>

              {/* Testimonials — { BACKEND } ACF relationship: testimonials or CPT */}
              <div className="mt-10">
                <Testimonials items={testimonials} />
              </div>

              {/* Author bio — { BACKEND } get_the_author_meta() + ACF user fields */}
              <div className="mt-10">
                <AuthorBio author={meta.author} />
              </div>
            </article>

            {/* Related posts — { BACKEND } WP_Query related by category+tags */}
            <div className="mt-10">
              <RelatedPosts posts={related} />
            </div>

            {/* Contact CTA */}
            <div className="mt-10">
              <ContactCTA />
            </div>
          </main>

          {/* ── SIDEBAR ── */}
          <div className="hidden xl:block">
            <ArticleSidebar meta={meta} tocItems={tocItems} />
          </div>
        </div>
      </div>
    </>
  );
}

/* ── Re-export atoms for convenience in post pages ── */
export { MedicalDisclaimer } from "@/components/ArticleAtoms";
export { AUTHOR } from "@/data/site";
