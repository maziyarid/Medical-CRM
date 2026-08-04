/**
 * BookingPage — page-booking.php equivalent
 * WP: Embedded iframe to app.drbastaninejad.com or custom booking plugin
 */
import { Calendar, Phone, CheckCircle, Clock, Shield } from "lucide-react";
import { BOOKING_URL, PHONES } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";

export default function BookingPage() {
  return (
    <>
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "نوبت‌گیری" }]} />

      {/* Header */}
      <section className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto text-center">
          <span className="text-[#28722C] font-bold text-sm tracking-wider">نوبت‌گیری آنلاین</span>
          <h1 className="text-4xl sm:text-5xl font-black mt-2 mb-4">رزرو نوبت</h1>
          <p className="text-white/70 text-lg">نوبت آنلاین سریع، آسان و بدون نیاز به تماس تلفنی</p>
        </div>
      </section>

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-16">
        <div className="grid lg:grid-cols-3 gap-8">

          {/* Info column */}
          <div className="space-y-5">
            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6">
              <h3 className="font-bold text-[#25272C] mb-4">نحوه نوبت‌گیری</h3>
              <div className="space-y-4">
                {[
                  { step: "۱", title: "تکمیل فرم رزرو",   desc: "اطلاعات اولیه خود را وارد کنید." },
                  { step: "۲", title: "تأیید شماره تلفن", desc: "کد OTP ارسال شده را وارد کنید." },
                  { step: "۳", title: "تماس تیم کلینیک",  desc: "ظرف ۲۴ ساعت با شما تماس می‌گیریم." },
                ].map(s => (
                  <div key={s.step} className="flex gap-3">
                    <div className="w-8 h-8 bg-[#E4F0E4] rounded-xl flex items-center justify-center flex-shrink-0">
                      <span className="text-[#28722C] font-black text-sm">{s.step}</span>
                    </div>
                    <div>
                      <p className="font-semibold text-[#25272C] text-sm">{s.title}</p>
                      <p className="text-xs text-[#6A7078]">{s.desc}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6">
              <h3 className="font-bold text-[#25272C] mb-4 flex items-center gap-2">
                <Clock size={16} className="text-[#28722C]" /> ساعات پذیرش
              </h3>
              <div className="bg-[#E4F0E4] rounded-xl px-4 py-3">
                <p className="text-sm font-bold text-[#25272C]">شنبه و سه‌شنبه</p>
                <p className="text-sm text-[#28722C] font-semibold">۱۵:۰۰ – ۱۹:۰۰</p>
              </div>
            </div>

            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6">
              <h3 className="font-bold text-[#25272C] mb-4 flex items-center gap-2">
                <Phone size={16} className="text-[#28722C]" /> تماس مستقیم
              </h3>
              {PHONES.map(p => (
                <a key={p} href={`tel:${p.replace(/–/g, "")}`}
                  className="flex items-center gap-3 p-2 rounded-lg hover:bg-[#E4F0E4] transition-colors">
                  <Phone size={14} className="text-[#28722C]" />
                  <span className="font-bold text-[#25272C] text-sm">{p}</span>
                </a>
              ))}
            </div>

            <div className="bg-[#E4F0E4] rounded-2xl p-5">
              <p className="text-sm font-bold text-[#28722C] mb-3">مزایای نوبت‌گیری آنلاین</p>
              {["رزرو ۲۴ ساعته بدون معطلی", "یادآوری خودکار قبل از نوبت", "مدیریت و تغییر نوبت آسان", "مشاوره اولیه رایگان"].map(f => (
                <div key={f} className="flex items-center gap-2 text-sm text-[#25272C] mb-2">
                  <CheckCircle size={13} className="text-[#28722C] flex-shrink-0" />{f}
                </div>
              ))}
            </div>
          </div>

          {/* Booking iframe / embed */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-3xl border border-[#DDE2DD] overflow-hidden shadow-lg">
              <div className="bg-gradient-to-br from-[#28722C] to-[#1a4e1d] p-6 text-white text-center">
                <Calendar size={32} className="mx-auto mb-3" />
                <h2 className="text-xl font-black mb-1">رزرو نوبت آنلاین</h2>
                <p className="text-white/80 text-sm">سیستم نوبت‌دهی دکتر باستانی‌نژاد</p>
              </div>
              {/*
                WP: In production replace the iframe/button below with the booking system embed.
                Options:
                  1. External booking URL iframe: <iframe src={BOOKING_URL} .../>
                  2. Custom WP booking plugin shortcode: echo do_shortcode('[booking_calendar ...]')
                  3. NovaTime / Nobatime embed script
              */}
              <div className="p-10 flex flex-col items-center gap-6">
                <div className="text-center">
                  <p className="text-[#6A7078] text-sm mb-6 leading-relaxed">
                    برای رزرو نوبت آنلاین، روی دکمه زیر کلیک کنید تا به سیستم نوبت‌دهی منتقل شوید.
                  </p>
                  <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 bg-[#28722C] text-white font-black px-10 py-5 rounded-2xl text-lg hover:bg-[#246b28] transition-colors shadow-xl">
                    <Calendar size={22} /> رزرو نوبت آنلاین
                  </a>
                </div>
                <div className="flex flex-wrap justify-center gap-4 text-xs text-[#6A7078] pt-4 border-t border-[#DDE2DD] w-full">
                  {["مشاوره رایگان", "بدون تعهد", "محرمانه", "امن"].map(t => (
                    <span key={t} className="flex items-center gap-1">
                      <Shield size={10} className="text-[#28722C]" />{t}
                    </span>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
