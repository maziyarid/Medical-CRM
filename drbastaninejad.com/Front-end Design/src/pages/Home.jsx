import React from "react";
import { Link } from "react-router-dom";
import { motion, useScroll, useTransform } from "framer-motion";
import { useRef } from "react";
import Marquee from "react-fast-marquee";
import { MaskText, Reveal, Parallax, EASE } from "../lib/motion";
import { SERVICES, STATS, MANIFESTO, TESTIMONIALS, POSTS, CLINIC, IMAGES } from "../lib/content";
import { SERVICE_ICONS, IconArrow, IconArrowUpLeft } from "../lib/icons";

function Hero() {
  const ref = useRef(null);
  const { scrollYProgress } = useScroll({ target: ref, offset: ["start start", "end start"] });
  const imgY = useTransform(scrollYProgress, [0, 1], [0, 140]);
  const scale = useTransform(scrollYProgress, [0, 1], [1, 1.12]);

  return (
    <section ref={ref} data-testid="hero" className="relative overflow-hidden pt-36 pb-20 lg:pt-44 lg:pb-28">
      <div className="pointer-events-none absolute -start-40 top-10 h-[520px] w-[520px] rounded-full bg-primary/10 blur-3xl" />
      <div className="pointer-events-none absolute -end-32 bottom-0 h-[420px] w-[420px] rounded-full bg-brass/10 blur-3xl" />

      <div className="mx-auto grid max-w-7xl items-center gap-12 px-5 lg:grid-cols-12 lg:px-10">
        <div className="lg:col-span-7">
          <motion.div
            initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }}
            className="mb-6 inline-flex items-center gap-2 rounded-full border border-border bg-card/60 px-4 py-1.5 text-xs tracking-wide text-muted-foreground"
          >
            <span className="h-2 w-2 rounded-full bg-primary" /> جراح و متخصص گوش، گلو و بینی
          </motion.div>

          <h1 className="font-display text-[13vw] leading-[0.98] tracking-tight text-foreground sm:text-6xl lg:text-[5.4rem]">
            <MaskText lines={["هنرِ بازآفرینیِ", "تناسبِ طبیعیِ چهره"]} delay={0.15} />
          </h1>

          <motion.p
            initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.7, duration: 0.8, ease: EASE }}
            className="mt-7 max-w-lg text-base leading-relaxed text-muted-foreground lg:text-lg"
          >
            راینوپلاستی اولیه و ثانویه با نگاهی محافظه‌کارانه؛ جایی که زیبایی، عملکرد طبیعی تنفس و امضای منحصربه‌فرد صورت شما در کنار هم حفظ می‌شوند.
          </motion.p>

          <motion.div
            initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.85, duration: 0.8, ease: EASE }}
            className="mt-9 flex flex-wrap items-center gap-4"
          >
            <Link to="/appointment" data-testid="hero-appointment-btn" className="group inline-flex items-center gap-2 rounded-full bg-primary px-7 py-3.5 text-sm font-medium text-primary-foreground transition-all hover:shadow-xl hover:shadow-primary/25">
              رزرو نوبت مشاوره
              <IconArrow size={18} className="transition-transform group-hover:-translate-x-1" />
            </Link>
            <Link to="/services" data-testid="hero-services-btn" className="inline-flex items-center gap-2 rounded-full border border-border px-7 py-3.5 text-sm font-medium transition-colors hover:border-primary hover:text-primary">
              مشاهده خدمات
            </Link>
          </motion.div>
        </div>

        <div className="lg:col-span-5">
          <Parallax distance={30}>
            <motion.div style={{ scale }} className="relative overflow-hidden rounded-[2rem] rounded-tl-[6rem]">
              <motion.img
                style={{ y: imgY }}
                src={IMAGES.heroProfile}
                alt="پروفایل چهره"
                className="h-[540px] w-full scale-110 object-cover grayscale"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent" />
              <div className="absolute bottom-5 start-5 rounded-2xl border border-white/20 bg-black/30 px-5 py-3 backdrop-blur-md">
                <div className="font-display text-2xl text-white">+۴۵۰۰</div>
                <div className="text-xs text-white/80">جراحی موفق بینی</div>
              </div>
            </motion.div>
          </Parallax>
        </div>
      </div>
    </section>
  );
}

function MarqueeStrip() {
  const words = ["راینوپلاستی", "•", "زیبایی صورت", "•", "بلفاروپلاستی", "•", "پروتز چانه", "•", "تزریق ژل", "•", "اوتوپلاستی", "•"];
  return (
    <section data-testid="marquee" className="border-y border-border bg-primary py-5 text-primary-foreground">
      <Marquee direction="right" speed={45} gradient={false} autoFill>
        <div className="flex items-center gap-8 px-4 font-display text-2xl tracking-tight lg:text-3xl">
          {words.map((w, i) => <span key={i} className={w === "•" ? "text-primary-foreground/50" : ""}>{w}</span>)}
        </div>
      </Marquee>
    </section>
  );
}

function Stats() {
  return (
    <section className="mx-auto max-w-7xl px-5 py-16 lg:px-10">
      <div className="grid grid-cols-2 gap-8 lg:grid-cols-4">
        {STATS.map((s, i) => (
          <Reveal key={s.label} delay={i * 0.08} className="text-center">
            <div className="font-display text-4xl text-primary lg:text-5xl">{s.value}</div>
            <div className="mt-2 text-sm text-muted-foreground">{s.label}</div>
          </Reveal>
        ))}
      </div>
    </section>
  );
}

function Services() {
  return (
    <section id="services" data-testid="services-section" className="mx-auto max-w-7xl px-5 py-20 lg:px-10">
      <div className="mb-12 flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
        <div>
          <div className="mb-3 text-xs font-bold tracking-[0.2em] text-brass">خدمات کلینیک</div>
          <h2 className="font-display text-4xl tracking-tight lg:text-5xl">
            <MaskText lines={["مراقبتی جامع،", "از مشاوره تا نتیجه"]} />
          </h2>
        </div>
        <Link to="/services" className="inline-flex items-center gap-2 text-sm text-primary hover:gap-3 transition-all">
          همه خدمات <IconArrow size={16} />
        </Link>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
        {SERVICES.map((s, i) => {
          const Icon = SERVICE_ICONS[s.icon];
          return (
            <Reveal key={s.id} delay={(i % 3) * 0.06} className={s.span}>
              <div data-testid={`service-card-${s.id}`} className="group relative flex h-full flex-col justify-between overflow-hidden rounded-3xl border border-border bg-card p-7 transition-colors hover:border-primary">
                <div className="absolute -end-6 -top-6 h-24 w-24 rounded-full bg-primary/5 transition-transform duration-500 group-hover:scale-150" />
                <div className="relative">
                  <div className="mb-5 grid h-14 w-14 place-items-center rounded-2xl bg-sage text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                    <Icon size={30} />
                  </div>
                  <h3 className="text-xl font-bold">{s.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{s.desc}</p>
                </div>
                <div className="relative mt-6 inline-flex items-center gap-2 text-sm text-primary opacity-0 transition-opacity group-hover:opacity-100">
                  بیشتر بدانید <IconArrow size={15} />
                </div>
              </div>
            </Reveal>
          );
        })}
      </div>
    </section>
  );
}

function Manifesto() {
  return (
    <section data-testid="manifesto" className="border-y border-border bg-card">
      <div className="mx-auto max-w-7xl px-5 py-24 lg:px-10">
        <div className="mb-16 text-xs font-bold tracking-[0.2em] text-brass">فلسفه‌ی ما</div>
        <div className="space-y-16">
          {MANIFESTO.map((m, i) => (
            <Reveal key={m.n} delay={0.05}>
              <div className="grid gap-6 border-b border-border pb-16 last:border-0 last:pb-0 lg:grid-cols-12">
                <div className="lg:col-span-3">
                  <span className="font-display text-7xl text-primary/25 lg:text-8xl">{m.n}</span>
                </div>
                <div className="lg:col-span-9">
                  <h3 className="font-display text-3xl tracking-tight lg:text-4xl">{m.title}</h3>
                  <p className="mt-4 max-w-2xl text-lg leading-relaxed text-muted-foreground">{m.text}</p>
                </div>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

function DoctorPreview() {
  return (
    <section data-testid="doctor-preview" className="mx-auto max-w-7xl px-5 py-24 lg:px-10">
      <div className="grid items-center gap-12 lg:grid-cols-2">
        <Reveal>
          <div className="relative">
            <Parallax distance={24}>
              <div className="overflow-hidden rounded-[2rem] rounded-br-[6rem]">
                <img src={IMAGES.doctor} alt={CLINIC.name} className="h-[560px] w-full object-cover" />
              </div>
            </Parallax>
            <div className="absolute -bottom-6 -start-4 rounded-2xl border border-border bg-background px-6 py-4 shadow-xl">
              <div className="font-display text-lg">{CLINIC.name}</div>
              <div className="text-xs text-muted-foreground">جراح پلاستیک بینی</div>
            </div>
          </div>
        </Reveal>
        <div>
          <div className="mb-3 text-xs font-bold tracking-[0.2em] text-brass">درباره‌ی جراح</div>
          <h2 className="font-display text-4xl tracking-tight lg:text-5xl">
            <MaskText lines={["تخصص، تجربه", "و نگاهی هنرمندانه"]} />
          </h2>
          <p className="mt-6 text-lg leading-relaxed text-muted-foreground">
            دکتر شاهین باستانی‌نژاد، جراح و متخصص گوش، گلو و بینی، با تمرکز ویژه بر جراحی زیبایی بینی، بر این باور است که بهترین نتیجه، بینی‌ای است که انگار همیشه بخشی از همان صورت بوده است.
          </p>
          <Link to="/about" className="mt-8 inline-flex items-center gap-2 rounded-full border border-border px-7 py-3.5 text-sm font-medium transition-colors hover:border-primary hover:text-primary">
            بیوگرافی کامل <IconArrow size={16} />
          </Link>
        </div>
      </div>
    </section>
  );
}

function Testimonials() {
  return (
    <section data-testid="testimonials" className="border-y border-border bg-card">
      <div className="mx-auto max-w-7xl px-5 py-24 lg:px-10">
        <div className="mb-12 text-center">
          <div className="mb-3 text-xs font-bold tracking-[0.2em] text-brass">تجربه‌ی مراجعین</div>
          <h2 className="font-display text-4xl tracking-tight lg:text-5xl">صدای اعتماد</h2>
        </div>
        <div className="grid gap-6 lg:grid-cols-3">
          {TESTIMONIALS.map((t, i) => (
            <Reveal key={t.name} delay={i * 0.1}>
              <figure className="flex h-full flex-col rounded-3xl border border-border bg-background p-8">
                <div className="mb-4 font-display text-5xl leading-none text-primary/30">”</div>
                <blockquote className="flex-1 text-base leading-relaxed text-foreground/90">{t.text}</blockquote>
                <figcaption className="mt-6 border-t border-border pt-4 text-sm">
                  <span className="font-bold">{t.name}</span>
                  <span className="text-muted-foreground"> — {t.city}</span>
                </figcaption>
              </figure>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

function BlogPreview() {
  return (
    <section data-testid="blog-preview" className="mx-auto max-w-7xl px-5 py-24 lg:px-10">
      <div className="mb-12 flex items-end justify-between">
        <div>
          <div className="mb-3 text-xs font-bold tracking-[0.2em] text-brass">مجله</div>
          <h2 className="font-display text-4xl tracking-tight lg:text-5xl">دانستنی‌ها</h2>
        </div>
        <Link to="/blog" className="inline-flex items-center gap-2 text-sm text-primary hover:gap-3 transition-all">همه مقالات <IconArrow size={16} /></Link>
      </div>
      <div className="grid gap-6 lg:grid-cols-3">
        {POSTS.slice(0, 3).map((p, i) => (
          <Reveal key={p.id} delay={i * 0.08}>
            <Link to="/blog" className="group block overflow-hidden rounded-3xl border border-border bg-card">
              <div className="overflow-hidden">
                <img src={p.img} alt={p.title} className="h-52 w-full object-cover transition-transform duration-700 group-hover:scale-105" />
              </div>
              <div className="p-6">
                <div className="text-xs text-brass">{p.read} مطالعه</div>
                <h3 className="mt-2 text-lg font-bold leading-snug transition-colors group-hover:text-primary">{p.title}</h3>
                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{p.excerpt}</p>
              </div>
            </Link>
          </Reveal>
        ))}
      </div>
    </section>
  );
}

function CTA() {
  return (
    <section data-testid="cta" className="mx-auto max-w-7xl px-5 pb-24 lg:px-10">
      <Reveal>
        <div className="relative overflow-hidden rounded-[2.5rem] bg-foreground px-8 py-16 text-center text-background lg:px-16 lg:py-24">
          <div className="pointer-events-none absolute -start-20 -top-20 h-72 w-72 rounded-full bg-primary/30 blur-3xl" />
          <div className="pointer-events-none absolute -end-20 -bottom-20 h-72 w-72 rounded-full bg-brass/30 blur-3xl" />
          <h2 className="relative mx-auto max-w-2xl font-display text-4xl tracking-tight lg:text-5xl">
            اولین قدم را برای چهره‌ای متناسب بردارید
          </h2>
          <p className="relative mx-auto mt-5 max-w-lg text-background/70">
            جلسه‌ی مشاوره‌ی تخصصی، بررسی ساختار بینی و پاسخ به تمام پرسش‌های شما.
          </p>
          <Link to="/appointment" data-testid="cta-appointment-btn" className="relative mt-9 inline-flex items-center gap-2 rounded-full bg-primary px-9 py-4 text-sm font-medium text-primary-foreground transition-transform hover:scale-105">
            رزرو نوبت مشاوره <IconArrowUpLeft size={16} />
          </Link>
        </div>
      </Reveal>
    </section>
  );
}

export default function Home() {
  return (
    <>
      <Hero />
      <MarqueeStrip />
      <Stats />
      <Services />
      <Manifesto />
      <DoctorPreview />
      <Testimonials />
      <BlogPreview />
      <CTA />
    </>
  );
}
