/**
 * SiteNav — shared navigation component (with motion animations)
 * PHP: include get_template_part('components/nav');
 * WP:  wp_nav_menu() for all link groups
 */
import { useState, useEffect, useRef } from "react";
import { Link, useLocation } from "react-router";
import { motion, AnimatePresence } from "motion/react";
import {
  ChevronDown, Phone, X, Menu,
} from "lucide-react";
import { NAV_MEGA_ITEMS, BOOKING_URL } from "@/data/site";
import { useFadeScaleVariants, useStaggerVariants, useCardVariants } from "@/components/useMotion";

export default function SiteNav() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [megaOpen, setMegaOpen]     = useState(false);
  const [scrolled, setScrolled]     = useState(false);
  const megaRef = useRef<HTMLDivElement>(null);
  const location = useLocation();

  const megaVariants   = useFadeScaleVariants();
  const mobileVariants = useStaggerVariants(0.04);
  const itemVariants   = useCardVariants();

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 50);
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (megaRef.current && !megaRef.current.contains(e.target as Node)) setMegaOpen(false);
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  // Close panels on navigation
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
        {/* WP/Theme: site_logo (image) — PHP: get_theme_mod('site_logo') */}
        <Link to="/" aria-label="صفحه اصلی — دکتر شاهین باستانی‌نژاد" className="flex items-center gap-3 group flex-shrink-0">
          {/* WP/Theme: site_logo (image) — PHP: get_theme_mod('custom_logo') */}
          <img
            src="/logo.svg"
            alt="لوگو کلینیک دکتر باستانی‌نژاد"
            className="w-9 h-9 object-contain"
            width="36" height="36"
          />
          <div className="leading-tight">
            {/* WP/Theme: doctor_name (text) — PHP: get_theme_mod('doctor_name') */}
            <div className="text-sm font-bold text-[#25272C]">دکتر شاهین باستانی‌نژاد</div>
            <div className="text-[10px] text-[#545B64]">جراح پلاستیک بینی · تهران</div>
          </div>
        </Link>

        {/* Desktop links */}
        <div className="hidden lg:flex items-center gap-0.5" ref={megaRef}>
          {[{ label: "خانه", href: "/" }, { label: "درباره دکتر", href: "/about" }].map(l => (
            <Link key={l.label} to={l.href}
              aria-current={location.pathname === l.href ? "page" : undefined}
              className="px-3 py-2 rounded-lg text-sm text-[#545B64] hover:text-[#28722C] hover:bg-[#E4F0E4] transition-all font-medium">
              {l.label}
            </Link>
          ))}

          {/* Mega dropdown */}
          <div className="relative">
            <button
              onClick={() => setMegaOpen(o => !o)}
              className={`flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all ${megaOpen ? "bg-[#E4F0E4] text-[#28722C]" : "text-[#545B64] hover:text-[#28722C] hover:bg-[#E4F0E4]"}`}
              aria-expanded={megaOpen}
              aria-haspopup="true"
            >
              جراحی بینی
              <motion.span
                animate={{ rotate: megaOpen ? 180 : 0 }}
                transition={{ duration: 0.2 }}
              >
                <ChevronDown size={14} aria-hidden />
              </motion.span>
            </button>

            <AnimatePresence>
              {megaOpen && (
                <motion.div
                  className="absolute top-full right-0 mt-1 w-[600px] bg-white border border-[#DDE2DD] rounded-2xl shadow-xl p-4 grid grid-cols-3 gap-4"
                  role="menu"
                  variants={megaVariants}
                  initial="hidden"
                  animate="visible"
                  exit="exit"
                >
                  {NAV_MEGA_ITEMS.map(col => (
                    <div key={col.group}>
                      <p className="text-[10px] font-bold text-[#545B64] uppercase tracking-widest mb-2 px-2">{col.group}</p>
                      {col.items.map(item => {
                        const Icon = item.icon;
                        return (
                          <Link key={item.href} to={item.href} role="menuitem"
                            className="flex items-start gap-2.5 p-2 rounded-xl hover:bg-[#E4F0E4] transition-colors group"
                            onClick={() => setMegaOpen(false)}>
                            <span className="w-7 h-7 rounded-lg bg-[#E4F0E4] flex items-center justify-center flex-shrink-0 group-hover:bg-[#28722C] transition-colors">
                              <Icon size={13} className="text-[#28722C] group-hover:text-white transition-colors" aria-hidden />
                            </span>
                            <span className="min-w-0">
                              <span className="text-xs font-semibold text-[#25272C] block leading-tight">{item.label}</span>
                              <span className="text-[10px] text-[#545B64] leading-tight">{item.sub}</span>
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
                    <a href="/booking"
                      className="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-semibold text-white bg-[#28722C] rounded-lg hover:bg-[#246b28] transition-colors">
                      رزرو نوبت
                    </a>
                  </div>
                </motion.div>
              )}
            </AnimatePresence>
          </div>

          {[
            { label: "گالری",   href: "/gallery" },
            { label: "مقالات",  href: "/blog" },
            { label: "سوالات",  href: "/faq" },
            { label: "تماس",    href: "/contact" },
          ].map(l => (
            <Link key={l.label} to={l.href}
              aria-current={location.pathname === l.href ? "page" : undefined}
              className="px-3 py-2 rounded-lg text-sm text-[#545B64] hover:text-[#28722C] hover:bg-[#E4F0E4] transition-all font-medium">
              {l.label}
            </Link>
          ))}
        </div>

        {/* CTA */}
        <div className="flex items-center gap-2">
          {/* WP/Theme: clinic_phone_1 (text) — PHP: get_theme_mod('clinic_phone_1') */}
          <a href="tel:02186087250"
            className="hidden md:flex items-center gap-1.5 text-sm text-[#28722C] font-semibold hover:text-[#246b28] transition-colors"
            aria-label="تماس تلفنی با کلینیک">
            <Phone size={13} aria-hidden />۰۲۱۸۶۰۸۷۲۵۰
          </a>
          <a href="/booking"
            className="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 bg-[#28722C] text-white text-sm font-bold rounded-lg hover:bg-[#246b28] transition-colors">
            رزرو نوبت
          </a>
          <button
            onClick={() => setMobileOpen(o => !o)}
            className="lg:hidden p-2 rounded-lg hover:bg-[#E4F0E4] transition-colors"
            aria-label={mobileOpen ? "بستن منو" : "باز کردن منو"}
            aria-expanded={mobileOpen}
            aria-controls="mobile-menu"
          >
            {mobileOpen ? <X size={20} aria-hidden /> : <Menu size={20} aria-hidden />}
          </button>
        </div>
      </div>

      {/* Mobile panel */}
      <AnimatePresence>
        {mobileOpen && (
          <motion.div
            id="mobile-menu"
            className="lg:hidden bg-white border-t border-[#DDE2DD] max-h-[80vh] overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-label="منوی موبایل"
            initial={{ opacity: 0, y: -8 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -8 }}
            transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
          >
            <motion.div
              className="p-4 space-y-1"
              variants={mobileVariants}
              initial="hidden"
              animate="visible"
            >
              {[
                { href: "/",       label: "خانه" },
                { href: "/about",  label: "درباره دکتر" },
              ].map(l => (
                <motion.div key={l.href} variants={itemVariants}>
                  <Link to={l.href}
                    aria-current={location.pathname === l.href ? "page" : undefined}
                    className="flex items-center gap-3 py-3 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm font-medium text-[#25272C]">{l.label}
                  </Link>
                </motion.div>
              ))}

              <div className="text-xs font-bold text-[#545B64] px-3 pt-3 pb-1 uppercase tracking-widest">جراحی بینی</div>
              {NAV_MEGA_ITEMS.flatMap(g => g.items).map(item => (
                <motion.div key={item.href} variants={itemVariants}>
                  <Link to={item.href} className="flex items-center gap-3 py-2.5 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm text-[#25272C]">
                    <span className="w-6 h-6 rounded-lg bg-[#E4F0E4] flex items-center justify-center flex-shrink-0">
                      <item.icon size={12} className="text-[#28722C]" aria-hidden />
                    </span>
                    {item.label}
                  </Link>
                </motion.div>
              ))}

              <div className="text-xs font-bold text-[#545B64] px-3 pt-3 pb-1 uppercase tracking-widest">صفحات</div>
              {([
                { href: "/gallery", label: "گالری" },
                { href: "/blog",    label: "مقالات" },
                { href: "/faq",     label: "سوالات" },
                { href: "/contact", label: "تماس" },
              ]).map(l => (
                <motion.div key={l.href} variants={itemVariants}>
                  <Link to={l.href}
                    aria-current={location.pathname === l.href ? "page" : undefined}
                    className="flex py-2.5 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm text-[#25272C]">{l.label}
                  </Link>
                </motion.div>
              ))}
            </motion.div>

            <div className="p-4 grid grid-cols-2 gap-2 border-t border-[#DDE2DD]">
              <Link to="/booking" className="flex items-center justify-center gap-1.5 py-3 border-2 border-[#28722C] text-[#28722C] rounded-xl text-sm font-bold hover:bg-[#28722C] hover:text-white transition-colors">رزرو نوبت</Link>
              <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer" className="flex items-center justify-center gap-1.5 py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">تشکیل پرونده</a>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </nav>
  );
}
