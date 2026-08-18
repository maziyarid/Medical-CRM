/**
 * TermsPage — شرایط استفاده
 * WP: page-terms.php
 * WP/Theme: last_updated_date (date) — PHP: get_post_meta()
 */
import Breadcrumb from "@/components/Breadcrumb";
import JsonLd, { buildBreadcrumbSchema } from "@/components/JsonLd";
import { FileText } from "lucide-react";

const LAST_UPDATED = "۱ فروردین ۱۴۰۴";

const SH = "text-sm font-bold text-[#25272C] mt-8 mb-2 pb-2 border-b border-[#DDE2DD] flex items-center gap-2 border-r-4 border-r-[#28722C] pr-3";
const SP = "text-sm text-[#545B64] leading-[2] mb-3";
const SUL = "list-none space-y-1.5 mb-4";
const SLI = "flex items-start gap-2 text-sm text-[#545B64]";

export default function TermsPage() {
  return (
    <>
      <JsonLd
        data={buildBreadcrumbSchema([{ label: "خانه", href: "/" }, { label: "شرایط استفاده" }])}
        id="terms-schema"
      />
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "شرایط استفاده" }]} />

      <header className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-12 px-4 sm:px-6">
        <div className="max-w-[760px] mx-auto">
          <div className="flex items-center gap-2 mb-4">
            <FileText size={20} className="text-[#28722C]" />
            <span className="text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full border border-[#28722C]/30">حقوقی</span>
          </div>
          <h1 className="text-3xl font-bold mb-2">شرایط و قوانین استفاده</h1>
          <p className="text-white/60 text-sm">آخرین به‌روزرسانی: {LAST_UPDATED}</p>
        </div>
      </header>

      <div className="max-w-[760px] mx-auto px-4 sm:px-6 py-10">
        <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6 sm:p-8">

          <p className={SP}>با استفاده از وب‌سایت کلینیک دکتر شاهین باستانی‌نژاد (drbastaninejad.com)، شما با این شرایط موافقت می‌کنید. لطفاً آن‌ها را با دقت مطالعه کنید.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۱</span>استفاده از اطلاعات پزشکی (YMYL)</h2>
          <p className={SP}>محتوای این وب‌سایت صرفاً برای اهداف اطلاع‌رسانی و آموزشی ارائه می‌شود. این اطلاعات جایگزین مشاوره، تشخیص یا درمان پزشکی حرفه‌ای نیست. برای هرگونه تصمیم پزشکی، با متخصص مجاز مشورت کنید.</p>
          <p className={SP}>کلینیک دکتر باستانی‌نژاد مسئولیتی در قبال اقدامات پزشکی انجام‌شده بر اساس اطلاعات این وب‌سایت بدون مشاوره مستقیم با پزشک نمی‌پذیرد.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۲</span>مالکیت معنوی</h2>
          <p className={SP}>تمام محتوا، تصاویر، نوشته‌ها، لوگو و طراحی این وب‌سایت تحت حمایت قوانین مالکیت معنوی ایران قرار دارند. هرگونه استفاده، تکثیر یا توزیع بدون اجازه کتبی ممنوع است.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۳</span>تصاویر بیماران</h2>
          <p className={SP}>تصاویر قبل و بعد از جراحی با رضایت کتبی بیماران منتشر شده‌اند. نتایج ممکن است برای هر فرد متفاوت باشد. این تصاویر تضمینی برای نتایج مشابه نیستند.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۴</span>محدودیت مسئولیت</h2>
          <p className={SP}>این وب‌سایت "به همان شکل که هست" (as-is) ارائه می‌شود. کلینیک هیچ ضمانتی در مورد دقت، کمال یا به‌روز بودن اطلاعات نمی‌دهد. در حداکثر حد مجاز قانونی، هیچ مسئولیتی برای خسارات مستقیم یا غیرمستقیم ناشی از استفاده از این وب‌سایت وجود ندارد.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۵</span>لینک‌های خارجی</h2>
          <p className={SP}>این وب‌سایت ممکن است شامل لینک به وب‌سایت‌های خارجی باشد. ما هیچ کنترلی بر محتوای آن‌ها نداریم و مسئولیتی در قبال آن‌ها نمی‌پذیریم.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۶</span>تغییرات</h2>
          <p className={SP}>ما حق داریم این شرایط را در هر زمانی تغییر دهیم. ادامه استفاده از وب‌سایت پس از تغییرات به منزله قبول شرایط جدید است.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۷</span>قانون حاکم</h2>
          <p className={`${SP} mb-0`}>این شرایط تحت قوانین جمهوری اسلامی ایران تفسیر و اجرا می‌شوند. هرگونه اختلاف در دادگاه‌های صالح تهران رسیدگی می‌شود.</p>

          {/* Disclaimer note */}
          <div className="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
            <span className="text-amber-500 mt-0.5 flex-shrink-0">⚠️</span>
            <p className="text-xs text-amber-800 leading-relaxed">محتوای پزشکی این سایت جایگزین مشاوره پزشکی نیست. همیشه با متخصص مجاز مشورت کنید.</p>
          </div>
        </div>
      </div>
    </>
  );
}
