import { motion, useScroll, useTransform } from "framer-motion";
import { useRef } from "react";

export const EASE = [0.22, 1, 0.36, 1];

// Masked line-by-line reveal for headings
export function MaskText({ lines, className = "", delay = 0 }) {
  return (
    <span className={className}>
      {lines.map((line, i) => (
        <span key={i} className="block overflow-hidden pb-[0.12em]">
          <motion.span
            className="block"
            initial={{ y: "110%" }}
            animate={{ y: 0 }}
            transition={{ duration: 0.9, ease: EASE, delay: delay + i * 0.12 }}
          >
            {line}
          </motion.span>
        </span>
      ))}
    </span>
  );
}

// Scroll-triggered reveal
export function Reveal({ children, delay = 0, y = 40, className = "", once = true }) {
  return (
    <motion.div
      className={className}
      initial={{ opacity: 0, y }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once, margin: "-80px" }}
      transition={{ duration: 0.8, ease: EASE, delay }}
    >
      {children}
    </motion.div>
  );
}

// Simple parallax wrapper
export function Parallax({ children, distance = 80, className = "" }) {
  const ref = useRef(null);
  const { scrollYProgress } = useScroll({ target: ref, offset: ["start end", "end start"] });
  const y = useTransform(scrollYProgress, [0, 1], [distance, -distance]);
  return (
    <div ref={ref} className={className} style={{ position: "relative" }}>
      <motion.div style={{ y }}>{children}</motion.div>
    <motion.div style={{ y }}>{children}</motion.div>
    </div>
  );
}

export const stagger = {
  hidden: {},
  show: { transition: { staggerChildren: 0.09 } },
};
export const fadeUp = {
  hidden: { opacity: 0, y: 28 },
  show: { opacity: 1, y: 0, transition: { duration: 0.7, ease: EASE } },
};
