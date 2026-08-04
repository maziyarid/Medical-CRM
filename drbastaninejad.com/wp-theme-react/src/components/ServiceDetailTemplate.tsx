/**
 * ServiceDetailTemplate — single-service.php equivalent
 * Used by ALL 11 service detail pages.
 *
 * WP: PHP template hierarchy → single-service.php or page-{slug}.php
 * ACF field group: Service Details
 *   • service_highlights (repeater: icon, title, body)
 *   • service_procedure_steps (repeater)
 *   • key_takeaways_list (textarea)
 *   • faq_items (repeater)
 *   • related_services (relationship)
 *   • quick_facts (repeater: label, value)
 *
 * BACKEND NOTE:
 *   All { BACKEND } sections replaced with dynamic PHP/ACF calls in production.
 */
import { Phone, CheckCircle, ChevronLeft } from "lucide-react";
import Breadcrumb from "@/components/Breadcrumb";
import { FAQSection, AuthorBio, Testimonials, ContactCTA, MedicalDisclaimer, type FAQItem } from "@/components/ArticleAtoms";
import { SHARED_TESTIMONIALS } from "@/components/BlogPostTemplate";
import { FOOTER_SERVICES, BOOKING_URL } from "@/data/site";
import { Link } from "react-router";

interface QuickFact  { label: string; val: string; }
interface Highlight  { icon: React.ReactNode; title: string; body: string; }
interface BulletItem { text: string; }

interface ServiceDetailTemplateProps {
  categoryLabel: string;
  title:         string;
  lead:          string;
  highlights:    Highlight[];
  quickFacts:    QuickFact[];
  bodyContent:   React.ReactNode;     // main article content
  keyTakeaways:  string[];
  faqs:          FAQItem[];
  breadcrumb?:   { label: string; href?: string }[];
}

export default function ServiceDetailTemplate({
  categoryLabel, title, lead,
  highlights, quickFacts,
  bodyContent, keyTakeaways, faqs,
  breadcrumb = [],
}: ServiceDetailTemplateProps) {
  return (
    <>
      <Breadcrumb items={[{ label: "خانه", href: "/" }, { label: "خدمات", href: "/services" }, ...breadcrumb, { label: title }]} />

      {/* Service Hero — { BACKEND } ACF: hero_label, page_title, hero_lead */}
      <header className="bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6">
        <div className="max-w-[1200px] mx-auto">
          <div className="inline-block text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full mb-4 border border-[#28722C]/30">
            {categoryLabel}
          </div>
          {/* { BACKEND } WP: the_title() */}
          <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">{title}</h1>
          {/* { BACKEND } ACF: hero_lead */}
          <p className="text-white/75 text-lg leading-relaxed max-w-xl">{lead}</p>
          <div className="flex flex-wrap gap-3 mt-6">
            <a href={BOOKING_URL} rel="noopener"
              className="flex items-center gap-2 px-5 py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
              <Phone size={14} />تشکیل پرونده اولیه
            </a>
            <a href="#faq"
              className="flex items-center gap-2 px-5 py-3 border border-white/30 text-white rounded-xl text-sm font-semibold hover:bg-white/10 transition-colors">
              سوالات متداول
            </a>
          </div>
        </div>
      </header>

      <div className="max-w-[1200px] mx-auto px-4 sm:px-6 py-10">
        <div className="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-8">
          <main id="main-content">

            {/* Highlights — { BACKEND } ACF repeater: service_highlights */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10">
              {highlights.map((h, i) => (
                <div key={i} className="bg-white rounded-2xl border border-[#DDE2DD] p-4 text-center">
                  <div className="w-10 h-10 rounded-xl bg-[#E4F0E4] flex items-center justify-center mx-auto mb-3">
                    {h.icon}
                  </div>
                  <p className="text-sm font-bold text-[#25272C] mb-1">{h.title}</p>
                  <p className="text-xs text-[#6A7078] leading-relaxed">{h.body}</p>
                </div>
              ))}
            </div>

            {/* Article body — { BACKEND } WP: the_content() or ACF content sections */}
            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-6 sm:p-8 mb-8">
              {bodyContent}

              {/* Key Takeaways — { BACKEND } ACF: key_takeaways_list */}
              <aside className="bg-[#E4F0E4] border border-[#28722C]/20 rounded-2xl p-5 mb-6 mt-8" aria-label="نکات کلیدی">
                <div className="flex items-center gap-2 mb-3">
                  <CheckCircle size={16} className="text-[#28722C]" />
                  <h3 className="font-bold text-[#28722C]">نکات کلیدی</h3>
                </div>
                <ul className="space-y-2">
                  {keyTakeaways.map((item, i) => (
                    <li key={i} className="flex items-start gap-2 text-sm text-[#25272C]">
                      <CheckCircle size={12} className="text-[#28722C] flex-shrink-0 mt-1" />{item}
                    </li>
                  ))}
                </ul>
              </aside>

              {/* Mid CTA — { BACKEND } ACF: mid_cta_content */}
              <div className="flex flex-col sm:flex-row items-center gap-4 bg-[#25272C] rounded-2xl p-5 text-white">
                <div className="flex-1">
                  <p className="font-bold text-sm mb-1">آیا این روش برای شما مناسب است؟</p>
                  <p className="text-white/70 text-xs">پرونده اولیه خود را تکمیل کنید تا دکتر وضعیت بینی شما را بررسی کند.</p>
                </div>
                <a href={BOOKING_URL} rel="noopener"
                  className="flex-shrink-0 flex items-center gap-1.5 px-4 py-2.5 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
                  تشکیل پرونده
                </a>
              </div>
            </div>

            {/* FAQ — { BACKEND } ACF repeater: faq_items */}
            <div id="faq" className="mb-8">
              <FAQSection faqs={faqs} heading={`سوالات متداول — ${title}`} />
            </div>

            <MedicalDisclaimer />

            {/* Testimonials — { BACKEND } ACF relationship or CPT */}
            <div className="mt-10">
              <Testimonials items={SHARED_TESTIMONIALS.slice(0, 2)} />
            </div>

            {/* Author bio */}
            <div className="mt-10">
              <AuthorBio />
            </div>
          </main>

          {/* Sidebar */}
          <aside className="space-y-5">
            {/* Booking card */}
            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-5 sticky top-20">
              <h4 className="font-bold text-[#25272C] mb-2">مشاوره رایگان</h4>
              <p className="text-xs text-[#6A7078] mb-4 leading-relaxed">برای اطلاعات بیشتر و بررسی وضعیت بینی خود، پرونده اولیه تکمیل کنید.</p>
              <a href={BOOKING_URL} rel="noopener"
                className="flex items-center justify-center gap-2 w-full py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors">
                <Phone size={14} />تشکیل پرونده اولیه
              </a>
              <a href="tel:02186087250"
                className="flex items-center justify-center gap-2 w-full py-2.5 mt-2 border border-[#DDE2DD] text-[#25272C] rounded-xl text-sm font-medium hover:border-[#28722C] hover:text-[#28722C] transition-colors">
                ۰۲۱۸۶۰۸۷۲۵۰
              </a>
            </div>

            {/* Related services — { BACKEND } ACF: related_services (relationship field) */}
            <div className="bg-white rounded-2xl border border-[#DDE2DD] p-5">
              <h4 className="font-bold text-[#25272C] mb-4">خدمات مرتبط</h4>
              <div className="space-y-2">
                {FOOTER_SERVICES.slice(0, 5).map(s => (
                  <Link key={s.href} to={s.href}
                    className="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-[#E4F0E4] transition-colors group">
                    <span className="text-sm text-[#25272C] group-hover:text-[#28722C]">{s.label}</span>
                    <ChevronLeft size={13} className="text-[#DDE2DD] group-hover:text-[#28722C]" />
                  </Link>
                ))}
              </div>
            </div>

            {/* Quick info — { BACKEND } ACF: service_quick_facts (repeater) */}
            <div className="bg-[#E4F0E4] rounded-2xl border border-[#28722C]/20 p-5">
              <h4 className="font-bold text-[#28722C] mb-4">اطلاعات سریع</h4>
              {quickFacts.map(({ label, val }) => (
                <div key={label} className="flex justify-between items-center py-2 border-b border-[#28722C]/10 last:border-0 text-sm">
                  <span className="text-[#6A7078]">{label}</span>
                  <span className="font-semibold text-[#25272C]">{val}</span>
                </div>
              ))}
            </div>
          </aside>
        </div>

        {/* Contact CTA */}
        <div className="mt-10">
          <ContactCTA />
        </div>
      </div>
    </>
  );
}
