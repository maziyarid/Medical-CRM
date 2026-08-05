/**
 * NotFoundPage — 404 with search + popular services + contact CTA
 * WP: 404.php
 */
import { useState } from "react";
import { Link } from "react-router";
import { Search, Phone, ArrowLeft, Home } from "lucide-react";
import { COSMETIC_SERVICES, FUNCTIONAL_SERVICES, BOOKING_URL, BLOG_POSTS } from "@/data/site";
import JsonLd, { buildBreadcrumbSchema } from "@/components/JsonLd";
import Breadcrumb from "@/components/Breadcrumb";

export default function NotFoundPage() {
  const [query, setQuery] = useState("");

  const allServices = [...COSMETIC_SERVICES, ...FUNCTIONAL_SERVICES];
  const popularServices = allServices.slice(0, 4);
  const popularPosts = BLOG_POSTS.filter(p => p.featured || p.id <= 3).slice(0, 3);

  const filtered = query.trim().length > 1
    ? [
        ...allServices.filter(s => s.title.includes(query) || s.desc.includes(query)).map(s => ({ label: s.title, href: `/services/${s.slug}`, type: "خدمات" })),
        ...BLOG_POSTS.filter(p => p.title.includes(query) || p.excerpt.includes(query)).map(p => ({ label: p.title, href: `/blog/${p.slug}`, type: "مقاله" })),
      ].slice(0, 6)
    : [];

  return (
    <>
      <JsonLd data={buildBreadcrumbSchema([{ label: "خانه", href: "/" }, { label: "صفحه پیدا نشد" }])} id="404-schema" />
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "۴۰۴" }]} />

      {/* ── Hero ── */}
      <section className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-20 px-4 sm:px-6 text-center">
        <div className="max-w-xl mx-auto">
          {/* Code */}
          <div
            className="text-8xl sm:text-9xl font-black leading-none mb-4 select-none"
            style={{ color: "rgba(40,114,44,0.25)", textShadow: "0 0 0 #28722C" }}
            aria-hidden
          >
            ۴۰۴
          </div>
          <h1 className="text-2xl sm:text-3xl font-bold mb-3">صفحه پیدا نشد</h1>
          <p className="text-white/70 text-sm sm:text-base leading-relaxed mb-8">
            صفحه‌ای که دنبالش می‌گردید وجود ندارد یا آدرس آن تغییر کرده است.
            می‌توانید از جستجوی زیر یا لینک‌های پرطرفدار استفاده کنید.
          </p>

          {/* Search */}
          <div className="relative max-w-md mx-auto">
            <Search size={16} className="absolute right-4 top-1/2 -translate-y-1/2 text-white/40" aria-hidden />
            <input
              type="search"
              value={query}
              onChange={e => setQuery(e.target.value)}
              placeholder="جستجو در خدمات و مقالات..."
              className="w-full pr-10 pl-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder:text-white/40 text-sm focus:outline-none focus:border-[#28722C] focus:ring-2 focus:ring-[#28722C]/30 transition-all"
              aria-label="جستجو"
            />
          </div>

          {/* Live results */}
          {filtered.length > 0 && (
            <div className="mt-3 bg-white rounded-xl overflow-hidden max-w-md mx-auto text-right">
              {filtered.map((r, i) => (
                <Link key={i} to={r.href}
                  className="flex items-center justify-between px-4 py-3 hover:bg-[#E4F0E4] transition-colors border-b border-[#DDE2DD] last:border-0">
                  <span className="text-sm text-[#25272C]">{r.label}</span>
                  <span className="text-xs text-[#28722C] bg-[#E4F0E4] px-2 py-0.5 rounded-full">{r.type}</span>
                </Link>
              ))}
            </div>
          )}
        </div>
      </section>

      {/* ── Popular services ── */}
      <section className="max-w-[1200px] mx-auto px-4 sm:px-6 py-12">
        <h2 className="text-xl font-bold text-[#25272C] mb-6">خدمات پرطرفدار</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
          {popularServices.map(s => (
            <Link key={s.id} to={`/services/${s.slug}`}
              className="bg-white rounded-2xl border border-[#DDE2DD] p-5 hover:border-[#28722C]/30 hover:shadow-md transition-all group">
              <div className="w-10 h-10 rounded-xl bg-[#E4F0E4] flex items-center justify-center mb-3 group-hover:bg-[#28722C] transition-colors">
                <s.icon size={18} className="text-[#28722C] group-hover:text-white transition-colors" />
              </div>
              <h3 className="font-semibold text-[#25272C] text-sm mb-1 group-hover:text-[#28722C] transition-colors">{s.title}</h3>
              <p className="text-xs text-[#545B64] line-clamp-2">{s.subtitle}</p>
            </Link>
          ))}
        </div>

        {/* ── Popular posts ── */}
        <h2 className="text-xl font-bold text-[#25272C] mb-6">مقالات محبوب</h2>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10">
          {popularPosts.map(p => (
            <Link key={p.id} to={`/blog/${p.slug}`}
              className="bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden group hover:shadow-md transition-shadow">
              <div className="h-36 bg-[#DDE2DD] overflow-hidden">
                <img src={p.img} alt={p.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
              </div>
              <div className="p-4">
                <span className="text-xs font-semibold text-[#28722C] bg-[#E4F0E4] px-2 py-0.5 rounded-full mb-2 inline-block">{p.cat}</span>
                <h3 className="font-semibold text-[#25272C] text-sm leading-relaxed group-hover:text-[#28722C] transition-colors">{p.title}</h3>
              </div>
            </Link>
          ))}
        </div>

        {/* ── CTA ── */}
        <div className="bg-gradient-to-br from-[#28722C] to-[#1a4e1d] rounded-2xl p-8 text-center text-white">
          <h2 className="text-xl font-bold mb-2">نیاز به مشاوره دارید؟</h2>
          <p className="text-white/80 text-sm mb-6">تیم ما آماده پاسخگویی است. همین حالا تماس بگیرید.</p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
            <Link to="/"
              className="flex items-center gap-2 px-5 py-3 bg-white text-[#28722C] rounded-xl font-bold text-sm hover:bg-[#E4F0E4] transition-colors">
              <Home size={15} />بازگشت به خانه
            </Link>
            <a href="tel:02186087250"
              className="flex items-center gap-2 px-5 py-3 bg-white/20 border border-white/30 text-white rounded-xl font-bold text-sm hover:bg-white/30 transition-colors">
              <Phone size={15} />۰۲۱۸۶۰۸۷۲۵۰
            </a>
            <a href={BOOKING_URL} rel="noopener"
              className="flex items-center gap-2 px-5 py-3 bg-white/20 border border-white/30 text-white rounded-xl font-bold text-sm hover:bg-white/30 transition-colors">
              رزرو نوبت آنلاین <ArrowLeft size={14} />
            </a>
          </div>
        </div>
      </section>
    </>
  );
}
