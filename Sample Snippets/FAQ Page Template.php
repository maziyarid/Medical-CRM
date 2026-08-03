/**
 * Teznevisan FAQ Page Template
 * Version: 2.1.0
 */

if (!defined('ABSPATH')) exit;

function tez_render_faq_page() {
    ?>
    <!-- Page Hero -->
    <section class="tez-page-hero">
        <div class="tez-hero-bg">
            <div class="tez-hero-pattern"></div>
        </div>
        <div class="tez-container">
            <div class="tez-hero-content">
                <h1>سوالات متداول</h1>
                <p>پاسخ به سوالات رایج شما</p>
                <nav class="tez-breadcrumb" aria-label="مسیر">
                    <a href="<?php echo home_url('/'); ?>">خانه</a>
                    <span>/</span>
                    <span>سوالات متداول</span>
                </nav>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="tez-section tez-faq-section">
        <div class="tez-container">
            <div class="tez-faq-layout">
                <!-- FAQ Categories -->
                <aside class="tez-faq-sidebar">
                    <div class="tez-faq-nav">
                        <h3>دسته‌بندی سوالات</h3>
                        <ul class="tez-faq-categories">
                            <li><a href="#faq-general" class="active"><i class="fa-solid fa-circle-question"></i> سوالات عمومی</a></li>
                            <li><a href="#faq-thesis"><i class="fa-solid fa-graduation-cap"></i> پایان‌نامه</a></li>
                            <li><a href="#faq-article"><i class="fa-solid fa-newspaper"></i> مقاله</a></li>
                            <li><a href="#faq-pricing"><i class="fa-solid fa-dollar-sign"></i> قیمت و پرداخت</a></li>
                            <li><a href="#faq-quality"><i class="fa-solid fa-certificate"></i> کیفیت و تضمین</a></li>
                            <li><a href="#faq-support"><i class="fa-solid fa-headset"></i> پشتیبانی</a></li>
                        </ul>
                    </div>
                    
                    <div class="tez-faq-contact">
                        <h4>سوال دیگری دارید؟</h4>
                        <p>با ما تماس بگیرید:</p>
                        <a href="tel:<?php echo TEZ_PHONE; ?>" class="tez-faq-phone">
                            <i class="fa-solid fa-phone"></i>
                            <?php echo TEZ_PHONE_DISPLAY; ?>
                        </a>
                    </div>
                </aside>
                
                <!-- FAQ Content -->
                <div class="tez-faq-content">
                    <!-- General Questions -->
                    <div class="tez-faq-category" id="faq-general">
                        <h2><i class="fa-solid fa-circle-question"></i> سوالات عمومی</h2>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                تز نویسان چه خدماتی ارائه می‌دهد؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>موسسه تز نویسان خدمات متنوعی در زمینه پژوهش و پروژه‌های دانشجویی ارائه می‌دهد که شامل:</p>
                                <ul>
                                    <li>انجام پایان‌نامه کارشناسی ارشد و دکتری</li>
                                    <li>نوشتن پروپوزال و طرح پژوهش</li>
                                    <li>نگارش و چاپ مقاله علمی (ISI, Scopus, داخلی)</li>
                                    <li>تحلیل آماری با نرم‌افزارهای مختلف</li>
                                    <li>ترجمه تخصصی متون علمی</li>
                                    <li>پروژه‌های برنامه‌نویسی و نرم‌افزار</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                چطور می‌توانم سفارش ثبت کنم؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>برای ثبت سفارش می‌توانید:</p>
                                <ol>
                                    <li>فرم ثبت سفارش را در سایت تکمیل کنید</li>
                                    <li>با شماره <?php echo TEZ_PHONE_DISPLAY; ?> تماس بگیرید</li>
                                    <li>از طریق واتساپ یا تلگرام پیام دهید</li>
                                </ol>
                                <p>پس از ثبت درخواست، کارشناسان ما در کمتر از ۲ ساعت با شما تماس خواهند گرفت.</p>
                            </div>
                        </div>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                آیا اطلاعات من محرمانه می‌ماند؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>بله، محرمانگی اطلاعات یکی از اصول اساسی کار ما است. تمامی اطلاعات شخصی و پروژه شما کاملاً محرمانه نگهداری می‌شود و با هیچ شخص ثالثی به اشتراک گذاشته نخواهد شد. ما متعهد به حفظ حریم خصوصی شما هستیم.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thesis Questions -->
                    <div class="tez-faq-category" id="faq-thesis">
                        <h2><i class="fa-solid fa-graduation-cap"></i> سوالات مربوط به پایان‌نامه</h2>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                انجام یک پایان‌نامه چقدر طول می‌کشد؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>مدت زمان انجام پایان‌نامه بستگی به عوامل مختلفی دارد:</p>
                                <ul>
                                    <li>پایان‌نامه کارشناسی ارشد: معمولاً ۳ تا ۶ ماه</li>
                                    <li>رساله دکتری: معمولاً ۶ تا ۱۲ ماه</li>
                                </ul>
                                <p>البته این زمان‌ها تقریبی است و بسته به موضوع، پیچیدگی و نیازهای خاص پروژه می‌تواند متفاوت باشد. در صورت نیاز فوری، امکان تسریع در کار نیز وجود دارد.</p>
                            </div>
                        </div>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                آیا موضوع پایان‌نامه را هم پیشنهاد می‌دهید؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>بله، در صورتی که هنوز موضوع مشخصی ندارید، تیم ما می‌تواند بر اساس رشته تحصیلی و علایق شما، موضوعات نوآورانه و کاربردی پیشنهاد دهد. موضوعات پیشنهادی با توجه به منابع موجود، قابلیت اجرا و جدید بودن انتخاب می‌شوند.</p>
                            </div>
                        </div>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                آیا جلسه توجیهی قبل از دفاع دارید؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>بله، یکی از خدمات ما برگزاری جلسه توجیهی قبل از دفاع است. در این جلسه:</p>
                                <ul>
                                    <li>محتوای پایان‌نامه به طور کامل توضیح داده می‌شود</li>
                                    <li>نکات کلیدی برای ارائه مرور می‌شود</li>
                                    <li>سوالات احتمالی داوران بررسی می‌شود</li>
                                    <li>نحوه پاسخگویی به سوالات آموزش داده می‌شود</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Article Questions -->
                    <div class="tez-faq-category" id="faq-article">
                        <h2><i class="fa-solid fa-newspaper"></i> سوالات مربوط به مقاله</h2>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                تضمین می‌دهید مقاله من چاپ شود؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>ما تضمین می‌کنیم که مقاله با بالاترین کیفیت نوشته شود و فرآیند ارسال و پیگیری را تا پذیرش انجام می‌دهیم. اما پذیرش نهایی به تشخیص داوران و سردبیر مجله بستگی دارد. با این حال، تجربه ما نشان می‌دهد که بیش از ۹۰٪ مقالات ما پذیرفته می‌شوند.</p>
                            </div>
                        </div>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                اگر مقاله رد شود چه می‌شود؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>در صورت رد مقاله، ما آن را بدون دریافت هزینه اضافی اصلاح کرده و به مجله دیگری ارسال می‌کنیم. این کار را تا پذیرش نهایی ادامه می‌دهیم.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pricing Questions -->
                    <div class="tez-faq-category" id="faq-pricing">
                        <h2><i class="fa-solid fa-dollar-sign"></i> قیمت و پرداخت</h2>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                قیمت خدمات چگونه تعیین می‌شود؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>قیمت خدمات بر اساس عوامل زیر تعیین می‌شود:</p>
                                <ul>
                                    <li>نوع پروژه (پایان‌نامه، مقاله، پروپوزال و...)</li>
                                    <li>مقطع تحصیلی (کارشناسی، ارشد، دکتری)</li>
                                    <li>حجم و پیچیدگی کار</li>
                                    <li>مهلت تحویل</li>
                                    <li>خدمات جانبی مورد نیاز</li>
                                </ul>
                                <p>برای دریافت قیمت دقیق، لطفاً با ما تماس بگیرید یا فرم سفارش را تکمیل کنید.</p>
                            </div>
                        </div>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                آیا امکان پرداخت اقساطی وجود دارد؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>بله، برای پروژه‌های بلندمدت امکان پرداخت اقساطی فراهم شده است. معمولاً نحوه پرداخت به این صورت است:</p>
                                <ul>
                                    <li>۵۰٪ هنگام شروع کار (پیش‌پرداخت)</li>
                                    <li>۵۰٪ باقیمانده هنگام تحویل نهایی</li>
                                </ul>
                                <p>برای پروژه‌های طولانی‌تر، امکان تقسیم پرداخت به مراحل بیشتر نیز وجود دارد.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quality Questions -->
                    <div class="tez-faq-category" id="faq-quality">
                        <h2><i class="fa-solid fa-certificate"></i> کیفیت و تضمین</h2>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                چگونه از کیفیت کار اطمینان حاصل کنم؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>برای تضمین کیفیت، ما اقدامات زیر را انجام می‌دهیم:</p>
                                <ul>
                                    <li>تخصیص پروژه به محقق متخصص در همان رشته</li>
                                    <li>بررسی دقیق Plagiarism با نرم‌افزارهای معتبر</li>
                                    <li>ویراستاری علمی و ادبی</li>
                                    <li>کنترل کیفیت نهایی قبل از تحویل</li>
                                    <li>ارسال گزارش پیشرفت در مراحل مختلف</li>
                                    <li>امکان درخواست اصلاحات پس از تحویل</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                اگر از کار راضی نباشم چه؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>رضایت شما برای ما اهمیت دارد. اگر پس از تحویل، نیاز به اصلاحات داشتید:</p>
                                <ul>
                                    <li>اصلاحات مطابق با توافق اولیه، رایگان انجام می‌شود</li>
                                    <li>تا زمان رضایت کامل شما، پشتیبانی ادامه می‌یابد</li>
                                    <li>در موارد خاص، امکان بازگشت وجه وجود دارد</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Support Questions -->
                    <div class="tez-faq-category" id="faq-support">
                        <h2><i class="fa-solid fa-headset"></i> پشتیبانی</h2>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                ساعات پاسخگویی شما چگونه است؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>ساعات پاسخگویی ما:</p>
                                <ul>
                                    <li>تماس تلفنی: شنبه تا پنجشنبه، ۹ صبح تا ۶ عصر</li>
                                    <li>واتساپ و تلگرام: تا ساعت ۱۱ شب</li>
                                    <li>ایمیل: پاسخ طی ۲۴ ساعت</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="tez-faq-item">
                            <button class="tez-faq-question" aria-expanded="false">
                                آیا می‌توانم با محقق پروژه‌ام ارتباط داشته باشم؟
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="tez-faq-answer">
                                <p>بله، یکی از مزایای ما امکان ارتباط مستقیم با محقق پروژه است. شما می‌توانید در تمام مراحل با محقق خود در ارتباط باشید و سوالات خود را مطرح کنید.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="tez-section tez-cta-section">
        <div class="tez-container">
            <div class="tez-cta-content">
                <h2>سوال دیگری دارید؟</h2>
                <p>با ما تماس بگیرید، کارشناسان ما آماده پاسخگویی هستند</p>
                <div class="tez-cta-buttons">
                    <a href="<?php echo home_url('/contact'); ?>" class="tez-btn tez-btn-white tez-btn-lg">
                        <i class="fa-solid fa-envelope"></i> تماس با ما
                    </a>
                    <a href="tel:<?php echo TEZ_PHONE; ?>" class="tez-btn tez-btn-outline-white tez-btn-lg">
                        <i class="fa-solid fa-phone"></i> <?php echo TEZ_PHONE_DISPLAY; ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php
}
