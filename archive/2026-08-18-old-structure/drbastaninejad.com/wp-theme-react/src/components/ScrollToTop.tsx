/**
 * ScrollToTop — floating back-to-top button
 * PHP: include get_template_part('components/scroll-to-top');
 */
import { useState, useEffect } from "react";
import { ChevronUp } from "lucide-react";

export default function ScrollToTop() {
  const [show, setShow] = useState(false);
  useEffect(() => {
    const h = () => setShow(window.scrollY > 400);
    window.addEventListener("scroll", h);
    return () => window.removeEventListener("scroll", h);
  }, []);
  if (!show) return null;
  return (
    <button
      onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
      className="fixed bottom-24 left-4 sm:bottom-6 sm:left-6 z-40 w-10 h-10 bg-white border border-[#DDE2DD] rounded-xl shadow-md flex items-center justify-center hover:bg-[#E4F0E4] hover:border-[#28722C] transition-all"
      aria-label="بازگشت به بالا"
    >
      <ChevronUp size={16} className="text-[#28722C]" />
    </button>
  );
}
