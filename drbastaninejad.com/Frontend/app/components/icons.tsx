import type { SVGProps } from "react";

type IconName =
  | "arrow"
  | "calendar"
  | "phone"
  | "location"
  | "clock"
  | "moon"
  | "sun"
  | "menu"
  | "close"
  | "nose"
  | "refresh"
  | "breathe"
  | "eye"
  | "face"
  | "spark"
  | "check"
  | "search"
  | "chevron";

type IconProps = SVGProps<SVGSVGElement> & {
  name: IconName;
  size?: number;
};

export function Icon({ name, size = 22, ...props }: IconProps) {
  const common = {
    width: size,
    height: size,
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: 1.7,
    strokeLinecap: "round" as const,
    strokeLinejoin: "round" as const,
    "aria-hidden": true,
    ...props,
  };

  const paths: Record<IconName, React.ReactNode> = {
    arrow: (
      <>
        <path d="M19 12H5" />
        <path d="m11 6-6 6 6 6" />
      </>
    ),
    calendar: (
      <>
        <rect x="3" y="5" width="18" height="16" rx="3" />
        <path d="M16 3v4M8 3v4M3 10h18" />
      </>
    ),
    phone: (
      <path d="M6.7 3.8 9.4 8 7.8 9.8a15.7 15.7 0 0 0 6.4 6.4l1.8-1.6 4.2 2.7-.7 3a2 2 0 0 1-2.2 1.5A17.7 17.7 0 0 1 2.2 6.7a2 2 0 0 1 1.5-2.2l3-.7Z" />
    ),
    location: (
      <>
        <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z" />
        <circle cx="12" cy="10" r="2.5" />
      </>
    ),
    clock: (
      <>
        <circle cx="12" cy="12" r="9" />
        <path d="M12 7v5l3.5 2" />
      </>
    ),
    moon: <path d="M20 15.7A8.5 8.5 0 0 1 8.3 4 8.5 8.5 0 1 0 20 15.7Z" />,
    sun: (
      <>
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4" />
      </>
    ),
    menu: <path d="M4 7h16M4 12h16M4 17h16" />,
    close: <path d="m5 5 14 14M19 5 5 19" />,
    nose: (
      <>
        <path d="M15 3c0 4-3 6-3 10 0 3 2.5 4 2.5 6 0 1.3-1.2 2-3 2" />
        <path d="M8 17.5c-1.5 0-2.5.8-2.5 2S6.7 21 8 21M15 3c-2.5 1.5-4 4-4.5 6.5" />
      </>
    ),
    refresh: (
      <>
        <path d="M20 7v5h-5" />
        <path d="M18.5 17a8 8 0 1 1 1.3-8.3L20 12" />
      </>
    ),
    breathe: (
      <path d="M12 4v16M12 12c-1-4-5-6-7-3-2 3-.5 8 3 9 2 .5 3-1 4-3M12 12c1-4 5-6 7-3 2 3 .5 8-3 9-2 .5-3-1-4-3" />
    ),
    eye: (
      <>
        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
        <circle cx="12" cy="12" r="2.5" />
      </>
    ),
    face: (
      <>
        <path d="M12 2a8 8 0 0 1 8 8c0 5-3 8.5-8 12-5-3.5-8-7-8-12a8 8 0 0 1 8-8Z" />
        <path d="M8 11c1 1 7 1 8 0M9.5 16c1.3 1 3.7 1 5 0" />
      </>
    ),
    spark: (
      <>
        <path d="m12 2 1.5 5.2L19 9l-5.5 1.8L12 16l-1.5-5.2L5 9l5.5-1.8L12 2Z" />
        <path d="m19 15 .8 2.7 2.7.8-2.7.8L19 22l-.8-2.7-2.7-.8 2.7-.8L19 15Z" />
      </>
    ),
    check: <path d="m5 12 4 4L19 6" />,
    search: (
      <>
        <circle cx="11" cy="11" r="7" />
        <path d="m20 20-4-4" />
      </>
    ),
    chevron: <path d="m9 18 6-6-6-6" />,
  };

  return <svg {...common}>{paths[name]}</svg>;
}

export function LogoMark({ size = 48 }: { size?: number }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 100 100"
      role="img"
      aria-label="نشان دکتر باستانی‌نژاد"
    >
      <g fill="currentColor" className="logo-leaves">
        {[0, 1, 2, 3, 4, 5, 6].map((item) => {
          const y = 16 + item * 10;
          const inset = Math.abs(item - 3) * 3.2;
          return (
            <rect
              key={item}
              x={12 + inset}
              y={y}
              width={30 - inset}
              height={6.2}
              rx={3.1}
            />
          );
        })}
      </g>
      <path
        d="M56 12c14 2 26 14 26 30 0 20-14 34-26 46 4-10 2-16-2-22 6-4 8-10 4-16-5 6-9 4-12-2 8-4 10-12 4-20 4 0 6-4 6-8-4 2-8 0-10-4 4-4 8-6 10-4Z"
        fill="currentColor"
        className="logo-face"
      />
    </svg>
  );
}
