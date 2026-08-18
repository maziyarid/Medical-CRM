/**
 * CancellationPage — سیاست لغو و کنسلی
 * WP: page-cancellation.php
 * WP/Theme: cancellation_window_hours (number) — PHP: get_theme_mod('cancellation_window_hours', 48)
 * WP/Theme: refund_policy_text (textarea) — PHP: get_post_meta()
 */
import Breadcrumb from "@/components/Breadcrumb";
import JsonLd, { buildBreadcrumbSchema } from "@/components/JsonLd";
import { Clock, AlertCircle, CheckCircle, Phone } from "lucide-react";
import { BOOKING_URL } from "@/data/site";

/* WP/Theme: cancellation_window_hours (number) — PHP: get_theme_mod('cancellation_window_hours', 48) */
const CANCEL_WINDOW_HRS = 48;
const LAST_UPDATED = "۱ فروردین ۱۴۰۴";

const SH = "text-sm font-bold text-[#25272C] mt-8 mb-2 pb-2 border-b border-[#DDE2DD] flex items-center gap-2 border-r-4 border-r-[#28722C] pr-3";
const SP = "text-sm text-[#545B64] leading-[2] mb-3";
const SUL = "list-none space-y-1.5 mb-4";
const SLI = "flex items-start gap-2 text-sm text-[#545B64]";

export default function CancellationPage() {
  return (
    <>
      <JsonLd
        data={buildBreadcrumbSchema([{ label: "خانه", href: "/" }, { label: "سیاست لغو" }])}
        id="cancel-schema"
      />
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "سیاست لغو و کنسلی" }]} />

      <header className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-12 px-4 sm:px-6">
        <div className="max-w-[760px] mx-auto">
          <div className="flex items-center gap-2 mb-4">
            <Clock size={20} className="text-[#28722C]" />
            <span className="text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full border border-[#28722C]/30">حقوقی</span>
          </div>
          <h1 className="text-3xl font-bold mb-2">سیاست لغو و کنسلی وقت</h1>
          <p className="text-white/60 text-sm">آخرین به‌روزرسانی: {LAST_UPDATED}</p>
        </div>
      </header>

      <div className="max-w-[760px] mx-auto px-4 sm:px-6 py-10">

        {/* Quick summary cards */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
          {[
            { Icon: CheckCircle, bg: "bg-[#E4F0E4]", ic: "text-[#28722C]", title: `${CANCEL_WINDOW_HRS} ساعت قبل`, desc: "لغو رایگان" },
            { Icon: AlertCircle, bg: "bg-amber-50",  ic: "text-amber-600", title: "۲۴–۴۸ ساعت",               desc: "۵۰٪ هزینه مشاوره" },
            { Icon: Clock,       bg: "bg-red-50",    ic: "text-red-600",   title: "کمتر از ۲۴ ساعت",          desc: "هزینه کامل" },
          ].map(({ Icon, bg, ic, title, desc }) => (
            <div key={title} className="bg-white border border-[#DDE2DD] rounded-2xl p-5 text-center">
              <div className={`w-10 h-10 rounded-xl ${bg} flex items-center justify-center mx-auto mb-3`}>
                <Icon size={18} className={ic} />
              </div>
              <p className="font-bold text-[#25272C] text-sm mb-1">{title}</p>
              <p className="text-xs text-[#545B64]">{desc}</p>
            </div>
          ))}
        </div>

        <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6 sm:p-8">

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۱</span>مهلت لغو</h2>
          <p className={SP}>بیماران می‌توانند وقت مشاوره خود را حداقل <strong className="text-[#25272C]">{CANCEL_WINDOW_HRS} ساعت</strong> قبل از زمان مقرر به‌صورت رایگان لغو کنند. برای لغو وقت، لطفاً از طریق تلفن یا سامانه آنلاین اقدام کنید.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۲</span>لغو با تأخیر</h2>
          <p className={SP}>در صورت لغو بین ۲۴ تا {CANCEL_WINDOW_HRS} ساعت قبل از وقت، ۵۰٪ هزینه ویزیت یا مشاوره دریافت می‌شود. این هزینه قابل کسر از هزینه خدمت بعدی می‌باشد.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۳</span>عدم حضور (No-Show)</h2>
          <p className={SP}>در صورت عدم حضور بدون اطلاع قبلی (کمتر از ۲۴ ساعت یا هیچ اطلاعی)، هزینه کامل ویزیت دریافت می‌شود. تکرار این موضوع ممکن است منجر به محدودیت در پذیرش شود.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۴</span>شرایط استثنائی</h2>
          <p className={SP}>در موارد اورژانس پزشکی، تصادف یا شرایط فورس‌ماژور، پس از ارائه مدارک لازم، هزینه کنسلی بخشوده می‌شود. لطفاً در اسرع وقت با کلینیک تماس بگیرید.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۵</span>تغییر وقت</h2>
          <p className={SP}>تغییر وقت حداقل {CANCEL_WINDOW_HRS} ساعت قبل از زمان مقرر رایگان است. برای تغییر وقت با تأخیر کمتر، بنا به موجودیت وقت و صلاحدید مجموعه عمل می‌شود.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۶</span>روش لغو / تغییر وقت</h2>
          <ul className={SUL}>
            {[
              `از طریق سامانه آنلاین: app.drbastaninejad.com`,
              `از طریق تلفن کلینیک: ۰۲۱۸۶۰۸۷۲۵۰`,
              `ساعات پذیرش: شنبه و سه‌شنبه — ۱۵:۰۰ تا ۱۹:۰۰`,
            ].map(i => (
              <li key={i} className={SLI}><span className="w-1.5 h-1.5 rounded-full bg-[#28722C] flex-shrink-0 mt-2" />{i}</li>
            ))}
          </ul>

          <div className="flex flex-col sm:flex-row gap-3 p-4 bg-[#E4F0E4] rounded-xl mt-4">
            <a href="tel:02186087250" className="flex items-center gap-2 text-sm text-[#28722C] font-bold hover:text-[#246b28] transition-colors">
              <Phone size={14} className="flex-shrink-0" />۰۲۱–۸۶۰۸۷۲۵۰
            </a>
            <a href="tel:02188205606" className="flex items-center gap-2 text-sm text-[#28722C] font-medium hover:text-[#246b28] transition-colors">
              <Phone size={14} className="flex-shrink-0" />۰۲۱–۸۸۲۰۵۶۰۶
            </a>
            <a href={BOOKING_URL} rel="noopener" className="flex items-center gap-2 text-sm text-[#28722C] font-medium hover:text-[#246b28] transition-colors mr-auto">
              رزرو نوبت ←
            </a>
          </div>
        </div>
      </div>
    </>
  );
}
