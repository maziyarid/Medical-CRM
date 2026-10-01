import React from "react";
import { Link } from "react-router-dom";
import PageHeader from "../components/PageHeader";
import { Reveal } from "../lib/motion";
import { SERVICES } from "../lib/content";
import { SERVICE_ICONS, IconArrow } from "../lib/icons";

const STEPS = [
  { n: "۰۱", t: "مشاوره و بررسی", d: "بررسی ساختار بینی، شنیدن خواسته‌ها و شبیه‌سازی نتیجه‌ی مورد انتظار." },
  { n: "۰۲", t: "طراحی اختصاصی", d: "برنامه‌ی جراحی متناسب با آناتومی و تناسب اجزای صورت شما." },
  { n: "۰۳", t: "جراحی دقیق", d: "اجرای ظریف با کمترین تهاجم و حفظ عملکرد طبیعی تنفس." },
  { n: "۰۴", t: "مراقبت پس از عمل", d: "پیگیری منظم دوران نقاهت تا رسیدن به نتیجه‌ی نهایی و پایدار." },
];

export default function Services() {
  return (
    <>
      <PageHeader
        overline="خدمات کلینیک"
        title={["خدمات تخصصی", "جراحی و زیبایی"]}
        subtitle="از راینوپلاستی اولیه و ثانویه تا جوان‌سازی صورت؛ هر خدمت با دقتی هنرمندانه و رویکردی محافظه‌کارانه."
      />

      <section className="mx-auto max-w-7xl px-5 pb-16 lg:px-10">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {SERVICES.map((s, i) => {
            const Icon = SERVICE_ICONS[s.icon];
            return (
              <Reveal key={s.id} delay={(i % 3) * 0.06}>
                <div data-testid={`service-detail-${s.id}`} className="group flex h-full flex-col rounded-3xl border border-border bg-card p-8 transition-colors hover:border-primary">
                  <div className="mb-6 grid h-16 w-16 place-items-center rounded-2xl bg-sage text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                    <Icon size={34} />
                  </div>
                  <h3 className="text-xl font-bold">{s.title}</h3>
                  <p className="mt-3 flex-1 text-sm leading-relaxed text-muted-foreground">{s.desc}</p>
                  <Link to="/appointment" className="mt-6 inline-flex items-center gap-2 text-sm text-primary">
                    رزرو مشاوره <IconArrow size={15} />
                  </Link>
                </div>
              </Reveal>
            );
          })}
        </div>
      </section>

      <section className="border-y border-border bg-card">
        <div className="mx-auto max-w-7xl px-5 py-24 lg:px-10">
          <div className="mb-14 text-xs font-bold tracking-[0.2em] text-brass">مسیر درمان</div>
          <div className="grid gap-8 lg:grid-cols-4">
            {STEPS.map((s, i) => (
              <Reveal key={s.n} delay={i * 0.08}>
                <div className="border-t-2 border-primary/20 pt-6">
                  <div className="font-display text-5xl text-primary/25">{s.n}</div>
                  <h3 className="mt-4 text-lg font-bold">{s.t}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{s.d}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
