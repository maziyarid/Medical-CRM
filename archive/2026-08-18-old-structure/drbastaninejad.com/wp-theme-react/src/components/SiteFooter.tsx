/**
 * SiteFooter — shared footer component
 * PHP: include get_template_part('components/footer');
 * WP:  wp_nav_menu() for footer-quick / footer-services,
 *      ACF options for socials, phones, address, hours
 */
import { Link } from "react-router";
import { Phone, MapPin, Clock } from "lucide-react";
import {
  DOCTOR_NAME, BOOKING_URL, INSTAGRAM, YOUTUBE, TELEGRAM, APARAT,
  FOOTER_QUICK, FOOTER_SERVICES, PHONES, ADDRESS_SHORT, ENAMAD_URL, IRANENT_URL,
} from "@/data/site";

/* ── Font Awesome–style social SVG icons (inline, no CDN) ── */
function IconInstagram({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden className={className}>
      <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
    </svg>
  );
}

function IconYoutube({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden className={className}>
      <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
    </svg>
  );
}

function IconTelegram({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden className={className}>
      <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
    </svg>
  );
}

function IconAparat({ className }: { className?: string }) {
  /* Aparat's brand shape — stylised "a" in circle */
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden className={className}>
      <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 2c5.523 0 10 4.477 10 10S17.523 22 12 22 2 17.523 2 12 6.477 2 12 2zm-1.5 5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9zm0 1.8a2.7 2.7 0 1 1 0 5.4 2.7 2.7 0 0 1 0-5.4zm4.8-.9a.9.9 0 1 0 0 1.8.9.9 0 0 0 0-1.8z"/>
    </svg>
  );
}

const SOCIAL_LINKS = [
  { label: "اینستاگرام", href: INSTAGRAM, Icon: IconInstagram, color: "hover:text-pink-400" },
  { label: "یوتیوب",     href: YOUTUBE,   Icon: IconYoutube,   color: "hover:text-red-400"  },
  { label: "آپارات",     href: APARAT,    Icon: IconAparat,    color: "hover:text-red-500"  },
  { label: "تلگرام",     href: TELEGRAM,  Icon: IconTelegram,  color: "hover:text-sky-400"  },
];

const MAP_LINKS = [
  { name: "Google Maps", href: "https://maps.google.com/?q=35.758881,51.413824", icon: "/images/Icons/GoogleMap.webp" },
  { name: "نشان",        href: "https://nshn.ir/gNbNgYZt_Rxg",                  icon: "/images/Icons/Neshan.webp"   },
  { name: "بلد",         href: "https://balad.ir",                               icon: "/images/Icons/Balad.webp"    },
];

export default function SiteFooter() {
  return (
    <footer className="bg-[#1a2318] text-white/80" role="contentinfo">
      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 pt-8">

        {/* Social bar — ACF options: social_* */}
        <div className="flex flex-wrap justify-center gap-6 pb-6 border-b border-white/10 mb-6">
          {SOCIAL_LINKS.map(({ label, href, Icon, color }) => (
            <a key={label} href={href} target="_blank" rel="noopener noreferrer"
              aria-label={label}
              className={`flex items-center gap-2 text-sm text-white/70 transition-colors ${color}`}>
              <Icon className="w-5 h-5 flex-shrink-0" />
              <span>{label}</span>
            </a>
          ))}
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 pb-8">

          {/* Brand column */}
          <div>
            {/* WP/Theme: custom_logo — PHP: get_theme_mod('custom_logo') */}
            <div className="flex items-center gap-2 mb-3">
              <img
                src="/logo-white.svg"
                alt="لوگو دکتر باستانی‌نژاد"
                className="w-9 h-9 object-contain"
                width="36" height="36"
              />
              <span className="font-bold text-sm text-white">{DOCTOR_NAME}</span>
            </div>
            <p className="text-xs leading-relaxed mb-3">جراح و متخصص گوش، گلو و بینی<br />جراح پلاستیک بینی — تهران</p>

            {/* Stats — ACF options: clinic_surgeries_count, clinic_years */}
            <div className="grid grid-cols-3 gap-2 mb-4">
              {[{ n: "+۱۵", l: "سال تجربه" }, { n: "+۲۰۰۰", l: "عمل موفق" }, { n: "+۱۷۸", l: "نمونه گالری" }].map(s => (
                <div key={s.l} className="text-center">
                  <div className="text-sm font-bold text-[#28722C]">{s.n}</div>
                  <div className="text-[10px] text-white/50">{s.l}</div>
                </div>
              ))}
            </div>

            {/* Trust seals — real images */}
            <div className="flex gap-2 items-center">
              <a href={ENAMAD_URL} target="_blank" rel="noopener" aria-label="نماد اعتماد الکترونیکی"
                className="rounded-xl overflow-hidden bg-white/10 border border-white/20 hover:bg-white/20 transition-colors">
                <img src="/images/enamad.webp" alt="نماد اعتماد" className="w-12 h-12 object-contain p-0.5" loading="lazy" />
              </a>
              <a href={IRANENT_URL} target="_blank" rel="noopener" aria-label="انجمن جراحان گوش، گلو و بینی"
                className="rounded-xl overflow-hidden bg-white/10 border border-white/20 hover:bg-white/20 transition-colors">
                <img src="/images/namad-n1.webp" alt="نشان انجمن" className="w-12 h-12 object-contain p-0.5" loading="lazy" />
              </a>
            </div>
          </div>

          {/* Quick links — WP: wp_nav_menu('footer-quick') */}
          <nav aria-label="لینک‌های سریع">
            <h4 className="text-sm font-bold text-white mb-4">لینک‌های سریع</h4>
            <ul className="space-y-2">
              {FOOTER_QUICK.map(l => (
                <li key={l.href}>
                  <Link to={l.href} className="text-xs text-white/60 hover:text-[#28722C] transition-colors">{l.label}</Link>
                </li>
              ))}
            </ul>
          </nav>

          {/* Services — WP: wp_nav_menu('footer-services') */}
          <nav aria-label="خدمات">
            <h4 className="text-sm font-bold text-white mb-4">خدمات</h4>
            <ul className="space-y-2">
              {FOOTER_SERVICES.map(l => (
                <li key={l.href}>
                  <Link to={l.href} className="text-xs text-white/60 hover:text-[#28722C] transition-colors">{l.label}</Link>
                </li>
              ))}
            </ul>
          </nav>

          {/* Contact info — ACF options: clinic_phone_1, clinic_address, clinic_hours */}
          <div>
            <h4 className="text-sm font-bold text-white mb-4">تماس با ما</h4>
            <div className="space-y-4">
              <div className="flex items-start gap-3">
                <div className="w-7 h-7 rounded-lg bg-[#28722C]/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <Phone size={12} className="text-[#28722C]" />
                </div>
                <div>
                  <p className="text-[10px] text-white/50 mb-0.5">تلفن کلینیک</p>
                  {PHONES.slice(0, 2).map(p => (
                    <a key={p} href={`tel:${p.replace(/\D/g, "")}`}
                      className="text-xs text-white/80 hover:text-[#28722C] block transition-colors" dir="ltr">
                      {p}
                    </a>
                  ))}
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-7 h-7 rounded-lg bg-[#28722C]/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <Clock size={12} className="text-[#28722C]" />
                </div>
                <div>
                  <p className="text-[10px] text-white/50 mb-0.5">ساعت پذیرش</p>
                  <p className="text-xs text-white/80">شنبه و سه‌شنبه<br />۱۵:۰۰ تا ۱۹:۰۰</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-7 h-7 rounded-lg bg-[#28722C]/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <MapPin size={12} className="text-[#28722C]" />
                </div>
                <div>
                  <p className="text-[10px] text-white/50 mb-0.5">آدرس</p>
                  <p className="text-xs text-white/70 leading-relaxed">{ADDRESS_SHORT}</p>
                  {/* Map links with real brand icons */}
                  <div className="flex gap-2 mt-2 flex-wrap">
                    {MAP_LINKS.map(m => (
                      <a key={m.name} href={m.href} target="_blank" rel="noopener"
                        aria-label={m.name}
                        className="w-7 h-7 rounded-lg bg-white/10 border border-white/20 hover:bg-white/25 transition-colors overflow-hidden flex items-center justify-center"
                        title={m.name}>
                        <img src={m.icon} alt={m.name} className="w-5 h-5 object-contain" loading="lazy" />
                      </a>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Legal links — WP: wp_nav_menu('footer-legal') */}
        <div className="flex flex-wrap justify-center gap-4 py-3 border-t border-white/10 text-xs text-white/40">
          {[
            { label: "سیاست حریم خصوصی",  href: "/legal/privacy" },
            { label: "شرایط استفاده",       href: "/legal/terms" },
            { label: "سیاست لغو",           href: "/legal/cancellation" },
            { label: "نقشه سایت",           href: "/sitemap" },
          ].map(l => (
            <Link key={l.href} to={l.href} className="hover:text-[#28722C] transition-colors">{l.label}</Link>
          ))}
        </div>

        {/* Bottom bar */}
        <div className="py-4 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-white/40">
          {/* WP: echo date('Y') */}
          <span>© ۱۴۰۵ کلینیک {DOCTOR_NAME} — تمامی حقوق محفوظ است.</span>
          <span>
            طراحی توسط{" "}
            <a href="https://maziyarid.com" target="_blank" rel="noopener" className="text-[#28722C] hover:text-white transition-colors">
              MA<span className="font-black">Z</span>
            </a>
          </span>
        </div>
      </div>
    </footer>
  );
}
