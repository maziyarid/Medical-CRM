import type { Metadata } from "next";
import Link from "next/link";
import { Icon } from "../components/icons";
import {
  CallToAction,
  PageHero,
  SectionHeading,
} from "../components/ui";
import { clinic, images } from "../site-data";

export const metadata: Metadata = {
  title: "درباره دکتر",
  description:
    "آشنایی با رویکرد حرفه‌ای دکتر شاهین باستانی‌نژاد در جراحی زیبایی و ترمیمی بینی و مراقبت از بیماران.",
};

const credentials = [
  "جراح و متخصص گوش، گلو و بینی",
  "فعال در زمینه جراحی زیبایی و ترمیمی بینی",
  "ارزیابی هم‌زمان فرم و عملکرد تنفسی",
  "پیگیری منظم مراجعان در دوره بهبود",
];

export default function AboutPage() {
  return (
    <>
      <PageHero
        eyebrow="درباره پزشک"
        title={
          <>
            پزشکی دقیق،
            <br />
            نتیجه‌ای <em>طبیعی</em>
          </>
        }
        text="رویکرد درمان بر شناخت دقیق ساختار چهره، گفت‌وگوی شفاف و حفظ هویت طبیعی هر فرد استوار است."
        aside={
          <blockquote className="page-quote">
            «زیبایی موفق، چهره را به نسخه‌ای دیگر تبدیل نمی‌کند؛ تعادل پنهان آن
            را آشکار می‌کند.»
          </blockquote>
        }
      />

      <section className="section container about-profile">
        <div className="about-portrait">
          <img src={images.doctor} alt={clinic.name} />
          <span className="portrait-index">۰۱</span>
        </div>
        <div className="about-profile-copy">
          <SectionHeading
            eyebrow="معرفی"
            title={clinic.name}
            text={clinic.role}
          />
          <p className="lead-copy">
            تصمیم برای جراحی زیبایی، فقط یک انتخاب ظاهری نیست. این تصمیم با
            سلامت، اعتمادبه‌نفس و تصویر فرد از خودش پیوند دارد؛ به همین دلیل
            گفت‌وگوی دقیق، شنیدن خواسته‌ها و توضیح محدودیت‌ها بخشی جدایی‌ناپذیر
            از درمان است.
          </p>
          <p>
            در طراحی مسیر درمان، تناسب بینی با پیشانی، لب‌ها، چانه و نمای کلی
            صورت در کنار کیفیت پوست و ساختار تنفسی بررسی می‌شود. هدف، نتیجه‌ای
            هماهنگ و قابل‌باور است؛ نه اجرای یک الگوی ثابت برای همه.
          </p>
          <div className="credential-list">
            {credentials.map((credential) => (
              <div key={credential}>
                <span>
                  <Icon name="check" size={17} />
                </span>
                {credential}
              </div>
            ))}
          </div>
          <Link href="/appointment" className="button button-primary button-large">
            رزرو جلسه مشاوره
            <Icon name="calendar" size={18} />
          </Link>
        </div>
      </section>

      <section className="values-section">
        <div className="container">
          <SectionHeading
            eyebrow="اصول حرفه‌ای"
            title="سه اصل در هر مسیر درمان"
            align="center"
          />
          <div className="values-grid">
            <article>
              <span>۰۱</span>
              <h2>شنیدن پیش از پیشنهاد</h2>
              <p>
                خواسته‌ها، نگرانی‌ها و اولویت‌های هر مراجعه‌کننده پیش از هر
                توصیه‌ای شنیده و بررسی می‌شوند.
              </p>
            </article>
            <article>
              <span>۰۲</span>
              <h2>شفافیت پیش از تصمیم</h2>
              <p>
                ظرفیت واقعی تغییر، محدودیت‌ها، دوره بهبود و ریسک‌های احتمالی به
                زبان روشن توضیح داده می‌شوند.
              </p>
            </article>
            <article>
              <span>۰۳</span>
              <h2>پیگیری پس از درمان</h2>
              <p>
                جراحی پایان مسیر نیست؛ روند بهبود با ویزیت‌های منظم و توصیه‌های
                متناسب با هر مرحله دنبال می‌شود.
              </p>
            </article>
          </div>
        </div>
      </section>

      <section className="section container clinic-story">
        <div className="clinic-story-copy">
          <SectionHeading
            eyebrow="فضای مطب"
            title="آرامش، حریم شخصی و گفت‌وگوی بی‌شتاب"
            text="فضای مراجعه برای تجربه‌ای آرام، منظم و محترمانه طراحی شده است؛ از پذیرش تا معاینه و توضیح برنامه درمان."
          />
          <div className="clinic-story-facts">
            <div>
              <Icon name="location" />
              <span>
                <small>موقعیت</small>
                تهران، نلسون ماندلا
              </span>
            </div>
            <div>
              <Icon name="clock" />
              <span>
                <small>پذیرش</small>
                با تعیین وقت قبلی
              </span>
            </div>
          </div>
        </div>
        <div className="clinic-story-image">
          <img src={images.clinic} alt="فضای آرام و روشن کلینیک" />
        </div>
      </section>

      <CallToAction
        title="هر چهره، پاسخ منحصربه‌فرد خودش را دارد."
        text="جلسه مشاوره فرصتی است برای بررسی دقیق شرایط شما و رسیدن به تصمیمی واقع‌بینانه و آگاهانه."
      />
    </>
  );
}
