/**
 * ChatyWidget — floating multi-channel contact button
 * PHP: include get_template_part('components/chaty');
 * ACF options: whatsapp_number, telegram_username, phone_1, phone_2, booking_url
 */
import { useState } from "react";
import { MessageCircle, Phone, X } from "lucide-react";
import { BOOKING_URL, TELEGRAM, WHATSAPP } from "@/data/site";

export default function ChatyWidget() {
  const [open, setOpen] = useState(false);

  const channels = [
    { label: "واتس‌اپ",       href: WHATSAPP,       color: "bg-green-50 border-green-200 text-green-800 hover:bg-green-100" },
    { label: "تلگرام",         href: TELEGRAM,       color: "bg-sky-50 border-sky-200 text-sky-800 hover:bg-sky-100" },
    { label: "۰۲۱۸۶۰۸۷۲۵۰", href: "tel:02186087250", color: "bg-[#E4F0E4] border-[#28722C]/20 text-[#28722C] hover:bg-[#28722C]/10" },
    { label: "۰۲۱۸۸۲۰۵۶۰۶", href: "tel:02188205606", color: "bg-[#E4F0E4] border-[#28722C]/20 text-[#28722C] hover:bg-[#28722C]/10" },
    { label: "نوبت‌گیری آنلاین", href: BOOKING_URL, color: "bg-[#28722C] border-[#28722C] text-white hover:bg-[#246b28]" },
  ];

  return (
    <div className="fixed bottom-6 left-6 z-50 flex flex-col items-end gap-2" aria-label="ارتباط سریع">
      {open && (
        <div className="bg-white rounded-2xl shadow-2xl border border-[#DDE2DD] w-60 overflow-hidden mb-1">
          <div className="p-3 border-b border-[#DDE2DD]">
            <p className="text-xs font-bold text-[#25272C]">ارتباط سریع</p>
            <p className="text-[10px] text-[#6A7078]">سوالی داشتین در خدمتیم</p>
          </div>
          <div className="p-2 space-y-1.5">
            {channels.map(ch => (
              <a key={ch.label} href={ch.href}
                target={ch.href.startsWith("http") ? "_blank" : undefined}
                rel={ch.href.startsWith("http") ? "noopener" : undefined}
                className={`flex items-center gap-2 px-3 py-2 rounded-xl border text-xs font-semibold transition-colors ${ch.color}`}>
                <Phone size={12} aria-hidden />
                {ch.label}
              </a>
            ))}
          </div>
        </div>
      )}
      <button
        onClick={() => setOpen(!open)}
        className="w-14 h-14 bg-gradient-to-br from-[#28722C] to-[#1a4e1d] rounded-2xl shadow-xl flex items-center justify-center hover:scale-105 transition-transform relative"
        aria-label={open ? "بستن گزینه‌های تماس" : "باز کردن گزینه‌های تماس"}
        aria-expanded={open}
      >
        {open ? <X size={20} className="text-white" /> : <MessageCircle size={22} className="text-white" />}
        {!open && (
          <span className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-[10px] text-white flex items-center justify-center font-bold" aria-label="۱ پیام جدید">۱</span>
        )}
      </button>
    </div>
  );
}
