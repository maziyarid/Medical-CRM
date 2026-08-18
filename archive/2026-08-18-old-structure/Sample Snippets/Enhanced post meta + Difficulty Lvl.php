/**
 * Enhanced Single Post Meta with Integrated Badges
 * - Reading time text changed to: دقیقه زمان مطالعه
 * - Content Type & Difficulty Level badges inside post-meta
 * - Improved UI/UX with better styling
 * Compatible with Jannah News Theme
 * Version: 5.0
 */

// =====================================================
// INITIALIZATION
// =====================================================
add_action('init', 'jannah_enhanced_meta_init', 20);
function jannah_enhanced_meta_init() {
    add_action('wp_enqueue_scripts', 'jannah_enhanced_meta_assets');
    add_action('admin_enqueue_scripts', 'jannah_badges_admin_assets');
}

// Frontend assets
function jannah_enhanced_meta_assets() {
    if (! wp_style_is('font-awesome', 'enqueued')) {
        wp_enqueue_style('jannah-fa-badges', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4. 0');
    }
}

// Admin assets
function jannah_badges_admin_assets($hook) {
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }
    wp_enqueue_style('jannah-fa-admin-badges', 'https://cdnjs. cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4. 0');
}

// =====================================================
// CONTENT TYPES DEFINITION
// =====================================================
function jannah_get_content_types() {
    return array(
        'tutorial' => array(
            'label' => 'آموزش',
            'icon' => 'fa-graduation-cap',
            'color' => '#00bfa5',
            'bg_color' => 'rgba(0, 191, 165, 0.12)'
        ),
        'opinion' => array(
            'label' => 'دیدگاه',
            'icon' => 'fa-comment-dots',
            'color' => '#ff6b6b',
            'bg_color' => 'rgba(255, 107, 107, 0.12)'
        ),
        'news' => array(
            'label' => 'خبر',
            'icon' => 'fa-newspaper',
            'color' => '#4ecdc4',
            'bg_color' => 'rgba(78, 205, 196, 0.12)'
        ),
        'case_study' => array(
            'label' => 'مطالعه موردی',
            'icon' => 'fa-microscope',
            'color' => '#8e44ad',
            'bg_color' => 'rgba(142, 68, 173, 0.12)'
        ),
        'review' => array(
            'label' => 'بررسی و نقد',
            'icon' => 'fa-star-half-stroke',
            'color' => '#f39c12',
            'bg_color' => 'rgba(243, 156, 18, 0.12)'
        ),
        'guide' => array(
            'label' => 'راهنما',
            'icon' => 'fa-map-signs',
            'color' => '#3498db',
            'bg_color' => 'rgba(52, 152, 219, 0. 12)'
        ),
        'analysis' => array(
            'label' => 'تحلیل',
            'icon' => 'fa-chart-line',
            'color' => '#e74c3c',
            'bg_color' => 'rgba(231, 76, 60, 0. 12)'
        ),
        'interview' => array(
            'label' => 'مصاحبه',
            'icon' => 'fa-microphone',
            'color' => '#9b59b6',
            'bg_color' => 'rgba(155, 89, 182, 0.12)'
        )
    );
}

// =====================================================
// DIFFICULTY LEVELS DEFINITION
// =====================================================
function jannah_get_difficulty_levels() {
    return array(
        'beginner' => array(
            'label' => 'مبتدی',
            'icon' => 'fa-seedling',
            'color' => '#2ecc71',
            'bg_color' => 'rgba(46, 204, 113, 0. 12)'
        ),
        'elementary' => array(
            'label' => 'ابتدایی',
            'icon' => 'fa-user-graduate',
            'color' => '#3498db',
            'bg_color' => 'rgba(52, 152, 219, 0. 12)'
        ),
        'intermediate' => array(
            'label' => 'متوسط',
            'icon' => 'fa-fire',
            'color' => '#f39c12',
            'bg_color' => 'rgba(243, 156, 18, 0. 12)'
        ),
        'advanced' => array(
            'label' => 'پیشرفته',
            'icon' => 'fa-rocket',
            'color' => '#e74c3c',
            'bg_color' => 'rgba(231, 76, 60, 0.12)'
        ),
        'expert' => array(
            'label' => 'حرفه‌ای',
            'icon' => 'fa-crown',
            'color' => '#9b59b6',
            'bg_color' => 'rgba(155, 89, 182, 0.12)'
        ),
        'master' => array(
            'label' => 'استادی',
            'icon' => 'fa-gem',
            'color' => '#34495e',
            'bg_color' => 'rgba(52, 73, 94, 0.12)'
        )
    );
}

// =====================================================
// ADMIN META BOXES
// =====================================================
add_action('add_meta_boxes', 'jannah_add_badges_metaboxes');
function jannah_add_badges_metaboxes() {
    add_meta_box(
        'jannah_content_type',
        'نوع محتوا - Content Type',
        'jannah_content_type_metabox_html',
        'post',
        'side',
        'high'
    );
    
    add_meta_box(
        'jannah_difficulty_level',
        'سطح دشواری - Difficulty Level',
        'jannah_difficulty_metabox_html',
        'post',
        'side',
        'high'
    );
}

// Content Type Meta Box HTML
function jannah_content_type_metabox_html($post) {
    wp_nonce_field('jannah_content_type_save', 'jannah_content_type_nonce');
    $selected = get_post_meta($post->ID, '_jannah_content_type', true);
    $types = jannah_get_content_types();
    ?>
    <style>
        .content-type-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }
        .content-type-option {
            position: relative;
        }
        .content-type-option input {
            display: none;
        }
        .content-type-option label {
            display: block;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            transition: all 0. 3s;
            font-size: 12px;
        }
        .content-type-option input:checked + label {
            border-color: var(--color);
            background: var(--bg);
        }
        .content-type-option label i {
            display: block;
            margin-bottom: 4px;
            font-size: 18px;
            color: var(--color);
        }
    </style>
    <div class="content-type-grid">
        <?php foreach ($types as $key => $type) : ?>
            <div class="content-type-option" style="--color: <?php echo $type['color']; ?>; --bg: <?php echo $type['bg_color']; ?>;">
                <input type="radio" id="ct_<?php echo $key; ?>" name="jannah_content_type" value="<?php echo $key; ?>" <?php checked($selected, $key); ?>>
                <label for="ct_<?php echo $key; ?>">
                    <i class="fas <?php echo $type['icon']; ?>"></i>
                    <?php echo $type['label']; ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

// Difficulty Level Meta Box HTML
function jannah_difficulty_metabox_html($post) {
    wp_nonce_field('jannah_difficulty_save', 'jannah_difficulty_nonce');
    $selected = get_post_meta($post->ID, '_jannah_difficulty_level', true);
    $levels = jannah_get_difficulty_levels();
    ?>
    <style>
        .difficulty-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }
        .difficulty-option {
            position: relative;
        }
        .difficulty-option input {
            display: none;
        }
        .difficulty-option label {
            display: block;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            font-size: 12px;
        }
        .difficulty-option input:checked + label {
            border-color: var(--color);
            background: var(--bg);
        }
        .difficulty-option label i {
            display: block;
            margin-bottom: 4px;
            font-size: 18px;
            color: var(--color);
        }
    </style>
    <div class="difficulty-grid">
        <?php foreach ($levels as $key => $level) : ?>
            <div class="difficulty-option" style="--color: <?php echo $level['color']; ?>; --bg: <?php echo $level['bg_color']; ?>;">
                <input type="radio" id="diff_<?php echo $key; ?>" name="jannah_difficulty_level" value="<?php echo $key; ?>" <?php checked($selected, $key); ?>>
                <label for="diff_<?php echo $key; ?>">
                    <i class="fas <?php echo $level['icon']; ?>"></i>
                    <?php echo $level['label']; ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

// Save both meta values
add_action('save_post', 'jannah_save_badges_meta');
function jannah_save_badges_meta($post_id) {
    if (isset($_POST['jannah_content_type_nonce']) && wp_verify_nonce($_POST['jannah_content_type_nonce'], 'jannah_content_type_save')) {
        if (! defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) {
            if (current_user_can('edit_post', $post_id)) {
                if (isset($_POST['jannah_content_type'])) {
                    update_post_meta($post_id, '_jannah_content_type', sanitize_text_field($_POST['jannah_content_type']));
                }
            }
        }
    }
    
    if (isset($_POST['jannah_difficulty_nonce']) && wp_verify_nonce($_POST['jannah_difficulty_nonce'], 'jannah_difficulty_save')) {
        if (!defined('DOING_AUTOSAVE') || ! DOING_AUTOSAVE) {
            if (current_user_can('edit_post', $post_id)) {
                if (isset($_POST['jannah_difficulty_level'])) {
                    update_post_meta($post_id, '_jannah_difficulty_level', sanitize_text_field($_POST['jannah_difficulty_level']));
                }
            }
        }
    }
}

// =====================================================
// FRONTEND: INJECT BADGES INSIDE POST-META
// =====================================================
add_action('wp_footer', 'jannah_inject_badges_into_meta', 999);
function jannah_inject_badges_into_meta() {
    if (! is_singular('post')) {
        return;
    }
    
    global $post;
    if (!$post) return;
    
    $type_key = get_post_meta($post->ID, '_jannah_content_type', true);
    $content_type_html = '';
    if ($type_key) {
        $types = jannah_get_content_types();
        if (isset($types[$type_key])) {
            $type = $types[$type_key];
            $content_type_html = sprintf(
                '<span class="meta-item meta-content-type" style="--badge-color: %s; --badge-bg: %s;"><i class="fas %s"></i><span class="meta-text">%s</span></span>',
                esc_attr($type['color']),
                esc_attr($type['bg_color']),
                esc_attr($type['icon']),
                esc_html($type['label'])
            );
        }
    }
    
    $diff_key = get_post_meta($post->ID, '_jannah_difficulty_level', true);
    $difficulty_html = '';
    if ($diff_key) {
        $levels = jannah_get_difficulty_levels();
        if (isset($levels[$diff_key])) {
            $level = $levels[$diff_key];
            $difficulty_html = sprintf(
                '<span class="meta-item meta-difficulty" style="--badge-color: %s; --badge-bg: %s;"><i class="fas %s"></i><span class="meta-text">سطح: %s</span></span>',
                esc_attr($level['color']),
                esc_attr($level['bg_color']),
                esc_attr($level['icon']),
                esc_html($level['label'])
            );
        }
    }
    
    $content_type_js = addslashes($content_type_html);
    $difficulty_js = addslashes($difficulty_html);
    ?>
    <script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            
            // Change Reading Time Text
            var readingTimeItems = document.querySelectorAll('.meta-reading-time, .reading-time, [class*="reading-time"]');
            readingTimeItems.forEach(function(item) {
                var html = item.innerHTML;
                html = html.replace(/min read/gi, 'دقیقه زمان مطالعه');
                html = html.replace(/minute read/gi, 'دقیقه زمان مطالعه');
                html = html.replace(/minutes read/gi, 'دقیقه زمان مطالعه');
                html = html.replace(/دقیقه خواندن/gi, 'دقیقه زمان مطالعه');
                html = html.replace(/دقیقه مطالعه/gi, 'دقیقه زمان مطالعه');
                html = html.replace(/زمان خواندن/gi, 'دقیقه زمان مطالعه');
                item.innerHTML = html;
            });
            
            var allMetaItems = document.querySelectorAll('.meta-item');
            allMetaItems.forEach(function(item) {
                if (item.textContent.match(/min|minute|خواندن/i)) {
                    var html = item.innerHTML;
                    html = html.replace(/(min read|minute read|minutes read|دقیقه خواندن)/gi, 'دقیقه زمان مطالعه');
                    item.innerHTML = html;
                }
            });
            
            // Insert Badges Inside Post-Meta
            var contentTypeBadge = '<?php echo $content_type_js; ?>';
            var difficultyBadge = '<?php echo $difficulty_js; ?>';
            
            if (!contentTypeBadge && !difficultyBadge) {
                return;
            }
            
            var metaSelectors = [
                '.single-post-meta.post-meta.clearfix',
                '.single-post-meta.post-meta',
                '.single-post-meta',
                '.post-meta.clearfix',
                '.post-meta'
            ];
            
            var metaContainer = null;
            for (var i = 0; i < metaSelectors.length; i++) {
                metaContainer = document.querySelector(metaSelectors[i]);
                if (metaContainer) break;
            }
            
            if (metaContainer) {
                var badgesWrapper = document.createElement('span');
                badgesWrapper.className = 'meta-badges-group';
                badgesWrapper.innerHTML = contentTypeBadge + difficultyBadge;
                metaContainer.appendChild(badgesWrapper);
                metaContainer.classList.add('jannah-enhanced-meta');
            }
        });
    })();
    </script>
    <?php
}

// =====================================================
// FRONTEND STYLES
// =====================================================
add_action('wp_head', 'jannah_enhanced_meta_styles', 999);
function jannah_enhanced_meta_styles() {
    ?>
    <style>
        .single-post-meta.post-meta.clearfix,
        .single-post-meta.post-meta,
        .jannah-enhanced-meta {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-start;
            gap: 8px 12px;
            padding: 12px 0;
            margin: 10px 0 15px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            line-height: 1.6;
            white-space: nowrap;
            overflow-x: auto;
        }
        
        .rtl .single-post-meta.post-meta.clearfix,
        .rtl .jannah-enhanced-meta {
            direction: rtl;
        }
        
        .single-post-meta .meta-item,
        .post-meta .meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #666;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .single-post-meta .meta-item i,
        .post-meta .meta-item i {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .single-post-meta .meta-item:hover,
        .post-meta .meta-item:hover {
            color: #333;
        }
        
        .meta-reading-time.meta-item,
        .reading-time {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 500;
            color: #667eea !important;
        }
        
        .meta-reading-time.meta-item i,
        .reading-time i {
            color: #667eea;
        }
        
        .meta-badges-group {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-right: auto;
            flex-wrap: nowrap;
            white-space: nowrap;
        }
        
        .rtl .meta-badges-group {
            margin-right: 0;
            margin-left: auto;
        }
        
        .meta-content-type,
        .meta-difficulty {
            display: inline-flex !important;
            align-items: center;
            gap: 6px;
            padding: 5px 14px !important;
            border-radius: 20px;
            font-size: 13px !important;
            font-weight: 600;
            background: var(--badge-bg) !important;
            color: var(--badge-color) !important;
            border: 1px solid transparent;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .meta-content-type:hover,
        .meta-difficulty:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            border-color: var(--badge-color);
        }
        
        .meta-content-type i,
        .meta-difficulty i {
            font-size: 13px !important;
            color: var(--badge-color) !important;
            opacity: 1 !important;
        }
        
        .meta-content-type .meta-text,
        .meta-difficulty .meta-text {
            color: var(--badge-color);
        }
        
        .meta-badges-group::before,
        .meta-content-type::after,
        .meta-difficulty::after,
        .meta-badges-group .meta-item::after {
            display: none !important;
        }
        
        body.dark .single-post-meta,
        body.dark-skin .single-post-meta,
        .dark-skin .single-post-meta,
        body.tie-dark-mode .single-post-meta,
        body.dark .jannah-enhanced-meta,
        body.dark-skin .jannah-enhanced-meta {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        
        body.dark .meta-item,
        body.dark-skin .meta-item,
        .dark-skin .meta-item,
        body.tie-dark-mode .meta-item {
            color: #aaa;
        }
        
        body.dark .meta-item:hover,
        body.dark-skin .meta-item:hover,
        .dark-skin .meta-item:hover,
        body.tie-dark-mode .meta-item:hover {
            color: #fff;
        }
        
        body.dark .meta-content-type,
        body.dark .meta-difficulty,
        body.dark-skin .meta-content-type,
        body.dark-skin .meta-difficulty {
            background: var(--badge-bg) !important;
            opacity: 0.95;
        }
        
        @media (max-width: 768px) {
            .single-post-meta.post-meta.clearfix,
            .jannah-enhanced-meta {
                gap: 6px 10px;
                padding: 10px 0;
                flex-wrap: wrap;
                justify-content: center;
                white-space: normal;
                text-align: center;
            }
            
            .single-post-meta .meta-item,
            .post-meta .meta-item {
                font-size: 12px;
                white-space: nowrap;
            }
            
            .meta-content-type,
            .meta-difficulty {
                font-size: 12px !important;
                padding: 4px 10px !important;
            }
            
            .meta-badges-group {
                width: 100%;
                margin-top: 8px;
                justify-content: center;
                flex-wrap: wrap;
                white-space: normal;
            }
            
            .meta-reading-time.meta-item {
                padding: 3px 10px;
            }
        }
        
        @media (max-width: 480px) {
            .meta-badges-group {
                gap: 6px;
            }
            
            .meta-content-type,
            .meta-difficulty {
                font-size: 11px !important;
                padding: 3px 8px !important;
            }
        }
        
        .entry-header .single-post-meta {
            margin-bottom: 20px;
        }
        
        .single-post-meta .fa,
        .single-post-meta .fas,
        .single-post-meta .far,
        .single-post-meta .fab {
            font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free', FontAwesome !important;
            font-weight: 900;
        }
        
        .single-post-meta .meta-author img {
            border-radius: 50%;
            width: 24px;
            height: 24px;
            margin-left: 6px;
        }
        
        .rtl .single-post-meta .meta-author img {
            margin-left: 0;
            margin-right: 6px;
        }
    </style>
    <?php
}

// =====================================================
// SHORTCODE FOR MANUAL PLACEMENT
// =====================================================
add_shortcode('content_badges', 'jannah_badges_shortcode');
function jannah_badges_shortcode($atts) {
    global $post;
    if (!$post) return '';
    
    $output = '<span class="meta-badges-group">';
    
    $type_key = get_post_meta($post->ID, '_jannah_content_type', true);
    if ($type_key) {
        $types = jannah_get_content_types();
        if (isset($types[$type_key])) {
            $type = $types[$type_key];
            $output .= sprintf(
                '<span class="meta-item meta-content-type" style="--badge-color: %s; --badge-bg: %s;"><i class="fas %s"></i><span class="meta-text">%s</span></span>',
                esc_attr($type['color']),
                esc_attr($type['bg_color']),
                esc_attr($type['icon']),
                esc_html($type['label'])
            );
        }
    }
    
    $diff_key = get_post_meta($post->ID, '_jannah_difficulty_level', true);
    if ($diff_key) {
        $levels = jannah_get_difficulty_levels();
        if (isset($levels[$diff_key])) {
            $level = $levels[$diff_key];
            $output .= sprintf(
                '<span class="meta-item meta-difficulty" style="--badge-color: %s; --badge-bg: %s;"><i class="fas %s"></i><span class="meta-text">سطح: %s</span></span>',
                esc_attr($level['color']),
                esc_attr($level['bg_color']),
                esc_attr($level['icon']),
                esc_html($level['label'])
            );
        }
    }
    
    $output .= '</span>';
    return $output;
}