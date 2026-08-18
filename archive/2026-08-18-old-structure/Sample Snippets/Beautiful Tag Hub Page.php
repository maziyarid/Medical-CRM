/**
 * Tag Hub Page Template
 * Creates a custom page template for displaying all tags
 * Select "Tag Hub" from page template dropdown when creating/editing a page
 */

// Register the page template
add_filter('theme_page_templates', 'jannah_add_tag_hub_template');
add_filter('page_template', 'jannah_tag_hub_template_include');

function jannah_add_tag_hub_template($templates) {
    $templates['tag-hub-template'] = 'مرکز برچسب‌ها';
    return $templates;
}

function jannah_tag_hub_template_include($template) {
    if (get_page_template_slug() === 'tag-hub-template') {
        add_action('wp_enqueue_scripts', 'jannah_tag_hub_assets');
        
        // Override the page content
        add_filter('the_content', 'jannah_tag_hub_render_content', 999);
    }
    return $template;
}

// Enqueue assets
function jannah_tag_hub_assets() {
    if (!wp_style_is('font-awesome', 'enqueued')) {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    }
}

// Render the tag hub content
function jannah_tag_hub_render_content($content) {
    if (get_page_template_slug() !== 'tag-hub-template') {
        return $content;
    }
    
    ob_start();
    ?>
    <div class="jannah-tags-hub-wrapper">
        <!-- Hero Section -->
        <div class="tags-hero-section">
            <div class="hero-content">
                <i class="fas fa-tags"></i>
                <h1>مرکز برچسب‌ها</h1>
                <p>کاوش در تمامی موضوعات و برچسب‌های سایت</p>
            </div>
            <div class="tags-stats">
                <?php 
                $tag_count = wp_count_terms('post_tag');
                $all_tags = get_tags();
                $total_posts = 0;
                foreach($all_tags as $tag) {
                    $total_posts += $tag->count;
                }
                ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $tag_count; ?></span>
                    <span class="stat-label">برچسب</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_posts; ?></span>
                    <span class="stat-label">مطلب</span>
                </div>
            </div>
        </div>

        <!-- Search Filter -->
        <div class="tags-filter-section">
            <div class="filter-container">
                <i class="fas fa-search"></i>
                <input type="text" id="tag-filter-input" placeholder="جستجو در برچسب‌ها..." />
                <span class="filter-clear" onclick="clearFilter()">×</span>
            </div>
        </div>

        <!-- Tags Grid -->
        <div class="tags-grid-container">
            <?php
            $tags = get_tags(array(
                'orderby' => 'count',
                'order' => 'DESC',
                'hide_empty' => true
            ));
            
            $colors = array(
                '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', 
                '#FFEAA7', '#DDA0DD', '#F7DC6F', '#FF8CC8',
                '#6C5CE7', '#00B894', '#FDCB6E', '#E17055'
            );
            
            foreach($tags as $index => $tag):
                $color = $colors[$index % count($colors)];
                $recent_posts = get_posts(array(
                    'tag_id' => $tag->term_id,
                    'numberposts' => 5,
                    'post_status' => 'publish'
                ));
            ?>
            <div class="tag-card" data-tag-name="<?php echo esc_attr(strtolower($tag->name)); ?>" style="--tag-color: <?php echo $color; ?>;">
                <div class="tag-card-header">
                    <div class="tag-icon">
                        <i class="fas fa-hashtag"></i>
                    </div>
                    <div class="tag-info">
                        <h3 class="tag-name"><?php echo esc_html($tag->name); ?></h3>
                        <span class="tag-count"><?php echo $tag->count; ?> مطلب</span>
                    </div>
                    <a href="<?php echo get_tag_link($tag->term_id); ?>" class="tag-view-all">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
                
                <?php if(!empty($recent_posts)): ?>
                <div class="tag-posts-list">
                    <?php foreach($recent_posts as $post): ?>
                    <a href="<?php echo get_permalink($post->ID); ?>" class="tag-post-link">
                        <i class="fas fa-chevron-left"></i>
                        <span><?php echo esc_html($post->post_title); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="tag-empty">
                    <p>هنوز مطلبی منتشر نشده</p>
                </div>
                <?php endif; ?>
                
                <div class="tag-card-footer">
                    <a href="<?php echo get_tag_link($tag->term_id); ?>" class="view-tag-btn">
                        مشاهده همه مطالب <i class="fas fa-long-arrow-alt-left"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- No Results Message -->
        <div class="no-results-message" style="display:none;">
            <i class="fas fa-search"></i>
            <p>هیچ برچسبی یافت نشد</p>
        </div>
    </div>

    <style>
    .jannah-tags-hub-wrapper {
        margin: -30px -30px 30px;
        font-family: inherit;
    }
    
    .tags-hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 60px 30px;
        text-align: center;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .tags-hero-section::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 30s linear infinite;
    }
    
    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .hero-content {
        position: relative;
        z-index: 1;
    }
    
    .hero-content i {
        font-size: 60px;
        margin-bottom: 20px;
        display: block;
        animation: bounce 2s infinite;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    .hero-content h1 {
        font-size: 36px;
        margin: 0 0 10px;
        font-weight: 700;
    }
    
    .hero-content p {
        font-size: 18px;
        opacity: 0.9;
    }
    
    .tags-stats {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-top: 30px;
        position: relative;
        z-index: 1;
    }
    
    .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .stat-number {
        font-size: 36px;
        font-weight: 700;
    }
    
    .stat-label {
        font-size: 14px;
        opacity: 0.8;
    }
    
    .tags-filter-section {
        max-width: 600px;
        margin: -30px auto 40px;
        padding: 0 20px;
        position: relative;
        z-index: 2;
    }
    
    .filter-container {
        position: relative;
        background: white;
        border-radius: 50px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .filter-container i {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }
    
    #tag-filter-input {
        width: 100%;
        padding: 15px 50px;
        border: none;
        font-size: 16px;
        background: transparent;
    }
    
    .filter-clear {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 24px;
        color: #999;
        cursor: pointer;
        display: none;
    }
    
    .filter-clear:hover {
        color: #333;
    }
    
    .tags-grid-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        padding: 0 30px;
        margin-bottom: 40px;
    }
    
    .tag-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border-top: 4px solid var(--tag-color);
    }
    
    .tag-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    
    .tag-card-header {
        padding: 20px;
        background: linear-gradient(135deg, var(--tag-color)15 0%, var(--tag-color)05 100%);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .tag-icon {
        width: 50px;
        height: 50px;
        background: var(--tag-color);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }
    
    .tag-info {
        flex: 1;
    }
    
    .tag-name {
        margin: 0;
        font-size: 18px;
        color: #333;
        font-weight: 700;
    }
    
    .tag-count {
        font-size: 13px;
        color: #666;
    }
    
    .tag-view-all {
        width: 36px;
        height: 36px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--tag-color);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: 0.3s;
    }
    
    .tag-view-all:hover {
        transform: scale(1.1);
    }
    
    .tag-posts-list {
        padding: 15px 20px;
    }
    
    .tag-post-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        margin-bottom: 8px;
        background: #f8f9fa;
        border-radius: 8px;
        text-decoration: none;
        color: #333;
        transition: 0.3s;
        font-size: 14px;
    }
    
    .tag-post-link:hover {
        background: var(--tag-color)20;
        padding-right: 15px;
    }
    
    .tag-post-link i {
        color: var(--tag-color);
        font-size: 10px;
    }
    
    .tag-post-link span {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .tag-empty {
        padding: 30px;
        text-align: center;
        color: #999;
    }
    
    .tag-card-footer {
        padding: 15px 20px;
        background: #f8f9fa;
    }
    
    .view-tag-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 10px;
        background: var(--tag-color);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: 0.3s;
    }
    
    .view-tag-btn:hover {
        opacity: 0.9;
        gap: 12px;
    }
    
    .no-results-message {
        text-align: center;
        padding: 60px;
        color: #999;
    }
    
    .no-results-message i {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
    }
    
    @media (max-width: 768px) {
        .tags-grid-container {
            grid-template-columns: 1fr;
            padding: 0 15px;
        }
        
        .tags-hero-section {
            padding: 40px 20px;
        }
        
        .hero-content h1 {
            font-size: 28px;
        }
    }
    </style>

    <script>
    // Filter functionality
    document.getElementById('tag-filter-input').addEventListener('input', function() {
        var searchTerm = this.value.toLowerCase();
        var tagCards = document.querySelectorAll('.tag-card');
        var clearBtn = document.querySelector('.filter-clear');
        var visibleCount = 0;
        
        // Show/hide clear button
        clearBtn.style.display = searchTerm ? 'block' : 'none';
        
        tagCards.forEach(function(card) {
            var tagName = card.getAttribute('data-tag-name');
            if (tagName.includes(searchTerm)) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show no results message
        var noResults = document.querySelector('.no-results-message');
        if (visibleCount === 0 && searchTerm) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
    });
    
    function clearFilter() {
        document.getElementById('tag-filter-input').value = '';
        document.getElementById('tag-filter-input').dispatchEvent(new Event('input'));
    }
    </script>
    <?php
    
    return ob_get_clean();
}
