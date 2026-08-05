/**
 * AboutPage — about.php / page-about.php equivalent
 * WP: ACF Options Page + static page content
 *
 * WP/Theme: certificate_images (gallery field, theme_mod repeater)
 *   Each cert: { cert_title (text), cert_image (image) }
 *   PHP: foreach ( get_theme_mod('certificate_images', []) as $cert ) { ... }
 */
import { useState } from "react";
import { Link } from "react-router";
import { CheckCircle, Award, X, ChevronLeft, ChevronRight, Calendar } from "lucide-react";
import { DOCTOR_NAME, TIMELINE, CERTIFICATES, TRUST_BADGES } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";
import JsonLd, { DOCTOR_SCHEMA, LOCAL_BUSINESS_SCHEMA, buildBreadcrumbSchema } from "@/components/JsonLd";

const ABOUT_IMG  = "/images/Doctor/DrShahinBastaninejadPortrait2.png";
const CLINIC_IMG = "/images/Doctor/Dr Shahin Bastani Nejad (9).webp";

/* WP/Theme: certificate_images (repeater, theme_mod)
 * PHP: get_theme_mod('certificate_images', $defaults)
 * Each item: { title: string, img: string }
 * Filenames must match actual files in public/images/Certificates/
 */
const CERT_IMG_FILES = [
  "7th international rhinoplasty n facial plastic surgery congress.jpg",
  "7Th Master Dissection Course Rhinology Reconstructive Plastic Surgery.jpg",
  "8th Intl Congress IR Rhinology Society.jpg",
  "3rd SRIC Shiraz Rhinology Intl Course Certificate.jpg",
  "TUMS Nasal Valve Reconstruction Certificate.jpg",
  "Tajikistan Certificate.jpg",
  "4th Intl Conference Iraqi Kurdistan Otorhinolaryngology Head-Neck Surgery.jpg",
  "5th Intl Conference Exhibition Iraqi Kurdistan Otorhinolaryngology Head-Neck Surgery.jpg",
  "Beauty treatments n health centres.jpg",
];

const CERT_IMAGES = CERTIFICATES.map((title, i) => ({
  title,
  img: `/images/Certificates/${CERT_IMG_FILES[i] ?? CERT_IMG_FILES[0]}`,
}));

export default function AboutPage() {
  const [lightbox, setLightbox] = useState<number | null>(null);
  const total = CERT_IMAGES.length;

  const crumbs = [{ label: "خانه", href: "/" }, { label: "درباره دکتر" }];

  return (
    <>
      <JsonLd id="jsonld-about" data={[
        buildBreadcrumbSchema(crumbs),
        DOCTOR_SCHEMA,
        LOCAL_BUSINESS_SCHEMA,
      ]} />

      <Breadcrumb items={crumbs} />

      {/* Hero */}
      <section className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-20 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div>
              <span className="text-[#28722C] font-bold text-sm tracking-wider">رزومه تخصصی</span>
              <h1 className="text-4xl sm:text-5xl font-black mt-2 mb-6 leading-tight">{DOCTOR_NAME}</h1>
              <p className="text-white/75 text-lg leading-relaxed mb-8">
                دانشیار دانشگاه علوم پزشکی تهران و عضو هیئت علمی بیمارستان امیراعلم، دبیر کمیته علمی رینولوژی انجمن جراحی پلاستیک بینی ایران. با بیش از ۱۵ سال تجربه تخصصی در جراحی‌های بینی اولیه، ترمیمی و درمانی.
              </p>
              <div className="flex flex-wrap gap-4">
                <Link to="/booking"
                  className="flex items-center gap-2 px-6 py-3 bg-[#28722C] text-white rounded-xl font-bold hover:bg-[#246b28] transition-colors">
                  <Calendar size={16} />رزرو مشاوره
                </Link>
                <Link to="/contact"
                  className="flex items-center gap-2 px-6 py-3 border border-white/30 text-white rounded-xl font-bold hover:bg-white/10 transition-colors">
                  تماس با ما
                </Link>
              </div>
            </div>
            <div className="relative">
              <div className="rounded-3xl overflow-hidden aspect-[4/5] shadow-2xl max-w-md mx-auto">
                <img src={ABOUT_IMG} alt={DOCTOR_NAME} className="w-full h-full object-cover" />
              </div>
              <div className="absolute -bottom-4 -right-4 bg-white rounded-2xl p-4 shadow-xl">
                <div className="grid grid-cols-2 gap-4">
                  {[{ val: "+۱۸", label: "سال تجربه" }, { val: "+۲۰۰۰", label: "عمل موفق" }].map(s => (
                    <div key={s.label} className="text-center">
                      <p className="text-xl font-black text-[#28722C]">{s.val}</p>
                      <p className="text-xs text-gray-500">{s.label}</p>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Trust Badges */}
      <section className="bg-[#28722C] py-10">
        <div className="max-w-[1200px] mx-auto px-4 sm:px-6">
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
            {TRUST_BADGES.map((b, i) => (
              <div key={i} className="flex flex-col items-center text-center gap-2">
                <div className="w-10 h-10 bg-white/15 rounded-2xl flex items-center justify-center">
                  <b.icon className="w-5 h-5 text-white" />
                </div>
                <p className="text-white font-bold text-xs leading-snug">{b.title}</p>
                <p className="text-white/60 text-[10px]">{b.subtitle}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Biography */}
      <section className="py-20 px-4 sm:px-6 bg-white">
        <div className="max-w-[1200px] mx-auto">
          <div className="grid lg:grid-cols-2 gap-12 items-start">
            <div>
              <span className="text-[#28722C] font-bold text-sm tracking-wider">بیوگرافی</span>
              <h2 className="text-3xl font-black text-[#25272C] mt-2 mb-6">مسیر علمی و حرفه‌ای</h2>
              <div className="space-y-4 text-[#545B64] leading-[2] text-sm">
                <p>دکتر شاهین باستانی‌نژاد پس از اخذ دکترای پزشکی عمومی از دانشگاه علوم پزشکی اصفهان، دوره تخصصی گوش، حلق و بینی را در دانشگاه علوم پزشکی تهران با رتبه ۴ بورد کشوری به پایان رساند.</p>
                <p>ایشان فلوشیپ تخصصی رینوپلاستی را در بیمارستان امیراعلم تهران گذراند و از سال ۱۳۸۸ به عضویت هیئت علمی دانشگاه علوم پزشکی تهران درآمد.</p>
                <p>دکتر باستانی‌نژاد در حال حاضر دبیر کمیته علمی رینولوژی انجمن جراحی پلاستیک بینی ایران بوده و در بیش از ۱۰ دوره سمینار تخصصی رینوپلاستی و آندوسکوپی سینوس به‌عنوان مدرس فعالیت داشته است.</p>
              </div>
              <div className="grid grid-cols-2 gap-3 mt-8">
                {["دانشگاه علوم پزشکی تهران", "بیمارستان امیراعلم تهران", "انجمن جراحی پلاستیک بینی ایران", "انجمن متخصصان گوش، حلق و بینی ایران"].map(a => (
                  <div key={a} className="flex items-center gap-2 bg-[#F7F8F6] rounded-xl p-3 border border-[#DDE2DD]">
                    <CheckCircle className="w-4 h-4 text-[#28722C] flex-shrink-0" />
                    <span className="text-xs font-medium text-[#25272C]">{a}</span>
                  </div>
                ))}
              </div>
            </div>
            <div>
              <h3 className="text-xl font-bold text-[#25272C] mb-6">مسیر تحصیل و تجربه</h3>
              <div className="relative">
                <div className="absolute right-5 top-0 bottom-0 w-0.5 bg-[#28722C]/20" />
                <div className="space-y-6">
                  {TIMELINE.map((t, i) => {
                    const Icon = t.icon;
                    return (
                      <div key={i} className="flex gap-5">
                        <div className="relative flex-shrink-0">
                          <div className="w-10 h-10 bg-[#28722C] rounded-full flex items-center justify-center shadow-md z-10 relative">
                            <Icon className="w-5 h-5 text-white" />
                          </div>
                        </div>
                        <div className="pb-2">
                          <span className="text-xs font-bold text-[#28722C] bg-[#E4F0E4] rounded-full px-3 py-1">{t.year}</span>
                          <p className="font-bold text-[#25272C] mt-2">{t.title}</p>
                          <p className="text-[#545B64] text-sm mt-0.5">{t.org}</p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Certificates — with image lightbox */}
      <section className="py-20 px-4 sm:px-6 bg-[#F7F8F6]">
        <div className="max-w-[1200px] mx-auto">
          <div className="text-center mb-12">
            <span className="text-[#28722C] font-bold text-sm tracking-wider">گواهینامه‌ها</span>
            <h2 className="text-3xl font-black text-[#25272C] mt-2">مدارک و افتخارات بین‌المللی</h2>
          </div>
          {/* WP/Theme: certificate_images (gallery, theme_mod) — PHP: get_theme_mod('certificate_images', []) */}
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {CERT_IMAGES.map((cert, i) => (
              <button key={i} onClick={() => setLightbox(i)}
                className="group bg-white border border-[#DDE2DD] rounded-2xl overflow-hidden hover:border-[#28722C]/40 hover:shadow-lg transition-all text-right focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#28722C]"
                aria-label={`مشاهده گواهینامه: ${cert.title}`}>
                {/* Certificate thumbnail */}
                <div className="aspect-[4/3] overflow-hidden bg-[#F0F4F0]">
                  <img src={cert.img} alt={cert.title} loading="lazy"
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    onError={e => { (e.currentTarget as HTMLImageElement).style.display = "none"; }} />
                </div>
                <div className="p-4 flex items-start gap-3">
                  <div className="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                    <Award className="w-4 h-4 text-amber-600" />
                  </div>
                  <p className="text-[#25272C] font-medium text-sm leading-relaxed">{cert.title}</p>
                </div>
              </button>
            ))}
          </div>
        </div>
      </section>

      {/* Lightbox */}
      {lightbox !== null && (
        <div
          role="dialog" aria-modal="true" aria-label="مشاهده گواهینامه"
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm px-4"
          onClick={() => setLightbox(null)}>
          <div className="relative max-w-3xl w-full" onClick={e => e.stopPropagation()}>
            {/* Close */}
            <button onClick={() => setLightbox(null)} aria-label="بستن"
              className="absolute -top-12 left-0 text-white hover:text-[#E4F0E4] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded-lg">
              <X size={28} />
            </button>
            {/* Image */}
            <img src={CERT_IMAGES[lightbox].img} alt={CERT_IMAGES[lightbox].title}
              className="w-full rounded-2xl shadow-2xl" />
            {/* Caption */}
            <p className="text-center text-white/90 mt-4 text-sm font-medium px-4">
              {CERT_IMAGES[lightbox].title}
            </p>
            {/* Prev / Next */}
            {total > 1 && (
              <div className="flex justify-between mt-4">
                <button onClick={() => setLightbox((lightbox + 1) % total)} aria-label="قبلی"
                  className="flex items-center gap-1 text-white/70 hover:text-white text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded-lg px-2 py-1">
                  <ChevronRight size={18} />بعدی
                </button>
                <span className="text-white/50 text-xs self-center">
                  {lightbox + 1} / {total}
                </span>
                <button onClick={() => setLightbox((lightbox - 1 + total) % total)} aria-label="بعدی"
                  className="flex items-center gap-1 text-white/70 hover:text-white text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded-lg px-2 py-1">
                  قبلی<ChevronLeft size={18} />
                </button>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Clinic photo */}
      <section className="py-20 px-4 sm:px-6 bg-white">
        <div className="max-w-[1200px] mx-auto">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div>
              <span className="text-[#28722C] font-bold text-sm tracking-wider">کلینیک</span>
              <h2 className="text-3xl font-black text-[#25272C] mt-2 mb-6">محیطی ایمن، مجهز و آرام</h2>
              <p className="text-[#545B64] leading-[2] mb-6 text-sm">
                کلینیک دکتر باستانی‌نژاد با بهره‌گیری از تکنولوژی روز دنیا و استانداردهای بین‌المللی، محیطی امن برای انجام جراحی‌های تخصصی بینی فراهم کرده است.
              </p>
              <div className="space-y-3">
                {["تجهیزات پیشرفته اتاق عمل", "تیم بیهوشی متخصص", "مجوز رسمی وزارت بهداشت", "سیستم تهویه استاندارد جراحی"].map(f => (
                  <div key={f} className="flex items-center gap-3">
                    <CheckCircle className="w-5 h-5 text-[#28722C] flex-shrink-0" />
                    <span className="text-sm text-[#25272C]">{f}</span>
                  </div>
                ))}
              </div>
            </div>
            <div className="rounded-3xl overflow-hidden aspect-video shadow-xl">
              <img src={CLINIC_IMG} alt="کلینیک دکتر باستانی‌نژاد" className="w-full h-full object-cover" />
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
