// Central Persian content for Dr. Bastaninejad clinic
export const CLINIC = {
  name: "دکتر شاهین باستانی‌نژاد",
  role: "جراح و متخصص گوش، گلو، بینی و جراح پلاستیک بینی",
  tagline: "هنرِ بازآفرینیِ تناسبِ طبیعیِ چهره",
  phones: ["۰۲۱۸۶۰۸۷۲۵۰", "۰۲۱۸۸۲۰۵۶۰۶", "۰۹۹۱۲۴۹۶۶۵۹"],
  phonesRaw: ["02186087250", "02188205606", "09912496659"],
  hours: "شنبه‌ها و سه‌شنبه‌ها، ساعت ۱۵ الی ۱۹",
  address:
    "تهران، خیابان نلسون ماندلا، قبل از چهارراه جهان کودک، خیابان صانعی، ساختمان نور، پلاک ۱، طبقه ۲، واحد ۶",
};

export const NAV = [
  { label: "خانه", to: "/" },
  { label: "درباره دکتر", to: "/about" },
  { label: "خدمات", to: "/services" },
  { label: "مجله", to: "/blog" },
  { label: "تماس", to: "/contact" },
];

export const STATS = [
  { value: "+۱۸", label: "سال تجربه بالینی" },
  { value: "+۴۵۰۰", label: "جراحی موفق بینی" },
  { value: "٪۹۸", label: "رضایت مراجعین" },
  { value: "۲۴/۷", label: "پشتیبانی پس از عمل" },
];

export const SERVICES = [
  { id: "primary", icon: "rhino", title: "راینوپلاستی اولیه", desc: "طراحی بینی متناسب با ساختار چهره و حفظ عملکرد طبیعی تنفس.", span: "lg:col-span-3" },
  { id: "secondary", icon: "redo", title: "راینوپلاستی ثانویه", desc: "اصلاح جراحی‌های پیشین با دقتی هنرمندانه و بازگرداندن تعادل.", span: "lg:col-span-3" },
  { id: "hump", icon: "hump", title: "رفع قوز بینی", desc: "تراش ظریف قوز و ایجاد خط پل بینی صاف و طبیعی.", span: "lg:col-span-2" },
  { id: "face", icon: "facelift", title: "جراحی زیبایی صورت", desc: "لیفت و جوان‌سازی خطوط صورت با رویکردی محافظه‌کارانه.", span: "lg:col-span-2" },
  { id: "eyelid", icon: "eyelid", title: "بلفاروپلاستی (پلک)", desc: "برداشتن افتادگی و پف پلک برای نگاهی شفاف و سرزنده.", span: "lg:col-span-2" },
  { id: "filler", icon: "filler", title: "تزریق ژل و بوتاکس", desc: "اصلاح غیرجراحی چروک و حجم‌دهی طبیعی و کنترل‌شده.", span: "lg:col-span-2" },
  { id: "chin", icon: "chin", title: "پروتز چانه و گونه", desc: "تعریف مجدد پروفایل صورت و ایجاد هارمونی اجزا.", span: "lg:col-span-2" },
  { id: "ear", icon: "ear", title: "اوتوپلاستی (گوش)", desc: "اصلاح فرم و زاویه گوش برای تناسب بیشتر با سر.", span: "lg:col-span-2" },
];

export const MANIFESTO = [
  { n: "۰۱", title: "سادگیِ مطبوع", text: "مهم‌ترین اصل در جراحی زیبایی بینی این است که از سادگی نامطبوع به پیچیدگی‌ای برسیم که ورای آن، سادگیِ مطبوعی حاصل شود." },
  { n: "۰۲", title: "بینی برای نفس کشیدن", text: "هدف، یک بینی بسیار کوچک یا نوکِ به‌شدت جمع‌شده نیست؛ بینی عضوی است جهت نفس کشیدن و باید عملکرد آن حفظ شود." },
  { n: "۰۳", title: "تناسب، نه تقلید", text: "هر چهره امضای خودش را دارد. نتیجه‌ی درست، بینی‌ای است که انگار همیشه بخشی از همان صورت بوده است." },
];

export const BLOG_CATS = [
  { id: "rhino", icon: "nose", label: "جراحی بینی" },
  { id: "recovery", icon: "recovery", label: "مراقبت پس از عمل" },
  { id: "nutrition", icon: "nutrition", label: "تغذیه و بهبودی" },
  { id: "choose", icon: "choose", label: "انتخاب جراح" },
];

export const POSTS = [
  { id: 1, cat: "rhino", title: "هر آنچه باید درباره جراحی بینی بدانید", excerpt: "از مشاوره تا نتیجه نهایی؛ راهنمای کامل مراحل راینوپلاستی و انتظارات واقع‌بینانه.", read: "۷ دقیقه", img: "https://images.pexels.com/photos/3373721/pexels-photo-3373721.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" },
  { id: 2, cat: "nutrition", title: "تأثیر تغذیه بر بهبودی بعد از جراحی بینی", excerpt: "چه بخوریم تا تورم سریع‌تر فروکش کند و روند ترمیم بهینه شود.", read: "۵ دقیقه", img: "https://images.pexels.com/photos/21849239/pexels-photo-21849239.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" },
  { id: 3, cat: "choose", title: "۱۰ نکته در انتخاب جراح بینی", excerpt: "معیارهای کلیدی برای انتخابی مطمئن؛ از تخصص تا نمونه‌کارها و اعتماد.", read: "۶ دقیقه", img: "https://images.pexels.com/photos/20010865/pexels-photo-20010865.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" },
  { id: 4, cat: "recovery", title: "هفته اول پس از عمل؛ گام به گام", excerpt: "برنامه مراقبتی روزانه برای گذراندن آرام و ایمن دوران نقاهت.", read: "۴ دقیقه", img: "https://images.pexels.com/photos/11583453/pexels-photo-11583453.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" },
];

export const TESTIMONIALS = [
  { name: "سارا م.", city: "تهران", text: "نتیجه دقیقاً همان چیزی بود که می‌خواستم؛ طبیعی و متناسب با صورتم. دکتر با حوصله همه‌چیز را توضیح داد." },
  { name: "نیما ر.", city: "کرج", text: "راینوپلاستی ثانویه‌ام را انجام دادند و بالاخره تنفسم اصلاح شد. حرفه‌ای و قابل اعتماد." },
  { name: "الهام ک.", city: "تهران", text: "از مشاوره تا مراقبت‌های بعد از عمل، همه‌چیز منظم و شفاف بود. کاملاً راضی‌ام." },
];

export const GALLERY = [
  "https://images.pexels.com/photos/11583453/pexels-photo-11583453.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940",
  "https://images.pexels.com/photos/20010865/pexels-photo-20010865.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940",
  "https://images.pexels.com/photos/3373721/pexels-photo-3373721.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940",
];

export const IMAGES = {
  heroProfile: "https://images.pexels.com/photos/11583453/pexels-photo-11583453.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=1000&w=800",
  doctor: "https://images.pexels.com/photos/29995617/pexels-photo-29995617.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=1000&w=800",
  clinic: "https://images.pexels.com/photos/16571735/pexels-photo-16571735.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=900&w=1200",
  beauty: "https://images.pexels.com/photos/3373721/pexels-photo-3373721.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=1000&w=800",
};
