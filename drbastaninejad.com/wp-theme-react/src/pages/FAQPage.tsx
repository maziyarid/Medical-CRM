/**
 * FAQPage — page-faq.php equivalent
 * WP: ACF Options or CPT: faq with repeater fields
 * Schema: FAQPage JSON-LD (inject in <head> via wp_head hook)
 */
import { useState } from "react";
import { ChevronDown } from "lucide-react";
import { HOME_FAQS } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";

export default function FAQPage() {
  const [openIdx, setOpenIdx] = useState<number | null>(null);
  const [cat, setCat] = useState("همه");
  const cats = ["همه", ...Array.from(new Set(HOME_FAQS.map(f => f.cat)))];
  const filtered = cat === "همه" ? HOME_FAQS : HOME_FAQS.filter(f => f.cat === cat);

  return (
    <>
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "سوالات متداول" }]} />

      {/* Header */}
      <section className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto text-center">
          <span className="text-[#28722C] font-bold text-sm tracking-wider">پاسخ سؤالات شما</span>
          <h1 className="text-4xl sm:text-5xl font-black mt-2 mb-4">سؤالات متداول</h1>
          <p className="text-white/70 text-lg">پاسخ جامع به رایج‌ترین سؤالات درباره جراحی بینی</p>
        </div>
      </section>

      <div className="max-w-4xl mx-auto px-4 sm:px-6 py-12">
        {/* Category filter — WP: custom taxonomy: faq_category */}
        <div className="flex flex-wrap justify-center gap-2 mb-10">
          {cats.map(c => (
            <button key={c} onClick={() => { setCat(c); setOpenIdx(null); }}
              className={`px-4 py-2 rounded-xl text-sm font-bold transition-all ${cat === c ? "bg-[#28722C] text-white" : "bg-white border border-[#DDE2DD] text-[#6A7078] hover:border-[#28722C]/30 hover:text-[#28722C]"}`}>
              {c}
            </button>
          ))}
        </div>

        {/* FAQs — PHP: foreach (get_field('faq_items') as $item) */}
        <div className="space-y-3 mb-10">
          {filtered.map((faq, i) => (
            <div key={i} className="bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden">
              <button onClick={() => setOpenIdx(openIdx === i ? null : i)}
                className="w-full flex items-center justify-between p-5 text-right gap-4">
                <div className="flex items-start gap-3">
                  <div className="w-6 h-6 bg-[#E4F0E4] rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-[#28722C] text-xs font-black">{i + 1}</span>
                  </div>
                  <div>
                    <span className="text-xs font-bold text-[#28722C]/70 mb-1 block">{faq.cat}</span>
                    <span className="font-bold text-[#25272C] text-sm leading-snug">{faq.q}</span>
                  </div>
                </div>
                <ChevronDown size={16} className={`text-[#28722C] flex-shrink-0 transition-transform ${openIdx === i ? "rotate-180" : ""}`} />
              </button>
              {openIdx === i && (
                <div className="px-5 pb-5 border-t border-[#DDE2DD] pt-4">
                  {/* PHP: echo wp_kses_post($item['faq_answer']); */}
                  <p className="text-sm text-[#6A7078] leading-[2]">{faq.a}</p>
                </div>
              )}
            </div>
          ))}
        </div>

        {/* Medical disclaimer */}
        <div className="bg-amber-50 border border-amber-200 rounded-2xl p-5">
          <p className="text-xs text-amber-800 leading-relaxed text-center">
            اطلاعات ارائه‌شده در این صفحه صرفاً جنبه آموزشی دارند و جایگزین مشاوره یا تشخیص پزشکی تخصصی نمی‌شوند.
            برای هر تصمیم پزشکی حتماً با جراح متخصص مجاز مشورت کنید.
          </p>
        </div>
      </div>
    </>
  );
}
