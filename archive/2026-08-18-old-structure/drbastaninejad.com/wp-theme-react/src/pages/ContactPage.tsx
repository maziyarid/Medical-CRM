/**
 * ContactPage — page-contact.php equivalent
 * WP: ACF Options for address/phones/hours + Contact Form 7 / Gravity Forms
 */
import { useState } from "react";
import { Phone, MapPin, Clock, CheckCircle, Send, Shield } from "lucide-react";
import { PHONES, ADDRESS, BOOKING_URL } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";

export default function ContactPage() {
  const [submitted, setSubmitted] = useState(false);
  const [form, setForm] = useState({ name: "", phone: "", subject: "", message: "" });

  return (
    <>
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "تماس با ما" }]} />

      {/* Header */}
      <section className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto text-center">
          <span className="text-[#28722C] font-bold text-sm tracking-wider">ارتباط با ما</span>
          <h1 className="text-4xl sm:text-5xl font-black mt-2 mb-4">تماس با کلینیک</h1>
          <p className="text-white/70 text-lg">آماده پاسخ‌گویی به سؤالات شما هستیم</p>
        </div>
      </section>

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-16">
        <div className="grid lg:grid-cols-5 gap-8">

          {/* Info cards */}
          <div className="lg:col-span-2 space-y-5">
            <div className="bg-white rounded-3xl border border-[#DDE2DD] p-6">
              <h3 className="font-bold text-[#25272C] mb-4 flex items-center gap-2">
                <Phone className="w-5 h-5 text-[#28722C]" /> تلفن تماس
              </h3>
              {PHONES.map(p => (
                <a key={p} href={`tel:${p.replace(/–/g, "")}`}
                  className="flex items-center gap-3 p-3 rounded-xl hover:bg-[#E4F0E4] transition-colors group">
                  <div className="w-8 h-8 bg-[#E4F0E4] rounded-lg flex items-center justify-center group-hover:bg-[#28722C] transition-colors">
                    <Phone className="w-4 h-4 text-[#28722C] group-hover:text-white transition-colors" />
                  </div>
                  <span className="font-bold text-[#25272C]">{p}</span>
                </a>
              ))}
            </div>

            <div className="bg-white rounded-3xl border border-[#DDE2DD] p-6">
              <h3 className="font-bold text-[#25272C] mb-3 flex items-center gap-2">
                <MapPin className="w-5 h-5 text-[#28722C]" /> آدرس
              </h3>
              <p className="text-[#545B64] text-sm leading-loose">{ADDRESS}</p>
              <div className="flex flex-wrap gap-2 mt-4">
                {[
                  { name: "Google Maps", href: "https://maps.google.com/?q=35.758881,51.413824" },
                  { name: "نشان",        href: "https://nshn.ir/gNbNgYZt_Rxg" },
                  { name: "بلد",         href: "https://balad.ir" },
                  { name: "Waze",        href: "https://waze.com" },
                ].map(l => (
                  <a key={l.name} href={l.href} target="_blank" rel="noopener noreferrer"
                    className="text-xs bg-[#F7F8F6] hover:bg-[#E4F0E4] hover:text-[#28722C] text-[#545B64] rounded-lg px-3 py-1.5 font-medium transition-colors border border-[#DDE2DD]">
                    {l.name}
                  </a>
                ))}
              </div>
            </div>

            <div className="bg-white rounded-3xl border border-[#DDE2DD] p-6">
              <h3 className="font-bold text-[#25272C] mb-3 flex items-center gap-2">
                <Clock className="w-5 h-5 text-[#28722C]" /> ساعات پذیرش
              </h3>
              <div className="flex items-center justify-between bg-[#E4F0E4] rounded-xl px-4 py-3">
                <span className="text-sm font-medium text-[#25272C]">شنبه و سه‌شنبه</span>
                <span className="text-sm font-bold text-[#28722C]">۱۵:۰۰ – ۱۹:۰۰</span>
              </div>
              <p className="text-xs text-[#545B64] mt-3">برای رزرو نوبت آنلاین ۲۴ ساعته در دسترس هستیم.</p>
              <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer"
                className="mt-3 flex items-center justify-center gap-2 w-full py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
                رزرو نوبت آنلاین
              </a>
            </div>
          </div>

          {/* Form + Map */}
          <div className="lg:col-span-3 space-y-5">
            <div className="bg-white rounded-3xl border border-[#DDE2DD] p-8">
              {submitted ? (
                <div className="flex flex-col items-center justify-center py-12 text-center gap-4">
                  <div className="w-20 h-20 bg-[#E4F0E4] rounded-full flex items-center justify-center">
                    <CheckCircle className="w-10 h-10 text-[#28722C]" />
                  </div>
                  <h3 className="text-2xl font-black text-[#25272C]">پیام شما ارسال شد!</h3>
                  <p className="text-[#545B64] max-w-sm">همکاران ما به‌زودی با شما تماس خواهند گرفت. ممنون از اعتماد شما.</p>
                  <button onClick={() => { setSubmitted(false); setForm({ name: "", phone: "", subject: "", message: "" }); }}
                    className="text-[#28722C] font-bold text-sm underline">ارسال پیام جدید</button>
                </div>
              ) : (
                <>
                  <h3 className="text-xl font-black text-[#25272C] mb-6">ارسال پیام</h3>
                  {/* PHP: Contact Form 7: [contact-form-7 id="..." title="Contact form"] */}
                  <form onSubmit={e => { e.preventDefault(); setSubmitted(true); }} className="space-y-5">
                    <div className="grid sm:grid-cols-2 gap-5">
                      <div>
                        <label className="block text-sm font-bold text-[#25272C] mb-2">نام و نام خانوادگی <span className="text-red-500">*</span></label>
                        <input required value={form.name} onChange={e => setForm({ ...form, name: e.target.value })}
                          placeholder="نام خود را وارد کنید"
                          className="w-full bg-[#F7F8F6] border border-[#DDE2DD] rounded-xl px-4 py-3 text-[#25272C] placeholder:text-[#545B64] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 focus:border-[#28722C] transition-all" />
                      </div>
                      <div>
                        <label className="block text-sm font-bold text-[#25272C] mb-2">شماره تلفن <span className="text-red-500">*</span></label>
                        <input required value={form.phone} onChange={e => setForm({ ...form, phone: e.target.value })}
                          placeholder="۰۹۱۲ *** ****"
                          className="w-full bg-[#F7F8F6] border border-[#DDE2DD] rounded-xl px-4 py-3 text-[#25272C] placeholder:text-[#545B64] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 focus:border-[#28722C] transition-all" dir="ltr" />
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-bold text-[#25272C] mb-2">موضوع</label>
                      <select value={form.subject} onChange={e => setForm({ ...form, subject: e.target.value })}
                        className="w-full bg-[#F7F8F6] border border-[#DDE2DD] rounded-xl px-4 py-3 text-[#25272C] focus:outline-none focus:border-[#28722C] transition-all">
                        <option value="">انتخاب موضوع</option>
                        <option>رینوپلاستی (جراحی بینی اولیه)</option>
                        <option>جراحی بینی ترمیمی</option>
                        <option>رفع قوز بینی</option>
                        <option>انحراف بینی (سپتوپلاستی)</option>
                        <option>سایر</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-bold text-[#25272C] mb-2">پیام شما <span className="text-red-500">*</span></label>
                      <textarea required value={form.message} onChange={e => setForm({ ...form, message: e.target.value })}
                        placeholder="سؤال یا درخواست خود را بنویسید..." rows={5}
                        className="w-full bg-[#F7F8F6] border border-[#DDE2DD] rounded-xl px-4 py-3 text-[#25272C] placeholder:text-[#545B64] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 focus:border-[#28722C] transition-all resize-none" />
                    </div>
                    <button type="submit"
                      className="w-full bg-[#28722C] hover:bg-[#246b28] text-white font-bold py-4 rounded-2xl transition-all flex items-center justify-center gap-2">
                      <Send size={16} /> ارسال پیام
                    </button>
                    <p className="text-xs text-[#545B64] text-center flex items-center justify-center gap-1">
                      <Shield size={11} />اطلاعات شما محرمانه و امن است
                    </p>
                  </form>
                </>
              )}
            </div>

            {/* Map */}
            <div className="rounded-3xl overflow-hidden border border-[#DDE2DD] shadow-lg h-64">
              <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3237.671371427505!2d51.4138246!3d35.758881300000006!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzXCsDQ1JzMyLjAiTiA1McKwMjQnNTAuMiJF!5e0!3m2!1sfa!2sir!4v1698000000000!5m2!1sfa!2sir"
                width="100%" height="100%" style={{ border: 0 }} allowFullScreen loading="lazy"
                referrerPolicy="no-referrer-when-downgrade" title="محل کلینیک دکتر باستانی‌نژاد"
              />
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
