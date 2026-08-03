/**
 * Beautiful 404 Page with Content Hub
 * Auto-replaces default 404 page
 */

add_filter('404_template', 'jannah_custom_404_template');
function jannah_custom_404_template($template) {
    // Start output buffering
    ob_start();
    get_header();
    ?>
    <div class="jannah-404-container">
        <div class="error-hero">
            <div class="error-animation">
                <span class="error-num">4</span>
                <i class="fas fa-dizzy"></i>
                <span class="error-num">4</span>
            </div>
            <h1>صفحه مورد نظر یافت نشد!</h1>
            <p>متاسفانه صفحه‌ای که دنبالش می‌گردید وجود ندارد</p>
        </div>

        <div class="search-section">
            <h3><i class="fas fa-search"></i> جستجو در سایت</h3>
            <form role="search" method="get" action="<?php echo home_url('/'); ?>">
                <input type="search" name="s" placeholder="دنبال چه چیزی می‌گردید؟" />
                <button type="submit">جستجو <i class="fas fa-arrow-left"></i></button>
            </form>
        </div>

        <div class="content-hub">
            <!-- Popular Posts -->
            <div class="hub-section">
                <h3><i class="fas fa-fire"></i> محبوب‌ترین مطالب</h3>
                <div class="posts-grid">
                    <?php
                    $popular = new WP_Query(array(
                        'posts_per_page' => 6,
                        'meta_key' => 'post_views_count',
                        'orderby' => 'meta_value_num',
                        'order' => 'DESC'
                    ));
                    
                    if(!$popular->have_posts()) {
                        $popular = new WP_Query(array('posts_per_page' => 6));
                    }
                    
                    while($popular->have_posts()): $popular->the_post();
                    ?>
                        <a href="<?php the_permalink(); ?>" class="post-card">
                            <?php if(has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('thumbnail'); ?>
                            <?php else: ?>
                                <div class="no-thumb"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                            <h4><?php the_title(); ?></h4>
                        </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </div>

            <!-- Categories -->
            <div class="hub-section">
                <h3><i class="fas fa-folder-open"></i> دسته‌بندی‌ها</h3>
                <div class="categories-cloud">
                    <?php 
                    $categories = get_categories(array('orderby' => 'count', 'order' => 'DESC', 'number' => 15));
                    foreach($categories as $cat) {
                        echo '<a href="' . get_category_link($cat->term_id) . '" class="cat-tag">
                                <i class="fas fa-folder"></i> ' . $cat->name . ' 
                                <span>(' . $cat->count . ')</span>
                              </a>';
                    }
                    ?>
                </div>
            </div>

            <!-- Tags -->
            <div class="hub-section">
                <h3><i class="fas fa-tags"></i> برچسب‌های پرکاربرد</h3>
                <div class="tags-cloud">
                    <?php 
                    $tags = get_tags(array('number' => 20, 'orderby' => 'count', 'order' => 'DESC'));
                    foreach($tags as $tag) {
                        echo '<a href="' . get_tag_link($tag->term_id) . '" class="tag-item">' . $tag->name . '</a>';
                    }
                    ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="hub-section">
                <h3><i class="fas fa-link"></i> دسترسی سریع</h3>
                <div class="quick-links">
                    <a href="<?php echo home_url(); ?>"><i class="fas fa-home"></i> صفحه اصلی</a>
                    <a href="<?php echo home_url('/sitemap'); ?>"><i class="fas fa-sitemap"></i> نقشه سایت</a>
                    <a href="<?php echo home_url('/contact'); ?>"><i class="fas fa-envelope"></i> تماس با ما</a>
                    <a href="<?php echo home_url('/about'); ?>"><i class="fas fa-info-circle"></i> درباره ما</a>
                </div>
            </div>
        </div>
    </div>

    <style>
    .jannah-404-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
    }
    
    .error-hero {
        text-align: center;
        padding: 60px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px;
        margin-bottom: 40px;
    }
    
    .error-animation {
        font-size: 120px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .error-animation i {
        animation: spin 2s infinite;
    }
    
    @keyframes spin {
        0%, 100% { transform: rotate(0deg); }
        50% { transform: rotate(180deg); }
    }
    
    .error-hero h1 {
        font-size: 32px;
        margin: 0 0 10px;
    }
    
    .search-section {
        max-width: 600px;
        margin: -30px auto 40px;
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    .search-section h3 {
        margin: 0 0 20px;
        color: #333;
    }
    
    .search-section form {
        display: flex;
        gap: 10px;
    }
    
    .search-section input {
        flex: 1;
        padding: 12px 20px;
        border: 2px solid #e0e0e0;
        border-radius: 50px;
        font-size: 16px;
    }
    
    .search-section button {
        padding: 12px 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
    }
    
    .search-section button:hover {
        transform: scale(1.05);
    }
    
    .content-hub {
        display: grid;
        gap: 30px;
    }
    
    .hub-section {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .hub-section h3 {
        margin: 0 0 20px;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .hub-section h3 i {
        color: #667eea;
    }
    
    .posts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
    }
    
    .post-card {
        text-decoration: none;
        color: #333;
        transition: 0.3s;
    }
    
    .post-card:hover {
        transform: translateY(-5px);
    }
    
    .post-card img, .post-card .no-thumb {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 10px;
    }
    
    .post-card .no-thumb {
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ccc;
        font-size: 40px;
    }
    
    .post-card h4 {
        margin: 0;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .categories-cloud, .tags-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .cat-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 15px;
        background: linear-gradient(135deg, #4ECDC4 0%, #44A5A0 100%);
        color: white;
        border-radius: 20px;
        text-decoration: none;
        transition: 0.3s;
    }
    
    .cat-tag:hover {
        transform: scale(1.05);
    }
    
    .tag-item {
        padding: 6px 15px;
        background: #f0f0f0;
        color: #333;
        border-radius: 15px;
        text-decoration: none;
        transition: 0.3s;
    }
    
    .tag-item:hover {
        background: #667eea;
        color: white;
    }
    
    .quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .quick-links a {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        text-decoration: none;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: 0.3s;
    }
    
    .quick-links a:hover {
        background: #667eea;
        color: white;
    }
    
    @media (max-width: 768px) {
        .error-animation { font-size: 80px; }
        .posts-grid { grid-template-columns: repeat(2, 1fr); }
        .search-section form { flex-direction: column; }
    }
    </style>
    <?php
    get_footer();
    $content = ob_get_clean();
    echo $content;
    exit;
}
