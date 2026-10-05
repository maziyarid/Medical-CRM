(() => {
  "use strict";

  const crmMode = document.getElementById("crmMode");
  const clubMode = document.getElementById("clubMode");
  const genericView = document.getElementById("genericView");
  const sideNav = document.getElementById("sideNav");
  const sidebar = document.querySelector(".sidebar");
  const pageTitle = document.getElementById("pageTitle");
  const genericTitle = document.getElementById("genericTitle");
  const genericDescription = document.getElementById("genericDescription");
  const modulePreview = document.getElementById("modulePreview");
  const leadDialog = document.getElementById("leadDialog");
  const memberPoints = document.getElementById("memberPoints");

  const state = {
    points: Number(localStorage.getItem("smartebDemoPoints") || 10860),
    leadAdded: localStorage.getItem("smartebDemoLeadAdded") === "1"
  };

  const modules = {
    leads: {
      title: "سرنخ‌ها و فروش درمان",
      description: "مدیریت چرخه کامل استعلام تا پذیرش طرح درمان، همراه با منبع جذب، ارزش مورد، SLA پیگیری و دلایل از دست‌رفتن.",
      cards: [
        ["پایپ‌لاین‌های تخصصی", "ایمپلنت، ارتودنسی، زیبایی و هر مسیر درمانی با مراحل قابل تنظیم.", "۱۲ سرنخ جدید"],
        ["امتیازدهی و SLA", "اولویت‌بندی سرنخ‌ها براساس ارزش، گرمی، منبع و زمان آخرین تماس.", "۴ پیگیری عقب‌افتاده"],
        ["اتریبیوشن کمپین", "اتصال منبع، کمپین و تماس تا رزرو، پرداخت و درآمد نهایی.", "ROI قابل ردیابی"],
        ["موارد ازدست‌رفته", "ثبت دلیل، بازیابی خودکار و لیست‌های بازفعال‌سازی.", "۷ فرصت قابل بازیابی"],
        ["مکالمات", "تاریخچه تماس، پیام، فرم، یادداشت و اقدام بعدی در یک تایم‌لاین.", "Inbox یکپارچه"],
        ["تبدیل به بیمار", "پس از رزرو یا احراز هویت، سرنخ به پرونده بیمار متصل می‌شود.", "بدون ورود دوباره داده"]
      ]
    },
    patients: {
      title: "بیماران و پروفایل ۳۶۰",
      description: "یک نمای واحد از ارتباطات، نوبت‌ها، فرم‌ها، اسناد، طرح درمان، پرداخت و وضعیت وفاداری هر مراجعه‌کننده.",
      cards: [
        ["پروفایل ۳۶۰", "اطلاعات تماس، ترجیحات ارتباطی، رضایت‌ها، خانواده و برچسب‌ها.", "پرونده یکپارچه"],
        ["Timeline", "نوبت، پیام، پرداخت، سند، فرم و تغییر وضعیت به ترتیب زمانی.", "قابل فیلتر"],
        ["فرم و مدارک", "فرم‌های پذیرش، سوابق، رضایت‌نامه و فایل‌های بیمار.", "امضای دیجیتال"],
        ["درخواست‌های بیمار", "درخواست تماس، شکایت، پیگیری، فایل و پرسش از پورتال.", "صف رسیدگی"],
        ["خانواده و ضامن", "رابطه خانوادگی، پرداخت‌کننده و عضویت خانوادگی.", "قابل اشتراک‌گذاری"],
        ["وفاداری", "امتیاز، سطح، عضویت و پاداش در همان نمای بیمار.", "اختیاری و مستقل"]
      ]
    },
    schedule: {
      title: "نوبت‌ها، صندلی‌ها و منابع",
      description: "تقویم چندپزشک و چندشعبه با کنترل صندلی/اتاق، لیست انتظار، رزرو آنلاین و جلوگیری از تداخل منابع.",
      cards: [
        ["تقویم چندمنبعی", "پزشک، صندلی، اتاق، شعبه و نوع خدمت در یک تقویم.", "ظرفیت زنده"],
        ["رزرو آنلاین ۲۴/۷", "قواعد رزرو براساس خدمت، مدت، پزشک و شعبه.", "لینک اختصاصی"],
        ["لیست انتظار", "پر کردن خودکار یا دستی زمان‌های خالی با بیماران واجد شرایط.", "۳ فرصت امروز"],
        ["ورود دیجیتال", "فرم، تأیید حضور، چک‌این و وضعیت صف پذیرش.", "بدون کاغذ"],
        ["یادآوری هوشمند", "تأیید، لغو و جابه‌جایی با قوانین چندمرحله‌ای.", "کاهش no-show"],
        ["کنترل تعارض", "هشدار هم‌پوشانی پزشک، صندلی و تجهیز.", "قبل از ثبت"]
      ]
    },
    treatment: {
      title: "طرح درمان و پذیرش کیس",
      description: "ساخت پیشنهاد درمان مرحله‌ای، گزینه‌های جایگزین، برآورد مالی، امضا، پیش‌پرداخت و پیگیری درمان‌های زمان‌بندی‌نشده.",
      cards: [
        ["طرح چندگزینه‌ای", "گزینه‌ها، فازها، آیتم‌ها، تخفیف و توضیحات.", "قابل نسخه‌بندی"],
        ["برآورد و امضا", "ارسال لینک امن برای مشاهده، تأیید و امضای دیجیتال.", "Audit trail"],
        ["پیش‌پرداخت", "لینک پرداخت یا برنامه اقساط در جریان پذیرش.", "Payment-ready"],
        ["درمان زمان‌بندی‌نشده", "صف فرصت‌های پذیرفته یا ارائه‌شده که هنوز نوبت ندارند.", "پیگیری خودکار"],
        ["دلایل رد/تعویق", "قیمت، زمان، ترس، تصمیم خانواده یا گزینه سفارشی.", "تحلیل پذیرش"],
        ["شاخص پذیرش", "براساس پزشک، درمان، هماهنگ‌کننده و شعبه.", "۷۲٪ این ماه"]
      ]
    },
    messages: {
      title: "پیام‌ها و ارتباطات",
      description: "Inbox یکپارچه برای پیامک، ایمیل، واتس‌اپ‌سازگار و رخدادهای تماس، با قالب‌ها، رضایت و محدودیت فراوانی.",
      cards: [
        ["Inbox مشترک", "تمام پیام‌ها و یادداشت‌های تیم در یک صف.", "مالک و وضعیت"],
        ["پیام دوطرفه", "پاسخ، فایل، قالب و متغیرهای بیمار.", "کانال‌محور"],
        ["تماس از دست‌رفته", "ساخت خودکار کار پیگیری یا پیام پاسخ.", "SLA قابل تنظیم"],
        ["قالب‌های هوشمند", "یادآوری، قبل/بعد درمان، تبریک و پیگیری.", "فارسی/انگلیسی"],
        ["رضایت و Quiet Hours", "ممانعت از ارسال خارج از رضایت یا ساعات مجاز.", "Compliance-first"],
        ["گزارش تحویل", "ارسال، تحویل، پاسخ، خطا و opt-out.", "قابل تحلیل"]
      ]
    },
    automation: {
      title: "اتوماسیون، Recall و کمپین",
      description: "جریان‌های رویدادمحور برای یادآوری، Recall، بازفعال‌سازی، بررسی رضایت، نظرخواهی، درمان‌های معوق و کمپین‌های سگمنت‌شده.",
      cards: [
        ["Journey Builder", "Trigger → شرط → تأخیر → پیام/کار/پاداش.", "قابل نسخه‌بندی"],
        ["Recall", "هشدار دوره‌ای براساس آخرین مراجعه یا خدمت.", "۶ ماهه و سفارشی"],
        ["Reactivation", "بازگرداندن بیمار غیرفعال با سگمنت و خروج خودکار.", "۱۲+ ماه"],
        ["Follow-up درمان", "پیگیری طرح درمان ارائه‌شده اما پذیرفته‌نشده.", "قابل توقف با تبدیل"],
        ["نظرخواهی", "ارسال درخواست نظر پس از outcome تعریف‌شده.", "فیلتر رضایت"],
        ["A/B و Attribution", "نسخه‌های پیام، نرخ تبدیل و درآمد منتسب.", "گزارش نهایی"]
      ]
    },
    finance: {
      title: "مالی، پرداخت و بیمه",
      description: "چرخه مالی از برآورد و پیش‌پرداخت تا مانده بیمار، اقساط، مطالبات، بیمه و تطبیق پرداخت.",
      cards: [
        ["مانده بیمار", "صورتحساب، پرداخت، اعتبار، تعدیل و بازپرداخت.", "Ledger دقیق"],
        ["Text-to-pay", "لینک امن پرداخت از پیام و پورتال.", "سریع و قابل ردیابی"],
        ["برنامه اقساط", "سررسید، پرداخت دوره‌ای و یادآوری مانده.", "Membership-ready"],
        ["بیمه", "استعلام eligibility، وضعیت claim و worklist.", "Adapter-based"],
        ["مطالبات", "Ageing، اولویت پیگیری و علت توقف.", "۰–۳۰ / ۳۰–۶۰ / ۹۰+"],
        ["تطبیق", "اتصال تراکنش، فاکتور و پرونده با audit history.", "Reconciliation"]
      ]
    },
    analytics: {
      title: "تحلیل و گزارش",
      description: "نمای مدیریتی از جذب، ظرفیت، پذیرش طرح درمان، تولید، وصول، بازگشت بیمار، کمپین و وفاداری.",
      cards: [
        ["Funnel جذب", "منبع → سرنخ → رزرو → مراجعه → پرداخت.", "Conversion"],
        ["پذیرش درمان", "ارزش ارائه‌شده، پذیرفته، زمان‌بندی‌شده و ازدست‌رفته.", "Case acceptance"],
        ["ظرفیت", "استفاده از صندلی، لغو، no-show و زمان خالی.", "Utilisation"],
        ["مالی", "تولید، وصول، A/R و روند پرداخت.", "Branch drill-down"],
        ["Retention", "Recall، بازفعال‌سازی، بازگشت و طول عمر بیمار.", "Cohorts"],
        ["Loyalty ROI", "هزینه پاداش، redemption، referral و ارزش عمر عضو.", "اختیاری"]
      ]
    }
  };

  function normaliseDigits(value) {
    return Number(value).toLocaleString("fa-IR");
  }

  function persist() {
    localStorage.setItem("smartebDemoPoints", String(state.points));
    localStorage.setItem("smartebDemoLeadAdded", state.leadAdded ? "1" : "0");
  }

  function setActiveNav(view) {
    document.querySelectorAll(".nav-item").forEach((button) => {
      button.classList.toggle("active", button.dataset.view === view);
    });
  }

  function setActiveMode(mode) {
    document.querySelectorAll(".mode-btn").forEach((button) => {
      button.classList.toggle("active", button.dataset.mode === mode);
    });
  }

  function showCrm() {
    crmMode.classList.remove("hidden");
    clubMode.classList.add("hidden");
    genericView.classList.add("hidden");
    setActiveMode("crm");
    setActiveNav("overview");
    pageTitle.textContent = "داشبورد رشد و عملیات";
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function showClub() {
    crmMode.classList.add("hidden");
    clubMode.classList.remove("hidden");
    genericView.classList.add("hidden");
    setActiveMode("club");
    setActiveNav("loyalty");
    pageTitle.textContent = "باشگاه مشتریان و وفاداری";
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function showGeneric(view) {
    const module = modules[view];
    if (!module) return;
    crmMode.classList.add("hidden");
    clubMode.classList.add("hidden");
    genericView.classList.remove("hidden");
    setActiveMode("crm");
    setActiveNav(view);
    pageTitle.textContent = module.title;
    genericTitle.textContent = module.title;
    genericDescription.textContent = module.description;
    modulePreview.innerHTML = module.cards.map(([title, body, meta]) => (
      '<article class="module-card"><strong>' + title + '</strong><small>' + body + '</small><span>' + meta + '</span></article>'
    )).join("");
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function toast(message) {
    let node = document.querySelector(".demo-toast");
    if (!node) {
      node = document.createElement("div");
      node.className = "demo-toast";
      Object.assign(node.style, {
        position: "fixed",
        left: "22px",
        bottom: "22px",
        zIndex: "1000",
        maxWidth: "min(390px, calc(100vw - 44px))",
        background: "#173f37",
        color: "#fff",
        borderRadius: "14px",
        padding: "12px 15px",
        boxShadow: "0 16px 36px rgba(10,35,29,.24)",
        fontSize: "12px",
        lineHeight: "1.8",
        opacity: "0",
        transform: "translateY(8px)",
        transition: ".18s ease"
      });
      document.body.appendChild(node);
    }
    node.textContent = message;
    requestAnimationFrame(() => {
      node.style.opacity = "1";
      node.style.transform = "translateY(0)";
    });
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => {
      node.style.opacity = "0";
      node.style.transform = "translateY(8px)";
    }, 2600);
  }

  function addSyntheticLead(silent = false) {
    if (state.leadAdded) return;
    const firstColumn = document.querySelector(".pipeline-col");
    if (!firstColumn) return;
    const card = document.createElement("div");
    card.className = "lead-card accent";
    card.dataset.synthetic = "user-added";
    card.innerHTML = "<strong>ایمپلنت · مراجعه‌کننده نمونه</strong><span>۱۸۰ م.ت</span><small>گوگل · اکنون ثبت شد</small>";
    firstColumn.appendChild(card);
    const counter = firstColumn.querySelector("header b");
    if (counter) counter.textContent = String(Number(counter.textContent || "12") + 1);
    state.leadAdded = true;
    persist();
    if (!silent) toast("سرنخ نمونه به پایپ‌لاین اضافه شد.");
  }

  if (state.leadAdded) addSyntheticLead(true);
  if (memberPoints) memberPoints.textContent = normaliseDigits(state.points);

  sideNav?.addEventListener("click", (event) => {
    const button = event.target.closest(".nav-item");
    if (!button) return;
    const view = button.dataset.view;
    if (view === "overview") showCrm();
    else if (view === "loyalty") showClub();
    else showGeneric(view);
    sidebar?.classList.remove("open");
  });

  document.querySelectorAll(".mode-btn").forEach((button) => {
    button.addEventListener("click", () => {
      if (button.dataset.mode === "club") showClub();
      else showCrm();
    });
  });

  document.querySelectorAll("[data-view-jump]").forEach((button) => {
    button.addEventListener("click", () => showGeneric(button.dataset.viewJump));
  });

  document.querySelectorAll("[data-mode-jump]").forEach((button) => {
    button.addEventListener("click", () => {
      if (button.dataset.modeJump === "club") showClub();
      else showCrm();
    });
  });

  document.querySelectorAll("[data-open='newLead']").forEach((button) => {
    button.addEventListener("click", () => {
      if (leadDialog?.showModal) leadDialog.showModal();
    });
  });

  document.getElementById("saveLead")?.addEventListener("click", (event) => {
    event.preventDefault();
    if (!state.leadAdded) addSyntheticLead();
    else toast("این سرنخ نمونه قبلاً در دمو ثبت شده است.");
    leadDialog?.close();
  });

  document.getElementById("simulateReward")?.addEventListener("click", () => {
    state.points += 120;
    persist();
    if (memberPoints) memberPoints.textContent = normaliseDigits(state.points);
    toast("رویداد «تکمیل مراجعه» ثبت شد و ۱۲۰ امتیاز به کیف عضو اضافه شد.");
  });

  document.getElementById("resetDemo")?.addEventListener("click", () => {
    localStorage.removeItem("smartebDemoPoints");
    localStorage.removeItem("smartebDemoLeadAdded");
    location.reload();
  });

  document.getElementById("mobileMenu")?.addEventListener("click", () => {
    sidebar?.classList.toggle("open");
  });

  document.addEventListener("click", (event) => {
    if (window.innerWidth > 800 || !sidebar?.classList.contains("open")) return;
    if (sidebar.contains(event.target) || event.target.closest("#mobileMenu")) return;
    sidebar.classList.remove("open");
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") sidebar?.classList.remove("open");
  });
})();