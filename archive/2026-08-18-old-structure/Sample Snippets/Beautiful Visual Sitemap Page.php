/**
 * Beautiful Visual Sitemap Page
 * Create a page with slug "sitemap" and it will auto-populate
 */

add_filter('the_content', 'jannah_visual_sitemap_content');
function jannah_visual_sitemap_content($content) {
    if (!is_page('sitemap')) return $content;
    
    ob_start();
    ?>
    <div class="jannah-sitemap-container">
        <!-- Header -->
        <div class="sitemap-header">
            <i class="fas fa-sitemap"></i>
            <h2>نقشه سایت</h2>
            <p>تمامی محتویات سایت در یک نگاه</p>
        </div>

        <!-- Search Box -->
        <div class="sitemap-search">
            <form role="search" method="get" action="<?php echo home_url('/'); ?>">
                <input type="search" placeholder="جستجو در سایت..." name="s" />
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="sitemap-grid">
            <!-- Pages Section -->
            <div class="sitemap-section">
                <div class="section-header" style="--color: #3498db;">
                    <i class="fas fa-file-alt"></i>
                    <h3>صفحات</h3>
                </div>
                <ul class="sitemap-list">
                    <?php
                    $pages = get_pages(array('number' => 20));
                    foreach($pages as $page) {
                        echo '<li><a href="' . get_page_link($page->ID) . '"><i class="fas fa-angle-left"></i> ' . $page->post_title . '</a></li>';
                    }
                    ?>
                </ul>
            </div>

            <!-- Recent Posts -->
            <div class="sitemap-section">
                <div class="section-header" style="--color: #e74c3c;">
                    <i class="fas fa-newspaper"></i>
                    <h3>آخرین مطالب</h3>
                </div>
                <ul class="sitemap-list">
                    <?php
                    $recent_posts = wp_get_recent_posts(array('numberposts' => 15, 'post_status' => 'publish'));
                    foreach($recent_posts as $post) {
                        echo '<li><a href="' . get_permalink($post["ID"]) . '"><i class="fas fa-angle-left"></i> ' . $post["post_title"] . '</a></li>';
                    }
                    ?>
                </ul>
            </div>

            <!-- Categories -->
            <div class="sitemap-section">
                <div class="section-header" style="--color: #f39c12;">
                    <i class="fas fa-folder-open"></i>
                    <h3>دسته‌بندی‌ها</h3>
                </div>
                <div class="category-grid">
                    <?php 
                    $categories = get_categories(array('orderby' => 'count', 'order' => 'DESC', 'number' => 20));
                    foreach($categories as $cat) {
                        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        echo '<a href="' . get_category_link($cat->term_id) . '" class="category-card" style="--cat-color: ' . $color . ';">
                                <i class="fas fa-folder"></i>
                                <span>' . $cat->name . '</span>
                                <small>' . $cat->count . ' مطلب</small>
                              </a>';
                    }
                    ?>
                </div>
            </div>

            <!-- Tags Cloud -->
            <div class="sitemap-section">
                <div class="section-header" style="--color: #9b59b6;">
                    <i class="fas fa-tags"></i>
                    <h3>برچسب‌ها</h3>
                </div>
                <div class="tags-cloud">
                    <?php 
                    $tags = get_tags(array('number' => 30));
                    foreach($tags as $tag) {
                        $size = rand(14, 24);
                        echo '<a href="' . get_tag_link($tag->term_id) . '" style="font-size:' . $size . 'px;">' . $tag->name . '</a>';
                    }
                    ?>
                </div>
            </div>

            <!-- Archives -->
            <div class="sitemap-section">
                <div class="section-header" style="--color: #1abc9c;">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>آرشیو ماهانه</h3>
                </div>
                <ul class="sitemap-list">
                    <?php wp_get_archives(array('type' => 'monthly', 'limit' => 12)); ?>
                </ul>
            </div>

            <!-- Authors -->
            <div class="sitemap-section">
                <div class="section-header" style="--color: #34495e;">
                    <i class="fas fa-users"></i>
                    <h3>نویسندگان</h3>
                </div>
                <div class="authors-grid">
                    <?php
                    $authors = get_users(array('who' => 'authors', 'orderby' => 'post_count', 'order' => 'DESC'));
                    foreach($authors as $author) {
                        echo '<a href="' . get_author_posts_url($author->ID) . '" class="author-card">
                                ' . get_avatar($author->ID, 50) . '
                                <span>' . $author->display_name . '</span>
                              </a>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Add styles
add_action('wp_head', 'jannah_sitemap_styles');
function jannah_sitemap_styles() {
    if (!is_page('sitemap')) return;
    ?>
    <style>
    .jannah-sitemap-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        font-family: inherit;
    }
    
    .sitemap-header {
        text-align: center;
        padding: 40px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px;
        margin-bottom: 40px;
    }
    
    .sitemap-header i {
        font-size: 48px;
        margin-bottom: 20px;
        display: block;
    }
    
    .sitemap-header h2 {
        font-size: 32px;
        margin: 0 0 10px;
    }
    
    .sitemap-search {
        max-width: 600px;
        margin: -20px auto 40px;
        position: relative;
    }
    
    .sitemap-search form {
        display: flex;
        background: white;
        border-radius: 50px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .sitemap-search input {
        flex: 1;
        padding: 15px 25px;
        border: none;
        font-size: 16px;
    }
    
    .sitemap-search button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 0 30px;
        color: white;
        cursor: pointer;
    }
    
    .sitemap-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
    }
    
    .sitemap-section {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        transition: transform 0.3s;
    }
    
    .sitemap-section:hover {
        transform: translateY(-5px);
    }
    
    .section-header {
        background: var(--color);
        color: white;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-header i {
        font-size: 24px;
    }
    
    .section-header h3 {
        margin: 0;
        font-size: 18px;
    }
    
    .sitemap-list {
        list-style: none;
        padding: 20px;
        margin: 0;
    }
    
    .sitemap-list li {
        margin-bottom: 10px;
    }
    
    .sitemap-list a {
        color: #333;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
        padding: 5px;
    }
    
    .sitemap-list a:hover {
        color: #667eea;
        padding-right: 10px;
    }
    
    .category-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        padding: 20px;
    }
    
    .category-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px;
        background: linear-gradient(135deg, var(--cat-color)20 0%, var(--cat-color)10 100%);
        border: 2px solid var(--cat-color);
        border-radius: 10px;
        text-decoration: none;
        color: #333;
        transition: 0.3s;
    }
    
    .category-card:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .category-card i {
        font-size: 24px;
        color: var(--cat-color);
        margin-bottom: 5px;
    }
    
    .tags-cloud {
        padding: 20px;
        text-align: center;
    }
    
    .tags-cloud a {
        display: inline-block;
        margin: 5px;
        padding: 5px 15px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border-radius: 20px;
        text-decoration: none;
        transition: 0.3s;
    }
    
    .tags-cloud a:hover {
        transform: scale(1.1);
    }
    
    .authors-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        padding: 20px;
    }
    
    .author-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 10px;
        text-decoration: none;
        color: #333;
        transition: 0.3s;
    }
    
    .author-card:hover {
        background: #e9ecef;
    }
    
    .author-card img {
        border-radius: 50%;
    }
    
    @media (max-width: 768px) {
        .sitemap-grid {
            grid-template-columns: 1fr;
        }
        .category-grid, .authors-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
}
