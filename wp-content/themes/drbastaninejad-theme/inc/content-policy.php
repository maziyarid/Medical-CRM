<?php

defined( 'ABSPATH' ) || exit;

define( 'DRB_CONTENT_POLICY_VERSION', '4.6.0' );

function drb_policy_clinic_hours() {
    return 'شنبه تا سه‌شنبه، از ساعت ۱۵:۰۰ تا ۱۸:۰۰';
}

function drb_policy_admission_notice() {
    return 'پذیرش فقط در بازه اعلام‌شده انجام می‌شود و مراجعینی که بعد از ساعت ۱۸:۰۰ در مطب حضور یابند، به هیچ‌وجه پذیرش نخواهند شد.';
}

function drb_policy_revision_notice() {
    return 'بررسی جراحی ترمیمی فقط پس از گذشت کامل ۲۴ ماه (۲ سال) از جراحی قبلی انجام می‌شود؛ زیرا انجام زودهنگام می‌تواند ریسک نکروز، عفونت و آسیب جدی به بافت و پوست را افزایش دهد و فرم بینی، غضروف‌ها و اسکلت آن تا ۲۴ ماه همچنان در حال تغییر است.';
}

function drb_policy_eligibility_html() {
    return '<section class="drb-approved-article" aria-labelledby="drb-eligibility-title">'
        . '<h2 id="drb-eligibility-title">شرایط قطعی پذیرش جراحی بینی</h2>'
        . '<div class="drb-notice drb-notice--important"><strong>ساعات حضور:</strong> ' . esc_html( drb_policy_clinic_hours() ) . '. ' . esc_html( drb_policy_admission_notice() ) . '</div>'
        . '<h3>چه افرادی پذیرش نمی‌شوند؟</h3>'
        . '<ul class="drb-checklist drb-checklist--blocked">'
        . '<li>افراد مبتلا به دیابت یا فشار خون.</li>'
        . '<li>بیماران دارای بیماری زمینه‌ای یا بیماری خودایمنی کنترل‌نشده.</li>'
        . '<li>افراد کمتر از ۱۸ سال یا بیشتر از ۴۵ سال؛ پذیرش فقط در بازه سنی ۱۸ تا ۴۵ سال انجام می‌شود.</li>'
        . '<li>افرادی که جراحی سنترال لب (لیفت لب) انجام داده‌اند.</li>'
        . '<li>متقاضیان جراحی بینی گوشتی؛ این خدمت در این مطب انجام نمی‌شود.</li>'
        . '</ul>'
        . '<div class="drb-notice"><strong>سبک جراحی:</strong> رویکرد دکتر، طبیعی و متناسب با اجزای صورت است؛ سبک فانتزی یا عروسکی انجام نمی‌شود.</div>'
        . '<h3>شرط جراحی ترمیمی</h3><p>' . esc_html( drb_policy_revision_notice() ) . '</p>'
        . '<p class="drb-medical-disclaimer">ثبت فرم یا تماس تلفنی به معنی پذیرش قطعی نیست؛ تصمیم نهایی پس از بررسی پرونده و معاینه پزشکی اعلام می‌شود.</p>'
        . '</section>';
}

function drb_policy_postop_html() {
    return '<section class="drb-approved-article" aria-labelledby="drb-postop-title">'
        . '<h2 id="drb-postop-title">پرسش‌های کاربردی بعد از عمل بینی</h2>'
        . '<p class="drb-lead">این راهنما به‌صورت پرسش و پاسخ تنظیم شده است. دستور اختصاصی جراح و تیم درمان همواره بر متن عمومی اولویت دارد.</p>'
        . '<div class="drb-faq-list">'
        . '<details open><summary>برای کاهش ورم و کبودی چه کار کنم؟</summary><p>در چند روز نخست، کمپرس سرد را فقط طبق آموزش تیم درمان استفاده کنید؛ نمک را کاهش دهید، آب کافی بنوشید، از خم شدن زیاد و فعالیت سنگین پرهیز کنید و داروهای تجویزشده را دقیق مصرف کنید. ورم بخشی طبیعی از روند ترمیم است.</p></details>'
        . '<details><summary>برای بخیه‌های پره و زیر بینی چه مراقبتی لازم است؟</summary><p>بخیه‌ها را تمیز نگه دارید، دستکاری نکنید، از فشار و ضربه جلوگیری کنید و لب بالا را بیش از حد نکشید. کرم ترمیم‌کننده فقط پس از کشیدن بخیه و با تأیید تیم درمان استفاده شود.</p></details>'
        . '<details><summary>چه زمانی می‌توان حمام کرد؟</summary><p>معمولاً پس از ۷۲ ساعت و با رعایت دستور تیم درمان می‌توان حمام کوتاه داشت. گچ و چسب نباید خیس یا جابه‌جا شود، آب خیلی داغ نباشد و شست‌وشوی موها با کمک همراه و به روش آموزش‌داده‌شده انجام شود.</p></details>'
        . '<details><summary>در روزهای اول چه بخورم؟</summary><p>غذاهای سبک، نرم و کم‌نمک انتخاب کنید و از خوراکی‌های سفت، تند، شور یا نیازمند جویدن زیاد پرهیز کنید. جزئیات رژیم ضدالتهابی در مقاله اختصاصی تغذیه آمده است.</p></details>'
        . '<details><summary>داروها را از چه زمانی شروع کنم؟</summary><p>داروها پس از ترخیص و دقیقاً مطابق نسخه آغاز می‌شوند. آنتی‌بیوتیک، مسکن، اسپری، سرم شست‌وشو یا پماد را خودسرانه کم، زیاد یا قطع نکنید.</p></details>'
        . '<details><summary>اسپلینت چه مدت روی بینی می‌ماند؟</summary><p>معمولاً حدود یک هفته، اما زمان دقیق به شرایط بینی و نظر جراح بستگی دارد. اسپلینت نباید خیس، شل یا جابه‌جا شود.</p></details>'
        . '<details><summary>چه زمانی بعد از عمل امکان پرواز وجود دارد؟</summary><p><strong>۱۰ روز پس از جراحی امکان پرواز وجود دارد.</strong> اگر تیم درمان با توجه به وضعیت اختصاصی شما دستور متفاوتی داده است، همان دستور ملاک خواهد بود.</p></details>'
        . '<details><summary>خونابه یا گرفتگی بینی طبیعی است؟</summary><p>مقدار کمی خونابه در ۲۴ تا ۷۲ ساعت اول و احساس گرفتگی خفیف می‌تواند طبیعی باشد. خونریزی شدید یا مداوم، تب، درد کنترل‌نشده یا دشواری قابل‌توجه در تنفس باید فوراً به پرستار پیگیری یا تیم درمان اطلاع داده شود.</p></details>'
        . '<details><summary>آیا یک دستور ثابت برای وضعیت خواب وجود دارد؟</summary><p>خیر. وضعیت خواب و استراحت باید فقط طبق دستور اختصاصی جراح برای پرونده شما تنظیم شود و از توصیه‌های عمومی یا محتوای قدیمی اینترنتی استفاده نشود.</p></details>'
        . '</div><p class="drb-medical-disclaimer">این چک‌لیست جایگزین نسخه، معاینه یا دستور اختصاصی پزشک نیست.</p>'
        . '</section>';
}

function drb_policy_preop_html( $include_eligibility = true ) {
    $eligibility = $include_eligibility ? drb_policy_eligibility_html() : '';
    return '<section class="drb-approved-article" aria-labelledby="drb-preop-title">'
        . '<h2 id="drb-preop-title">چک‌لیست اقدامات قبل از جراحی بینی</h2>'
        . '<p class="drb-lead">آمادگی صحیح، اضطراب روز عمل را کاهش می‌دهد و به برنامه‌ریزی ایمن‌تر جراحی کمک می‌کند.</p>'
        . $eligibility
        . '<h3>پیش از تعیین وقت جراحی</h3><ul class="drb-checklist">'
        . '<li>سوابق پزشکی، بیماری‌ها، حساسیت‌ها، داروها و مکمل‌های مصرفی را کامل و صادقانه اعلام کنید.</li>'
        . '<li>آزمایش‌ها، تصویربرداری‌ها و مشاوره‌های درخواست‌شده را در زمان اعلام‌شده انجام دهید و نتیجه را همراه داشته باشید.</li>'
        . '<li>هیچ دارو یا مکملی را خودسرانه قطع یا شروع نکنید؛ تصمیم فقط با نظر پزشک معالج و تیم جراحی انجام می‌شود.</li>'
        . '<li>دخانیات، ویپ، قلیان و الکل را طبق بازه‌ای که تیم درمان اعلام می‌کند قطع کنید.</li>'
        . '<li>در صورت سرماخوردگی، عفونت تنفسی یا تغییر وضعیت سلامت، پیش از مراجعه تیم درمان را مطلع کنید.</li>'
        . '</ul><h3>شب و روز جراحی</h3><ul class="drb-checklist">'
        . '<li>دستور ناشتایی مرکز جراحی را دقیق رعایت کنید.</li>'
        . '<li>مدارک هویتی، نتایج آزمایش‌ها و تصویربرداری‌ها و نامه‌های پزشکی لازم را همراه داشته باشید.</li>'
        . '<li>بدون آرایش، لاک، زیورآلات، لنز و وسایل غیرضروری مراجعه کنید و لباس راحتِ دکمه‌دار یا زیپ‌دار بپوشید.</li>'
        . '<li>حضور یک همراه مسئول و قابل‌اعتماد برای ترخیص و بازگشت به منزل الزامی است.</li>'
        . '</ul><p class="drb-medical-disclaimer">جزئیات آزمایش، ناشتایی و قطع دارو برای هر فرد متفاوت است؛ فقط دستور کتبی تیم درمان ملاک عمل است.</p>'
        . '</section>';
}

function drb_policy_nutrition_tab_html() {
    return '<section class="drb-approved-article" aria-labelledby="drb-nutrition-title">'
        . '<h2 id="drb-nutrition-title">رژیم ضد التهابی</h2>'
        . '<p class="drb-lead">این مقاله از فایل اختصاصی و تأییدشده کلینیک تنظیم شده و برای کپی یا جایگزینی با مطالب سایت‌های دیگر نیست. هدف، حمایت از ترمیم بافت و کنترل التهاب در کنار دستور پزشکی است.</p>'
        . '<div class="drb-notice drb-notice--important">افراد دارای بیماری مزمن یا زمینه‌ای باید برنامه غذایی و مکمل‌ها را با پزشک معالج خود بررسی کنند. ابتلا به دیابت یا فشار خون از موارد عدم پذیرش جراحی در این مطب است.</div>'
        . '<h3>الگوی روزانه</h3><ul class="drb-checklist"><li>حدود ۲۵ تا ۳۵ درصد از دریافت روزانه از پروتئین و چربی‌های مفید.</li><li>حدود ۴۰ تا ۶۰ درصد از کربوهیدرات‌های منتخب و کم‌فرآوری‌شده.</li><li>غذاهای ساده، زودهضم و کم‌نمک؛ وعده‌ها متعادل و بدون پرخوری باشند.</li></ul>'
        . '<div class="drb-notice drb-notice--important"><strong>ممنوعیت‌های قطعی:</strong> شیر، ماست، بستنی و هر نوع لبنیات ممنوع است؛ تنها استثنا پنیر کاتیج است که به‌دلیل پروتئین بالا و در حد برنامه مجاز است. سیب‌زمینی، پوره سیب‌زمینی، گوجه‌فرنگی، برنج، شیربرنج و همه فرآورده‌های برنجی نیز در این رژیم ممنوع‌اند. توصیه‌های قدیمی یا عمومیِ مغایر با این موارد ملاک نیستند.</div>'
        . '<div class="drb-food-filter" data-drb-food-filter>'
        . '<div class="drb-food-filter__controls" role="group" aria-label="فیلتر مواد غذایی"><button type="button" class="is-active" data-food-filter="all">همه</button><button type="button" data-food-filter="allowed">مجاز</button><button type="button" data-food-filter="banned">ممنوع</button></div>'
        . '<div class="drb-food-grid">'
        . '<article class="drb-food-card drb-food-card--allowed" data-food-status="allowed"><span aria-hidden="true">✓</span><strong>سبزیجات منتخب</strong><small>بروکلی، کلم، خیار، کرفس، گل‌کلم، شاهی و اسفناج</small></article>'
        . '<article class="drb-food-card drb-food-card--allowed" data-food-status="allowed"><span aria-hidden="true">✓</span><strong>پروتئین‌های منتخب</strong><small>ماهی آب آزاد، گوشت سفید، تخم‌مرغ و پنیر کاتیج</small></article>'
        . '<article class="drb-food-card drb-food-card--allowed" data-food-status="allowed"><span aria-hidden="true">✓</span><strong>حبوبات و غلات منتخب</strong><small>لوبیا، عدس، لپه، کینوا، جو دوسر، چاودار و نان کامل جو</small></article>'
        . '<article class="drb-food-card drb-food-card--allowed" data-food-status="allowed"><span aria-hidden="true">✓</span><strong>آب و میوه متعادل</strong><small>۲ تا ۲٫۵ لیتر آب؛ خود میوه به‌جای آبمیوه و بدون زیاده‌روی</small></article>'
        . '<article class="drb-food-card drb-food-card--banned" data-food-status="banned"><span aria-hidden="true">×</span><strong>لبنیات</strong><small>شیر، ماست، بستنی و همه لبنیات؛ فقط پنیر کاتیج استثناست</small></article>'
        . '<article class="drb-food-card drb-food-card--banned" data-food-status="banned"><span aria-hidden="true">×</span><strong>سبزیجات التهاب‌زا</strong><small>سیب‌زمینی، پوره سیب‌زمینی و گوجه‌فرنگی</small></article>'
        . '<article class="drb-food-card drb-food-card--banned" data-food-status="banned"><span aria-hidden="true">×</span><strong>برنج و فرآورده‌ها</strong><small>برنج، شیربرنج، رایس‌کیک و همه فرآورده‌های برنجی</small></article>'
        . '<article class="drb-food-card drb-food-card--banned" data-food-status="banned"><span aria-hidden="true">×</span><strong>نوشیدنی‌ها و مواد محرک</strong><small>نوشابه گازدار، انرژی‌زا، قهوه، الکل و بادام‌زمینی</small></article>'
        . '</div></div>'
        . '<h3>سبزیجات و میوه‌ها</h3><p>اولویت با سبزیجات کم‌کربوهیدرات مانند بروکلی، کاهو، انواع کلم، خیار، کرفس، گل‌کلم، جوانه‌ها، شاهی و اسفناج است. لوبیاسبز، چغندر، پیازچه، بادمجان، پیاز، تره، فلفل قرمز، کدو، جعفری، شلغم و لبو نیز در گروه بعدی قرار می‌گیرند. در صورت حساسیت گوارشی، سبزیجات را کمی بخارپز کنید.</p>'
        . '<p><strong>گوجه‌فرنگی و سیب‌زمینی:</strong> به‌دلیل التهاب‌زا بودن در این رژیم ممنوع‌اند؛ پوره سیب‌زمینی نیز مجاز نیست.</p>'
        . '<p>میوه را متعادل مصرف کنید. هندوانه، طالبی و توت‌فرنگی در گروه کم‌کربوهیدرات‌تر قرار می‌گیرند؛ زردآلو، شاتوت، زغال‌اخته، هلو، کیوی، سیب، بلوبری، انگور، انار و گلابی نیز می‌توانند در برنامه متعادل قرار گیرند. مصرف زیاد میوه و آبمیوه به دلیل قند بالا توصیه نمی‌شود؛ خود میوه بر آبمیوه اولویت دارد.</p>'
        . '<h3>غلات، حبوبات و روغن‌ها</h3><ul class="drb-checklist"><li>حبوبات پخته مانند لوبیا، عدس و لپه.</li><li>کینوا، جو دوسر، چاودار و نان کامل جو طبق تحمل فرد.</li><li>روغن‌های کنجد، کانولا، آفتابگردان، کتان و روغن زیتون؛ روغن‌های هیدروژنه و نیمه‌هیدروژنه توصیه نمی‌شوند.</li><li><strong>برنج، شیربرنج و همه فرآورده‌های برنجی ممنوع‌اند.</strong> فرآورده‌های حاوی آرد گندم، ذرت، نشاسته ذرت و غلات صبحانه نیز توصیه نمی‌شوند.</li></ul>'
        . '<h3>پروتئین‌ها</h3><ul class="drb-checklist"><li>ماهی‌های آب آزاد مانند سالمون، قزل‌آلا، ساردین و تون به دلیل چربی‌های مفید.</li><li>گوشت سفید ماکیان مانند مرغ و بوقلمون در حد متعادل.</li><li>تخم‌مرغ یا تخم بلدرچینِ مطمئن به‌عنوان منبع پروتئین.</li><li>غذاهای دریایی پوسته‌سخت، ماهی پرورشی، گوشت قرمز و احشا در برنامه پیشنهادی کلینیک توصیه نمی‌شوند.</li></ul>'
        . '<h3>مایعات و نوشیدنی‌ها</h3><ul class="drb-checklist"><li>روزانه حدود ۲ تا ۲٫۵ لیتر آب، مگر این‌که پزشک محدودیت دیگری تعیین کرده باشد.</li><li>دمنوش و چای گیاهی در حد متعادل.</li><li>آب‌های گازدار، نوشیدنی انرژی‌زا و ترکیبات پرکافئین توصیه نمی‌شوند.</li><li>در روزهای نخست پس از جراحی، نوشیدنی بسیار داغ یا یخ‌زده مصرف نشود؛ دمای متعادل انتخاب شود.</li></ul>'
        . '<h3>لبنیات</h3><p>در این رژیم، شیر، ماست، بستنی و هر نوع فرآورده لبنی ممنوع است. پنیر کاتیج تنها استثنای تأییدشده است و به‌دلیل پروتئین بالا می‌تواند در حد برنامه مصرف شود.</p>'
        . '<h3>آجیل، ادویه و مکمل</h3><p>گردو، فندق و پسته در حد متعادل قابل استفاده‌اند؛ بادام‌زمینی و فرآورده‌های آن در برنامه تأییدشده توصیه نشده‌اند. ادویه تند و نمک محدود شود. زردچوبه و زنجبیل فقط در چارچوب برنامه و با توجه به داروهای مصرفی استفاده شوند. هر مکمل، حتی ویتامین C یا E، باید با تأیید پزشک مصرف شود.</p>'
        . '<h3>دخانیات و الکل</h3><p>سیگار، قلیان، ویپ و سایر مواد دخانی خون‌رسانی و اکسیژن‌رسانی بافت را مختل می‌کنند و می‌توانند خطر عفونت و اختلال ترمیم را افزایش دهند. طبق فایل کلینیک، قطع دخانیات دست‌کم ۳ تا ۴ هفته قبل و ۴ تا ۶ هفته پس از جراحی توصیه شده است. الکل نیز می‌تواند فشار خون، التهاب و تورم را افزایش دهد.</p>'
        . '<h3>پرسش‌های پرتکرار تغذیه</h3><div class="drb-faq-list">'
        . '<details open><summary>آیا شیر، ماست یا سایر لبنیات مجاز است؟</summary><p>خیر؛ همه لبنیات ممنوع‌اند. پنیر کاتیج تنها استثنای تأییدشده است و به‌دلیل پروتئین بالا در حد برنامه مجاز است.</p></details>'
        . '<details><summary>آیا پوره سیب‌زمینی یا گوجه‌فرنگی مناسب است؟</summary><p>خیر؛ سیب‌زمینی، پوره سیب‌زمینی و گوجه‌فرنگی التهاب‌زا و در این رژیم ممنوع‌اند.</p></details>'
        . '<details><summary>آیا برنج یا شیربرنج مجاز است؟</summary><p>خیر؛ برنج، شیربرنج و فرآورده‌های برنجی در این رژیم ممنوع‌اند.</p></details>'
        . '<details><summary>این رژیم تا چه مدت ادامه دارد؟</summary><p>حداقل تا یک ماه پس از جراحی، مگر این‌که تیم درمان برای پرونده شما برنامه دیگری تعیین کند.</p></details>'
        . '</div><div class="drb-notice"><strong>جمع‌بندی:</strong> رژیم ضدالتهابی حداقل تا یک ماه پس از جراحی ادامه یابد، مگر این‌که تیم درمان برنامه دیگری تعیین کند. کیفیت مواد غذایی، تعادل وعده‌ها و پایبندی به دستور اختصاصی از هر فهرست عمومی مهم‌تر است.</div>'
        . '<p class="drb-medical-disclaimer">این برنامه برای آموزش عمومی است و جایگزین ارزیابی تغذیه‌ای، نسخه دارویی یا توصیه اختصاصی پزشک نیست.</p>'
        . '</section>';
}

function drb_policy_nutrition_html() {
    return '<section class="drb-patient-guide" data-drb-guide>'
        . '<header class="drb-patient-guide__hero"><span>راهنمای ضروری مراجعان</span><h2>رژیم ضد التهابی و مراقبت‌های جراحی بینی</h2><p>خلاصه کاربردی فایل‌های تأییدشده کلینیک؛ آخرین دستور مستقیم پزشک بر هر نسخه قدیمی یا راهنمای عمومی اولویت دارد.</p></header>'
        . '<div class="drb-patient-guide__tabs" role="tablist" aria-label="بخش‌های راهنمای بیمار">'
        . '<button type="button" role="tab" id="drb-tab-diet" aria-controls="drb-panel-diet" aria-selected="true" tabindex="0" data-guide-tab="diet">رژیم ضد التهابی</button>'
        . '<button type="button" role="tab" id="drb-tab-preop" aria-controls="drb-panel-preop" aria-selected="false" tabindex="-1" data-guide-tab="preop">قبل از عمل</button>'
        . '<button type="button" role="tab" id="drb-tab-postop" aria-controls="drb-panel-postop" aria-selected="false" tabindex="-1" data-guide-tab="postop">بعد از عمل</button>'
        . '<button type="button" role="tab" id="drb-tab-eligibility" aria-controls="drb-panel-eligibility" aria-selected="false" tabindex="-1" data-guide-tab="eligibility">شرایط پذیرش</button>'
        . '</div>'
        . '<div class="drb-patient-guide__panel" role="tabpanel" id="drb-panel-diet" aria-labelledby="drb-tab-diet" data-guide-panel="diet">' . drb_policy_nutrition_tab_html() . '</div>'
        . '<div class="drb-patient-guide__panel" role="tabpanel" id="drb-panel-preop" aria-labelledby="drb-tab-preop" data-guide-panel="preop" hidden>' . drb_policy_preop_html( false ) . '</div>'
        . '<div class="drb-patient-guide__panel" role="tabpanel" id="drb-panel-postop" aria-labelledby="drb-tab-postop" data-guide-panel="postop" hidden>' . drb_policy_postop_html() . '</div>'
        . '<div class="drb-patient-guide__panel" role="tabpanel" id="drb-panel-eligibility" aria-labelledby="drb-tab-eligibility" data-guide-panel="eligibility" hidden>' . drb_policy_eligibility_html() . '</div>'
        . '</section>';
}

function drb_policy_strip_unsafe_content( $html ) {
    $html = (string) $html;
    $unsafe = '(?:بیمارستان\s*میلاد|بینی(?:‌|\s)*گوشتی|گوشتی|fleshy|بینی(?:‌|\s)*(?:فانتزی|عروسکی)|fantasy|جراحی\s*زیبایی\s*بینی\s*کودکان|بالا\s*نگه\s*داشتن\s*سر|سر(?:\s|‌)*بالاتر\s*از\s*بدن|سر(?:\s|‌)*را\s*بالا)';
    for ( $pass = 0; $pass < 3; $pass++ ) {
        $html = preg_replace( '#<(p|li|h[1-6]|blockquote|figcaption)[^>]*>[^<]*(?:<[^>]+>[^<]*)*' . $unsafe . '.*?</\1>#isu', '', $html );
    }
    $html = preg_replace( '#<img[^>]+(?:استاد|professor|faculty)[^>]*>#iu', '', $html );
    $html = preg_replace( '/بیمارستان\s*میلاد(?:\s*[—–-]\s*تهران)?/u', '', $html );
    $html = preg_replace( '/(?:بینی(?:‌|\s)*گوشتی|گوشتی|fleshy)/iu', '', $html );
    $html = preg_replace( '/(?:بینی(?:‌|\s)*(?:فانتزی|عروسکی)|fantasy)/iu', '', $html );
    $html = preg_replace( '/شنبه(?:\s*ها)?\s*(?:و|تا)\s*سه(?:‌|\s)*شنبه(?:\s*ها)?\s*(?:،|—|–|-)?\s*(?:از\s*)?ساعت?\s*۱۵\s*(?:الی|تا|—|–|-)\s*(?:۱۹|۱۸)/u', drb_policy_clinic_hours(), $html );
    $html = preg_replace( '/۱۵\s*(?:الی|تا|—|–|-)\s*۱۹/u', '۱۵:۰۰ تا ۱۸:۰۰', $html );
    return trim( $html );
}

function drb_policy_find_source_post( $source_id, $slug ) {
    $found = get_posts( array(
        'post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => 1,
        'meta_key' => '_drb_source_id', 'meta_value' => (int) $source_id,
    ) );
    if ( $found ) return $found[0];
    return get_page_by_path( $slug, OBJECT, 'post' );
}

function drb_policy_update_article( $source_id, $slug, $title, $excerpt, $content, $remove_thumbnail = false ) {
    // Disabled permanently: live Persian WordPress content is authoritative.
    return false;
}

function drb_policy_upsert_faq( $question, $answer, $category, $order ) {
    // Disabled permanently: localization packages must never mutate Persian FAQs.
    return false;
}

function drb_apply_content_policy_migration() {
    // Intentionally read-only. Historical migrations were removed from the
    // executable theme so activation/admin/front-end requests cannot rewrite
    // Persian posts, pages, FAQs, stats, media, SEO metadata, or clinic options.
    return false;
}
/*
 * IMPORTANT: production Persian content is live-managed and is the source of
 * truth. The legacy migration above is retained only as historical/reference
 * code and is intentionally NOT hooked to activation, admin_init, init, or any
 * request lifecycle. Localization must never rewrite Persian posts, FAQs,
 * stats, SEO fields, media state, or clinic options automatically.
 */

function drb_policy_block_quarantined_attachment() {
    if ( ! is_attachment() || ! get_post_meta( get_queried_object_id(), '_drb_gallery_quarantined', true ) ) return;
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
    include get_404_template();
    exit;
}
add_action( 'template_redirect', 'drb_policy_block_quarantined_attachment' );

function drb_policy_nutrition_url() {
    $post = drb_policy_find_source_post( 2515, 'تاثیر-تغذیه-بعد-از-جراحی-بینی' );
    if ( $post && 'publish' === get_post_status( $post ) ) return get_permalink( $post );
    return home_url( '/تاثیر-تغذیه-بعد-از-جراحی-بینی/' );
}

function drb_is_nutrition_guide() {
    $post = drb_policy_find_source_post( 2515, 'تاثیر-تغذیه-بعد-از-جراحی-بینی' );
    return $post && is_singular( 'post' ) && (int) get_queried_object_id() === (int) $post->ID;
}

function drb_render_nutrition_quicklink( $placed = false ) {
    if ( drb_is_nutrition_guide() ) return;
    ?>
    <aside id="drb-nutrition-quicklink" class="drb-nutrition-quicklink<?php echo $placed ? ' is-placed' : ''; ?>" aria-label="راهنمای ضروری رژیم غذایی">
    <a href="<?php echo esc_url( drb_policy_nutrition_url() ); ?>" aria-label="مطالعه راهنمای ضروری رژیم ضد التهابی">
        <span class="drb-nutrition-quicklink__icon" aria-hidden="true">
            <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.7 5.9C16.2 5.6 9.9 9.6 7.4 15.8c-1.6 4-.8 7.9 1.4 10.5 4.2-7.9 9.5-11.7 15.3-14.1-5.2 3.2-9.7 7.6-12.7 14.3 3.5 1.1 7.1.1 9.7-2.5 4.6-4.5 4.9-11.8 4.6-18.1Z" stroke="currentColor" stroke-width="2.1" stroke-linejoin="round"/><path d="M6.2 27.3c1.7-5.7 5.4-10.4 10.8-13.8" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/></svg>
        </span>
        <span class="drb-nutrition-quicklink__copy"><small>راهنمای ضروری قبل و بعد از عمل</small><strong>رژیم ضد التهابی</strong></span>
        <span class="drb-nutrition-quicklink__arrow" aria-hidden="true">←</span>
    </a>
    </aside>
    <?php
}

function drb_render_nutrition_quicklink_portal() {
    if ( function_exists( 'drb_use_native_template' ) && drb_use_native_template() ) return;
    drb_render_nutrition_quicklink( false );
}
add_action( 'wp_footer', 'drb_render_nutrition_quicklink_portal', 8 );

function drb_enqueue_patient_guide() {
    if ( ! is_singular( 'post' ) ) return;
    $post = drb_policy_find_source_post( 2515, 'تاثیر-تغذیه-بعد-از-جراحی-بینی' );
    if ( ! $post || (int) get_queried_object_id() !== (int) $post->ID ) return;
    wp_enqueue_script( 'drb-patient-guide', DRB_THEME_URI . '/assets/patient-guide.js', array(), DRB_THEME_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'drb_enqueue_patient_guide', 30 );

function drb_render_borderless_favicons() {
    $base = trailingslashit( DRB_THEME_URI ) . 'assets/dist6/';
    ?>
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url( $base . 'favicon-16.png?v=' . DRB_THEME_VERSION ); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( $base . 'favicon-32.png?v=' . DRB_THEME_VERSION ); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url( $base . 'favicon-192.png?v=' . DRB_THEME_VERSION ); ?>">
    <link rel="apple-touch-icon" sizes="192x192" href="<?php echo esc_url( $base . 'favicon-192.png?v=' . DRB_THEME_VERSION ); ?>">
    <?php
}
add_action( 'wp_head', 'drb_render_borderless_favicons', 99 );
