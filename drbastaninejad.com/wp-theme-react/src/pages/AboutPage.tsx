/**
 * AboutPage — about.php / page-about.php equivalent
 * WP: ACF Options Page + static page content
 */
import { Link } from "react-router";
import { CheckCircle, Award, Star, Users, BookOpen, Shield, Calendar } from "lucide-react";
import { DOCTOR_NAME, BOOKING_URL, TIMELINE, CERTIFICATES, TRUST_BADGES } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";

const ABOUT_IMG  = "/images/Doctor/DrShahinBastaninejadPortrait2.png";
const CLINIC_IMG = "/images/Doctor/Dr Shahin Bastani Nejad (9).webp";

export default function AboutPage() {
  return (
    <>
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "درباره دکتر" }]} />

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
                <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer"
                  className="flex items-center gap-2 px-6 py-3 bg-[#28722C] text-white rounded-xl font-bold hover:bg-[#246b28] transition-colors">
                  <Calendar size={16} />رزرو مشاوره
                </a>
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
              <div className="space-y-4 text-[#6A7078] leading-[2] text-sm">
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
                          <p className="text-[#6A7078] text-sm mt-0.5">{t.org}</p>
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

      {/* Certificates */}
      <section className="py-20 px-4 sm:px-6 bg-[#F7F8F6]">
        <div className="max-w-[1200px] mx-auto">
          <div className="text-center mb-12">
            <span className="text-[#28722C] font-bold text-sm tracking-wider">گواهینامه‌ها</span>
            <h2 className="text-3xl font-black text-[#25272C] mt-2">مدارک و افتخارات بین‌المللی</h2>
          </div>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {CERTIFICATES.map((cert, i) => (
              <div key={i} className="bg-white border border-[#DDE2DD] rounded-2xl p-5 flex items-start gap-4 hover:border-[#28722C]/30 hover:shadow-md transition-all">
                <div className="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center flex-shrink-0">
                  <Award className="w-5 h-5 text-amber-600" />
                </div>
                <p className="text-[#25272C] font-medium text-sm leading-relaxed">{cert}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Clinic photo */}
      <section className="py-20 px-4 sm:px-6 bg-white">
        <div className="max-w-[1200px] mx-auto">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div>
              <span className="text-[#28722C] font-bold text-sm tracking-wider">کلینیک</span>
              <h2 className="text-3xl font-black text-[#25272C] mt-2 mb-6">محیطی ایمن، مجهز و آرام</h2>
              <p className="text-[#6A7078] leading-[2] mb-6 text-sm">
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
