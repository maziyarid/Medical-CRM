/**
 * BlogListPage — blog.php / archive.php equivalent
 * WP: WP_Query post_type='post', query_var for category filtering
 * ACF: post_read_time, post_difficulty, post_featured
 */
import { useState } from "react";
import { Link } from "react-router";
import { Clock, ArrowLeft, Calendar } from "lucide-react";
import { BLOG_POSTS } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";

export default function BlogListPage() {
  const [activecat, setActiveCat] = useState("همه");
  const cats = ["همه", ...Array.from(new Set(BLOG_POSTS.map(p => p.cat)))];
  const filtered = activecat === "همه" ? BLOG_POSTS : BLOG_POSTS.filter(p => p.cat === activecat);
  const featured = filtered.find(p => p.featured) ?? filtered[0];
  const rest = filtered.filter(p => p.id !== featured.id);

  return (
    <>
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "مقالات" }]} />

      {/* Header */}
      <section className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-12 sm:py-16 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto text-center">
          <span className="text-[#28722C] font-bold text-sm tracking-wider">دانش پزشکی</span>
          <h1 className="text-3xl sm:text-5xl font-black mt-2 mb-3">مقالات تخصصی</h1>
          <p className="text-white/70 text-base sm:text-lg">آموزش، راهنمایی و اطلاعات علمی درباره جراحی بینی</p>
        </div>
      </section>

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-8 sm:py-12">
        {/* Category filter — WP: wp_list_categories() */}
        <div className="flex flex-wrap gap-2 mb-8">
          {cats.map(c => (
            <button key={c} onClick={() => setActiveCat(c)}
              className={`px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold transition-all ${
                activecat === c
                  ? "bg-[#28722C] text-white"
                  : "bg-white border border-[#DDE2DD] text-[#545B64] hover:border-[#28722C]/30 hover:text-[#28722C]"
              }`}>
              {c}
            </button>
          ))}
        </div>

        {/* Featured post — mobile: compact vertical; desktop: wide banner */}
        <Link to={`/blog/${featured.slug}`} className="block group mb-8 sm:mb-10">
          {/* Mobile layout: card style */}
          <div className="sm:hidden bg-white border border-[#DDE2DD] rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div className="relative h-48 overflow-hidden">
              <img src={featured.img} alt={featured.title}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
              <span className="absolute top-3 right-3 bg-[#28722C] text-white text-xs font-bold px-3 py-1 rounded-full">مقاله ویژه</span>
            </div>
            <div className="p-4">
              <span className="text-xs font-bold text-[#28722C] bg-[#E4F0E4] px-2.5 py-1 rounded-full inline-block mb-2">{featured.cat}</span>
              <h2 className="text-base font-black text-[#25272C] leading-snug mb-2 group-hover:text-[#28722C] transition-colors">{featured.title}</h2>
              <p className="text-sm text-[#545B64] line-clamp-2 mb-3">{featured.excerpt}</p>
              <div className="flex items-center justify-between text-xs text-[#545B64]">
                <span className="flex items-center gap-1"><Clock size={11} />۱۲ دقیقه مطالعه</span>
                <span className="flex items-center gap-1"><Calendar size={11} />{featured.date}</span>
              </div>
            </div>
          </div>

          {/* Desktop layout: wide banner */}
          <div className="hidden sm:block relative rounded-3xl overflow-hidden aspect-[16/7] shadow-lg">
            <img src={featured.img} alt={featured.title}
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
            <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
            <div className="absolute top-4 right-4">
              <span className="bg-[#28722C] text-white text-xs font-bold px-3 py-1.5 rounded-full">مقاله ویژه</span>
            </div>
            <div className="absolute bottom-0 inset-x-0 p-6 sm:p-8 text-white">
              <span className="text-xs font-bold bg-white/20 backdrop-blur-sm rounded-full px-3 py-1.5 mb-3 inline-block">{featured.cat}</span>
              <h2 className="text-xl sm:text-3xl font-black leading-snug mb-2">{featured.title}</h2>
              <p className="text-white/75 line-clamp-2 text-sm sm:text-base">{featured.excerpt}</p>
              <div className="flex items-center gap-4 mt-3 text-white/60 text-sm">
                <span className="flex items-center gap-1"><Clock size={12} />۱۲ دقیقه</span>
                <span>{featured.date}</span>
              </div>
            </div>
          </div>
        </Link>

        {/* Post grid — PHP: foreach ($posts as $post) the_post(); */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          {rest.map(post => (
            <article key={post.id}>
              <Link to={`/blog/${post.slug}`}
                className="group bg-white border border-[#DDE2DD] rounded-2xl overflow-hidden hover:shadow-md hover:border-[#28722C]/20 transition-all block h-full">
                {/* Image */}
                <div className="overflow-hidden">
                  <img src={post.img} alt={post.title}
                    className="w-full h-40 sm:h-48 object-cover group-hover:scale-105 transition-transform duration-500" />
                </div>
                {/* Body */}
                <div className="p-4">
                  <span className="text-xs font-bold text-[#28722C] bg-[#E4F0E4] px-2.5 py-0.5 rounded-full inline-block mb-2">{post.cat}</span>
                  <h3 className="font-bold text-[#25272C] mb-2 leading-snug text-sm sm:text-base group-hover:text-[#28722C] transition-colors line-clamp-2">{post.title}</h3>
                  <p className="text-xs sm:text-sm text-[#545B64] line-clamp-2 mb-3">{post.excerpt}</p>
                  {/* Meta row — proper spacing on mobile */}
                  <div className="flex items-center justify-between text-xs text-[#545B64] pt-2 border-t border-[#DDE2DD]/50">
                    <span className="flex items-center gap-1">
                      <Clock size={10} />
                      <span>۸ دقیقه</span>
                    </span>
                    <span className="flex items-center gap-1">
                      <Calendar size={10} />
                      <span>{post.date}</span>
                    </span>
                  </div>
                </div>
              </Link>
            </article>
          ))}
        </div>

        {/* Pagination placeholder — WP: the_posts_pagination() */}
        <div className="mt-10 sm:mt-12 flex justify-center gap-2">
          {[1, 2, 3].map(n => (
            <button key={n}
              className={`w-10 h-10 rounded-xl font-bold text-sm ${
                n === 1
                  ? "bg-[#28722C] text-white"
                  : "bg-white border border-[#DDE2DD] text-[#545B64] hover:border-[#28722C] hover:text-[#28722C]"
              }`}>
              {n}
            </button>
          ))}
          <button className="flex items-center gap-1 px-4 h-10 rounded-xl bg-white border border-[#DDE2DD] text-[#545B64] text-sm hover:border-[#28722C] hover:text-[#28722C]">
            بعدی <ArrowLeft size={13} />
          </button>
        </div>
      </div>
    </>
  );
}
