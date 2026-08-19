import type { Metadata } from "next";
import "./globals.css";
import { Footer, Header, MobileDock } from "./components/site-chrome";

export const metadata: Metadata = {
  title: {
    default: "دکتر شاهین باستانی‌نژاد | جراحی زیبایی بینی و صورت",
    template: "%s | دکتر باستانی‌نژاد",
  },
  description:
    "وب‌سایت رسمی دکتر شاهین باستانی‌نژاد، جراح و متخصص گوش، گلو و بینی و جراحی پلاستیک بینی در تهران.",
  other: {
    "codex-preview": "development",
  },
  icons: {
    icon: "/favicon.svg",
    shortcut: "/favicon.svg",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="fa" dir="rtl" suppressHydrationWarning>
      <body>
        <Header />
        <main>{children}</main>
        <Footer />
        <MobileDock />
      </body>
    </html>
  );
}
