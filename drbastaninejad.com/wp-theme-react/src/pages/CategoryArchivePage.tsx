/**
 * CategoryArchivePage — WP: category.php
 * Displays all posts in a given category.
 *
 * WP NOTE:
 *   URL: /category/:slug
 *   Data: WP_Query([ 'category_name' => $slug, 'posts_per_page' => 12 ])
 *   Pagination: WP paginate_links()
 */
import { useParams, Link } from "react-router";
import { Calendar, Clock, ArrowLeft, Tag } from "lucide-react";
import { BLOG_POSTS } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";
import JsonLd, { buildBreadcrumbSchema } from "@/components/JsonLd";

/* WP NOTE: register_taxonomy('category', 'post') — built-in */
const CATEGORY_META: Record<string, { label: string; description: string }> = {
  "جراحی-بینی":   { label: "جراحی بینی",    description: "مقالات تخصصی درباره رینوپلاستی و انواع جراحی بینی" },
  "راهنمای-بیمار":{ label: "راهنمای بیمار", description: "اطلاعات کاربردی برای بیماران قبل و بعد از عمل" },
  "مراقبت‌ها":    { label: "مراقبت‌ها",     description: "نکات مراقبتی و ریکاوری پس از جراحی بینی" },
  "آموزشی":       { label: "آموزشی",         description: "مقالات علمی و آموزشی درباره جراحی پلاستیک بینی" },
};

export default function CategoryArchivePage() {
  const { slug = "" } = useParams<{ slug: string }>();

  // WP: get_category_by_slug($slug)
  const meta = CATEGORY_META[slug] || { label: slug || "دسته‌بندی", description: "مقالات این دسته‌بندی" };

  // WP: WP_Query category filter
  const posts = BLOG_POSTS.filter(p => {
    const normalized = p.cat.replace(/\s/g, "-");
    return normalized === slug || p.cat === slug;
  });

  return (
    <>
      <JsonLd
        data={buildBreadcrumbSchema([
          { label: "خانه", href: "/" },
          { label: "وبلاگ", href: "/blog" },
          { label: meta.label },
        ])}
        id="cat-schema"
      />

      <Breadcrumb items={[
        { label: "خانه", href: "/" },
        { label: "وبلاگ", href: "/blog" },
        { label: meta.label },
      ]} />

      {/* Archive hero */}
      <header className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-14 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto">
          <span className="inline-flex items-center gap-1.5 text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full border border-[#28722C]/30 mb-4">
            <Tag size={12} />دسته‌بندی
          </span>
          {/* WP: single_cat_title() */}
          <h1 className="text-3xl sm:text-4xl font-bold mb-3">{meta.label}</h1>
          <p className="text-white/70 text-base leading-relaxed max-w-xl">{meta.description}</p>
          {/* WP: $wp_query->found_posts */}
          <p className="text-white/50 text-sm mt-3">{posts.length} مقاله</p>
        </div>
      </header>

      {/* Posts grid */}
      <section className="max-w-[1200px] mx-auto px-4 sm:px-6 py-12">
        {posts.length === 0 ? (
          <div className="text-center py-16">
            <p className="text-[#545B64] mb-4">هیچ مقاله‌ای در این دسته‌بندی یافت نشد.</p>
            <Link to="/blog" className="inline-flex items-center gap-1.5 px-4 py-2 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
              همه مقالات <ArrowLeft size={13} />
            </Link>
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {/* WP: foreach ($posts as $post) setup_postdata($post) */}
            {posts.map(p => (
              <article key={p.id} className="bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden group hover:shadow-md transition-shadow">
                <div className="h-48 bg-[#DDE2DD] overflow-hidden">
                  <img
                    src={p.img}
                    alt={p.title}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    loading="lazy"
                  />
                </div>
                <div className="p-5">
                  <div className="flex items-center gap-2 mb-3">
                    <span className="text-xs font-semibold text-[#28722C] bg-[#E4F0E4] px-2 py-0.5 rounded-full">{p.cat}</span>
                  </div>
                  {/* WP: the_title() */}
                  <h2 className="font-bold text-[#25272C] leading-relaxed mb-2 group-hover:text-[#28722C] transition-colors">{p.title}</h2>
                  {/* WP: the_excerpt() */}
                  <p className="text-sm text-[#545B64] line-clamp-2 mb-4">{p.excerpt}</p>
                  <div className="flex items-center justify-between text-xs text-[#545B64]">
                    <div className="flex items-center gap-3">
                      <span className="flex items-center gap-1"><Calendar size={11} />{p.date}</span>
                    </div>
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

        {/* Pagination — WP: paginate_links() */}
        {/* WP/Theme: pagination_type (select) — PHP: paginate_links(['prev_text'=>'قبلی','next_text'=>'بعدی']) */}
        {posts.length > 0 && (
          <div className="flex justify-center gap-2 mt-12">
            <span className="px-4 py-2 bg-[#28722C] text-white rounded-lg text-sm font-bold cursor-default" aria-current="page">۱</span>
          </div>
        )}
      </section>
    </>
  );
}
