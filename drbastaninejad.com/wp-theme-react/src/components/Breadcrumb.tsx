/**
 * Breadcrumb — site-wide breadcrumb nav
 * PHP: include get_template_part('components/breadcrumb');
 *      or yoast_breadcrumb()
 * Schema: BreadcrumbList JSON-LD injected in <head>
 */
import { Link } from "react-router";
import { ChevronLeft } from "lucide-react";

interface BreadcrumbItem { label: string; href?: string; }

export default function Breadcrumb({ items }: { items: BreadcrumbItem[] }) {
  return (
    <nav className="bg-white border-b border-[#DDE2DD] px-4 sm:px-6 py-2.5" aria-label="مسیر صفحه">
      <div className="max-w-[1200px] mx-auto">
        <ol className="flex flex-wrap items-center gap-1.5 text-xs text-[#6A7078]">
          {items.map((b, i) => (
            <li key={b.label} className="flex items-center gap-1.5">
              {i > 0 && <ChevronLeft size={11} className="text-[#DDE2DD]" />}
              {b.href && i < items.length - 1
                ? <Link to={b.href} className="hover:text-[#28722C] transition-colors">{b.label}</Link>
                : <span className="text-[#28722C] font-semibold" aria-current={i === items.length - 1 ? "page" : undefined}>{b.label}</span>
              }
            </li>
          ))}
        </ol>
      </div>
    </nav>
  );
}
