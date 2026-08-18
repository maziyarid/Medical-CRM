/**
 * BookingPage — page-booking.php equivalent
 *
 * WP/Theme: This page hosts an inline booking request form.
 *   Form data is submitted via wp-ajax (action: 'submit_booking_request')
 *   or a REST endpoint (POST /wp-json/clinic/v1/booking).
 *
 *   Fields to register in theme:
 *   - booking_name        (text)   — پنل: نام و نام‌خانوادگی
 *   - booking_phone       (text)   — شماره تلفن (OTP verification)
 *   - booking_procedure   (select) — نوع عمل / خدمت
 *   - booking_message     (textarea) — توضیحات تکمیلی
 *   - booking_otp         (text)   — کد تأیید ارسال شده
 *
 *   PHP: register_post_type('booking_request', [...]) or WP form plugin
 *   WP: Save each submission as a CPT 'booking_request' or to options table.
 *
 * OTP flow (React simulation — replace with real SMS API call in production):
 *   Step 1: User fills name + phone + procedure + message → click "ارسال کد تأیید"
 *   Step 2: OTP field appears → user enters code → click "ثبت نهایی"
 *   Step 3: Success screen shown
 */
import { useState } from "react";
import { Link } from "react-router";
import { Calendar, Phone, CheckCircle, Clock, Shield, ArrowLeft, MessageSquare, User, Smartphone } from "lucide-react";
import { PHONES, COSMETIC_SERVICES, FUNCTIONAL_SERVICES } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";
import JsonLd, { buildBreadcrumbSchema } from "@/components/JsonLd";

/* WP/Theme: procedure_options (select field) — PHP: get_theme_mod('booking_procedures', $defaults) */
const PROCEDURES = [
  ...COSMETIC_SERVICES.map(s => s.title),
  ...FUNCTIONAL_SERVICES.map(s => s.title),
  "مشاوره عمومی",
];

type Step = "form" | "otp" | "done";

interface FormState {
  name: string;
  phone: string;
  procedure: string;
  message: string;
  otp: string;
}

const EMPTY: FormState = { name: "", phone: "", procedure: "", message: "", otp: "" };

/* Simulated OTP — in production replaced by SMS API call from WP backend */
const MOCK_OTP = "12345";

export default function BookingPage() {
  const [step, setStep] = useState<Step>("form");
  const [form, setForm] = useState<FormState>(EMPTY);
  const [errors, setErrors] = useState<Partial<FormState>>({});
  const [loading, setLoading] = useState(false);

  function set(field: keyof FormState, value: string) {
    setForm(prev => ({ ...prev, [field]: value }));
    setErrors(prev => ({ ...prev, [field]: "" }));
  }

  function validateForm(): boolean {
    const errs: Partial<FormState> = {};
    if (!form.name.trim())      errs.name      = "نام الزامی است";
    if (!form.phone.trim())     errs.phone     = "شماره تلفن الزامی است";
    else if (!/^0\d{10}$/.test(form.phone.trim())) errs.phone = "فرمت شماره تلفن اشتباه است (مثال: ۰۹۱۲۱۲۳۴۵۶۷)";
    if (!form.procedure)        errs.procedure = "لطفاً نوع خدمت را انتخاب کنید";
    setErrors(errs);
    return Object.keys(errs).length === 0;
  }

  function handleSendOtp(e: React.FormEvent) {
    e.preventDefault();
    if (!validateForm()) return;
    setLoading(true);
    /* WP: POST /wp-json/clinic/v1/otp/send  { phone: form.phone }
     * On success, backend sends SMS OTP and returns { sent: true }
     * Mock: simulate delay */
    setTimeout(() => {
      setLoading(false);
      setStep("otp");
    }, 900);
  }

  function handleVerifyOtp(e: React.FormEvent) {
    e.preventDefault();
    if (!form.otp.trim()) {
      setErrors(prev => ({ ...prev, otp: "کد تأیید الزامی است" }));
      return;
    }
    /* WP: POST /wp-json/clinic/v1/otp/verify  { phone, otp, ...formData }
     * On success: create CPT 'booking_request' and send confirmation SMS */
    if (form.otp.trim() === MOCK_OTP) {
      setLoading(true);
      setTimeout(() => { setLoading(false); setStep("done"); }, 700);
    } else {
      setErrors(prev => ({ ...prev, otp: "کد وارد شده اشتباه است" }));
    }
  }

  const crumbs = [{ label: "خانه", href: "/" }, { label: "رزرو نوبت" }];

  return (
    <>
      <JsonLd id="jsonld-booking" data={buildBreadcrumbSchema(crumbs)} />

      <Breadcrumb items={crumbs} />

      {/* Page hero */}
      <section className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto text-center">
          {/* WP/Theme: booking_page_eyebrow (text) — PHP: get_theme_mod('booking_page_eyebrow') */}
          <span className="text-[#28722C] font-bold text-sm tracking-wider">رزرو نوبت آنلاین</span>
          <h1 className="text-4xl sm:text-5xl font-black mt-2 mb-4">رزرو نوبت</h1>
          <p className="text-white/70 text-lg">فرم زیر را تکمیل کنید — تیم ما ظرف ۲۴ ساعت با شما تماس می‌گیرد</p>
        </div>
      </section>

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-16">
        <div className="grid lg:grid-cols-3 gap-8">

          {/* Sidebar info */}
          <aside className="space-y-5">
            {/* Steps */}
            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6">
              <h3 className="font-bold text-[#25272C] mb-4">نحوه رزرو نوبت</h3>
              <div className="space-y-4">
                {[
                  { step: "۱", title: "تکمیل فرم",       desc: "اطلاعات اولیه خود را وارد کنید." },
                  { step: "۲", title: "تأیید شماره",      desc: "کد OTP ارسال شده را وارد کنید." },
                  { step: "۳", title: "تماس تیم کلینیک", desc: "ظرف ۲۴ ساعت با شما تماس می‌گیریم." },
                ].map(s => (
                  <div key={s.step} className="flex gap-3">
                    <div className="w-8 h-8 bg-[#E4F0E4] rounded-xl flex items-center justify-center flex-shrink-0">
                      <span className="text-[#28722C] font-black text-sm">{s.step}</span>
                    </div>
                    <div>
                      <p className="font-semibold text-[#25272C] text-sm">{s.title}</p>
                      <p className="text-xs text-[#545B64]">{s.desc}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Hours — WP/Theme: clinic_hours_sat, clinic_hours_wed (text fields) */}
            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6">
              <h3 className="font-bold text-[#25272C] mb-4 flex items-center gap-2">
                <Clock size={16} className="text-[#28722C]" /> ساعات پذیرش
              </h3>
              <div className="bg-[#E4F0E4] rounded-xl px-4 py-3">
                <p className="text-sm font-bold text-[#25272C]">شنبه و سه‌شنبه</p>
                <p className="text-sm text-[#28722C] font-semibold">۱۵:۰۰ – ۱۹:۰۰</p>
              </div>
            </div>

            {/* Direct contact — WP/Theme: clinic_phones (repeater) */}
            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6">
              <h3 className="font-bold text-[#25272C] mb-4 flex items-center gap-2">
                <Phone size={16} className="text-[#28722C]" /> تماس مستقیم
              </h3>
              {PHONES.map(p => (
                <a key={p} href={`tel:${p.replace(/[^0-9]/g, "")}`}
                  className="flex items-center gap-3 p-2 rounded-lg hover:bg-[#E4F0E4] transition-colors mb-1">
                  <Phone size={14} className="text-[#28722C]" />
                  <span className="font-bold text-[#25272C] text-sm" dir="ltr">{p}</span>
                </a>
              ))}
            </div>

            {/* Benefits */}
            <div className="bg-[#E4F0E4] rounded-2xl p-5">
              <p className="text-sm font-bold text-[#28722C] mb-3">مزایای رزرو آنلاین</p>
              {["رزرو ۲۴ ساعته بدون معطلی", "یادآوری خودکار قبل از نوبت", "مشاوره اولیه رایگان", "محرمانه و ایمن"].map(f => (
                <div key={f} className="flex items-center gap-2 text-sm text-[#25272C] mb-2">
                  <CheckCircle size={13} className="text-[#28722C] flex-shrink-0" />{f}
                </div>
              ))}
            </div>
          </aside>

          {/* Main form area */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-3xl border border-[#DDE2DD] overflow-hidden shadow-lg">
              {/* Card header */}
              <div className="bg-gradient-to-br from-[#28722C] to-[#1a4e1d] p-6 text-white text-center">
                <Calendar size={32} className="mx-auto mb-3" />
                <h2 className="text-xl font-black mb-1">فرم رزرو نوبت</h2>
                {/* WP/Theme: booking_form_subtitle (text) */}
                <p className="text-white/80 text-sm">تمام موارد ستاره‌دار الزامی هستند</p>
              </div>

              <div className="p-8">
                {/* ── Step: FORM ── */}
                {step === "form" && (
                  <form onSubmit={handleSendOtp} noValidate className="space-y-5">
                    {/* Name */}
                    <div>
                      <label className="block text-sm font-bold text-[#25272C] mb-1.5">
                        <User size={13} className="inline ml-1 text-[#28722C]" />
                        نام و نام‌خانوادگی <span className="text-red-500">*</span>
                      </label>
                      <input type="text" value={form.name} onChange={e => set("name", e.target.value)}
                        placeholder="مثال: علی محمدی"
                        className={`w-full px-4 py-3 rounded-xl border text-sm bg-[#F7F8F6] placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 transition-all ${errors.name ? "border-red-400" : "border-[#DDE2DD]"}`} />
                      {errors.name && <p className="text-xs text-red-500 mt-1">{errors.name}</p>}
                    </div>

                    {/* Phone */}
                    <div>
                      <label className="block text-sm font-bold text-[#25272C] mb-1.5">
                        <Smartphone size={13} className="inline ml-1 text-[#28722C]" />
                        شماره موبایل <span className="text-red-500">*</span>
                      </label>
                      <input type="tel" value={form.phone} onChange={e => set("phone", e.target.value)}
                        placeholder="09123456789" dir="ltr"
                        className={`w-full px-4 py-3 rounded-xl border text-sm bg-[#F7F8F6] placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 transition-all ${errors.phone ? "border-red-400" : "border-[#DDE2DD]"}`} />
                      {errors.phone && <p className="text-xs text-red-500 mt-1">{errors.phone}</p>}
                    </div>

                    {/* Procedure — WP/Theme: procedure_options (select, theme_mod repeater) */}
                    <div>
                      <label className="block text-sm font-bold text-[#25272C] mb-1.5">
                        <Calendar size={13} className="inline ml-1 text-[#28722C]" />
                        نوع خدمت / عمل مورد نظر <span className="text-red-500">*</span>
                      </label>
                      <select value={form.procedure} onChange={e => set("procedure", e.target.value)}
                        className={`w-full px-4 py-3 rounded-xl border text-sm bg-[#F7F8F6] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 transition-all ${errors.procedure ? "border-red-400" : "border-[#DDE2DD]"}`}>
                        <option value="">-- انتخاب کنید --</option>
                        {PROCEDURES.map(p => <option key={p} value={p}>{p}</option>)}
                      </select>
                      {errors.procedure && <p className="text-xs text-red-500 mt-1">{errors.procedure}</p>}
                    </div>

                    {/* Message */}
                    <div>
                      <label className="block text-sm font-bold text-[#25272C] mb-1.5">
                        <MessageSquare size={13} className="inline ml-1 text-[#28722C]" />
                        توضیحات تکمیلی <span className="text-[#9CA3AF] font-normal text-xs">(اختیاری)</span>
                      </label>
                      <textarea value={form.message} onChange={e => set("message", e.target.value)}
                        rows={4} placeholder="سوالات یا توضیحات بیشتر درباره وضعیت بینی خود..."
                        className="w-full px-4 py-3 rounded-xl border border-[#DDE2DD] text-sm bg-[#F7F8F6] placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 transition-all resize-none" />
                    </div>

                    <button type="submit" disabled={loading}
                      className="w-full py-4 bg-[#28722C] text-white font-black rounded-xl hover:bg-[#246b28] disabled:opacity-60 transition-colors flex items-center justify-center gap-2">
                      {loading ? "در حال ارسال..." : <><Smartphone size={16} />ارسال کد تأیید</>}
                    </button>

                    <div className="flex items-center justify-center gap-1.5 text-xs text-[#545B64]">
                      <Shield size={11} className="text-[#28722C]" />اطلاعات شما کاملاً محرمانه است
                    </div>
                  </form>
                )}

                {/* ── Step: OTP ── */}
                {step === "otp" && (
                  <form onSubmit={handleVerifyOtp} noValidate className="space-y-5">
                    <div className="text-center mb-2">
                      <div className="w-16 h-16 bg-[#E4F0E4] rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <Smartphone size={28} className="text-[#28722C]" />
                      </div>
                      <p className="font-bold text-[#25272C]">کد تأیید ارسال شد</p>
                      <p className="text-sm text-[#545B64] mt-1">
                        کد ۵ رقمی به شماره <span dir="ltr" className="font-bold text-[#25272C]">{form.phone}</span> ارسال شد
                      </p>
                      {/* Demo notice — remove in production */}
                      <p className="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-3">
                        نمایشی: کد تأیید = <strong>12345</strong>
                      </p>
                    </div>

                    <div>
                      <label className="block text-sm font-bold text-[#25272C] mb-1.5">
                        کد تأیید <span className="text-red-500">*</span>
                      </label>
                      <input type="text" value={form.otp} onChange={e => set("otp", e.target.value)}
                        placeholder="- - - - -" dir="ltr" maxLength={6}
                        className={`w-full px-4 py-3 rounded-xl border text-sm bg-[#F7F8F6] text-center text-lg font-black tracking-widest placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 transition-all ${errors.otp ? "border-red-400" : "border-[#DDE2DD]"}`} />
                      {errors.otp && <p className="text-xs text-red-500 mt-1 text-center">{errors.otp}</p>}
                    </div>

                    <button type="submit" disabled={loading}
                      className="w-full py-4 bg-[#28722C] text-white font-black rounded-xl hover:bg-[#246b28] disabled:opacity-60 transition-colors">
                      {loading ? "در حال تأیید..." : "ثبت نهایی نوبت"}
                    </button>

                    <button type="button" onClick={() => setStep("form")}
                      className="w-full py-2.5 border border-[#DDE2DD] text-[#545B64] rounded-xl text-sm hover:border-[#28722C]/40 transition-colors flex items-center justify-center gap-1">
                      <ArrowLeft size={13} />ویرایش اطلاعات
                    </button>
                  </form>
                )}

                {/* ── Step: DONE ── */}
                {step === "done" && (
                  <div className="text-center py-8">
                    <div className="w-20 h-20 bg-[#E4F0E4] rounded-3xl flex items-center justify-center mx-auto mb-6">
                      <CheckCircle size={40} className="text-[#28722C]" />
                    </div>
                    <h3 className="text-2xl font-black text-[#25272C] mb-3">نوبت شما ثبت شد ✓</h3>
                    <p className="text-[#545B64] text-sm leading-relaxed mb-6">
                      اطلاعات شما با موفقیت دریافت شد.<br />
                      تیم کلینیک دکتر باستانی‌نژاد ظرف <strong className="text-[#28722C]">۲۴ ساعت</strong> با شماره{" "}
                      <span dir="ltr" className="font-bold text-[#25272C]">{form.phone}</span> با شما تماس می‌گیرد.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-3 justify-center">
                      <Link to="/" className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-[#28722C] text-white font-bold rounded-xl hover:bg-[#246b28] transition-colors">
                        بازگشت به صفحه اصلی
                      </Link>
                      <Link to="/services" className="inline-flex items-center justify-center gap-2 px-6 py-3 border border-[#DDE2DD] text-[#25272C] font-bold rounded-xl hover:border-[#28722C]/30 transition-colors">
                        مشاهده خدمات
                      </Link>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
