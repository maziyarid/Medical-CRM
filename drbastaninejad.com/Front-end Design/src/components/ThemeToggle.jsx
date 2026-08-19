import React from "react";
import { motion, AnimatePresence } from "framer-motion";
import { useThemeMode } from "../lib/theme";
import { IconSun, IconMoon } from "../lib/icons";

export default function ThemeToggle() {
  const { theme, toggle } = useThemeMode();
  const isDark = theme === "dark";
  return (
    <button
      data-testid="theme-toggle"
      aria-label="تغییر تم"
      onClick={toggle}
      className="relative grid h-10 w-10 place-items-center rounded-full border border-border bg-card/60 text-foreground transition-colors hover:border-primary hover:text-primary"
    >
      <AnimatePresence mode="wait" initial={false}>
        <motion.span
          key={isDark ? "moon" : "sun"}
          initial={{ rotate: -40, opacity: 0, scale: 0.6 }}
          animate={{ rotate: 0, opacity: 1, scale: 1 }}
          exit={{ rotate: 40, opacity: 0, scale: 0.6 }}
          transition={{ duration: 0.25 }}
        >
          {isDark ? <IconMoon size={18} /> : <IconSun size={18} />}
        </motion.span>
      </AnimatePresence>
    </button>
  );
}
