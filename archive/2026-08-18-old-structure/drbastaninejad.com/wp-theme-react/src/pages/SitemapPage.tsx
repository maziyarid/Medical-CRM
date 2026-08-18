/**
 * SitemapPage — Visual Sitemap with search
 * WP: page-sitemap.php
 *
 * WP NOTE: Links populated from wp_nav_menu(), get_posts(), get_terms().
 *   PHP: register custom page template 'page-sitemap.php'
 */
import { useState } from "react";
import { Link } from "react-router";
import { Search, Home, Info, Scissors, BookOpen, Image, HelpCircle, Phone, Calendar, FileText, Shield, Lock, XCircle } from "lucide-react";
import Breadcrumb from "@/components/Breadcrumb";
import JsonLd, { buildBreadcrumbSchema } from "@/components/JsonLd";
import { COSMETIC_SERVICES, FUNCTIONAL_SERVICES, BLOG_POSTS } from "@/data/site";

interface SitemapItem {
  label: string;
  href:  string;
  desc?: string;
}

interface SitemapGroup {
  title:   string;
  icon:    React.ReactNode;
  color:   string;
  items:   SitemapItem[];
}

const SITEMAP_GROUPS: SitemapGroup[] = [
  {
    title: "صفحات اصلی",
    icon: <Home size={16} />,
    color: "text-[#28722C]",
    items: [
      { label: "خانه",              href: "/",        desc: "صفحه اصلی کلینیک" },
      { label: "درباره دکتر",       href: "/about",   desc: "سوابق و اعتبارنامه‌های دکتر باستانی‌نژاد" },
      { label: "گالری",             href: "/gallery", desc: "گالری قبل و بعد از جراحی" },
      { label: "سوالات متداول",     href: "/faq",     desc: "پرسش‌های رایج بیماران" },
      { label: "تماس با ما",        href: "/contact", desc: "اطلاعات تماس و فرم درخواست مشاوره" },
      { label: "رزرو نوبت",         href: "/booking", desc: "رزرو مشاوره آنلاین" },
      { label: "نقشه سایت",         href: "/sitemap", desc: "این صفحه" },
    ],
  },
  {
    title: "خدمات زیبایی",
    icon: <Scissors size={16} />,
    color: "text-[#B8860B]",
    items: [
      { label: "همه خدمات",               href: "/services",                       desc: "فهرست کامل خدمات" },
      { label: "جراحی بینی — نمای کلی",   href: "/services/rhinoplasty",           desc: "معرفی انواع رینوپلاستی" },
      ...COSMETIC_SERVICES.map(s => ({ label: s.title, href: `/services/${s.slug}`, desc: s.subtitle })),
    ],
  },
  {
    title: "خدمات درمانی",
    icon: <Shield size={16} />,
    color: "text-[#1e6fce]",
    items: FUNCTIONAL_SERVICES.map(s => ({ label: s.title, href: `/services/${s.slug}`, desc: s.subtitle })),
  },
  {
    title: "وبلاگ",
    icon: <BookOpen size={16} />,
    color: "text-[#9333ea]",
    items: [
      { label: "همه مقالات", href: "/blog", desc: "آرشیو کامل مقالات" },
      ...BLOG_POSTS.map(p => ({ label: p.title, href: `/blog/${p.slug}`, desc: p.cat })),
    ],
  },
  {
    title: "صفحات قانونی",
    icon: <Lock size={16} />,
    color: "text-[#545B64]",
    items: [
      { label: "سیاست حریم خصوصی",  href: "/legal/privacy",      desc: "نحوه جمع‌آوری و استفاده از اطلاعات" },
      { label: "شرایط استفاده",       href: "/legal/terms",        desc: "قوانین و مقررات استفاده از سایت" },
      { label: "سیاست لغو",           href: "/legal/cancellation", desc: "شرایط لغو و کنسلی وقت" },
    ],
  },
];

export default function SitemapPage() {
  const [query, setQuery] = useState("");

  const filtered: (SitemapGroup & { items: SitemapItem[] })[] = SITEMAP_GROUPS.map(g => ({
    ...g,
    items: query
      ? g.items.filter(i => i.label.includes(query) || (i.desc && i.desc.includes(query)))
      : g.items,
  })).filter(g => g.items.length > 0);

  const totalLinks = SITEMAP_GROUPS.reduce((sum, g) => sum + g.items.length, 0);

  return (
    <>
      <JsonLd
        data={buildBreadcrumbSchema([{ label: "خانه", href: "/" }, { label: "نقشه سایت" }])}
        id="sitemap-schema"
      />
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "نقشه سایت" }]} />

      {/* Hero */}
      <header className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-14 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto">
          <h1 className="text-3xl sm:text-4xl font-bold mb-3">نقشه سایت</h1>
          <p className="text-white/70 mb-6">{totalLinks} صفحه در سایت کلینیک دکتر باستانی‌نژاد</p>
          {/* Search */}
          <div className="relative max-w-sm">
            <Search size={15} className="absolute right-3 top-1/2 -translate-y-1/2 text-white/40" aria-hidden />
            <input
              type="search"
              value={query}
              onChange={e => setQuery(e.target.value)}
              placeholder="جستجو در نقشه سایت..."
              className="w-full pr-9 pl-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder:text-white/40 text-sm focus:outline-none focus:border-[#28722C] transition-all"
              aria-label="جستجو در نقشه سایت"
            />
            {query && (
              <button onClick={() => setQuery("")} className="absolute left-3 top-1/2 -translate-y-1/2 text-white/40 hover:text-white" aria-label="پاک کردن جستجو">
                <XCircle size={15} />
              </button>
            )}
          </div>
        </div>
      </header>

      {/* Sitemap grid */}
      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-10 space-y-6">
        {filtered.map(group => (
          <div key={group.title} className="bg-white rounded-2xl border border-[#DDE2DD] p-5">
            <h2 className={`flex items-center gap-2 text-base font-bold mb-4 pb-3 border-b border-[#DDE2DD] ${group.color}`}>
              {group.icon}
              {group.title}
              <span className="text-xs font-normal text-[#545B64] mr-auto">{group.items.length} مورد</span>
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">
              {group.items.map(item => (
                <Link key={item.href} to={item.href}
                  className="flex items-start gap-2 px-3 py-2 rounded-lg text-sm text-[#545B64] hover:text-[#28722C] hover:bg-[#E4F0E4] transition-colors">
                  <FileText size={12} aria-hidden className="flex-shrink-0 mt-0.5" />
                  <span>
                    <span className="block text-[#25272C] font-medium text-sm leading-tight">{item.label}</span>
                    {item.desc && <span className="text-[11px] text-[#545B64]">{item.desc}</span>}
                  </span>
                </Link>
              ))}
            </div>
          </div>
        ))}

        {filtered.length === 0 && (
          <div className="text-center py-16">
            <p className="text-[#545B64]">نتیجه‌ای یافت نشد. جستجوی دیگری امتحان کنید.</p>
          </div>
        )}
      </div>
    </>
  );
}
