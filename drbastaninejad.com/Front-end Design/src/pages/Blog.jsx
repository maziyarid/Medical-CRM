import React, { useState } from "react";
import PageHeader from "../components/PageHeader";
import { Reveal } from "../lib/motion";
import { POSTS, BLOG_CATS } from "../lib/content";
import { CAT_ICONS, IconArrow } from "../lib/icons";

export default function Blog() {
  const [active, setActive] = useState("all");
  const filtered = active === "all" ? POSTS : POSTS.filter((p) => p.cat === active);
  const catLabel = (id) => BLOG_CATS.find((c) => c.id === id)?.label || "";

  return (
    <>
      <PageHeader overline="مجله" title={["دانستنی‌های", "جراحی زیبایی"]} subtitle="مقالات تخصصی درباره‌ی جراحی بینی، مراقبت پس از عمل و انتخاب آگاهانه." />

      <section className="mx-auto max-w-7xl px-5 pb-24 lg:px-10">
        <div className="mb-12 flex flex-wrap gap-3">
          <button
            data-testid="blog-cat-all"
            onClick={() => setActive("all")}
            className={`rounded-full border px-5 py-2.5 text-sm transition-colors ${active === "all" ? "border-primary bg-primary text-primary-foreground" : "border-border hover:border-primary"}`}
          >
            همه مقالات
          </button>
          {BLOG_CATS.map((c) => {
            const Icon = CAT_ICONS[c.icon];
            const on = active === c.id;
            return (
              <button
                key={c.id}
                data-testid={`blog-cat-${c.id}`}
                onClick={() => setActive(c.id)}
                className={`inline-flex items-center gap-2 rounded-full border px-5 py-2.5 text-sm transition-colors ${on ? "border-primary bg-primary text-primary-foreground" : "border-border hover:border-primary"}`}
              >
                <Icon size={18} /> {c.label}
              </button>
            );
          })}
        </div>

        <div className="grid gap-6 lg:grid-cols-3">
          {filtered.map((p, i) => {
            const Icon = CAT_ICONS[p.cat];
            return (
              <Reveal key={p.id} delay={(i % 3) * 0.07}>
                <article data-testid={`blog-post-${p.id}`} className="group flex h-full flex-col overflow-hidden rounded-3xl border border-border bg-card">
                  <div className="relative overflow-hidden">
                    <img src={p.img} alt={p.title} className="h-56 w-full object-cover transition-transform duration-700 group-hover:scale-105" />
                    <span className="absolute start-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-background/90 px-3 py-1.5 text-xs backdrop-blur">
                      <Icon size={15} className="text-primary" /> {catLabel(p.cat)}
                    </span>
                  </div>
                  <div className="flex flex-1 flex-col p-6">
                    <div className="text-xs text-brass">{p.read} مطالعه</div>
                    <h3 className="mt-2 text-lg font-bold leading-snug transition-colors group-hover:text-primary">{p.title}</h3>
                    <p className="mt-2 flex-1 text-sm leading-relaxed text-muted-foreground">{p.excerpt}</p>
                    <span className="mt-5 inline-flex items-center gap-2 text-sm text-primary">ادامه مطلب <IconArrow size={15} /></span>
                  </div>
                </article>
              </Reveal>
            );
          })}
        </div>
      </section>
    </>
  );
}
