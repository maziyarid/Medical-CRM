/**
 * JsonLd — SEO schema injection component
 * Injects JSON-LD structured data into <head> via a <script> tag.
 *
 * WP NOTE: In production, render via wp_head() action:
 *   add_action('wp_head', function() { /* PHP echo JSON-LD *\/ });
 *
 * Supported schemas: MedicalWebPage, FAQPage, LocalBusiness,
 *   Person, BreadcrumbList, Article, Speakable
 */
import { useEffect } from "react";

interface JsonLdProps {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  data: Record<string, any> | Record<string, any>[];
  id?: string; // stable ID for dedup
}

export default function JsonLd({ data, id = "jsonld" }: JsonLdProps) {
  const json = JSON.stringify(Array.isArray(data) ? data : [data]);

  useEffect(() => {
    // Remove previous script with same id to avoid duplicates on navigation
    const prev = document.getElementById(id);
    if (prev) prev.remove();

    const script = document.createElement("script");
    script.id = id;
    script.type = "application/ld+json";
    script.textContent = json;
    document.head.appendChild(script);

    return () => {
      const s = document.getElementById(id);
      if (s) s.remove();
    };
  }, [json, id]);

  return null;
}

/* ── Pre-built schema builders ── */

export const LOCAL_BUSINESS_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "MedicalBusiness",
  "@id": "https://drbastaninejad.com/#clinic",
  name: "کلینیک دکتر شاهین باستانی‌نژاد",
  alternateName: "Dr. Shahin Bastaninejad Clinic",
  url: "https://drbastaninejad.com",
  telephone: ["+982186087250", "+982188205606"],
  priceRange: "$$",
  medicalSpecialty: "Plastic Surgery",
  image: "https://drbastaninejad.com/images/Doctor/DrShahinBastaninejadPortrait1.png",
  description: "کلینیک تخصصی جراحی پلاستیک بینی دکتر شاهین باستانی‌نژاد — رینوپلاستی، سپتوپلاستی، آندوسکوپی سینوس",
  address: {
    "@type": "PostalAddress",
    streetAddress: "خیابان نلسون ماندلا، نرسیده به چهارراه جهان کودک، خیابان صانعی، ساختمان نور، پلاک ۱، واحد ۶",
    addressLocality: "تهران",
    addressCountry: "IR",
    postalCode: "1514773119",
  },
  geo: {
    "@type": "GeoCoordinates",
    latitude: 35.758881,
    longitude: 51.413824,
  },
  openingHoursSpecification: [
    { "@type": "OpeningHoursSpecification", dayOfWeek: "Saturday", opens: "15:00", closes: "19:00" },
    { "@type": "OpeningHoursSpecification", dayOfWeek: "Wednesday", opens: "15:00", closes: "19:00" },
  ],
  sameAs: [
    "https://www.instagram.com/dr.shahin.bastaninejad/",
    "https://www.youtube.com/@Drshahinbastaninejad",
    "https://t.me/dr_bastaninejad",
  ],
};

export const DOCTOR_SCHEMA = {
  "@context": "https://schema.org",
  "@type": "Person",
  "@id": "https://drbastaninejad.com/#doctor",
  name: "دکتر شاهین باستانی‌نژاد",
  alternateName: "Dr. Shahin Bastaninejad",
  jobTitle: "فوق‌تخصص جراحی پلاستیک بینی",
  medicalSpecialty: "Otolaryngology, Plastic Surgery",
  worksFor: { "@id": "https://drbastaninejad.com/#clinic" },
  url: "https://drbastaninejad.com/about",
  alumniOf: [
    { "@type": "CollegeOrUniversity", name: "دانشگاه علوم پزشکی اصفهان" },
    { "@type": "CollegeOrUniversity", name: "دانشگاه علوم پزشکی تهران" },
  ],
  memberOf: [
    { "@type": "MedicalOrganization", name: "انجمن جراحی پلاستیک بینی ایران" },
    { "@type": "MedicalOrganization", name: "ISAPS — International Society of Aesthetic Plastic Surgery" },
  ],
};

export function buildFaqSchema(faqs: { question: string; answer: string }[]) {
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: faqs.map(f => ({
      "@type": "Question",
      name: f.question,
      acceptedAnswer: { "@type": "Answer", text: f.answer },
    })),
  };
}

export function buildBreadcrumbSchema(items: { label: string; href?: string }[]) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: items.map((item, i) => ({
      "@type": "ListItem",
      position: i + 1,
      name: item.label,
      ...(item.href ? { item: `https://drbastaninejad.com${item.href}` } : {}),
    })),
  };
}

export function buildArticleSchema(opts: {
  title: string;
  excerpt: string;
  publishDate: string;
  modifiedDate: string;
  url: string;
  image: string;
}) {
  return {
    "@context": "https://schema.org",
    "@type": "MedicalWebPage",
    name: opts.title,
    headline: opts.title,
    description: opts.excerpt,
    datePublished: opts.publishDate,
    dateModified: opts.modifiedDate,
    url: `https://drbastaninejad.com${opts.url}`,
    image: opts.image,
    author: { "@id": "https://drbastaninejad.com/#doctor" },
    publisher: { "@id": "https://drbastaninejad.com/#clinic" },
    medicalAudience: { "@type": "MedicalAudience", audienceType: "بیمار و خانواده بیمار" },
    speakable: {
      "@type": "SpeakableSpecification",
      cssSelector: ["h1", ".speakable"],
    },
  };
}
