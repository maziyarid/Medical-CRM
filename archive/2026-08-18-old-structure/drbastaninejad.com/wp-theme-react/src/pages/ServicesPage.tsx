/**
 * ServicesPage — services.php / page-services.php equivalent
 * WP: WP_Query with post_type='service', ACF fields for each service card
 */
import { Link } from "react-router";
import { ArrowLeft, Calendar } from "lucide-react";
import { COSMETIC_SERVICES, FUNCTIONAL_SERVICES } from "@/data/site";
import Breadcrumb from "@/components/Breadcrumb";
import JsonLd, { buildBreadcrumbSchema } from "@/components/JsonLd";
import ServiceIcon from "@/components/ServiceIcon";

export default function ServicesPage() {
  const crumbs = [{ label: "خانه", href: "/" }, { label: "همه خدمات" }];
  return (
    <>
      <JsonLd id="jsonld-services" data={buildBreadcrumbSchema(crumbs)} />
      <Breadcrumb items={crumbs} />

      {/* Page hero */}
      <section className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto text-center">
          <span className="text-[#28722C] font-bold text-sm tracking-wider">تخصص‌های ما</span>
          <h1 className="text-4xl sm:text-5xl font-black mt-2 mb-4">خدمات جراحی بینی</h1>
          <p className="text-white/70 text-lg max-w-xl mx-auto">
            از راینوپلاستی زیبایی تا جراحی‌های درمانی — همه با بالاترین استانداردهای پزشکی
          </p>
        </div>
      </section>

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-16">

        {/* Cosmetic */}
        <div className="mb-16">
          <div className="flex items-center gap-3 mb-8">
            <div className="w-1 h-8 bg-[#28722C] rounded-full" />
            <h2 className="text-2xl font-black text-[#25272C]">خدمات زیبایی</h2>
          </div>
          {/* PHP: WP_Query service_type='cosmetic' */}
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {COSMETIC_SERVICES.map(s => (
              <Link key={s.id} to={`/services/${s.slug}`}
                className="group bg-white border border-[#DDE2DD] rounded-2xl p-6 hover:border-[#28722C]/40 hover:shadow-lg hover:shadow-[#28722C]/5 transition-all">
                <div className="w-12 h-12 bg-[#E4F0E4] rounded-2xl flex items-center justify-center mb-4 group-hover:bg-[#28722C] transition-colors [&>span>span>svg]:w-6 [&>span>span>svg]:h-6 [&>span>svg]:w-6 [&>span>svg]:h-6">
                  <ServiceIcon slug={s.slug} FallbackIcon={s.icon} size={24}
                    className="text-[#28722C] group-hover:text-white transition-colors" />
                </div>
                <h3 className="text-lg font-bold text-[#25272C] mb-1">{s.title}</h3>
                <p className="text-sm text-[#28722C] font-medium mb-3">{s.subtitle}</p>
                <p className="text-sm text-[#545B64] leading-relaxed line-clamp-3">{s.desc}</p>
                <div className="flex items-center gap-1 mt-4 text-[#28722C] text-sm font-bold group-hover:gap-2 transition-all">
                  اطلاعات بیشتر <ArrowLeft size={14} />
                </div>
              </Link>
            ))}
          </div>
        </div>

        {/* Functional */}
        <div className="mb-16">
          <div className="flex items-center gap-3 mb-8">
            <div className="w-1 h-8 bg-blue-500 rounded-full" />
            <h2 className="text-2xl font-black text-[#25272C]">خدمات درمانی</h2>
          </div>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {FUNCTIONAL_SERVICES.map(s => (
              <Link key={s.id} to={`/services/${s.slug}`}
                className="group bg-white border border-[#DDE2DD] rounded-2xl p-6 hover:border-blue-400/40 hover:shadow-lg transition-all">
                <div className="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-blue-500 transition-colors [&>span>span>svg]:w-6 [&>span>span>svg]:h-6 [&>span>svg]:w-6 [&>span>svg]:h-6">
                  <ServiceIcon slug={s.slug} FallbackIcon={s.icon} size={24}
                    className="text-blue-500 group-hover:text-white transition-colors" />
                </div>
                <h3 className="text-lg font-bold text-[#25272C] mb-1">{s.title}</h3>
                <p className="text-sm text-blue-600 font-medium mb-3">{s.subtitle}</p>
                <p className="text-sm text-[#545B64] leading-relaxed line-clamp-3">{s.desc}</p>
                <div className="flex items-center gap-1 mt-4 text-blue-600 text-sm font-bold group-hover:gap-2 transition-all">
                  اطلاعات بیشتر <ArrowLeft size={14} />
                </div>
              </Link>
            ))}
          </div>
        </div>

        {/* CTA */}
        <div className="bg-gradient-to-br from-[#28722C] to-[#1a4e1d] rounded-3xl p-10 text-white text-center">
          <h2 className="text-2xl font-black mb-3">می‌خواهید بدانید کدام خدمت برای شما مناسب است؟</h2>
          <p className="text-white/80 mb-6">با دکتر باستانی‌نژاد مشاوره رایگان داشته باشید.</p>
          <Link to="/booking"
            className="inline-flex items-center gap-2 bg-white text-[#28722C] font-black px-8 py-4 rounded-2xl hover:bg-[#E4F0E4] transition-colors">
            <Calendar size={18} /> رزرو مشاوره رایگان
          </Link>
        </div>
      </div>
    </>
  );
}
