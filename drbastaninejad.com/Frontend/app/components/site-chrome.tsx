"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import { clinic, navItems, services } from "../site-data";
import { Icon, LogoMark } from "./icons";

function Brand({ compact = false }: { compact?: boolean }) {
  return (
    <span className="brand">
      <span className="brand-mark">
        <LogoMark size={compact ? 38 : 46} />
      </span>
      <span className="brand-copy">
        <strong>{clinic.shortName}</strong>
        {!compact && <small>جراحی زیبایی بینی و صورت</small>}
      </span>
    </span>
  );
}

export function Header() {
  const pathname = usePathname();
  const [menuOpen, setMenuOpen] = useState(false);
  const [dark, setDark] = useState(false);

  useEffect(() => {
    const saved = window.localStorage.getItem("drbst-theme");
    const shouldUseDark =
      saved === "dark" ||
      (!saved && window.matchMedia("(prefers-color-scheme: dark)").matches);
    const frame = window.requestAnimationFrame(() => {
      setDark(shouldUseDark);
      document.documentElement.dataset.theme = shouldUseDark ? "dark" : "light";
    });
    return () => window.cancelAnimationFrame(frame);
  }, []);

  useEffect(() => {
    document.body.style.overflow = menuOpen ? "hidden" : "";
    return () => {
      document.body.style.overflow = "";
    };
  }, [menuOpen]);

  const toggleTheme = () => {
    const next = !dark;
    setDark(next);
    document.documentElement.dataset.theme = next ? "dark" : "light";
    window.localStorage.setItem("drbst-theme", next ? "dark" : "light");
  };

  return (
    <>
      <div className="availability-bar">
        <div className="container availability-inner">
          <span>
            <span className="status-dot" /> پذیرش حضوری با تعیین وقت قبلی
          </span>
          <a href={clinic.phones[0].href}>
            <Icon name="phone" size={15} />
            {clinic.phones[0].display}
          </a>
        </div>
      </div>
      <header className="site-header" data-testid="site-header">
        <div className="container header-inner">
          <Link href="/" aria-label="صفحه اصلی" className="header-brand">
            <Brand />
          </Link>

          <nav className="desktop-nav" aria-label="منوی اصلی">
            {navItems.map((item) => {
              const active =
                item.href === "/"
                  ? pathname === "/"
                  : pathname.startsWith(item.href);
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={active ? "active" : ""}
                >
                  {item.label}
                </Link>
              );
            })}
          </nav>

          <div className="header-actions">
            <button
              type="button"
              className="icon-button"
              onClick={toggleTheme}
              aria-label={dark ? "فعال کردن حالت روشن" : "فعال کردن حالت تیره"}
              title={dark ? "حالت روشن" : "حالت تیره"}
            >
              <Icon name={dark ? "sun" : "moon"} size={19} />
            </button>
            <Link href="/appointment" className="button button-primary header-cta">
              <Icon name="calendar" size={18} />
              رزرو نوبت
            </Link>
            <button
              type="button"
              className="icon-button menu-button"
              onClick={() => setMenuOpen((value) => !value)}
              aria-label={menuOpen ? "بستن منو" : "باز کردن منو"}
              aria-expanded={menuOpen}
              aria-controls="mobile-navigation"
            >
              <Icon name={menuOpen ? "close" : "menu"} />
            </button>
          </div>
        </div>
      </header>

      <div
        className={`mobile-menu ${menuOpen ? "open" : ""}`}
        id="mobile-navigation"
        aria-hidden={!menuOpen}
      >
        <div className="mobile-menu-panel">
          <div className="mobile-menu-top">
            <Brand compact />
            <button
              type="button"
              className="icon-button"
              onClick={() => setMenuOpen(false)}
              aria-label="بستن منو"
            >
              <Icon name="close" />
            </button>
          </div>
          <nav aria-label="منوی موبایل">
            {navItems.map((item, index) => (
              <Link
                key={item.href}
                href={item.href}
                className={pathname === item.href ? "active" : ""}
                onClick={() => setMenuOpen(false)}
              >
                <span>۰{index + 1}</span>
                {item.label}
                <Icon name="arrow" size={18} />
              </Link>
            ))}
          </nav>
          <Link href="/appointment" className="button button-primary mobile-booking">
            رزرو نوبت مشاوره
            <Icon name="calendar" size={18} />
          </Link>
          <p>{clinic.hours}</p>
        </div>
        <button
          type="button"
          className="mobile-menu-backdrop"
          onClick={() => setMenuOpen(false)}
          aria-label="بستن منو"
        />
      </div>
    </>
  );
}

export function Footer() {
  return (
    <footer className="site-footer">
      <div className="container">
        <div className="footer-grid">
          <div className="footer-intro">
            <Brand />
            <p>
              زیبایی برای ما تغییر چهره نیست؛ یافتن تعادلی است که با هویت،
              عملکرد و ویژگی‌های طبیعی شما هماهنگ باشد.
            </p>
            <Link href="/appointment" className="text-link">
              درخواست مشاوره
              <Icon name="arrow" size={17} />
            </Link>
          </div>
          <div>
            <h2>دسترسی سریع</h2>
            <ul>
              {navItems.map((item) => (
                <li key={item.href}>
                  <Link href={item.href}>{item.label}</Link>
                </li>
              ))}
            </ul>
          </div>
          <div>
            <h2>خدمات منتخب</h2>
            <ul>
              {services.slice(0, 5).map((service) => (
                <li key={service.id}>
                  <Link href={`/services#${service.id}`}>{service.title}</Link>
                </li>
              ))}
            </ul>
          </div>
          <div className="footer-contact">
            <h2>اطلاعات مطب</h2>
            <p>
              <Icon name="location" size={18} />
              <span>{clinic.address}</span>
            </p>
            <p>
              <Icon name="clock" size={18} />
              <span>{clinic.hours}</span>
            </p>
            <a href={clinic.phones[0].href}>
              <Icon name="phone" size={18} />
              <span>{clinic.phones[0].display}</span>
            </a>
          </div>
        </div>

        <div className="footer-wordmark" aria-hidden="true">
          باستانی‌نژاد
        </div>
        <div className="footer-bottom">
          <span>© ۱۴۰۵ تمامی حقوق این وب‌سایت محفوظ است.</span>
          <span>مطالب این وب‌سایت جایگزین معاینه و توصیه پزشکی نیست.</span>
        </div>
      </div>
    </footer>
  );
}

export function MobileDock() {
  return (
    <aside className="mobile-dock" aria-label="دسترسی سریع">
      <a href={clinic.phones[0].href}>
        <Icon name="phone" size={19} />
        تماس
      </a>
      <Link href="/appointment" className="primary">
        <Icon name="calendar" size={19} />
        رزرو نوبت
      </Link>
    </aside>
  );
}
