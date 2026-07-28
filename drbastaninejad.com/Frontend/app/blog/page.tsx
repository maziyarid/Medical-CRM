import type { Metadata } from "next";
import { BlogExplorer } from "../components/blog-explorer";
import { CallToAction, PageHero } from "../components/ui";

export const metadata: Metadata = {
  title: "مجله سلامت و زیبایی",
  description:
    "مقاله‌های آموزشی درباره جراحی بینی، انتخاب آگاهانه، مراقبت‌های پیش و پس از عمل و سلامت تنفس.",
};

export default function BlogPage() {
  return (
    <>
      <PageHero
        eyebrow="مجله سلامت و زیبایی"
        title={
          <>
            دانستن،
            <br />
            بخشی از <em>درمان</em> است.
          </>
        }
        text="مطالب کاربردی برای شناخت بهتر جراحی بینی، دوره بهبود و پرسش‌هایی که پیش از تصمیم باید پاسخ آن‌ها را بدانید."
        aside={
          <div className="editorial-mark">
            <span>JOURNAL</span>
            <strong>۰۶</strong>
            <small>راهنمای منتخب برای شروع</small>
          </div>
        }
      />
      <section className="section container blog-page">
        <BlogExplorer />
      </section>
      <CallToAction
        title="پاسخ شرایط شما در یک مقاله عمومی نیست."
        text="برای ارزیابی دقیق فرم، عملکرد و گزینه‌های درمانی متناسب با خودتان، جلسه مشاوره رزرو کنید."
      />
    </>
  );
}
