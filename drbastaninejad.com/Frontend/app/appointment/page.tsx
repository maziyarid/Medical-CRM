import type { Metadata } from "next";
import { AppointmentForm } from "../components/appointment-form";
import { Icon } from "../components/icons";
import { PageHero } from "../components/ui";
import { clinic } from "../site-data";

export const metadata: Metadata = {
  title: "رزرو نوبت مشاوره",
  description:
    "فرم درخواست نوبت مشاوره برای خدمات جراحی زیبایی و ترمیمی بینی دکتر شاهین باستانی‌نژاد.",
};

export default function AppointmentPage() {
  return (
    <>
      <PageHero
        eyebrow="رزرو نوبت"
        title={
          <>
            شروع یک تصمیم
            <br />
            <em>آگاهانه</em>
          </>
        }
        text="فرم سه‌مرحله‌ای زیر را تکمیل کنید. پس از بررسی درخواست، برای هماهنگی نهایی زمان مراجعه با شما تماس گرفته می‌شود."
        aside={
          <div className="appointment-help">
            <Icon name="phone" size={26} />
            <div>
              <small>برای هماهنگی تلفنی</small>
              <a href={clinic.phones[0].href}>{clinic.phones[0].display}</a>
              <span>{clinic.hours}</span>
            </div>
          </div>
        }
      />
      <section className="section container appointment-page">
        <AppointmentForm />
        <aside className="appointment-aside">
          <div>
            <span className="eyebrow">پیش از مراجعه</span>
            <h2>برای جلسه مشاوره چه همراه داشته باشیم؟</h2>
          </div>
          <ul>
            <li>
              <span>
                <Icon name="check" size={17} />
              </span>
              فهرست داروها و سابقه بیماری‌های مهم
            </li>
            <li>
              <span>
                <Icon name="check" size={17} />
              </span>
              مدارک و تصاویر جراحی قبلی، در صورت وجود
            </li>
            <li>
              <span>
                <Icon name="check" size={17} />
              </span>
              پرسش‌ها و نگرانی‌هایی که می‌خواهید مطرح کنید
            </li>
          </ul>
          <div className="appointment-aside-note">
            <Icon name="clock" size={20} />
            <p>
              لطفاً برای حفظ نظم پذیرش، ۱۰ دقیقه پیش از زمان تعیین‌شده در مطب
              حضور داشته باشید.
            </p>
          </div>
        </aside>
      </section>
    </>
  );
}
