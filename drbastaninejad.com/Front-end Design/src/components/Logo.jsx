import React from "react";

// Two-color logo mark: green stratified almond + graphite face profile
export default function Logo({ size = 40, showWord = true, className = "" }) {
  return (
    <div className={`flex items-center gap-3 ${className}`} data-testid="brand-logo">
      <svg width={size} height={size} viewBox="0 0 100 100" aria-hidden="true">
        <g fill="hsl(var(--primary))">
          {[0, 1, 2, 3, 4, 5, 6].map((i) => {
            const y = 16 + i * 10;
            const inset = Math.abs(i - 3) * 3.2;
            return <rect key={i} x={12 + inset} y={y} width={30 - inset} height={6.2} rx={3.1} />;
          })}
        </g>
        <path
          d="M56 12c14 2 26 14 26 30 0 20-14 34-26 46 4-10 2-16-2-22 6-4 8-10 4-16-5 6-9 4-12-2 8-4 10-12 4-20 4 0 6-4 6-8-4 2-8 0-10-4 4-4 8-6 10-4Z"
          fill="hsl(var(--foreground))"
        />
      </svg>
      {showWord && (
        <div className="leading-tight text-start">
          <div className="font-display text-base tracking-tight">دکتر باستانی‌نژاد</div>
          <div className="text-[10px] text-muted-foreground tracking-wide">جراحی زیبایی بینی و صورت</div>
        </div>
      )}
    </div>
  );
}
