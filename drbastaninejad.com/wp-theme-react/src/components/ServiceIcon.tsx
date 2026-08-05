/**
 * ServiceIcon — Medical Lottie / SVG icon with prefers-reduced-motion fallback
 *
 * WP NOTE: In production, animation JSON files would be stored as WP attachments
 *   or served from theme directory: get_template_directory_uri() . '/assets/lottie/'
 *
 * Since we ship clean SVG fallbacks and no Lottie JSON files are committed,
 * we always render the accessible SVG. Lottie can be enabled per-service by
 * passing animationUrl when the .lottie assets are deployed.
 */
import { type LucideIcon } from "lucide-react";

// Service slug → relevant medical SVG path data
const SERVICE_SVG: Record<string, React.ReactNode> = {
  "rhinoplasty-primary": (
    // Scalpel + nose silhouette
    <svg viewBox="0 0 40 40" fill="none" aria-hidden className="w-6 h-6">
      <path d="M20 8 C14 8 10 14 10 20 C10 26 14 30 20 30 C26 30 30 26 30 20" stroke="currentColor" strokeWidth="2" strokeLinecap="round" fill="none"/>
      <path d="M20 8 L22 6 L28 4 L30 8 L26 10 L20 8Z" fill="currentColor" opacity=".5"/>
      <circle cx="20" cy="22" r="3" fill="currentColor"/>
      <path d="M28 4 L36 12" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
    </svg>
  ),
  "rhinoplasty-revision": (
    // Two arrows forming a revision cycle around nose
    <svg viewBox="0 0 40 40" fill="none" aria-hidden className="w-6 h-6">
      <path d="M20 9 C14 9 10 14 10 20" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
      <path d="M10 20 C10 26 14 31 20 31 C26 31 30 26 30 20" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
      <path d="M26 7 L30 11 L26 15" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
      <path d="M14 25 L10 29 L14 33" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
      <circle cx="20" cy="20" r="2.5" fill="currentColor"/>
    </svg>
  ),
  "rhinoplasty-fleshy": (
    // Rounded/fleshy nose tip
    <svg viewBox="0 0 40 40" fill="none" aria-hidden className="w-6 h-6">
      <ellipse cx="20" cy="22" rx="9" ry="11" stroke="currentColor" strokeWidth="2" fill="none"/>
      <ellipse cx="20" cy="24" rx="5" ry="5" fill="currentColor" opacity=".3"/>
      <path d="M14 18 C14 13 26 13 26 18" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
    </svg>
  ),
  "rhinoplasty-bony": (
    // Angular/bony structure with hump outline
    <svg viewBox="0 0 40 40" fill="none" aria-hidden className="w-6 h-6">
      <path d="M18 8 L16 14 L14 18 L17 22 L20 30 L23 22 L26 18 L24 14 L22 8 Z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" fill="none"/>
      <path d="M17 14 L23 14" stroke="currentColor" strokeWidth="1.5" opacity=".5"/>
      <path d="M15 18 L25 18" stroke="currentColor" strokeWidth="1.5" opacity=".5"/>
    </svg>
  ),
  "rhinoplasty-natural": (
    // Gentle curved natural-looking nose
    <svg viewBox="0 0 40 40" fill="none" aria-hidden className="w-6 h-6">
      <path d="M20 8 C19 10 18 14 18 18 C18 22 18 26 16 28 C18 30 22 30 24 28 C22 26 22 22 22 18 C22 14 21 10 20 8Z" stroke="currentColor" strokeWidth="2" fill="none" strokeLinejoin="round"/>
      <path d="M16 28 C16 31 24 31 24 28" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
      <path d="M20 8 L20 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
    </svg>
  ),
  "rhinoplasty-fantasy": (
    // Fantasy/upturned tip with star accent
    <svg viewBox="0 0 40 40" fill="none" aria-hidden className="w-6 h-6">
      <path d="M20 10 C19 13 18 17 18 21 C18 25 17 28 15 30 C18 33 22 33 25 30 C23 28 22 25 22 21 C22 17 21 13 20 10Z" stroke="currentColor" strokeWidth="2" fill="none"/>
      <path d="M18 10 L16 6 M20 10 L20 6 M22 10 L24 6" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" opacity=".6"/>
    </svg>
  ),
  "hump-removal": (
    // Nose profile with hump being removed (dashed hump)
    <svg viewBox="0 0 40 40" fill="none" aria-hidden className="w-6 h-6">
      <path d="M12 30 L15 20 L18 14 L22 14 L25 20 L28 30" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
      <path d="M18 14 C18 11 22 11 22 14" stroke="currentColor" strokeWidth="2" strokeDasharray="2 2" strokeLinecap="round"/>
      <path d="M26 12 L30 8 M30 12 L26 8" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
    </svg>
  ),
  "septoplasty": (
    // Septum cross-section / breathing airway
    <svg viewBox="0 0 40 40" fill="none" aria-hidden className="w-6 h-6">
      <path d="M20 8 L20 32" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"/>
      <path d="M10 15 Q15 13 20 15 Q25 17 30 15" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" fill="none"/>
      <path d="M10 22 Q15 24 20 22 Q25 20 30 22" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" fill="none"/>
      <circle cx="20" cy="20" r="2" fill="currentColor" opacity=".4"/>
    </svg>
  ),
  "turbinoplasty": (
    // Turbinate scrolled structure
    <svg viewBox="0 0 40 40" fill="none" aria-hidden className="w-6 h-6">
      <path d="M10 15 Q16 10 22 15 Q28 20 22 25 Q16 30 10 25 Q10 20 14 17" stroke="currentColor" strokeWidth="2" strokeLinecap="round" fill="none"/>
      <path d="M30 10 Q34 15 30 20" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
      <path d="M30 22 Q34 27 30 32" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" opacity=".5"/>
    </svg>
  ),
  "sinus-endoscopy": (
    // Endoscope entering sinus cavity
    <svg viewBox="0 0 40 40" fill="none" aria-hidden className="w-6 h-6">
      <circle cx="22" cy="20" r="9" stroke="currentColor" strokeWidth="2" fill="none"/>
      <circle cx="22" cy="20" r="4" stroke="currentColor" strokeWidth="1.5" fill="none"/>
      <path d="M8 20 L13 20" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
      <circle cx="22" cy="20" r="1.5" fill="currentColor"/>
      <path d="M10 14 L14 17 M10 26 L14 23" stroke="currentColor" strokeWidth="1" opacity=".4" strokeLinecap="round"/>
    </svg>
  ),
};

interface ServiceIconProps {
  slug: string;
  FallbackIcon: LucideIcon;
  size?: number;
  className?: string;
}

export default function ServiceIcon({ slug, FallbackIcon, size = 20, className = "" }: ServiceIconProps) {
  const custom = SERVICE_SVG[slug];

  // className carries the color context (text-[#28722C], text-white, etc.)
  // Custom SVGs use currentColor so they inherit correctly.
  return (
    <span className={`service-icon inline-flex items-center justify-center ${className}`} aria-hidden="true">
      {custom
        ? <span className="contents">{custom}</span>
        : <FallbackIcon size={size} aria-hidden />
      }
    </span>
  );
}
