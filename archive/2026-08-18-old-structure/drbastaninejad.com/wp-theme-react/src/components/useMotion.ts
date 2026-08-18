/**
 * useMotion — centralised animation variants for consistent motion design
 * Uses motion/react (Framer Motion v12 compat). Respects prefers-reduced-motion.
 *
 * GPU-only props: x, y, scale, rotate, opacity (never width/height/top/left)
 */
import { useReducedMotion, type Variants } from "motion/react";

/** Card reveal on scroll enter */
export function useCardVariants(): Variants {
  const reduced = useReducedMotion();
  return reduced
    ? { hidden: {}, visible: {} }
    : {
        hidden:  { opacity: 0, y: 24 },
        visible: {
          opacity: 1, y: 0,
          transition: { duration: 0.45, ease: [0.22, 1, 0.36, 1] },
        },
      };
}

/** Stagger children container */
export function useStaggerVariants(stagger = 0.08): Variants {
  const reduced = useReducedMotion();
  return reduced
    ? { hidden: {}, visible: {} }
    : {
        hidden: {},
        visible: { transition: { staggerChildren: stagger } },
      };
}

/** Fade + scale in (for modals, panels) */
export function useFadeScaleVariants(): Variants {
  const reduced = useReducedMotion();
  return reduced
    ? { hidden: {}, visible: {} }
    : {
        hidden:  { opacity: 0, scale: 0.95 },
        visible: {
          opacity: 1, scale: 1,
          transition: { duration: 0.25, ease: [0.34, 1.56, 0.64, 1] },
        },
        exit: {
          opacity: 0, scale: 0.95,
          transition: { duration: 0.15 },
        },
      };
}

/** Slide in from right (RTL: from left) — mobile panel */
export function useSlideVariants(): Variants {
  const reduced = useReducedMotion();
  return reduced
    ? { hidden: {}, visible: {} }
    : {
        hidden:  { opacity: 0, x: 40 },
        visible: {
          opacity: 1, x: 0,
          transition: { duration: 0.3, ease: [0.22, 1, 0.36, 1] },
        },
        exit: {
          opacity: 0, x: 40,
          transition: { duration: 0.2 },
        },
      };
}

/** For Chaty channels stagger */
export function useChatyItem(): Variants {
  const reduced = useReducedMotion();
  return reduced
    ? { hidden: {}, visible: {} }
    : {
        hidden:  { opacity: 0, x: -16, scale: 0.95 },
        visible: {
          opacity: 1, x: 0, scale: 1,
          transition: { duration: 0.25, ease: [0.22, 1, 0.36, 1] },
        },
      };
}
