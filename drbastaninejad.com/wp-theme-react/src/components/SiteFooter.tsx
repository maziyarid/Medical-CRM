/**
 * SiteFooter — shared footer component
 * PHP: include get_template_part('components/footer');
 * WP:  wp_nav_menu() for footer-quick / footer-services,
 *      ACF options for socials, phones, address, hours
 */
import { Link } from "react-router";
import { Phone, MapPin, Clock, ExternalLink, Stethoscope } from "lucide-react";
import {
  DOCTOR_NAME, BOOKING_URL, INSTAGRAM, YOUTUBE, TELEGRAM, APARAT,
  FOOTER_QUICK, FOOTER_SERVICES, PHONES, ADDRESS_SHORT, ENAMAD_URL, IRANENT_URL,
} from "@/data/site";

export default function SiteFooter() {
  return (
    <footer className="bg-[#1a2318] text-white/80" role="contentinfo">
      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 pt-8">

        {/* Social bar — ACF options: social_* */}
        <div className="flex flex-wrap justify-center gap-5 pb-6 border-b border-white/10 mb-6">
          {[
            { label: "اینستاگرام", href: INSTAGRAM, color: "hover:text-pink-400" },
            { label: "یوتیوب",     href: YOUTUBE,   color: "hover:text-red-400" },
            { label: "آپارات",     href: APARAT,    color: "hover:text-red-500" },
            { label: "تلگرام",     href: TELEGRAM,  color: "hover:text-sky-400" },
          ].map(s => (
            <a key={s.label} href={s.href} target="_blank" rel="noopener noreferrer"
              className={`text-sm flex items-center gap-1.5 text-white/70 transition-colors ${s.color}`}>
              <ExternalLink size={13} aria-hidden /> {s.label}
            </a>
          ))}
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 pb-8">

          {/* Brand column */}
          <div>
            {/* Production: <img src="<?= get_template_directory_uri() ?>/assets/images/logo-monochrome.svg" .../> */}
            <div className="flex items-center gap-2 mb-3">
              <div className="w-9 h-9 rounded-xl bg-[#28722C] flex items-center justify-center">
                <Stethoscope size={17} className="text-white" />
              </div>
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

            {/* Trust seals */}
            <div className="flex gap-2">
              <a href={ENAMAD_URL} target="_blank" rel="noopener" aria-label="نماد اعتماد الکترونیکی"
                className="w-12 h-12 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center hover:bg-white/20 transition-colors text-[10px] text-center leading-tight text-white/60 p-1">
                نماد اعتماد
              </a>
              <a href={IRANENT_URL} target="_blank" rel="noopener" aria-label="انجمن جراحان گوش، گلو و بینی"
                className="w-12 h-12 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center hover:bg-white/20 transition-colors text-[10px] text-center leading-tight text-white/60 p-1">
                GBN انجمن
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
                    <a key={p} href={`tel:${p.replace(/–/g, "")}`} className="text-xs text-white/80 hover:text-[#28722C] block transition-colors" dir="ltr">{p}</a>
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
                  <div className="flex gap-2 mt-2">
                    {[
                      { name: "Google Maps", href: "https://maps.google.com/?q=35.758881,51.413824" },
                      { name: "نشان",        href: "https://nshn.ir/gNbNgYZt_Rxg" },
                      { name: "بلد",         href: "https://balad.ir" },
                    ].map(m => (
                      <a key={m.name} href={m.href} target="_blank" rel="noopener"
                        className="px-1.5 py-0.5 text-[10px] bg-white/10 rounded border border-white/20 text-white/60 hover:bg-[#28722C]/30 transition-colors">{m.name}</a>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
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
