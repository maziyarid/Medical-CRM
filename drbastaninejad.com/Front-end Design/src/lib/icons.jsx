// Custom thin-line SVG icon set for the clinic. stroke = currentColor.
import React from "react";

const P = { fill: "none", stroke: "currentColor", strokeWidth: 1.4, strokeLinecap: "round", strokeLinejoin: "round" };

const wrap = (children) => ({ size = 28, className = "" }) => (
  <svg width={size} height={size} viewBox="0 0 32 32" className={className} {...P} aria-hidden="true">
    {children}
  </svg>
);

// Service icons
export const IconRhino = wrap(<>
  <path d="M20 4c0 5-4 7-4 12 0 4 3 5 3 8 0 2-1.6 3.5-4 3.5" />
  <path d="M12 22c-2 0-3.5 1-3.5 2.6 0 1.4 1.3 2 3 2" />
  <path d="M20 4c-3 2-5 5-6 9" />
</>);
export const IconRedo = wrap(<>
  <path d="M20 5c0 5-4 7-4 12 0 4 3 5 3 8 0 2-1.6 3.4-4 3.4" />
  <path d="M9 9a7 7 0 0 1 11-2" /><path d="M20 4v4h-4" />
</>);
export const IconHump = wrap(<>
  <path d="M6 24c3 0 5-2 7-8 1-3 2-5 4-5s3 2 4 5c1 4 3 6 5 6" />
  <path d="M4 27h24" />
</>);
export const IconFacelift = wrap(<>
  <path d="M16 4a10 10 0 0 1 10 10c0 6-4 10-10 14C10 24 6 20 6 14A10 10 0 0 1 16 4Z" />
  <path d="M11 14c1.5 1.5 8.5 1.5 10 0" /><path d="M13 20c1.5 1.2 4.5 1.2 6 0" />
</>);
export const IconEyelid = wrap(<>
  <path d="M3 16c4-6 9-9 13-9s9 3 13 9c-4 6-9 9-13 9S7 22 3 16Z" />
  <circle cx="16" cy="16" r="3.4" />
</>);
export const IconFiller = wrap(<>
  <path d="M6 26l14-14" /><path d="M17 9l6 6" /><path d="M20 6l6 6-3 3-6-6z" />
  <path d="M6 26l-2 2M9 21l2 2" />
</>);
export const IconChin = wrap(<>
  <path d="M9 6v6c0 6 3 10 7 12 4-2 7-6 7-12V6" />
  <path d="M9 6h14" /><path d="M13 24c1.5 1 4.5 1 6 0" />
</>);
export const IconEar = wrap(<>
  <path d="M10 13a6 6 0 0 1 12 0c0 4-3 5-3 8a3 3 0 0 1-6 0" />
  <path d="M14 13a2.5 2.5 0 0 1 5 0c0 2-2 2.5-2 4" />
</>);

// Blog category icons
export const IconNose = IconRhino;
export const IconRecovery = wrap(<>
  <path d="M16 27s-9-5-9-13a5 5 0 0 1 9-3 5 5 0 0 1 9 3" />
  <path d="M12 15h3l1.5-3 2 6 1.5-3h3" />
</>);
export const IconNutrition = wrap(<>
  <path d="M16 9c-3-5-11-3-11 5 0 6 6 11 11 13 5-2 11-7 11-13 0-8-8-10-11-5Z" />
  <path d="M16 9V4M16 4c1.5 0 3-1 3-2" />
</>);
export const IconChoose = wrap(<>
  <path d="M16 4l3 8 8 .5-6 5 2 8-7-4.5L9 25.5l2-8-6-5 8-.5z" />
</>);

// UI icons
export const IconArrow = wrap(<><path d="M20 8l-8 8 8 8" /><path d="M20 16H4" /></>);
export const IconArrowUpLeft = wrap(<><path d="M9 9h14M9 9v14M9 9l14 14" /></>);
export const IconPhone = wrap(<><path d="M7 5c0 11 9 20 20 20l-1-5-5-2-2 3c-3-2-6-5-8-8l3-2-2-5-5-1z" /></>);
export const IconClock = wrap(<><circle cx="16" cy="16" r="11" /><path d="M16 9v7l5 3" /></>);
export const IconMap = wrap(<><path d="M16 27s9-8 9-14a9 9 0 1 0-18 0c0 6 9 14 9 14Z" /><circle cx="16" cy="13" r="3.2" /></>);
export const IconSun = wrap(<><circle cx="16" cy="16" r="6" /><path d="M16 3v3M16 26v3M3 16h3M26 16h3M6 6l2 2M24 24l2 2M26 6l-2 2M8 24l-2 2" /></>);
export const IconMoon = wrap(<><path d="M25 18a9 9 0 1 1-11-11 7 7 0 0 0 11 11Z" /></>);
export const IconCheck = wrap(<><path d="M6 17l6 6L26 8" /></>);

export const SERVICE_ICONS = { rhino: IconRhino, redo: IconRedo, hump: IconHump, facelift: IconFacelift, eyelid: IconEyelid, filler: IconFiller, chin: IconChin, ear: IconEar };
export const CAT_ICONS = { nose: IconNose, recovery: IconRecovery, nutrition: IconNutrition, choose: IconChoose };
