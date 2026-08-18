/**
 * TagArchivePage — WP: tag.php / Tag Hub
 * Shows posts filtered by tag with tag cloud.
 *
 * WP NOTE:
 *   URL: /tag/:slug
 *   register_taxonomy('post_tag', 'post') — built-in
 *   Data: WP_Query([ 'tag' => $slug ])
 */
import { useState } from "react";
import { useParams, Link } from "react-router";
import { Hash, ArrowLeft, Calendar } from "lucide-react";
import { BLOG_POSTS } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";
import JsonLd, { buildBreadcrumbSchema } from "@/components/JsonLd";

/* WP NOTE: Built-in post_tag taxonomy — PHP: register_taxonomy() */
const ALL_TAGS = [
  { slug: "رینوپلاستی",         label: "رینوپلاستی",          count: 8 },
  { slug: "جراحی-بینی-ترمیمی", label: "جراحی بینی ترمیمی",   count: 4 },
  { slug: "بینی-گوشتی",         label: "بینی گوشتی",           count: 3 },
  { slug: "بینی-استخوانی",      label: "بینی استخوانی",        count: 3 },
  { slug: "سپتوپلاستی",         label: "سپتوپلاستی",           count: 2 },
  { slug: "مراقبت-بعد-از-عمل",  label: "مراقبت بعد از عمل",   count: 5 },
  { slug: "ریکاوری",            label: "ریکاوری",              count: 4 },
  { slug: "جراحی-بینی-مردانه",  label: "جراحی بینی مردانه",   count: 2 },
  { slug: "آندوسکوپی-سینوس",   label: "آندوسکوپی سینوس",      count: 2 },
  { slug: "قوز-بینی",           label: "قوز بینی",             count: 3 },
  { slug: "هزینه-جراحی-بینی",  label: "هزینه جراحی بینی",    count: 2 },
  { slug: "دکتر-باستانی‌نژاد", label: "دکتر باستانی‌نژاد",   count: 11 },
];

export default function TagArchivePage() {
  const { slug = "" } = useParams<{ slug: string }>();
  const [activeTag, setActiveTag] = useState(slug || "");

  const currentTag = ALL_TAGS.find(t => t.slug === activeTag) || { slug: activeTag, label: activeTag || "تگ", count: 0 };

  // WP: WP_Query tag filter — all posts if no tag selected
  const posts = activeTag
    ? BLOG_POSTS.filter(p =>
        p.title.includes(currentTag.label.split("-")[0]) ||
        p.excerpt.includes(currentTag.label.split("-")[0])
      )
    : BLOG_POSTS;

  return (
    <>
      <JsonLd
        data={buildBreadcrumbSchema([
          { label: "خانه", href: "/" },
          { label: "برچسب‌ها", href: "/tag" },
          ...(activeTag ? [{ label: currentTag.label }] : []),
        ])}
        id="tag-schema"
      />
      <Breadcrumb items={[
        { label: "خانه", href: "/" },
        { label: "وبلاگ", href: "/blog" },
        { label: activeTag ? `#${currentTag.label}` : "همه برچسب‌ها" },
      ]} />

      {/* Tag Hub Header */}
      <header className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-14 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto">
          <div className="inline-flex items-center gap-1.5 text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full border border-[#28722C]/30 mb-4">
            <Hash size={12} />برچسب‌ها
          </div>
          <h1 className="text-3xl sm:text-4xl font-bold mb-3">
            {activeTag ? `#${currentTag.label}` : "مرکز برچسب‌ها"}
          </h1>
          <p className="text-white/70 text-base max-w-xl">
            {activeTag ? `${posts.length} مقاله با برچسب "${currentTag.label}"` : "همه برچسب‌های سایت و مقالات مرتبط"}
          </p>
        </div>
      </header>

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-10">

        {/* ── Tag cloud ── */}
        <section aria-labelledby="tagcloud-heading" className="mb-10">
          <h2 id="tagcloud-heading" className="text-lg font-bold text-[#25272C] mb-4">همه برچسب‌ها</h2>
          <div className="archive__tag-cloud">
            <button
              onClick={() => setActiveTag("")}
              className={`archive__tag ${!activeTag ? "active" : ""}`}
            >
              همه
            </button>
            {ALL_TAGS.map(t => (
              <button
                key={t.slug}
                onClick={() => setActiveTag(t.slug)}
                className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors cursor-pointer ${activeTag === t.slug ? "bg-[#28722C] text-white border-[#28722C]" : "bg-[#E4F0E4] text-[#28722C] border-[#28722C]/20 hover:bg-[#28722C] hover:text-white"}`}
                aria-pressed={activeTag === t.slug}
              >
                #{t.label}
                <span className="text-[10px] opacity-60 mr-1">({t.count})</span>
              </button>
            ))}
          </div>
        </section>

        {/* ── Posts ── */}
        <section aria-labelledby="tagposts-heading">
          <h2 id="tagposts-heading" className="text-lg font-bold text-[#25272C] mb-5">
            {activeTag ? `مقالات با برچسب #${currentTag.label}` : "همه مقالات"}
            <span className="text-sm font-normal text-[#545B64] mr-2">({posts.length})</span>
          </h2>
          {posts.length === 0 ? (
            <p className="text-[#545B64] py-8 text-center">مقاله‌ای یافت نشد.</p>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
              {posts.map(p => (
                <article key={p.id} className="bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden group hover:shadow-md transition-shadow">
                  <div className="h-44 bg-[#DDE2DD] overflow-hidden">
                    <img src={p.img} alt={p.title}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                      loading="lazy" />
                  </div>
                  <div className="p-4">
                    <span className="inline-block text-xs font-semibold text-[#28722C] bg-[#E4F0E4] px-2 py-0.5 rounded-full mb-2">{p.cat}</span>
                    <h3 className="font-bold text-[#25272C] text-sm leading-relaxed mb-2 group-hover:text-[#28722C] transition-colors">{p.title}</h3>
                    <div className="flex items-center justify-between text-xs text-[#545B64]">
                      <span className="flex items-center gap-1"><Calendar size={11} />{p.date}</span>
                      <Link to={`/blog/${p.slug}`}
                        className="flex items-center gap-1 text-[#28722C] font-semibold hover:gap-2 transition-all">
                        بیشتر <ArrowLeft size={12} />
                      </Link>
                    </div>
                  </div>
                </article>
              ))}
            </div>
          )}
        </section>
      </div>
    </>
  );
}
