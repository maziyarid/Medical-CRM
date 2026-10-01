import type { Metadata } from "next";
import { ContactForm } from "../components/contact-form";
import { Icon } from "../components/icons";
import { PageHero, SectionHeading } from "../components/ui";
import { clinic } from "../site-data";

export const metadata: Metadata = {
  title: "تماس با مطب",
  description:
    "نشانی، ساعت پذیرش، شماره‌های تماس و فرم ارتباط با مطب دکتر شاهین باستانی‌نژاد.",
};

export default function ContactPage() {
  return (
    <>
      <PageHero
        eyebrow="تماس با مطب"
        title={
          <>
            از سؤال تا
            <br />
            یک مسیر <em>روشن</em>
          </>
        }
        text="برای رزرو نوبت، دریافت راهنمایی اولیه یا پیگیری درمان می‌توانید از راه‌های زیر با مطب در ارتباط باشید."
        aside={
          <div className="contact-hero-aside">
            <span className="status-dot" />
            <div>
              <strong>پاسخ‌گویی در ساعات کاری</strong>
              <small>{clinic.hours}</small>
            </div>
          </div>
        }
      />

      <section className="section container contact-layout">
        <div className="contact-info-panel">
          <SectionHeading
            eyebrow="اطلاعات تماس"
            title="کنار شما هستیم."
            text="برای امور فوری پزشکی پس از جراحی، مطابق دستورالعمل اختصاصی ارائه‌شده با مطب تماس بگیرید."
          />
          <div className="contact-cards">
            <div className="contact-card">
              <span>
                <Icon name="phone" />
              </span>
              <div>
                <small>شماره‌های تماس</small>
                {clinic.phones.map((phone) => (
                  <a href={phone.href} key={phone.href}>
                    {phone.display}
                  </a>
                ))}
              </div>
            </div>
            <div className="contact-card">
              <span>
                <Icon name="clock" />
              </span>
              <div>
                <small>ساعت حضور پزشک</small>
                <p>{clinic.hours}</p>
              </div>
            </div>
            <div className="contact-card">
              <span>
                <Icon name="location" />
              </span>
              <div>
                <small>نشانی مطب</small>
                <p>{clinic.address}</p>
              </div>
            </div>
          </div>
        </div>

        <div className="form-panel">
          <div className="form-panel-head">
            <span className="eyebrow">ارسال پیام</span>
            <h2>چطور می‌توانیم کمک کنیم؟</h2>
            <p>فرم را تکمیل کنید تا درخواست شما در اولین فرصت بررسی شود.</p>
          </div>
          <ContactForm />
        </div>
      </section>

      <section className="container map-panel">
        <div className="map-visual">
          <div className="map-grid" />
          <div className="map-route map-route-a" />
          <div className="map-route map-route-b" />
          <div className="map-pin">
            <Icon name="location" size={26} />
          </div>
          <span className="map-label map-label-a">بلوار نلسون ماندلا</span>
          <span className="map-label map-label-b">خیابان صانعی</span>
        </div>
        <div className="map-copy">
          <span className="eyebrow">موقعیت مطب</span>
          <h2>ساختمان نور، طبقه دوم</h2>
          <p>{clinic.address}</p>
          <a
            href="https://www.google.com/maps/search/?api=1&query=Nelson+Mandela+Blvd+Tehran"
            target="_blank"
            rel="noreferrer"
            className="button button-outline"
          >
            مسیریابی روی نقشه
            <Icon name="location" size={18} />
          </a>
        </div>
      </section>
    </>
  );
}
