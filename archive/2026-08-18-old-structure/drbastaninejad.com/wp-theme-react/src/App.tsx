/**
 * App Router — React Router v7 client-side routing
 *
 * WP equivalent: WordPress template hierarchy routing
 *   /                        → front-page.php (HomePage)
 *   /about                   → page-about.php (AboutPage)
 *   /services                → page-services.php (ServicesPage)
 *   /services/:slug          → single-service.php (ServiceDetailTemplate)
 *   /blog                    → archive.php (BlogListPage)
 *   /blog/:slug              → single-post.php (BlogPostTemplate)
 *   /gallery                 → page-gallery.php (GalleryPage)
 *   /faq                     → page-faq.php (FAQPage)
 *   /contact                 → page-contact.php (ContactPage)
 *   /booking                 → page-booking.php (BookingPage)
 *   /category/:slug          → category.php (CategoryArchivePage)
 *   /tag/:slug               → tag.php (TagArchivePage)
 *   /tag                     → Tag Hub (TagArchivePage)
 *   /sitemap                 → page-sitemap.php (SitemapPage)
 *   /legal/privacy           → page-privacy.php (PrivacyPage)
 *   /legal/terms             → page-terms.php (TermsPage)
 *   /legal/cancellation      → page-cancellation.php (CancellationPage)
 *   *                        → 404.php (NotFoundPage)
 */
import { BrowserRouter, Routes, Route } from "react-router";
import RootLayout from "@/components/RootLayout";
import HomePage from "@/pages/HomePage";
import AboutPage from "@/pages/AboutPage";
import ServicesPage from "@/pages/ServicesPage";
import GalleryPage from "@/pages/GalleryPage";
import BlogListPage from "@/pages/BlogListPage";
import FAQPage from "@/pages/FAQPage";
import ContactPage from "@/pages/ContactPage";
import BookingPage from "@/pages/BookingPage";
import NotFoundPage from "@/pages/NotFoundPage";
import CategoryArchivePage from "@/pages/CategoryArchivePage";
import TagArchivePage from "@/pages/TagArchivePage";
import SitemapPage from "@/pages/SitemapPage";
import PrivacyPage from "@/pages/legal/PrivacyPage";
import TermsPage from "@/pages/legal/TermsPage";
import CancellationPage from "@/pages/legal/CancellationPage";

/* ── Blog post pages ── */
import BlogRhinoplasty from "@/pages/blog/BlogRhinoplasty";
import BlogRhinoplastyRevision from "@/pages/blog/BlogRhinoplastyRevision";
import {
  BlogPreOpSteps, BlogPostOpCare, BlogFleshyRhinoplasty,
  BlogChooseSurgeon, BlogRevisionGuide, BlogComplications,
  BlogMaleRhinoplasty, BlogNutrition, BlogATLRemoval,
} from "@/pages/blog/BlogOtherPosts";

/* ── Service pages ── */
import {
  ServiceRhinoplastyPrimary, ServiceRhinoplastyRevision,
  ServiceRhinoplastyFleshy, ServiceRhinoplastyBony,
  ServiceRhinoplastyNatural, ServiceRhinoplastyFantasy,
  ServiceHumpRemoval, ServiceSeptoplasty,
  ServiceTurbinoplasty, ServiceSinusEndoscopy,
  ServiceRhinoplastyOverview,
} from "@/pages/services/AllServicePages";

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<RootLayout />}>
          {/* ── Core pages ── */}
          <Route index element={<HomePage />} />
          <Route path="about"   element={<AboutPage />} />
          <Route path="gallery" element={<GalleryPage />} />
          <Route path="faq"     element={<FAQPage />} />
          <Route path="contact" element={<ContactPage />} />
          <Route path="booking" element={<BookingPage />} />
          <Route path="sitemap" element={<SitemapPage />} />

          {/* ── Services ── */}
          <Route path="services" element={<ServicesPage />} />
          <Route path="services/rhinoplasty"          element={<ServiceRhinoplastyOverview />} />
          <Route path="services/rhinoplasty-primary"  element={<ServiceRhinoplastyPrimary />} />
          <Route path="services/rhinoplasty-revision" element={<ServiceRhinoplastyRevision />} />
          <Route path="services/rhinoplasty-fleshy"   element={<ServiceRhinoplastyFleshy />} />
          <Route path="services/rhinoplasty-bony"     element={<ServiceRhinoplastyBony />} />
          <Route path="services/rhinoplasty-natural"  element={<ServiceRhinoplastyNatural />} />
          <Route path="services/rhinoplasty-fantasy"  element={<ServiceRhinoplastyFantasy />} />
          <Route path="services/hump-removal"         element={<ServiceHumpRemoval />} />
          <Route path="services/septoplasty"          element={<ServiceSeptoplasty />} />
          <Route path="services/turbinoplasty"        element={<ServiceTurbinoplasty />} />
          <Route path="services/sinus-endoscopy"      element={<ServiceSinusEndoscopy />} />

          {/* ── Blog ── */}
          <Route path="blog" element={<BlogListPage />} />
          <Route path="blog/rhinoplasty"            element={<BlogRhinoplasty />} />
          <Route path="blog/rhinoplasty-revision"   element={<BlogRhinoplastyRevision />} />
          <Route path="blog/pre-op-steps"           element={<BlogPreOpSteps />} />
          <Route path="blog/post-op-care"           element={<BlogPostOpCare />} />
          <Route path="blog/rhinoplasty-fleshy"     element={<BlogFleshyRhinoplasty />} />
          <Route path="blog/choose-surgeon"         element={<BlogChooseSurgeon />} />
          <Route path="blog/revision-rhinoplasty"   element={<BlogRevisionGuide />} />
          <Route path="blog/rhinoplasty-complications" element={<BlogComplications />} />
          <Route path="blog/male-rhinoplasty"       element={<BlogMaleRhinoplasty />} />
          <Route path="blog/nutrition-rhinoplasty"  element={<BlogNutrition />} />
          <Route path="blog/atl-removal"            element={<BlogATLRemoval />} />

          {/* ── Taxonomy archives ── */}
          <Route path="category/:slug" element={<CategoryArchivePage />} />
          <Route path="tag"            element={<TagArchivePage />} />
          <Route path="tag/:slug"      element={<TagArchivePage />} />

          {/* ── Legal ── */}
          <Route path="legal/privacy"      element={<PrivacyPage />} />
          <Route path="legal/terms"        element={<TermsPage />} />
          <Route path="legal/cancellation" element={<CancellationPage />} />

          {/* ── Catch-all → 404 (NOT Home) ── */}
          <Route path="*" element={<NotFoundPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
