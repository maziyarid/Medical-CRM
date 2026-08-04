/**
 * HomePage — front-page.php / home.php equivalent
 * All sections taken from "Iranian Rhinoplasty Landing Page" Figma design.
 *
 * WP: ACF Options Page drives most dynamic content.
 *     Each section is a get_template_part() call in the WP version.
 */
import { useState, useRef, useCallback, useEffect } from "react";
import { Link } from "react-router";
import {
  Phone, MapPin, Clock, ChevronDown, ChevronLeft, ChevronRight,
  Instagram, Star, Award, CheckCircle, Stethoscope, Scissors, Heart,
  ArrowLeft, ArrowRight, MessageCircle, Calendar, ExternalLink, BookOpen,
  Shield, Users, TrendingUp, Eye,
} from "lucide-react";
import {
  DOCTOR_NAME, BOOKING_URL, PHONES, INSTAGRAM as IG_URL, ADDRESS,
  TRUST_BADGES, COSMETIC_SERVICES, FUNCTIONAL_SERVICES, TIMELINE,
  CERTIFICATES, PROCESS_STEPS, HOME_FAQS, BLOG_POSTS, GALLERY_ITEMS,
  STATS,
} from "@/data/site";

/* ── Hero ── */
const HERO_IMG   = "/images/Doctor/Dr Shahin Bastani Nejad (1).webp";
const DOCTOR_IMG = "/images/Doctor/DrShahinBastaninejadPortrait1.png";
const ABOUT_BG   = "/images/Doctor/Dr Shahin Bastani Nejad (5).webp";

/* ─── Hero Section ─── */
function HeroSection() {
  return (
    <section className="relative min-h-screen flex items-center overflow-hidden">
      <div className="absolute inset-0">
        <img src={HERO_IMG} alt="کلینیک جراحی بینی" className="w-full h-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-l from-black/80 via-black/60 to-black/30" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent" />
      </div>
      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-28 pb-20">
        <div className="grid lg:grid-cols-2 gap-16 items-center">
          <div className="text-white space-y-8">
            <div className="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 text-sm">
              <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse" />
              جراح متخصص بینی در تهران
            </div>
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-black leading-tight">
              دستیابی به زیبایی<br />
              <span className="text-transparent bg-clip-text bg-gradient-to-l from-green-300 to-emerald-200">طبیعی</span>{" "}
              با حفظ<br />عملکرد تنفسی
            </h1>
            <p className="text-xl text-white/75 leading-relaxed max-w-lg">
              {DOCTOR_NAME} — دانشیار دانشگاه علوم پزشکی تهران، با بیش از ۱۵ سال تجربه تخصصی و ۲۰۰۰+ عمل موفق
            </p>
            <div className="flex flex-wrap gap-4">
              <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer"
                className="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-8 py-4 rounded-2xl transition-all shadow-xl shadow-primary/30 hover:scale-105 text-lg">
                <Calendar className="w-5 h-5" /> رزرو مشاوره رایگان
              </a>
              <Link to="/gallery"
                className="flex items-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/30 text-white font-bold px-8 py-4 rounded-2xl transition-all text-lg">
                <Eye className="w-5 h-5" /> مشاهده گالری
              </Link>
            </div>
            <div className="flex flex-wrap gap-8 pt-2">
              {STATS.map(s => (
                <div key={s.label} className="text-center">
                  <p className="text-3xl font-black">{s.val}</p>
                  <p className="text-sm text-white/60">{s.label}</p>
                </div>
              ))}
            </div>
          </div>
          <div className="hidden lg:flex justify-center">
            <div className="relative">
              <div className="w-80 h-[420px] rounded-3xl overflow-hidden border-4 border-white/20 shadow-2xl">
                <img src={DOCTOR_IMG} alt={DOCTOR_NAME} className="w-full h-full object-cover" />
                <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
                <div className="absolute bottom-0 inset-x-0 p-6 text-white">
                  <p className="font-bold text-lg">{DOCTOR_NAME}</p>
                  <p className="text-white/75 text-sm">متخصص گوش، حلق و بینی</p>
                  <p className="text-white/75 text-sm">دانشیار دانشگاه علوم پزشکی تهران</p>
                </div>
              </div>
              <div className="absolute -right-8 top-12 bg-white rounded-2xl p-4 shadow-xl">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
                    <Award className="w-5 h-5 text-primary" />
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">رتبه بورد</p>
                    <p className="font-bold text-sm">رتبه ۴ کشوری</p>
                  </div>
                </div>
              </div>
              <div className="absolute -left-8 bottom-24 bg-white rounded-2xl p-4 shadow-xl">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center">
                    <Star className="w-5 h-5 text-accent" />
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">رضایت بیمار</p>
                    <p className="font-bold text-sm">۹۸٪ موفقیت</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
        <ChevronDown className="w-8 h-8 text-white/60" />
      </div>
    </section>
  );
}

/* ─── Trust Strip ─── */
function TrustStrip() {
  return (
    <section className="bg-primary py-10">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
          {TRUST_BADGES.map((b, i) => (
            <div key={i} className="flex flex-col items-center text-center gap-3 group">
              <div className="w-12 h-12 bg-white/15 rounded-2xl flex items-center justify-center group-hover:bg-white/25 transition-colors">
                <b.icon className="w-6 h-6 text-white" />
              </div>
              <div>
                <p className="text-white font-bold text-sm leading-snug">{b.title}</p>
                <p className="text-white/60 text-xs mt-1">{b.subtitle}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─── Services Section ─── */
function ServicesSection() {
  const [tab, setTab] = useState<"cosmetic" | "functional">("cosmetic");
  const [activeId, setActiveId] = useState(1);
  const services = tab === "cosmetic" ? COSMETIC_SERVICES : FUNCTIONAL_SERVICES;
  const active = services.find(s => s.id === activeId) ?? services[0];
  useEffect(() => { setActiveId(services[0].id); }, [tab]);

  return (
    <section id="services" className="py-24 bg-background">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <span className="text-primary font-bold text-sm tracking-wider">تخصص‌های ما</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">خدمات جراحی بینی</h2>
          <p className="text-muted-foreground text-lg">از راینوپلاستی زیبایی تا جراحی‌های درمانی، همه خدمات با بالاترین استاندارد</p>
        </div>
        <div className="flex justify-center mb-10">
          <div className="bg-muted rounded-2xl p-1.5 flex gap-1">
            {[{ k: "cosmetic", l: "خدمات زیبایی" }, { k: "functional", l: "خدمات درمانی" }].map(t => (
              <button key={t.k} onClick={() => setTab(t.k as typeof tab)}
                className={`px-6 py-3 rounded-xl font-bold text-sm transition-all ${tab === t.k ? "bg-primary text-white shadow-lg" : "text-muted-foreground hover:text-foreground"}`}>
                {t.l}
              </button>
            ))}
          </div>
        </div>
        <div className="grid lg:grid-cols-3 gap-6">
          <div className="flex flex-col gap-2">
            {services.map(s => {
              const Icon = s.icon;
              const on = s.id === activeId;
              return (
                <button key={s.id} onClick={() => setActiveId(s.id)}
                  className={`flex items-center gap-4 p-4 rounded-2xl text-right transition-all ${on ? "bg-primary text-white shadow-lg shadow-primary/20" : "bg-white hover:bg-secondary border border-border text-foreground"}`}>
                  <div className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${on ? "bg-white/20" : "bg-primary/10"}`}>
                    <Icon className={`w-5 h-5 ${on ? "text-white" : "text-primary"}`} />
                  </div>
                  <div className="flex-1">
                    <p className="font-bold text-sm">{s.title}</p>
                    <p className={`text-xs mt-0.5 ${on ? "text-white/70" : "text-muted-foreground"}`}>{s.subtitle}</p>
                  </div>
                  {on && <ChevronLeft className="w-4 h-4 flex-shrink-0" />}
                </button>
              );
            })}
          </div>
          <div className="lg:col-span-2">
            <div className="bg-white rounded-3xl border border-border p-8 h-full">
              <div className="flex items-start gap-6 mb-6">
                <div className="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center flex-shrink-0">
                  <active.icon className="w-8 h-8 text-primary" />
                </div>
                <div>
                  <h3 className="text-2xl font-black text-foreground">{active.title}</h3>
                  <p className="text-primary font-medium text-sm mt-1">{active.subtitle}</p>
                </div>
              </div>
              <p className="text-muted-foreground leading-loose text-lg mb-8">{active.desc}</p>
              <div className="grid sm:grid-cols-2 gap-4 mb-8">
                {["مشاوره تخصصی رایگان", "تکنیک‌های روز دنیا", "بیهوشی ایمن", "مراقبت پس از عمل"].map(f => (
                  <div key={f} className="flex items-center gap-3">
                    <CheckCircle className="w-5 h-5 text-primary flex-shrink-0" />
                    <span className="text-foreground font-medium text-sm">{f}</span>
                  </div>
                ))}
              </div>
              <div className="flex flex-wrap gap-3">
                <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer"
                  className="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-6 py-3 rounded-2xl transition-all shadow-lg hover:shadow-primary/30">
                  <Calendar className="w-5 h-5" /> رزرو مشاوره
                </a>
                <Link to={`/services/${active.slug}`}
                  className="inline-flex items-center gap-2 border border-primary text-primary font-bold px-6 py-3 rounded-2xl hover:bg-primary/5 transition-all">
                  اطلاعات بیشتر <ArrowLeft className="w-4 h-4" />
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

/* ─── Stats Band ─── */
function StatsBand() {
  return (
    <section className="bg-gradient-to-l from-primary to-emerald-700 py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-8">
          {STATS.map(s => (
            <div key={s.label} className="text-center">
              <p className="text-5xl font-black text-white">{s.val}</p>
              <p className="text-xl font-bold text-white/90 mt-1">{s.label}</p>
              <p className="text-white/60 text-sm mt-1">{s.sub}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─── About Section ─── */
function AboutSection() {
  return (
    <section id="about" className="py-24 bg-secondary/30">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid lg:grid-cols-2 gap-16 items-start">
          <div className="relative">
            <div className="rounded-3xl overflow-hidden aspect-[4/5] shadow-2xl">
              <img src={ABOUT_BG} alt={DOCTOR_NAME} className="w-full h-full object-cover" />
              <div className="absolute inset-0 bg-gradient-to-t from-primary/70 via-transparent to-transparent" />
            </div>
            <div className="absolute -bottom-6 -right-4 lg:-right-8 max-w-sm bg-white rounded-3xl p-6 shadow-2xl border border-border">
              <div className="text-4xl text-primary/20 font-serif leading-none mb-2">"</div>
              <blockquote className="text-foreground text-sm leading-loose font-medium">
                مهمترین اصل در جراحی زیبایی بینی این است که از یک سادگی نامطبوع به پیچیدگی‌ای برسیم که ورای آن، سادگی مطبوعی حاصل شود… بینی عضوی است جهت نفس کشیدن.
              </blockquote>
              <footer className="mt-4 flex items-center gap-3">
                <div className="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                  <Star className="w-4 h-4 text-white" />
                </div>
                <p className="text-xs text-muted-foreground">{DOCTOR_NAME}</p>
              </footer>
            </div>
          </div>
          <div className="pt-8 lg:pt-0">
            <span className="text-primary font-bold text-sm tracking-wider">رزومه تخصصی</span>
            <h2 className="text-4xl font-black text-foreground mt-2 mb-6">{DOCTOR_NAME}</h2>
            <p className="text-muted-foreground text-lg leading-loose mb-8">
              دانشیار دانشگاه علوم پزشکی تهران و عضو هیئت علمی بیمارستان امیراعلم، دبیر کمیته علمی رینولوژی انجمن جراحی پلاستیک بینی ایران. با بیش از ۱۵ سال تجربه در جراحی‌های بینی اولیه، ترمیمی و درمانی.
            </p>
            <div className="grid sm:grid-cols-2 gap-3 mb-10">
              {["دانشگاه علوم پزشکی تهران", "بیمارستان امیراعلم تهران", "انجمن جراحی پلاستیک بینی ایران", "انجمن متخصصان گوش، حلق و بینی ایران"].map(a => (
                <div key={a} className="flex items-center gap-3 bg-white rounded-xl p-3 border border-border">
                  <CheckCircle className="w-5 h-5 text-primary flex-shrink-0" />
                  <span className="text-sm font-medium text-foreground">{a}</span>
                </div>
              ))}
            </div>
            <h3 className="text-lg font-bold text-foreground mb-6">مسیر تحصیل و تجربه</h3>
            <div className="relative">
              <div className="absolute right-5 top-0 bottom-0 w-0.5 bg-primary/20" />
              <div className="space-y-6">
                {TIMELINE.map((t, i) => {
                  const Icon = t.icon;
                  return (
                    <div key={i} className="flex gap-5">
                      <div className="relative flex-shrink-0">
                        <div className="w-10 h-10 bg-primary rounded-full flex items-center justify-center shadow-md shadow-primary/30 z-10 relative">
                          <Icon className="w-5 h-5 text-white" />
                        </div>
                      </div>
                      <div className="pb-2">
                        <span className="text-xs font-bold text-primary bg-primary/10 rounded-full px-3 py-1">{t.year}</span>
                        <p className="font-bold text-foreground mt-2">{t.title}</p>
                        <p className="text-muted-foreground text-sm mt-0.5">{t.org}</p>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

/* ─── Certificates Section ─── */
function CertificatesSection() {
  return (
    <section id="certificates" className="py-24 bg-background">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <span className="text-primary font-bold text-sm tracking-wider">گواهینامه‌ها</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">مدارک و افتخارات</h2>
          <p className="text-muted-foreground text-lg">تأییدیه‌ها و گواهینامه‌های معتبر ملی و بین‌المللی</p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
          {CERTIFICATES.map((cert, i) => (
            <div key={i} className="group bg-white border border-border rounded-2xl p-6 hover:border-primary/30 hover:shadow-lg hover:shadow-primary/5 transition-all">
              <div className="flex items-start gap-4">
                <div className="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-accent/20 transition-colors">
                  <Award className="w-5 h-5 text-accent" />
                </div>
                <p className="text-foreground font-medium text-sm leading-relaxed">{cert}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─── Before/After Slider ─── */
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
    const onUp = () => setDragging(false);
    window.addEventListener("mousemove", onMove);
    window.addEventListener("mouseup", onUp);
    return () => { window.removeEventListener("mousemove", onMove); window.removeEventListener("mouseup", onUp); };
  }, [dragging, updatePos]);

  const fallbackSrc = (e: React.SyntheticEvent<HTMLImageElement>) => { (e.target as HTMLImageElement).src = fallback; };

  return (
    <div ref={ref}
      className="relative w-full aspect-[3/4] rounded-2xl overflow-hidden cursor-col-resize select-none"
      onMouseDown={e => { setDragging(true); updatePos(e.clientX); }}>
      <img src={after}  alt="بعد" className="absolute inset-0 w-full h-full object-cover" onError={fallbackSrc} />
      <div className="absolute inset-0 overflow-hidden" style={{ clipPath: `inset(0 0 0 ${100 - pos}%)` }}>
        <img src={before} alt="قبل" className="absolute inset-0 w-full h-full object-cover" onError={fallbackSrc} />
      </div>
      <div className="absolute top-0 bottom-0 w-0.5 bg-white shadow-lg" style={{ right: `${pos}%` }}>
        <div className="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-8 h-8 bg-white rounded-full shadow-xl flex items-center justify-center">
          <ArrowRight className="w-3 h-3 text-primary" />
          <ArrowLeft  className="w-3 h-3 text-primary" />
        </div>
      </div>
      <div className="absolute bottom-3 right-3 bg-black/60 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full font-bold">قبل</div>
      <div className="absolute bottom-3 left-3  bg-primary/80 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full font-bold">بعد</div>
    </div>
  );
}

/* ─── Gallery Section ─── */
function GallerySection() {
  const [page, setPage] = useState(0);
  const PER = 4;
  const total = Math.ceil(GALLERY_ITEMS.length / PER);
  const visible = GALLERY_ITEMS.slice(page * PER, page * PER + PER);

  return (
    <section id="gallery" className="py-24 bg-muted/50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-6">
          <span className="text-primary font-bold text-sm tracking-wider">نتایج واقعی</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">گالری قبل و بعد</h2>
          <p className="text-muted-foreground text-lg">+۱۷۸ نمونه واقعی از نتایج جراحی بینی دکتر باستانی‌نژاد</p>
        </div>
        <div className="flex flex-wrap justify-center gap-6 mb-12">
          {STATS.map(s => (
            <div key={s.label} className="flex items-center gap-3 bg-white rounded-2xl px-5 py-3 shadow-sm border border-border">
              <p className="text-2xl font-black text-primary">{s.val}</p>
              <p className="text-sm font-medium text-muted-foreground">{s.label}</p>
            </div>
          ))}
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          {visible.map(item => (
            <BeforeAfterSlider key={item.id} before={item.before} after={item.after} fallback={item.fallback} />
          ))}
        </div>
        <div className="flex justify-center items-center gap-3">
          <button onClick={() => setPage(p => Math.max(0, p - 1))} disabled={page === 0}
            className="w-10 h-10 rounded-xl border border-border bg-white flex items-center justify-center hover:bg-secondary disabled:opacity-40 transition-colors">
            <ChevronRight className="w-5 h-5" />
          </button>
          {Array.from({ length: total }).map((_, i) => (
            <button key={i} onClick={() => setPage(i)}
              className={`w-10 h-10 rounded-xl font-bold text-sm transition-all ${page === i ? "bg-primary text-white" : "bg-white border border-border text-foreground hover:bg-secondary"}`}>
              {i + 1}
            </button>
          ))}
          <button onClick={() => setPage(p => Math.min(total - 1, p + 1))} disabled={page === total - 1}
            className="w-10 h-10 rounded-xl border border-border bg-white flex items-center justify-center hover:bg-secondary disabled:opacity-40 transition-colors">
            <ChevronLeft className="w-5 h-5" />
          </button>
        </div>
        <p className="text-center text-xs text-muted-foreground mt-6 max-w-xl mx-auto">
          تمام تصاویر با رضایت بیماران و رعایت کامل حریم خصوصی منتشر شده‌اند. نتایج ممکن است از فردی به فرد دیگر متفاوت باشد.
        </p>
        <div className="text-center mt-8">
          <Link to="/gallery" className="inline-flex items-center gap-2 border-2 border-primary text-primary font-bold px-8 py-3 rounded-2xl hover:bg-primary hover:text-white transition-all">
            مشاهده همه نمونه‌ها <ArrowLeft className="w-4 h-4" />
          </Link>
        </div>
      </div>
    </section>
  );
}

/* ─── Process Section ─── */
function ProcessSection() {
  const icons = [MessageCircle, CheckCircle, Scissors, Heart];
  return (
    <section className="py-24 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <span className="text-primary font-bold text-sm tracking-wider">مسیر درمان</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">فرایند جراحی در کلینیک ما</h2>
          <p className="text-muted-foreground text-lg">از اولین تماس تا بهبودی کامل، در هر قدم کنارتان هستیم</p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {PROCESS_STEPS.map((s, i) => {
            const Icon = icons[i];
            return (
              <div key={i} className="group bg-gradient-to-br from-white to-secondary/30 border border-border rounded-3xl p-6 hover:shadow-xl hover:shadow-primary/10 hover:border-primary/30 transition-all">
                <div className="text-5xl font-black text-primary/10 mb-4">{s.num}</div>
                <div className="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-primary/30 group-hover:scale-110 transition-transform">
                  <Icon className="w-6 h-6 text-white" />
                </div>
                <h3 className="text-xl font-black text-foreground mb-3">{s.title}</h3>
                <p className="text-muted-foreground text-sm leading-loose">{s.desc}</p>
              </div>
            );
          })}
        </div>
        <div className="text-center mt-12">
          <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer"
            className="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-10 py-4 rounded-2xl transition-all shadow-xl hover:shadow-primary/30 text-lg">
            <Calendar className="w-5 h-5" /> شروع مسیر درمان — رزرو مشاوره
          </a>
        </div>
      </div>
    </section>
  );
}

/* ─── FAQ Section ─── */
function HomeFAQSection() {
  const [openIdx, setOpenIdx] = useState<number | null>(0);
  const [cat, setCat] = useState("همه");
  const cats = ["همه", ...Array.from(new Set(HOME_FAQS.map(f => f.cat)))];
  const filtered = cat === "همه" ? HOME_FAQS : HOME_FAQS.filter(f => f.cat === cat);

  return (
    <section id="faq" className="py-24 bg-secondary/30">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <span className="text-primary font-bold text-sm tracking-wider">پاسخ سؤالات شما</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">سؤالات متداول</h2>
          <p className="text-muted-foreground text-lg">پاسخ جامع به رایج‌ترین سؤالات درباره جراحی بینی</p>
        </div>
        <div className="flex flex-wrap justify-center gap-2 mb-10">
          {cats.map(c => (
            <button key={c} onClick={() => { setCat(c); setOpenIdx(null); }}
              className={`px-4 py-2 rounded-xl text-sm font-bold transition-all ${cat === c ? "bg-primary text-white" : "bg-white border border-border text-foreground hover:border-primary/30 hover:text-primary"}`}>
              {c}
            </button>
          ))}
        </div>
        <div className="space-y-3">
          {filtered.map((faq, i) => (
            <div key={i} className="bg-white rounded-2xl border border-border overflow-hidden">
              <button onClick={() => setOpenIdx(openIdx === i ? null : i)} className="w-full flex items-center justify-between p-6 text-right gap-4">
                <div className="flex items-start gap-4">
                  <div className="w-6 h-6 bg-primary/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-primary text-xs font-black">{i + 1}</span>
                  </div>
                  <div>
                    <span className="text-xs font-bold text-primary/60 mb-1 block">{faq.cat}</span>
                    <span className="font-bold text-foreground text-base leading-snug">{faq.q}</span>
                  </div>
                </div>
                <ChevronDown className={`w-5 h-5 text-primary flex-shrink-0 transition-transform ${openIdx === i ? "rotate-180" : ""}`} />
              </button>
              {openIdx === i && (
                <div className="px-6 pb-6">
                  <div className="border-t border-border pt-5">
                    <p className="text-muted-foreground leading-loose">{faq.a}</p>
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
        <p className="text-center text-xs text-muted-foreground mt-8 bg-white rounded-2xl border border-border p-4">
          اطلاعات ارائه‌شده صرفاً جنبه آموزشی دارند و جایگزین مشاوره پزشکی تخصصی نمی‌شوند.
        </p>
      </div>
    </section>
  );
}

/* ─── Blog Section ─── */
function BlogSection() {
  const featured = BLOG_POSTS.find(b => b.featured)!;
  const rest = BLOG_POSTS.filter(b => !b.featured);
  return (
    <section id="blog" className="py-24 bg-background">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col sm:flex-row sm:items-end justify-between mb-12 gap-4">
          <div>
            <span className="text-primary font-bold text-sm tracking-wider">دانش پزشکی</span>
            <h2 className="text-4xl font-black text-foreground mt-2">مقالات تخصصی</h2>
          </div>
          <Link to="/blog" className="inline-flex items-center gap-2 text-primary font-bold hover:gap-3 transition-all">
            مشاهده همه مقالات <ArrowLeft className="w-4 h-4" />
          </Link>
        </div>
        <div className="grid lg:grid-cols-3 gap-6">
          <Link to={`/blog/${featured.slug}`} className="lg:col-span-2 group cursor-pointer">
            <div className="relative rounded-3xl overflow-hidden aspect-[16/9]">
              <img src={featured.img} alt={featured.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
              <div className="absolute bottom-0 inset-x-0 p-8 text-white">
                <span className="text-xs font-bold bg-primary rounded-full px-3 py-1.5 mb-3 inline-block">{featured.cat}</span>
                <h3 className="text-2xl font-black leading-snug mb-2">{featured.title}</h3>
                <p className="text-white/75 text-sm line-clamp-2">{featured.excerpt}</p>
                <p className="text-white/50 text-xs mt-3">{featured.date}</p>
              </div>
            </div>
          </Link>
          <div className="flex flex-col gap-4">
            {rest.slice(0, 4).map(post => (
              <Link key={post.id} to={`/blog/${post.slug}`}
                className="group flex gap-4 bg-white border border-border rounded-2xl p-4 hover:shadow-md hover:border-primary/20 transition-all">
                <div className="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0">
                  <img src={post.img} alt={post.title} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" />
                </div>
                <div className="flex-1 min-w-0">
                  <span className="text-xs font-bold text-primary">{post.cat}</span>
                  <h4 className="text-sm font-bold text-foreground mt-1 leading-snug line-clamp-2">{post.title}</h4>
                  <p className="text-xs text-muted-foreground mt-1">{post.date}</p>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

/* ─── Instagram Section ─── */
function InstagramSection() {
  const imgs = [
    "/images/Doctor/Dr Shahin Bastani Nejad (2).webp",
    "/images/Doctor/Dr Shahin Bastani Nejad (3).webp",
    "/images/Doctor/Dr Shahin Bastani Nejad (4).webp",
    "/images/Doctor/Dr Shahin Bastani Nejad (6).webp",
    "/images/Doctor/Dr Shahin Bastani Nejad (7).webp",
    "/images/Doctor/Dr Shahin Bastani Nejad (8).webp",
  ];
  return (
    <section className="py-20 bg-gradient-to-br from-purple-50 to-pink-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-12">
          <div className="inline-flex items-center gap-2 mb-4">
            <Instagram className="w-6 h-6 text-pink-500" />
            <a href={IG_URL} target="_blank" rel="noopener noreferrer" className="text-lg font-bold text-pink-600 hover:text-pink-700 transition-colors">
              @dr.shahin.bastaninejad
            </a>
          </div>
          <h2 className="text-3xl font-black text-foreground">ما را در اینستاگرام دنبال کنید</h2>
          <p className="text-muted-foreground mt-2">آخرین مطالب، نمونه‌کارها و ویدیوهای آموزشی</p>
        </div>
        <div className="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-8">
          {imgs.map((src, i) => (
            <a key={i} href={IG_URL} target="_blank" rel="noopener noreferrer" className="group aspect-square rounded-xl overflow-hidden block">
              <img src={src} alt="اینستاگرام" className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" />
            </a>
          ))}
        </div>
        <div className="text-center">
          <a href={IG_URL} target="_blank" rel="noopener noreferrer"
            className="inline-flex items-center gap-2 bg-gradient-to-l from-pink-500 to-purple-600 text-white font-bold px-8 py-4 rounded-2xl hover:shadow-xl transition-all">
            <Instagram className="w-5 h-5" /> مشاهده پروفایل اینستاگرام
          </a>
        </div>
      </div>
    </section>
  );
}

/* ─── Booking CTA Section ─── */
function BookingSection() {
  return (
    <section className="py-24 bg-primary relative overflow-hidden">
      <div className="absolute inset-0 opacity-10">
        <div className="absolute top-0 right-0 w-96 h-96 bg-white rounded-full -translate-y-1/2 translate-x-1/2" />
        <div className="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full translate-y-1/2 -translate-x-1/2" />
      </div>
      <div className="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-12">
          <h2 className="text-4xl font-black text-white mb-4">همین امروز نوبت رزرو کنید</h2>
          <p className="text-white/75 text-xl">رزرو آنلاین سریع، آسان و بدون نیاز به تماس تلفنی</p>
        </div>
        <div className="grid sm:grid-cols-3 gap-6 mb-12">
          {[
            { step: "۱", title: "تکمیل فرم رزرو",     desc: "اطلاعات اولیه خود را در فرم رزرو آنلاین وارد کنید." },
            { step: "۲", title: "تأیید شماره تلفن",   desc: "برای تأیید هویت، کد OTP ارسال‌شده را وارد کنید." },
            { step: "۳", title: "تماس تیم کلینیک",    desc: "همکاران ما ظرف ۲۴ ساعت با شما تماس می‌گیرند." },
          ].map(s => (
            <div key={s.step} className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 text-white">
              <div className="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-4">
                <span className="font-black text-lg">{s.step}</span>
              </div>
              <h3 className="font-bold text-lg mb-2">{s.title}</h3>
              <p className="text-white/70 text-sm">{s.desc}</p>
            </div>
          ))}
        </div>
        <div className="text-center">
          <a href={BOOKING_URL} target="_blank" rel="noopener noreferrer"
            className="inline-flex items-center gap-3 bg-white text-primary font-black px-10 py-5 rounded-2xl text-xl hover:bg-white/90 transition-all shadow-2xl">
            <Calendar className="w-6 h-6" /> رزرو نوبت آنلاین <ExternalLink className="w-5 h-5" />
          </a>
        </div>
      </div>
    </section>
  );
}

/* ─── Contact Section ─── */
function ContactSection() {
  const [submitted, setSubmitted] = useState(false);
  const [form, setForm] = useState({ name: "", phone: "", message: "" });
  return (
    <section id="contact" className="py-24 bg-muted/50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <span className="text-primary font-bold text-sm tracking-wider">ارتباط با ما</span>
          <h2 className="text-4xl font-black text-foreground mt-2 mb-4">تماس با کلینیک</h2>
          <p className="text-muted-foreground text-lg">آماده پاسخ‌گویی به سؤالات شما هستیم</p>
        </div>
        <div className="grid lg:grid-cols-5 gap-8">
          <div className="lg:col-span-2 space-y-5">
            <div className="bg-white rounded-3xl border border-border p-6">
              <h3 className="font-bold text-foreground mb-4 flex items-center gap-2">
                <Phone className="w-5 h-5 text-primary" /> تلفن تماس
              </h3>
              {PHONES.map(p => (
                <a key={p} href={`tel:${p.replace(/–/g, "")}`} className="flex items-center gap-3 p-3 rounded-xl hover:bg-secondary transition-colors group">
                  <div className="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                    <Phone className="w-4 h-4 text-primary" />
                  </div>
                  <span className="font-bold text-foreground">{p}</span>
                </a>
              ))}
            </div>
            <div className="bg-white rounded-3xl border border-border p-6">
              <h3 className="font-bold text-foreground mb-3 flex items-center gap-2">
                <MapPin className="w-5 h-5 text-primary" /> آدرس
              </h3>
              <p className="text-muted-foreground text-sm leading-loose">{ADDRESS}</p>
              <div className="flex flex-wrap gap-2 mt-4">
                {[
                  { name: "Google Maps", href: "https://maps.google.com/?q=35.758881,51.413824" },
                  { name: "نشان", href: "https://nshn.ir/gNbNgYZt_Rxg" },
                  { name: "بلد", href: "https://balad.ir" },
                  { name: "Waze", href: "https://waze.com" },
                ].map(l => (
                  <a key={l.name} href={l.href} target="_blank" rel="noopener noreferrer"
                    className="text-xs bg-secondary hover:bg-primary/10 hover:text-primary text-foreground rounded-lg px-3 py-1.5 font-medium transition-colors">
                    {l.name}
                  </a>
                ))}
              </div>
            </div>
            <div className="bg-white rounded-3xl border border-border p-6">
              <h3 className="font-bold text-foreground mb-3 flex items-center gap-2">
                <Clock className="w-5 h-5 text-primary" /> ساعات پذیرش
              </h3>
              <div className="flex items-center justify-between bg-secondary rounded-xl px-4 py-3">
                <span className="text-sm font-medium text-foreground">شنبه و سه‌شنبه</span>
                <span className="text-sm font-bold text-primary">۱۵:۰۰ – ۱۹:۰۰</span>
              </div>
              <p className="text-xs text-muted-foreground mt-3">برای رزرو نوبت آنلاین ۲۴ ساعته در دسترس هستیم.</p>
            </div>
          </div>
          <div className="lg:col-span-3 space-y-5">
            <div className="bg-white rounded-3xl border border-border p-8">
              {submitted ? (
                <div className="flex flex-col items-center justify-center py-12 text-center gap-4">
                  <div className="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center">
                    <CheckCircle className="w-10 h-10 text-primary" />
                  </div>
                  <h3 className="text-2xl font-black text-foreground">پیام شما ارسال شد!</h3>
                  <p className="text-muted-foreground max-w-sm">همکاران ما به‌زودی با شما تماس خواهند گرفت. ممنون از اعتماد شما.</p>
                  <button onClick={() => { setSubmitted(false); setForm({ name: "", phone: "", message: "" }); }}
                    className="text-primary font-bold text-sm underline">ارسال پیام جدید</button>
                </div>
              ) : (
                <>
                  <h3 className="text-xl font-black text-foreground mb-6">ارسال پیام</h3>
                  <form onSubmit={e => { e.preventDefault(); setSubmitted(true); }} className="space-y-5">
                    <div className="grid sm:grid-cols-2 gap-5">
                      <div>
                        <label className="block text-sm font-bold text-foreground mb-2">نام و نام خانوادگی <span className="text-red-500">*</span></label>
                        <input required value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} placeholder="نام خود را وارد کنید"
                          className="w-full bg-muted/50 border border-border rounded-xl px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                      </div>
                      <div>
                        <label className="block text-sm font-bold text-foreground mb-2">شماره تلفن <span className="text-red-500">*</span></label>
                        <input required value={form.phone} onChange={e => setForm({ ...form, phone: e.target.value })} placeholder="۰۹۱۲ *** ****"
                          className="w-full bg-muted/50 border border-border rounded-xl px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" dir="ltr" />
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-bold text-foreground mb-2">پیام شما <span className="text-red-500">*</span></label>
                      <textarea required value={form.message} onChange={e => setForm({ ...form, message: e.target.value })} placeholder="سؤال یا درخواست خود را بنویسید..." rows={5}
                        className="w-full bg-muted/50 border border-border rounded-xl px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all resize-none" />
                    </div>
                    <button type="submit"
                      className="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-2xl transition-all shadow-lg hover:shadow-primary/30 text-lg">
                      ارسال پیام
                    </button>
                  </form>
                </>
              )}
            </div>
            <div className="rounded-3xl overflow-hidden border border-border shadow-lg h-64">
              <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3237.671371427505!2d51.4138246!3d35.758881300000006!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzXCsDQ1JzMyLjAiTiA1McKwMjQnNTAuMiJF!5e0!3m2!1sfa!2sir!4v1698000000000!5m2!1sfa!2sir"
                width="100%" height="100%" style={{ border: 0 }} allowFullScreen loading="lazy"
                referrerPolicy="no-referrer-when-downgrade" title="محل کلینیک دکتر باستانی‌نژاد"
              />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

/* ─── Page Export ─── */
export default function HomePage() {
  return (
    <>
      <HeroSection />
      <TrustStrip />
      <ServicesSection />
      <StatsBand />
      <AboutSection />
      <CertificatesSection />
      <GallerySection />
      <ProcessSection />
      <HomeFAQSection />
      <BlogSection />
      <InstagramSection />
      <BookingSection />
      <ContactSection />
    </>
  );
}
