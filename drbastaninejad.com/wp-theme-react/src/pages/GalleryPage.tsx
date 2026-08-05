/**
 * GalleryPage — page-gallery.php equivalent
 * WP: ACF gallery field or CPT: case-study with before/after fields
 * ACF: gallery_items (repeater: before_image, after_image, description)
 */
import { useState, useRef, useCallback, useEffect } from "react";
import { ArrowRight, ArrowLeft, ChevronRight, ChevronLeft } from "lucide-react";
import { GALLERY_ITEMS, STATS } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";
import JsonLd, { buildBreadcrumbSchema } from "@/components/JsonLd";

function BeforeAfterSlider({ before, after, fallback }: { before: string; after: string; fallback: string }) {
  const [pos, setPos] = useState(50);
  const [dragging, setDragging] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  const updatePos = useCallback((clientX: number) => {
    if (!ref.current) return;
    const rect = ref.current.getBoundingClientRect();
    const pct = Math.max(5, Math.min(95, ((rect.right - clientX) / rect.width) * 100));
    setPos(pct);
  }, []);

  useEffect(() => {
    const onMove = (e: MouseEvent) => { if (dragging) updatePos(e.clientX); };
    const onUp   = () => setDragging(false);
    window.addEventListener("mousemove", onMove);
    window.addEventListener("mouseup",   onUp);
    return () => {
      window.removeEventListener("mousemove", onMove);
      window.removeEventListener("mouseup",   onUp);
    };
  }, [dragging, updatePos]);

  const onError = (e: React.SyntheticEvent<HTMLImageElement>) => { (e.target as HTMLImageElement).src = fallback; };

  return (
    <div ref={ref}
      className="relative w-full aspect-[3/4] rounded-2xl overflow-hidden cursor-col-resize select-none touch-none"
      onMouseDown={e  => { setDragging(true); updatePos(e.clientX); }}
      onTouchStart={e => { setDragging(true); updatePos(e.touches[0].clientX); }}
      onTouchMove={e  => { if (dragging) updatePos(e.touches[0].clientX); }}
      onTouchEnd={()  => setDragging(false)}
    >
      <img src={after}  alt="بعد" className="absolute inset-0 w-full h-full object-cover" onError={onError} />
      <div className="absolute inset-0 overflow-hidden" style={{ clipPath: `inset(0 0 0 ${100 - pos}%)` }}>
        <img src={before} alt="قبل" className="absolute inset-0 w-full h-full object-cover" onError={onError} />
      </div>
      <div className="absolute top-0 bottom-0 w-0.5 bg-white shadow-lg pointer-events-none" style={{ right: `${pos}%` }}>
        <div className="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-9 h-9 bg-white rounded-full shadow-xl flex items-center justify-center ring-2 ring-[#28722C]/30">
          <ArrowRight className="w-3 h-3 text-[#28722C]" />
          <ArrowLeft  className="w-3 h-3 text-[#28722C]" />
        </div>
      </div>
      <div className="absolute bottom-3 right-3 bg-black/60 text-white text-xs px-3 py-1.5 rounded-full font-bold">قبل</div>
      <div className="absolute bottom-3 left-3  bg-[#28722C]/80 text-white text-xs px-3 py-1.5 rounded-full font-bold">بعد</div>
    </div>
  );
}

export default function GalleryPage() {
  const [page, setPage] = useState(0);
  const PER = 8;
  const total = Math.ceil(GALLERY_ITEMS.length / PER);
  const visible = GALLERY_ITEMS.slice(page * PER, page * PER + PER);

  const crumbs = [{ label: "خانه", href: "/" }, { label: "گالری قبل و بعد" }];

  return (
    <>
      <JsonLd id="jsonld-gallery" data={buildBreadcrumbSchema(crumbs)} />
      <Breadcrumb items={crumbs} />

      {/* Header */}
      <section className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto text-center">
          <span className="text-[#28722C] font-bold text-sm tracking-wider">نتایج واقعی</span>
          <h1 className="text-4xl sm:text-5xl font-black mt-2 mb-4">گالری قبل و بعد</h1>
          <p className="text-white/70 text-lg">+۱۷۸ نمونه واقعی از نتایج جراحی بینی دکتر باستانی‌نژاد</p>
        </div>
      </section>

      {/* Stats */}
      <section className="bg-[#28722C] py-8">
        <div className="max-w-[1200px] mx-auto px-4 sm:px-6">
          <div className="flex flex-wrap justify-center gap-8">
            {STATS.map(s => (
              <div key={s.label} className="text-center">
                <p className="text-3xl font-black text-white">{s.val}</p>
                <p className="text-white/70 text-sm">{s.label}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Gallery grid */}
      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-12">
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          {visible.map(item => (
            <BeforeAfterSlider key={item.id} before={item.before} after={item.after} fallback={item.fallback} />
          ))}
        </div>

        {/* Pagination */}
        <div className="flex justify-center items-center gap-3">
          <button onClick={() => setPage(p => Math.max(0, p - 1))} disabled={page === 0}
            className="w-10 h-10 rounded-xl border border-[#DDE2DD] bg-white flex items-center justify-center hover:bg-[#E4F0E4] disabled:opacity-40 transition-colors">
            <ChevronRight className="w-5 h-5" />
          </button>
          {Array.from({ length: total }).map((_, i) => (
            <button key={i} onClick={() => setPage(i)}
              className={`w-10 h-10 rounded-xl font-bold text-sm transition-all ${page === i ? "bg-[#28722C] text-white" : "bg-white border border-[#DDE2DD] text-[#25272C] hover:bg-[#E4F0E4]"}`}>
              {i + 1}
            </button>
          ))}
          <button onClick={() => setPage(p => Math.min(total - 1, p + 1))} disabled={page === total - 1}
            className="w-10 h-10 rounded-xl border border-[#DDE2DD] bg-white flex items-center justify-center hover:bg-[#E4F0E4] disabled:opacity-40 transition-colors">
            <ChevronLeft className="w-5 h-5" />
          </button>
        </div>

        <p className="text-center text-xs text-[#545B64] mt-6 max-w-xl mx-auto">
          تمام تصاویر با رضایت کامل بیماران و رعایت حریم خصوصی منتشر شده‌اند. نتایج ممکن است متفاوت باشد.
        </p>
      </div>
    </>
  );
}
