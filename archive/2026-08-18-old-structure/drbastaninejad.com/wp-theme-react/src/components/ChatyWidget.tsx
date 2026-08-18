/**
 * ChatyWidget — floating multi-channel contact button
 * PHP: include get_template_part('components/chaty');
 *
 * WP/Theme: chaty_channels (repeater) — register in theme functions.php
 *   Fields per row:
 *     chaty_channel_type  (text)   e.g. "whatsapp"
 *     chaty_channel_label (text)   e.g. "واتس‌اپ"
 *     chaty_channel_href  (url)    e.g. "https://wa.me/..."
 *     chaty_channel_color (select) green|sky|primary|booking
 *
 *   PHP: $channels = get_theme_mod('chaty_channels', []);
 *        foreach ($channels as $ch) { ... }
 *
 * Adding new channels requires ZERO code changes — update WP admin only.
 */
import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import { MessageCircle, X, Phone, ExternalLink } from "lucide-react";
import { useChatyItem, useStaggerVariants, useFadeScaleVariants } from "@/components/useMotion";

// WP/Theme: chaty_channels repeater — booking_url from theme options
const BOOKING_INTERNAL = "/booking";

/* ── Channel definition ──────────────────────────────────────
 * WP/Theme: chaty_channels (repeater, theme_mod)
 *   register_setting('theme_options', 'chaty_channels')
 *   Each channel is a row in the repeater with the fields above.
 *   PHP: $channels = get_theme_mod('chaty_channels', $defaults);
 */
interface ChatChannel {
  type:  string;   // WP/Theme: chaty_channel_type
  label: string;   // WP/Theme: chaty_channel_label
  href:  string;   // WP/Theme: chaty_channel_href
  color: string;   // Tailwind class string
  icon:  React.ReactNode;
}

/* Default channels — replaced by WP theme options in production */
const DEFAULT_CHANNELS: ChatChannel[] = [
  {
    type:  "phone-1",
    label: "۰۲۱۸۶۰۸۷۲۵۰",
    href:  "tel:02186087250",
    color: "bg-[#E4F0E4] border-[#28722C]/20 text-[#1a4e1d] hover:bg-[#d0e8d1]",
    icon:  <Phone size={12} aria-hidden />,
  },
  {
    type:  "phone-2",
    label: "۰۲۱۸۸۲۰۵۶۰۶",
    href:  "tel:02188205606",
    color: "bg-[#E4F0E4] border-[#28722C]/20 text-[#1a4e1d] hover:bg-[#d0e8d1]",
    icon:  <Phone size={12} aria-hidden />,
  },
  {
    type:  "phone-3",
    label: "۰۹۹۱۲۴۹۶۶۵۹",
    href:  "tel:09912496659",
    color: "bg-[#E4F0E4] border-[#28722C]/20 text-[#1a4e1d] hover:bg-[#d0e8d1]",
    icon:  <Phone size={12} aria-hidden />,
  },
  {
    type:  "booking",
    label: "رزرو نوبت آنلاین",
    href:  BOOKING_INTERNAL,
    color: "bg-[#28722C] border-[#28722C] text-white hover:bg-[#246b28]",
    icon:  <ExternalLink size={12} aria-hidden />,
  },
];

export default function ChatyWidget() {
  const [open, setOpen] = useState(false);

  /* WP/Theme: In production load channels from PHP-injected window.__chatChannels
   * const channels: ChatChannel[] = (window as any).__chatChannels ?? DEFAULT_CHANNELS;
   */
  const channels = DEFAULT_CHANNELS;

  const panelVariants = useFadeScaleVariants();
  const stagger       = useStaggerVariants(0.06);
  const itemVariants  = useChatyItem();

  return (
    <div className="chaty" aria-label="ارتباط سریع" role="complementary">
      <AnimatePresence>
        {open && (
          <motion.div
            className="chaty__panel"
            variants={panelVariants}
            initial="hidden"
            animate="visible"
            exit="exit"
            role="dialog"
            aria-modal="true"
            aria-label="گزینه‌های تماس"
          >
            {/* Header */}
            <div className="p-3 border-b border-[#DDE2DD]">
              <p className="text-xs font-bold text-[#25272C]">ارتباط سریع</p>
              <p className="text-[10px] text-[#545B64]">سوالی داشتین در خدمتیم</p>
            </div>

            {/* Channel list — WP/Theme: chaty_channels repeater */}
            <motion.div
              className="p-2 space-y-1.5"
              variants={stagger}
              initial="hidden"
              animate="visible"
            >
              {channels.map(ch => (
                <motion.a
                  key={ch.type}
                  href={ch.href}
                  variants={itemVariants}
                  target={ch.href.startsWith("http") ? "_blank" : undefined}
                  rel={ch.href.startsWith("http") ? "noopener noreferrer" : undefined}
                  className={`flex items-center gap-2 px-3 py-2 rounded-xl border text-xs font-semibold transition-colors ${ch.color}`}
                >
                  {ch.icon}
                  {ch.label}
                </motion.a>
              ))}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Trigger button */}
      <motion.button
        onClick={() => setOpen(o => !o)}
        className="chaty__trigger"
        aria-label={open ? "بستن گزینه‌های تماس" : "باز کردن گزینه‌های تماس"}
        aria-expanded={open}
        aria-haspopup="dialog"
        whileHover={{ scale: 1.06 }}
        whileTap={{ scale: 0.95 }}
        transition={{ type: "spring", stiffness: 400, damping: 17 }}
      >
        <AnimatePresence mode="wait" initial={false}>
          {open
            ? <motion.span key="x"  initial={{ rotate: -90, opacity: 0 }} animate={{ rotate: 0, opacity: 1 }} exit={{ rotate: 90, opacity: 0 }} transition={{ duration: 0.15 }}><X size={20} className="text-white" /></motion.span>
            : <motion.span key="msg" initial={{ rotate: 90, opacity: 0 }} animate={{ rotate: 0, opacity: 1 }} exit={{ rotate: -90, opacity: 0 }} transition={{ duration: 0.15 }}><MessageCircle size={22} className="text-white" /></motion.span>
          }
        </AnimatePresence>
        {!open && (
          <span
            className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-[10px] text-white flex items-center justify-center font-bold"
            aria-label="۱ پیام جدید"
          >
            ۱
          </span>
        )}
      </motion.button>
    </div>
  );
}
