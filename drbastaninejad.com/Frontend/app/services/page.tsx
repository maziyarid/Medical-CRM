import type { Metadata } from "next";
import Link from "next/link";
import { Icon } from "../components/icons";
import {
  CallToAction,
  PageHero,
  SectionHeading,
  ServicesGrid,
} from "../components/ui";
import { faqs, journey } from "../site-data";

export const metadata: Metadata = {
  title: "خدمات جراحی و زیبایی",
  description:
    "معرفی خدمات راینوپلاستی اولیه و ترمیمی، اصلاح مشکلات تنفسی، بلفاروپلاستی و روش‌های زیبایی صورت.",
};

export default function ServicesPage() {
  return (
    <>
      <PageHero
        eyebrow="خدمات تخصصی"
        title={
          <>
            درمان متناسب
            <br />
            با <em>چهره شما</em>
          </>
        }
        text="هر خدمت با یک ارزیابی جامع آغاز می‌شود تا انتخاب روش درمان بر پایه آناتومی، نیاز و انتظار واقع‌بینانه شما باشد."
        aside={
          <div className="hero-service-note">
            <Icon name="nose" size={36} />
            <span>تمرکز اصلی</span>
            <strong>جراحی زیبایی و ترمیمی بینی</strong>
            <p>با توجه هم‌زمان به فرم و عملکرد تنفسی</p>
          </div>
        }
      />

      <section className="section container">
        <SectionHeading
          eyebrow="فهرست خدمات"
          title="از اصلاح ساختار تا هماهنگی چهره"
          text="جزئیات مناسب هر درمان، محدودیت‌ها و روند بهبود در جلسه مشاوره و پس از معاینه توضیح داده می‌شوند."
        />
        <ServicesGrid />
      </section>

      <section className="featured-service">
        <div className="container featured-service-grid">
          <div className="featured-service-visual featured-service-nose">
            <span className="feature-number">01</span>
            <Icon name="nose" size={94} />
            <p>FORM + FUNCTION</p>
          </div>
          <div className="featured-service-copy">
            <span className="eyebrow">راینوپلاستی اولیه</span>
            <h2>طراحی فرم، با احترام به عملکرد</h2>
            <p>
              در جراحی اولیه، نسبت بینی با سایر اجزای صورت، ضخامت پوست، استحکام
              غضروف‌ها و وضعیت مسیر تنفسی کنار هم ارزیابی می‌شوند. برنامه جراحی
              برای هر فرد متفاوت است و از یک مدل ثابت پیروی نمی‌کند.
            </p>
            <ul className="check-list">
              <li>
                <Icon name="check" size={18} />
                بررسی نمای روبه‌رو، نیم‌رخ و سه‌رخ
              </li>
              <li>
                <Icon name="check" size={18} />
                ارزیابی سپتوم و مسیر تنفس
              </li>
              <li>
                <Icon name="check" size={18} />
                توضیح روند بهبود و تغییرات تورم
              </li>
            </ul>
            <Link href="/appointment" className="button button-primary button-large">
              درخواست مشاوره
              <Icon name="arrow" size={18} />
            </Link>
          </div>
        </div>
      </section>

      <section className="featured-service alternate">
        <div className="container featured-service-grid">
          <div className="featured-service-visual featured-service-revision">
            <span className="feature-number">02</span>
            <Icon name="refresh" size={88} />
            <p>REVIEW + REBUILD</p>
          </div>
          <div className="featured-service-copy">
            <span className="eyebrow">راینوپلاستی ترمیمی</span>
            <h2>بررسی دوباره، با برنامه‌ای دقیق‌تر</h2>
            <p>
              جراحی ترمیمی به شناخت کامل تغییرات قبلی، وضعیت بافت باقی‌مانده و
              محدودیت‌های ساختاری نیاز دارد. زمان مناسب جراحی و امکان دستیابی
              به نتیجه مطلوب تنها پس از معاینه مشخص می‌شود.
            </p>
            <ul className="check-list">
              <li>
                <Icon name="check" size={18} />
                بررسی مدارک و تصاویر جراحی پیشین
              </li>
              <li>
                <Icon name="check" size={18} />
                ارزیابی کیفیت پوست و ساختار غضروفی
              </li>
              <li>
                <Icon name="check" size={18} />
                تعیین زمان مناسب مداخله ترمیمی
              </li>
            </ul>
            <Link href="/appointment" className="button button-outline button-large">
              رزرو ارزیابی تخصصی
              <Icon name="arrow" size={18} />
            </Link>
          </div>
        </div>
      </section>

      <section className="section container journey-section">
        <SectionHeading
          eyebrow="مسیر درمان"
          title="از نخستین ارزیابی تا مراقبت نهایی"
          align="center"
        />
        <div className="journey-grid">
          {journey.map((item) => (
            <article key={item.number}>
              <span>{item.number}</span>
              <h3>{item.title}</h3>
              <p>{item.text}</p>
            </article>
          ))}
        </div>
      </section>

      <section className="faq-section compact-faq">
        <div className="container faq-grid">
          <div>
            <span className="eyebrow">پیش از مراجعه</span>
            <h2>پاسخ کوتاه به پرسش‌های مهم</h2>
            <p>
              برای بررسی شرایط اختصاصی خود، مشاوره حضوری دقیق‌ترین مسیر است.
            </p>
          </div>
          <div className="accordion">
            {faqs.slice(0, 3).map((faq) => (
              <details key={faq.question}>
                <summary>
                  <span>{faq.question}</span>
                  <i>+</i>
                </summary>
                <p>{faq.answer}</p>
              </details>
            ))}
          </div>
        </div>
      </section>

      <CallToAction />
    </>
  );
}
