import Link from "next/link";
import type { ReactNode } from "react";
import { services } from "../site-data";
import { Icon } from "./icons";

export function SectionHeading({
  eyebrow,
  title,
  text,
  align = "start",
}: {
  eyebrow: string;
  title: ReactNode;
  text?: string;
  align?: "start" | "center";
}) {
  return (
    <header className={`section-heading ${align === "center" ? "center" : ""}`}>
      <span className="eyebrow">{eyebrow}</span>
      <h2>{title}</h2>
      {text && <p>{text}</p>}
    </header>
  );
}

export function PageHero({
  eyebrow,
  title,
  text,
  aside,
}: {
  eyebrow: string;
  title: ReactNode;
  text: string;
  aside?: ReactNode;
}) {
  return (
    <section className="page-hero">
      <div className="page-hero-glow page-hero-glow-a" />
      <div className="page-hero-glow page-hero-glow-b" />
      <div className={`container page-hero-grid ${aside ? "" : "single"}`}>
        <div>
          <span className="eyebrow">{eyebrow}</span>
          <h1>{title}</h1>
          <p>{text}</p>
        </div>
        {aside && <div className="page-hero-aside">{aside}</div>}
      </div>
    </section>
  );
}

export function ServicesGrid({ limit }: { limit?: number }) {
  const list = typeof limit === "number" ? services.slice(0, limit) : services;
  return (
    <div className="services-grid">
      {list.map((service, index) => (
        <article
          className={`service-card service-card-${(index % 3) + 1}`}
          key={service.id}
          id={service.id}
        >
          <div className="service-icon">
            <Icon
              name={
                service.icon as
                  | "nose"
                  | "refresh"
                  | "breathe"
                  | "eye"
                  | "face"
                  | "spark"
              }
              size={30}
            />
          </div>
          <span>{service.eyebrow}</span>
          <h3>{service.title}</h3>
          <p>{service.description}</p>
          <Link href="/appointment" className="service-link">
            رزرو مشاوره
            <Icon name="arrow" size={16} />
          </Link>
        </article>
      ))}
    </div>
  );
}

export function CallToAction({
  title = "اولین قدم، یک گفت‌وگوی روشن است.",
  text = "در جلسه مشاوره، ساختار بینی، خواسته‌های شما و مسیرهای درمانی مناسب با دقت بررسی می‌شوند.",
}: {
  title?: string;
  text?: string;
}) {
  return (
    <section className="container cta-wrap">
      <div className="cta-panel">
        <div className="cta-orb cta-orb-a" />
        <div className="cta-orb cta-orb-b" />
        <span className="eyebrow">درخواست مشاوره</span>
        <h2>{title}</h2>
        <p>{text}</p>
        <div className="cta-actions">
          <Link href="/appointment" className="button button-primary button-large">
            رزرو نوبت
            <Icon name="calendar" size={19} />
          </Link>
          <Link href="/contact" className="button button-ghost-light button-large">
            راه‌های ارتباطی
          </Link>
        </div>
      </div>
    </section>
  );
}
