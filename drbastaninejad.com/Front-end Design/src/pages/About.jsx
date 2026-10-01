import React from "react";
import { Link } from "react-router-dom";
import PageHeader from "../components/PageHeader";
import { Reveal, Parallax } from "../lib/motion";
import { CLINIC, STATS, IMAGES } from "../lib/content";
import { IconCheck, IconArrow } from "../lib/icons";

const CREDENTIALS = [
  "بورد تخصصی گوش، گلو و بینی",
  "فلوشیپ جراحی پلاستیک و زیبایی بینی",
  "عضو انجمن جراحان گوش و حلق و بینی ایران",
  "بیش از ۱۸ سال تجربه‌ی بالینی و جراحی",
];

export default function About() {
  return (
    <>
      <PageHeader
        overline="درباره‌ی جراح"
        title={["دکتر شاهین", "باستانی‌نژاد"]}
        subtitle="جراح و متخصص گوش، گلو و بینی، با تمرکز ویژه بر جراحی زیبایی و ترمیمی بینی."
      />

      <section className="mx-auto max-w-7xl px-5 pb-24 lg:px-10">
        <div className="grid items-start gap-12 lg:grid-cols-2">
          <Reveal>
            <Parallax distance={20}>
              <div className="overflow-hidden rounded-[2rem] rounded-tl-[6rem]">
                <img src={IMAGES.doctor} alt={CLINIC.name} className="h-[620px] w-full object-cover" />
              </div>
            </Parallax>
          </Reveal>

          <div>
            <Reveal>
              <p className="text-lg leading-loose text-foreground/90">
                «مهم‌ترین اصل در جراحی زیبایی بینی این است که از یک سادگی نامطبوع به پیچیدگی‌ای برسیم که ورای آن، سادگیِ مطبوعی حاصل شود. اگر هدف شما یک بینی بسیار کوچک یا نوکِ به‌شدت جمع‌شده است، هدف را اشتباه انتخاب کرده‌اید؛ بینی عضوی است جهت نفس کشیدن.»
              </p>
            </Reveal>

            <Reveal delay={0.1}>
              <div className="mt-10 space-y-3">
                {CREDENTIALS.map((c) => (
                  <div key={c} className="flex items-center gap-3 rounded-2xl border border-border bg-card p-4">
                    <span className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-sage text-primary"><IconCheck size={18} /></span>
                    <span className="text-sm">{c}</span>
                  </div>
                ))}
              </div>
            </Reveal>

            <Reveal delay={0.15}>
              <div className="mt-10 grid grid-cols-2 gap-6">
                {STATS.map((s) => (
                  <div key={s.label} className="rounded-2xl border border-border bg-card p-6 text-center">
                    <div className="font-display text-3xl text-primary">{s.value}</div>
                    <div className="mt-1 text-xs text-muted-foreground">{s.label}</div>
                  </div>
                ))}
              </div>
            </Reveal>

            <Link to="/appointment" className="mt-10 inline-flex items-center gap-2 rounded-full bg-primary px-8 py-4 text-sm font-medium text-primary-foreground transition-transform hover:scale-105">
              رزرو نوبت مشاوره <IconArrow size={16} />
            </Link>
          </div>
        </div>
      </section>
    </>
  );
}
