import type { Metadata } from "next";
import Link from "next/link";
import { BeforeAfter } from "./components/before-after";
import { Icon } from "./components/icons";
import {
  CallToAction,
  SectionHeading,
  ServicesGrid,
} from "./components/ui";
import {
  clinic,
  faqs,
  images,
  journey,
  posts,
} from "./site-data";

export const metadata: Metadata = {
  title: "جراحی طبیعی بینی و صورت",
  description:
    "معرفی خدمات جراحی زیبایی و ترمیمی بینی، فلسفه درمان، مراحل مشاوره و راه‌های رزرو نوبت دکتر شاهین باستانی‌نژاد.",
};

function Hero() {
  return (
    <section className="home-hero" data-testid="home-hero">
      <div className="hero-art hero-art-a" />
      <div className="hero-art hero-art-b" />
      <div className="container hero-grid">
        <div className="hero-copy">
          <div className="hero-kicker">
            <span />
            {clinic.role}
          </div>
          <h1>
            زیبایی، وقتی
            <em>طبیعی می‌ماند.</em>
          </h1>
          <p>
            راینوپلاستی اولیه و ترمیمی با نگاهی دقیق و محافظه‌کارانه؛ برای
            رسیدن به تناسبی که با چهره شما هماهنگ است و عملکرد طبیعی تنفس را
            جدی می‌گیرد.
          </p>
          <div className="hero-actions">
            <Link href="/appointment" className="button button-primary button-large">
              رزرو نوبت مشاوره
              <Icon name="arrow" size={18} />
            </Link>
            <Link href="/services" className="button button-outline button-large">
              مشاهده خدمات
            </Link>
          </div>
          <div className="hero-trust" aria-label="ویژگی‌های رویکرد درمان">
            <span>
              <Icon name="check" size={16} />
              طراحی متناسب با چهره
            </span>
            <span>
              <Icon name="check" size={16} />
              توجه به عملکرد تنفسی
            </span>
            <span>
              <Icon name="check" size={16} />
              پیگیری پس از درمان
            </span>
          </div>
        </div>

        <div className="hero-visual">
          <div className="hero-image-frame">
            <img src={images.hero} alt="نمای نیم‌رخ طبیعی چهره" />
            <div className="hero-image-shade" />
          </div>
          <div className="hero-note">
            <span>فلسفه درمان</span>
            <strong>تناسب، نه تقلید</strong>
            <p>هر چهره، امضای خودش را دارد.</p>
          </div>
          <div className="hero-seal" aria-hidden="true">
            <span>طبیعی</span>
            <i />
            <span>دقیق</span>
          </div>
        </div>
      </div>
    </section>
  );
}

function ClinicStrip() {
  return (
    <section className="clinic-strip">
      <div className="container clinic-strip-grid">
        <div>
          <Icon name="location" />
          <span>
            <small>نشانی مطب</small>
            تهران، نلسون ماندلا، ساختمان نور
          </span>
        </div>
        <div>
          <Icon name="clock" />
          <span>
            <small>ساعت پذیرش</small>
            {clinic.hours}
          </span>
        </div>
        <a href={clinic.phones[0].href}>
          <Icon name="phone" />
          <span>
            <small>تماس با مطب</small>
            {clinic.phones[0].display}
          </span>
        </a>
      </div>
    </section>
  );
}

export default function Home() {
  return (
    <>
      <Hero />
      <ClinicStrip />

      <section className="section container services-section">
        <div className="section-topline">
          <SectionHeading
            eyebrow="خدمات کلینیک"
            title={
              <>
                مراقبتی دقیق،
                <br />
                از مشاوره تا نتیجه
              </>
            }
            text="هر مسیر درمان با ارزیابی فردی آغاز می‌شود؛ چون فرم مناسب برای یک چهره، الزاماً برای چهره دیگر مناسب نیست."
          />
          <Link href="/services" className="text-link section-link">
            همه خدمات
            <Icon name="arrow" size={17} />
          </Link>
        </div>
        <ServicesGrid />
      </section>

      <section className="manifesto">
        <div className="container manifesto-grid">
          <div className="manifesto-sticky">
            <span className="eyebrow">فلسفه درمان</span>
            <h2>
              جراحی خوب
              <br />
              <em>دیده نمی‌شود،</em>
              <br />
              احساس می‌شود.
            </h2>
          </div>
          <div className="manifesto-list">
            <article>
              <span>۰۱</span>
              <div>
                <h3>تنفس، اولویت همیشگی</h3>
                <p>
                  بینی پیش از هر چیز یک عضو تنفسی است. زیبایی زمانی ارزشمند است
                  که عملکرد طبیعی در طراحی و درمان حفظ شود.
                </p>
              </div>
            </article>
            <article>
              <span>۰۲</span>
              <div>
                <h3>تناسب به‌جای الگوی ثابت</h3>
                <p>
                  هدف، تکرار یک مدل برای همه نیست. اندازه، زاویه و فرم باید با
                  اجزای صورت، جنس پوست و ویژگی‌های فردی هماهنگ باشند.
                </p>
              </div>
            </article>
            <article>
              <span>۰۳</span>
              <div>
                <h3>انتظار واقع‌بینانه</h3>
                <p>
                  گفت‌وگوی شفاف درباره ظرفیت‌ها و محدودیت‌ها، بخش مهمی از
                  درمان است و به تصمیمی آگاهانه‌تر کمک می‌کند.
                </p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section className="section container comparison-section">
        <div className="comparison-copy">
          <SectionHeading
            eyebrow="بررسی نمونه‌ها"
            title="نتیجه باید با خودِ چهره گفت‌وگو کند."
            text="در جلسه مشاوره می‌توانید نمونه‌های واقعی و مرتبط با ساختار بینی خود را با توضیح کامل پزشک بررسی کنید."
          />
          <ul className="check-list">
            <li>
              <Icon name="check" size={18} />
              بررسی نتیجه در چند زاویه
            </li>
            <li>
              <Icon name="check" size={18} />
              توجه به نوع پوست و ساختار اولیه
            </li>
            <li>
              <Icon name="check" size={18} />
              توضیح محدودیت‌ها و تفاوت‌های فردی
            </li>
          </ul>
          <small className="image-disclaimer">
            تصاویر این بخش صرفاً نمایشی‌اند و نتیجه درمان برای افراد متفاوت است.
          </small>
        </div>
        <BeforeAfter />
      </section>

      <section className="doctor-section">
        <div className="container doctor-grid">
          <div className="doctor-image">
            <img src={images.doctor} alt={clinic.name} />
            <div className="doctor-caption">
              <span>جراح و متخصص</span>
              <strong>{clinic.name}</strong>
            </div>
          </div>
          <div className="doctor-copy">
            <SectionHeading
              eyebrow="درباره پزشک"
              title={
                <>
                  تخصص و تجربه،
                  <br />
                  با نگاهی <em>انسانی</em>
                </>
              }
              text="یک تصمیم زیبایی، تصمیمی شخصی و مهم است. مشاوره باید فضایی امن برای شنیدن خواسته‌ها، توضیح واقعیت‌ها و رسیدن به انتخاب آگاهانه باشد."
            />
            <blockquote>
              «بهترین نتیجه، فرمی است که انگار همیشه بخشی از همان چهره بوده
              است.»
            </blockquote>
            <div className="doctor-points">
              <span>تخصص گوش، گلو و بینی</span>
              <span>تمرکز بر جراحی زیبایی و ترمیمی بینی</span>
              <span>پیگیری منظم دوره بهبود</span>
            </div>
            <Link href="/about" className="button button-outline button-large">
              آشنایی بیشتر
              <Icon name="arrow" size={18} />
            </Link>
          </div>
        </div>
      </section>

      <section className="section container journey-section">
        <SectionHeading
          eyebrow="مسیر مراجعه"
          title="چهار قدم تا یک تصمیم آگاهانه"
          text="از نخستین تماس تا پیگیری پس از درمان، هر مرحله با توضیح شفاف و برنامه مشخص پیش می‌رود."
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

      <section className="faq-section">
        <div className="container faq-grid">
          <div>
            <span className="eyebrow">پرسش‌های پرتکرار</span>
            <h2>پیش از تصمیم، پاسخ روشن بگیرید.</h2>
            <p>
              پاسخ‌ها عمومی‌اند و جایگزین ارزیابی تخصصی نیستند. برای بررسی شرایط
              شخصی خود، نوبت مشاوره رزرو کنید.
            </p>
            <Link href="/appointment" className="text-link">
              رزرو جلسه مشاوره
              <Icon name="arrow" size={17} />
            </Link>
          </div>
          <div className="accordion">
            {faqs.map((faq, index) => (
              <details key={faq.question} open={index === 0}>
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

      <section className="section container blog-preview">
        <div className="section-topline">
          <SectionHeading
            eyebrow="مجله سلامت و زیبایی"
            title="برای تصمیم بهتر، بیشتر بدانید."
          />
          <Link href="/blog" className="text-link section-link">
            مشاهده همه مقاله‌ها
            <Icon name="arrow" size={17} />
          </Link>
        </div>
        <div className="post-grid">
          {posts.slice(0, 3).map((post) => (
            <article className="post-card" key={post.slug}>
              <Link href="/blog" className="post-image">
                <img src={post.image} alt="" />
              </Link>
              <div className="post-content">
                <div className="post-meta">
                  <span>{post.category}</span>
                  <span>{post.readTime} مطالعه</span>
                </div>
                <h3>
                  <Link href="/blog">{post.title}</Link>
                </h3>
                <p>{post.excerpt}</p>
                <Link href="/blog" className="text-link">
                  ادامه مطلب
                  <Icon name="arrow" size={16} />
                </Link>
              </div>
            </article>
          ))}
        </div>
      </section>

      <CallToAction />
    </>
  );
}
