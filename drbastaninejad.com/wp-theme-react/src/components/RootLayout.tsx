/**
 * RootLayout — wraps every page with shared chrome
 * PHP: header.php + footer.php + wp_footer()
 */
import { Outlet } from "react-router";
import SiteNav from "@/components/SiteNav";
import SiteFooter from "@/components/SiteFooter";
import ChatyWidget from "@/components/ChatyWidget";
import ScrollToTop from "@/components/ScrollToTop";

export default function RootLayout() {
  return (
    <div dir="rtl" lang="fa-IR" className="min-h-screen bg-[#F9F6F1] text-[#25272C]"
      style={{ fontFamily: "'Irancell', 'Tahoma', Arial, system-ui, sans-serif" }}>
      <SiteNav />
      <main>
        <Outlet />
      </main>
      <SiteFooter />
      <ChatyWidget />
      <ScrollToTop />
    </div>
  );
}
