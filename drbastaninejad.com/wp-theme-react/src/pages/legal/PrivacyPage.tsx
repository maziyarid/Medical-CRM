/**
 * PrivacyPage — سیاست حریم خصوصی
 * WP: page-privacy.php
 * WP/Theme: last_updated_date (date) — PHP: get_post_meta(get_the_ID(), 'last_updated_date', true)
 */
import Breadcrumb from "@/components/Breadcrumb";
import JsonLd, { buildBreadcrumbSchema } from "@/components/JsonLd";
import { Shield, Mail, Phone } from "lucide-react";

const LAST_UPDATED = "۱ فروردین ۱۴۰۴";

/* Shared legal heading + paragraph styles */
const SH = "text-sm font-bold text-[#25272C] mt-8 mb-2 pb-2 border-b border-[#DDE2DD] flex items-center gap-2 border-r-4 border-r-[#28722C] pr-3";
const SP = "text-sm text-[#545B64] leading-[2] mb-3";
const SUL = "list-none space-y-1.5 mb-4";
const SLI = "flex items-start gap-2 text-sm text-[#545B64]";

export default function PrivacyPage() {
  return (
    <>
      <JsonLd
        data={buildBreadcrumbSchema([{ label: "خانه", href: "/" }, { label: "حریم خصوصی" }])}
        id="privacy-schema"
      />
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "سیاست حریم خصوصی" }]} />

      {/* Dark hero header — matches other pages */}
      <header className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-12 px-4 sm:px-6">
        <div className="max-w-[760px] mx-auto">
          <div className="flex items-center gap-2 mb-4">
            <Shield size={20} className="text-[#28722C]" />
            <span className="text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full border border-[#28722C]/30">حقوقی</span>
          </div>
          <h1 className="text-3xl font-bold mb-2">سیاست حریم خصوصی</h1>
          <p className="text-white/60 text-sm">آخرین به‌روزرسانی: {LAST_UPDATED}</p>
        </div>
      </header>

      {/* Body */}
      <div className="max-w-[760px] mx-auto px-4 sm:px-6 py-10">
        <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6 sm:p-8 space-y-0">

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۱</span>جمع‌آوری اطلاعات</h2>
          <p className={SP}>کلینیک دکتر شاهین باستانی‌نژاد اطلاعاتی را که شما هنگام استفاده از این وب‌سایت، پر کردن فرم‌های تماس یا ثبت‌نام برای نوبت‌گیری ارائه می‌دهید، جمع‌آوری می‌کند. این اطلاعات ممکن است شامل نام، شماره تماس، آدرس ایمیل و اطلاعات مرتبط با وضعیت سلامتی برای اهداف مشاوره باشد.</p>
          <p className={SP}>همچنین هنگام بازدید از این وب‌سایت، اطلاعاتی مانند آدرس IP، نوع مرورگر، صفحات بازدید‌شده و مدت زمان حضور در سایت به‌صورت خودکار جمع‌آوری می‌شود.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۲</span>استفاده از اطلاعات</h2>
          <p className={SP}>اطلاعات جمع‌آوری‌شده برای اهداف زیر استفاده می‌شود:</p>
          <ul className={SUL}>
            {["هماهنگی وقت مشاوره و نوبت‌گیری پزشکی","پاسخ به سوالات و درخواست‌های شما","بهبود کیفیت خدمات وب‌سایت","ارسال اطلاعیه‌های پزشکی مرتبط (با رضایت شما)"].map(i => (
              <li key={i} className={SLI}><span className="w-1.5 h-1.5 rounded-full bg-[#28722C] flex-shrink-0 mt-2" />{i}</li>
            ))}
          </ul>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۳</span>اشتراک‌گذاری اطلاعات</h2>
          <p className={SP}>اطلاعات شخصی شما بدون رضایت صریح شما با اشخاص ثالث به اشتراک گذاشته نمی‌شود، مگر در موارد زیر:</p>
          <ul className={SUL}>
            {["الزامات قانونی یا حکم دادگاه","حفاظت از حقوق، دارایی یا ایمنی کلینیک، بیماران یا دیگران","ارائه‌دهندگان خدمات فنی مورد اعتماد که تحت قرارداد محرمانگی با ما کار می‌کنند"].map(i => (
              <li key={i} className={SLI}><span className="w-1.5 h-1.5 rounded-full bg-[#28722C] flex-shrink-0 mt-2" />{i}</li>
            ))}
          </ul>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۴</span>امنیت اطلاعات</h2>
          <p className={SP}>ما از روش‌های استاندارد صنعت برای حفاظت از اطلاعات شخصی شما استفاده می‌کنیم. با این حال، هیچ روش انتقال اطلاعات از طریق اینترنت ۱۰۰٪ امن نیست. ما متعهد هستیم که از اطلاعات شما با بهترین روش‌های موجود محافظت کنیم.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۵</span>کوکی‌ها</h2>
          <p className={SP}>این وب‌سایت ممکن است از کوکی‌ها برای بهبود تجربه کاربری استفاده کند. شما می‌توانید کوکی‌ها را از طریق تنظیمات مرورگر خود مدیریت کنید. غیرفعال کردن کوکی‌ها ممکن است برخی عملکردهای سایت را تحت تأثیر قرار دهد.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۶</span>حقوق شما</h2>
          <p className={SP}>شما حق دارید:</p>
          <ul className={SUL}>
            {["به اطلاعات شخصی‌ای که از شما نگهداری می‌کنیم، دسترسی داشته باشید","درخواست اصلاح اطلاعات نادرست دهید","درخواست حذف اطلاعات خود را بدهید (در حدود الزامات قانونی)","از پردازش اطلاعات خود برای اهداف بازاریابی انصراف دهید"].map(i => (
              <li key={i} className={SLI}><span className="w-1.5 h-1.5 rounded-full bg-[#28722C] flex-shrink-0 mt-2" />{i}</li>
            ))}
          </ul>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۷</span>تغییرات در این سیاست</h2>
          <p className={SP}>ما ممکن است این سیاست حریم خصوصی را هر از چند گاهی به‌روز کنیم. تغییرات مهم از طریق وب‌سایت اطلاع‌رسانی می‌شود. ادامه استفاده از سایت پس از این تغییرات به منزله قبول سیاست جدید است.</p>

          <h2 className={SH}><span className="w-6 h-6 rounded-full bg-[#E4F0E4] text-[#28722C] text-xs font-black flex items-center justify-center flex-shrink-0">۸</span>تماس با ما</h2>
          <p className={SP}>برای هرگونه سوال درباره این سیاست حریم خصوصی، با ما تماس بگیرید:</p>
          <div className="flex flex-col sm:flex-row gap-3 p-4 bg-[#F7F8F6] rounded-xl border border-[#DDE2DD] mt-2">
            <a href="tel:02186087250" className="flex items-center gap-2 text-sm text-[#28722C] font-medium hover:text-[#246b28] transition-colors">
              <Phone size={14} className="flex-shrink-0" />۰۲۱۸۶۰۸۷۲۵۰
            </a>
            <a href="mailto:info@drbastaninejad.com" className="flex items-center gap-2 text-sm text-[#28722C] font-medium hover:text-[#246b28] transition-colors">
              <Mail size={14} className="flex-shrink-0" />info@drbastaninejad.com
            </a>
          </div>
        </div>
      </div>
    </>
  );
}
