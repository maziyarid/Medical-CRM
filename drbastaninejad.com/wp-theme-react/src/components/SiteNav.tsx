/**
 * SiteNav — shared navigation component
 * PHP: include get_template_part('components/nav');
 * WP:  wp_nav_menu() for all link groups
 */
import { useState, useEffect, useRef } from "react";
import { Link, useLocation } from "react-router";
import {
  ChevronDown, Phone, X, Menu, Stethoscope,
} from "lucide-react";
import { NAV_MEGA_ITEMS, BOOKING_URL } from "@/data/site";

export default function SiteNav() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [megaOpen, setMegaOpen]     = useState(false);
  const [scrolled, setScrolled]     = useState(false);
  const megaRef = useRef<HTMLDivElement>(null);
  const location = useLocation();

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 50);
    window.addEventListener("scroll", onScroll);
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (megaRef.current && !megaRef.current.contains(e.target as Node)) setMegaOpen(false);
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  // Close mobile menu on navigation
  useEffect(() => { setMobileOpen(false); setMegaOpen(false); }, [location.pathname]);

  return (
    <nav
      role="navigation"
      aria-label="ناوبری اصلی"
      className={`site-nav sticky top-0 z-50 transition-all duration-200 border-b ${
        scrolled ? "bg-white/97 backdrop-blur-md shadow-sm border-[#DDE2DD]" : "bg-white border-[#DDE2DD]"
      }`}
    >
      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 flex items-center justify-between h-16">

        {/* Brand — WP: bloginfo('url') + custom_logo */}
        <Link to="/" aria-label="صفحه اصلی — دکتر شاهین باستانی‌نژاد" className="flex items-center gap-3 group flex-shrink-0">
          {/* Production: <img src="<?= get_template_directory_uri() ?>/assets/images/logo.svg" ... /> */}
          <div className="w-9 h-9 rounded-xl bg-[#28722C] flex items-center justify-center shadow-sm group-hover:bg-[#246b28] transition-colors">
            <Stethoscope size={18} className="text-white" />
          </div>
          <div className="leading-tight">
            <div className="text-sm font-bold text-[#25272C]">دکتر شاهین باستانی‌نژاد</div>
            <div className="text-[10px] text-[#6A7078]">جراح پلاستیک بینی · تهران</div>
          </div>
        </Link>

        {/* Desktop links */}
        <div className="hidden lg:flex items-center gap-0.5" ref={megaRef}>
          {[{ label: "خانه", href: "/" }, { label: "درباره دکتر", href: "/about" }].map(l => (
            <Link key={l.label} to={l.href}
              className="px-3 py-2 rounded-lg text-sm text-[#6A7078] hover:text-[#28722C] hover:bg-[#E4F0E4] transition-all font-medium">
              {l.label}
            </Link>
          ))}

          {/* Mega dropdown */}
          <div className="relative">
            <button
              onClick={() => setMegaOpen(!megaOpen)}
              className={`flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all ${megaOpen ? "bg-[#E4F0E4] text-[#28722C]" : "text-[#6A7078] hover:text-[#28722C] hover:bg-[#E4F0E4]"}`}
              aria-expanded={megaOpen}
            >
              جراحی بینی <ChevronDown size={14} className={`transition-transform ${megaOpen ? "rotate-180" : ""}`} />
            </button>
            {megaOpen && (
              <div className="absolute top-full right-0 mt-1 w-[600px] bg-white border border-[#DDE2DD] rounded-2xl shadow-xl p-4 grid grid-cols-3 gap-4" role="menu">
                {NAV_MEGA_ITEMS.map(col => (
                  <div key={col.group}>
                    <p className="text-[10px] font-bold text-[#6A7078] uppercase tracking-widest mb-2 px-2">{col.group}</p>
                    {col.items.map(item => {
                      const Icon = item.icon;
                      return (
                        <Link key={item.href} to={item.href} role="menuitem"
                          className="flex items-start gap-2.5 p-2 rounded-xl hover:bg-[#E4F0E4] transition-colors group"
                          onClick={() => setMegaOpen(false)}>
                          <span className="w-7 h-7 rounded-lg bg-[#E4F0E4] flex items-center justify-center flex-shrink-0 group-hover:bg-[#28722C] transition-colors">
                            <Icon size={13} className="text-[#28722C] group-hover:text-white transition-colors" />
                          </span>
                          <span className="min-w-0">
                            <span className="text-xs font-semibold text-[#25272C] block leading-tight">{item.label}</span>
                            <span className="text-[10px] text-[#6A7078] leading-tight">{item.sub}</span>
                          </span>
                        </Link>
                      );
                    })}
                  </div>
                ))}
                <div className="col-span-3 border-t border-[#DDE2DD] pt-3 mt-1 flex gap-3">
                  <Link to="/services" className="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-semibold text-[#28722C] bg-[#E4F0E4] rounded-lg hover:bg-[#28722C] hover:text-white transition-colors">
                    همه خدمات
                  </Link>
                  <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer"
                    className="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-semibold text-white bg-[#28722C] rounded-lg hover:bg-[#246b28] transition-colors">
                    نوبت‌گیری آنلاین
                  </a>
                </div>
              </div>
            )}
          </div>

          {[
            { label: "نمونه کارها", href: "/gallery" },
            { label: "مقالات",     href: "/blog" },
            { label: "سوالات",     href: "/faq" },
            { label: "تماس",       href: "/contact" },
          ].map(l => (
            <Link key={l.label} to={l.href}
              className="px-3 py-2 rounded-lg text-sm text-[#6A7078] hover:text-[#28722C] hover:bg-[#E4F0E4] transition-all font-medium">
              {l.label}
            </Link>
          ))}
        </div>

        {/* CTA */}
        <div className="flex items-center gap-2">
          <a href="tel:02186087250"
            className="hidden md:flex items-center gap-1.5 text-sm text-[#28722C] font-semibold hover:text-[#246b28] transition-colors"
            aria-label="تماس تلفنی">
            <Phone size={13} />۰۲۱۸۶۰۸۷۲۵۰
          </a>
          <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer"
            className="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 bg-[#28722C] text-white text-sm font-bold rounded-lg hover:bg-[#246b28] transition-colors">
            نوبت‌گیری
          </a>
          <button onClick={() => setMobileOpen(!mobileOpen)} className="lg:hidden p-2 rounded-lg hover:bg-[#E4F0E4] transition-colors" aria-label="منو" aria-expanded={mobileOpen}>
            {mobileOpen ? <X size={20} /> : <Menu size={20} />}
          </button>
        </div>
      </div>

      {/* Mobile panel */}
      {mobileOpen && (
        <div className="lg:hidden bg-white border-t border-[#DDE2DD] max-h-[80vh] overflow-y-auto" role="dialog" aria-modal="true" aria-label="منوی موبایل">
          <div className="p-4 space-y-1">
            <Link to="/"       className="flex items-center gap-3 py-3 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm font-medium text-[#25272C]">خانه</Link>
            <Link to="/about"  className="flex items-center gap-3 py-3 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm font-medium text-[#25272C]">درباره دکتر</Link>
            <div className="text-xs font-bold text-[#6A7078] px-3 pt-3 pb-1 uppercase tracking-widest">جراحی بینی</div>
            {NAV_MEGA_ITEMS.flatMap(g => g.items).map(item => (
              <Link key={item.href} to={item.href} className="flex items-center gap-3 py-2.5 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm text-[#25272C]">
                <span className="w-6 h-6 rounded-lg bg-[#E4F0E4] flex items-center justify-center"><item.icon size={12} className="text-[#28722C]" /></span>
                {item.label}
              </Link>
            ))}
            <div className="text-xs font-bold text-[#6A7078] px-3 pt-3 pb-1 uppercase tracking-widest">صفحات</div>
            {([
              { href: "/gallery", label: "نمونه کارها" },
              { href: "/blog",    label: "مقالات" },
              { href: "/faq",     label: "سوالات" },
              { href: "/contact", label: "تماس" },
            ]).map(l => (
              <Link key={l.href} to={l.href} className="flex py-2.5 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm text-[#25272C]">{l.label}</Link>
            ))}
          </div>
          <div className="p-4 grid grid-cols-2 gap-2 border-t border-[#DDE2DD]">
            <Link to="/booking" className="flex items-center justify-center gap-1.5 py-3 border-2 border-[#28722C] text-[#28722C] rounded-xl text-sm font-bold">نوبت‌گیری</Link>
            <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer" className="flex items-center justify-center gap-1.5 py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold">تشکیل پرونده</a>
          </div>
        </div>
      )}
    </nav>
  );
}
