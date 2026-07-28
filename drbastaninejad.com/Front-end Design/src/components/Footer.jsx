import React from "react";
import { Link } from "react-router-dom";
import Logo from "./Logo";
import { CLINIC, NAV, SERVICES } from "../lib/content";
import { IconPhone, IconMap, IconClock } from "../lib/icons";

export default function Footer() {
  return (
    <footer data-testid="footer" className="border-t border-border bg-card">
      <div className="mx-auto max-w-7xl px-5 py-16 lg:px-10">
        <div className="grid gap-12 lg:grid-cols-12">
          <div className="lg:col-span-4">
            <Logo size={44} />
            <p className="mt-5 max-w-xs text-sm leading-relaxed text-muted-foreground">
              {CLINIC.tagline}. تخصص، دقت و نگاهی هنرمندانه به تناسب طبیعی چهره.
            </p>
          </div>

          <div className="lg:col-span-2">
            <h4 className="mb-4 text-xs font-bold tracking-widest text-brass">پیوندها</h4>
            <ul className="space-y-2 text-sm">
              {NAV.map((n) => (
                <li key={n.to}>
                  <Link to={n.to} className="text-muted-foreground transition-colors hover:text-primary">{n.label}</Link>
                </li>
              ))}
            </ul>
          </div>

          <div className="lg:col-span-3">
            <h4 className="mb-4 text-xs font-bold tracking-widest text-brass">خدمات</h4>
            <ul className="space-y-2 text-sm">
              {SERVICES.slice(0, 5).map((s) => (
                <li key={s.id}><Link to="/services" className="text-muted-foreground transition-colors hover:text-primary">{s.title}</Link></li>
              ))}
            </ul>
          </div>

          <div className="lg:col-span-3">
            <h4 className="mb-4 text-xs font-bold tracking-widest text-brass">تماس</h4>
            <ul className="space-y-3 text-sm text-muted-foreground">
              <li className="flex items-start gap-2"><IconMap size={18} className="mt-0.5 shrink-0 text-primary" /><span className="leading-relaxed">{CLINIC.address}</span></li>
              <li className="flex items-center gap-2"><IconPhone size={18} className="text-primary" /><span dir="ltr">{CLINIC.phones[0]}</span></li>
              <li className="flex items-center gap-2"><IconClock size={18} className="text-primary" /><span>{CLINIC.hours}</span></li>
            </ul>
          </div>
        </div>

        <div className="mt-14 select-none overflow-hidden">
          <div className="font-display text-[13vw] leading-none tracking-tighter text-foreground/[0.06] lg:text-[9vw]">
            باستانی‌نژاد
          </div>
        </div>

        <div className="mt-6 flex flex-col items-center justify-between gap-3 border-t border-border pt-6 text-xs text-muted-foreground sm:flex-row">
          <span>© ۱۴۰۳ کلینیک دکتر شاهین باستانی‌نژاد. تمامی حقوق محفوظ است.</span>
          <span>طراحی‌شده با دقت و ظرافت پزشکی</span>
        </div>
      </div>
    </footer>
  );
}
