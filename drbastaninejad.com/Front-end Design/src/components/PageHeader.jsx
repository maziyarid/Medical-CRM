import React from "react";
import { MaskText } from "../lib/motion";

export default function PageHeader({ overline, title, subtitle }) {
  return (
    <section className="relative overflow-hidden pt-36 pb-14 lg:pt-44 lg:pb-20">
      <div className="pointer-events-none absolute -start-32 top-16 h-96 w-96 rounded-full bg-primary/10 blur-3xl" />
      <div className="mx-auto max-w-7xl px-5 lg:px-10">
        {overline && <div className="mb-4 text-xs font-bold tracking-[0.2em] text-brass">{overline}</div>}
        <h1 className="font-display text-5xl tracking-tight lg:text-7xl">
          <MaskText lines={Array.isArray(title) ? title : [title]} />
        </h1>
        {subtitle && <p className="mt-6 max-w-2xl text-lg leading-relaxed text-muted-foreground">{subtitle}</p>}
      </div>
    </section>
  );
}
