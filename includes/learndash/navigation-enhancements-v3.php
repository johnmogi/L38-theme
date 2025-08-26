<?php
/**
 * LearnDash Navigation Enhancements
 * Fixes navigation button text and functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add CSS styles for navigation buttons
 */
add_action('wp_head', 'lilac_fix_learndash_navigation_styles');
function lilac_fix_learndash_navigation_styles() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'])) {
        ?>
        <style>
        /* Navigation button styling */
        .ld-button, .learndash_mark_complete_button, .btn-blue {
            background-color: #4a90e2 !important;
            color: white !important;
            border: none !important;
            padding: 12px 24px !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            display: inline-block !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
        }
        
        .ld-button:hover, .learndash_mark_complete_button:hover, .btn-blue:hover {
            background-color: #357abd !important;
            transform: translateY(-1px) !important;
        }
        
        .ld-button:active, .learndash_mark_complete_button:active, .btn-blue:active {
            transform: translateY(0) !important;
        }
        
        /* Disabled state */
        .ld-button:disabled, .learndash_mark_complete_button:disabled, .btn-blue:disabled {
            background-color: #cccccc !important;
            cursor: not-allowed !important;
            transform: none !important;
        }
        
        /* Loading state */
        .ld-button.loading, .learndash_mark_complete_button.loading, .btn-blue.loading {
            background-color: #f39c12 !important;
            cursor: wait !important;
        }
        
        /* Navigation container */
        .learndash_navigation {
            margin: 20px 0 !important;
            text-align: center !important;
        }
        
        /* Button spacing */
        .ld-button + .ld-button {
            margin-left: 10px !important;
        }
        </style>
        <?php
    }
}

/**
 * Add JavaScript to enhance navigation buttons
 */
add_action('wp_footer', 'lilac_navigation_enhancement_script');
function lilac_navigation_enhancement_script() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'])) {
        $current_post_id = get_the_ID();
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('Lilac Navigation Enhancement Script v3 Loaded');
            
            var currentPostId = <?php echo intval($current_post_id); ?>;
            
            function enhanceNavigationButtons() {
                console.log('Running enhanceNavigationButtons');
                
                // Find next navigation buttons
                var nextButtons = $('a').filter(function() {
                    var text = $(this).text().trim();
                    var ldText = $(this).find('.ld-text').text().trim();
                    
                    return text === 'המבחן הבא' || 
                           ldText === 'המבחן הבא' ||
                           text === 'השיעור הבא' || 
                           ldText === 'השיעור הבא' ||
                           text === 'הנושא הבא' ||
                           ldText === 'הנושא הבא';
                });
                
                console.log('Found next buttons:', nextButtons.length);
                
                nextButtons.each(function() {
                    var $button = $(this);
                    var href = $button.attr('href');
                    var buttonText = $button.text().trim() || $button.find('.ld-text').text().trim();
                    var currentUrl = window.location.href;
                    var needsFixing = false;
                    
                    // Check if button needs fixing
                    if (buttonText === 'המבחן הבא' && 
                        (href === currentUrl || !href || href === '#' || href.includes(window.location.pathname))) {
                        needsFixing = true;
                        console.log('Button needs fixing:', buttonText, href);
                    }
                    
                    if (needsFixing) {
                        var $textSpan = $button.find('.ld-text');
                        
                        $button.off('click.lilac-nav');
                        $button.on('click.lilac-nav', function(e) {
                            e.preventDefault();
                            console.log('Navigation button clicked');
                            
                            var courseId = null;
                            var bodyClasses = $('body').attr('class');
                            var courseMatch = bodyClasses.match(/learndash-cpt-sfwd-courses-(\\d+)-parent/);
                            if (courseMatch) {
                                courseId = courseMatch[1];
                            }
                            
                            if (courseId && currentPostId) {
                                var originalText = $button.text();
                                $button.text('טוען...');
                                $button.prop('disabled', true);
                                
                                $.ajax({
                                    url: lilac_ajax.ajax_url,
                                    type: 'POST',
                                    data: {
                                        action: 'lilac_get_next_step',
                                        course_id: courseId,
                                        current_post_id: currentPostId,
                                        nonce: lilac_ajax.nonce
                                    },
                                    success: function(response) {
                                        if (response.success && response.data.next_url) {
                                            console.log('Navigating to:', response.data.next_url);
                                            if (response.data.button_text) {
                                                if ($textSpan.length) {
                                                    $textSpan.text(response.data.button_text);
                                                } else {
                                                    $button.text(response.data.button_text);
                                                }
                                            }
                                            window.location.href = response.data.next_url;
                                        } else {
                                            console.log('No next step found');
                                            $button.text(originalText);
                                            $button.prop('disabled', false);
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.log('AJAX error:', error);
                                        var originalHref = $button.attr('href');
                                        if (originalHref && originalHref !== '#' && originalHref !== window.location.href) {
                                            window.location.href = originalHref;
                                        } else {
                                            $button.text(originalText);
                                            $button.prop('disabled', false);
                                            alert('Navigation failed. Please try again.');
                                        }
                                    }
                                });
                            }
                        });
                    }
                });
                
                // Handle back buttons
                var backButtons = $('a').filter(function() {
                    var text = $(this).text().trim();
                    var ldText = $(this).find('.ld-text').text().trim();
                    
                    return text.includes('קודם') || ldText.includes('קודם') || 
                           text.includes('Previous') || ldText.includes('Previous');
                });
                
                backButtons.each(function() {
                    var $button = $(this);
                    $button.text('לשיעור הקודם');
                });
            }
            
            // Run enhancement
            enhanceNavigationButtons();
            
            // Run again after delays
            setTimeout(enhanceNavigationButtons, 500);
            setTimeout(enhanceNavigationButtons, 1000);
            setTimeout(enhanceNavigationButtons, 2000);
            
            // Watch for DOM changes
            var observer = new MutationObserver(function(mutations) {
                var shouldRun = false;
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        for (var i = 0; i < mutation.addedNodes.length; i++) {
                            var node = mutation.addedNodes[i];
                            if (node.nodeType === 1 && (node.tagName === 'A' || node.querySelector('a'))) {
                                shouldRun = true;
                                break;
                            }
                        }
                    }
                });
                if (shouldRun) {
                    setTimeout(enhanceNavigationButtons, 100);
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
        </script>
        <?php
    }
}

/**
 * AJAX handler to get next step in course
 */
add_action('wp_ajax_lilac_get_next_step', 'lilac_handle_get_next_step');
add_action('wp_ajax_nopriv_lilac_get_next_step', 'lilac_handle_get_next_step');
function lilac_handle_get_next_step() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'lilac_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $course_id = intval($_POST['course_id']);
    $current_post_id = intval($_POST['current_post_id']);
    
    if (!$course_id || !$current_post_id) {
        wp_send_json_error('Missing required parameters');
        return;
    }
    
    $current_post_type = get_post_type($current_post_id);
    
    // Handle topic navigation
    if ($current_post_type === 'sfwd-topic') {
        $parent_lesson = learndash_course_get_single_parent_step($course_id, $current_post_id, 'sfwd-lessons');
        
        if ($parent_lesson) {
            $lesson_topics = learndash_get_topic_list($parent_lesson, $course_id);
            
            if (!empty($lesson_topics)) {
                $topic_ids = array_map(function($topic) { return $topic->ID; }, $lesson_topics);
                $current_topic_index = array_search($current_post_id, $topic_ids);
                
                // Check if there's a next topic in the same lesson
                if ($current_topic_index !== false && isset($topic_ids[$current_topic_index + 1])) {
                    $next_topic_id = $topic_ids[$current_topic_index + 1];
                    wp_send_json_success([
                        'next_url' => home_url('?p=' . $next_topic_id),
                        'next_title' => get_the_title($next_topic_id),
                        'button_text' => 'הנושא הבא'
                    ]);
                    return;
                } else if ($current_topic_index !== false) {
                    // End of lesson - find next lesson's first topic
                    $lessons = learndash_get_course_lessons_list($course_id);
                    $current_lesson_found = false;
                    
                    foreach ($lessons as $lesson) {
                        if ($current_lesson_found) {
                            $next_lesson_topics = learndash_get_topic_list($lesson['post']->ID, $course_id);
                            if (!empty($next_lesson_topics)) {
                                // Sort topics by order
                                usort($next_lesson_topics, function($a, $b) {
                                    $order_a = get_post_meta($a->ID, '_ld_topic_order', true);
                                    $order_b = get_post_meta($b->ID, '_ld_topic_order', true);
                                    if ($order_a == $order_b) {
                                        return $a->menu_order - $b->menu_order;
                                    }
                                    return $order_a - $order_b;
                                });
                                
                                $first_topic = reset($next_lesson_topics);
                                wp_send_json_success([
                                    'next_url' => home_url('?p=' . $first_topic->ID),
                                    'next_title' => get_the_title($first_topic->ID),
                                    'button_text' => 'לשיעור הבא'
                                ]);
                                return;
                            } else {
                                // Next lesson has no topics
                                wp_send_json_success([
                                    'next_url' => home_url('?p=' . $lesson['post']->ID),
                                    'next_title' => get_the_title($lesson['post']->ID),
                                    'button_text' => 'לשיעור הבא'
                                ]);
                                return;
                            }
                        }
                        
                        if ($lesson['post']->ID == $parent_lesson) {
                            $current_lesson_found = true;
                        }
                    }
                }
            }
        }
    }
    
    wp_send_json_error('No next step found');
}

/**
 * Enqueue AJAX script
 */
add_action('wp_enqueue_scripts', 'lilac_enqueue_ajax_script');
function lilac_enqueue_ajax_script() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'])) {
        wp_localize_script('jquery', 'lilac_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lilac_ajax_nonce')
        ]);
    }
}

/**
 * Debug course structure
 */
add_action('wp_footer', 'lilac_debug_course_structure');
function lilac_debug_course_structure() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz']) && current_user_can('manage_options')) {
        global $post;
        $course_id = learndash_get_course_id($post->ID);
        
        if ($course_id) {
            echo '<div style="position: fixed; bottom: 0; left: 0; right: 0; background: #000; color: #0f0; padding: 10px; font-family: monospace; font-size: 11px; max-height: 300px; overflow-y: auto; z-index: 9999; border-top: 2px solid #0f0;">';
            echo '<h4 style="color: #ff0; margin: 0 0 10px 0;">🔍 LEARNDASH DEBUG - Course ID: ' . $course_id . '</h4>';
            echo '<div style="color: #0ff; margin-bottom: 10px;"><strong>CURRENT POST: ' . $post->ID . ' (' . get_post_type($post->ID) . ') - ' . get_the_title($post->ID) . '</strong></div>';
            
            $lessons = learndash_get_course_lessons_list($course_id);
            echo '<div style="margin-bottom: 10px;"><strong>TOTAL LESSONS: ' . count($lessons) . '</strong></div>';
            
            if (!empty($lessons)) {
                $lesson_index = 0;
                foreach ($lessons as $lesson) {
                    $lesson_id = $lesson['post']->ID;
                    $lesson_title = get_the_title($lesson_id);
                    $lesson_url = get_permalink($lesson_id);
                    $is_current_lesson = ($lesson_id == $post->ID);
                    
                    $lesson_style = $is_current_lesson ? 'background: #ff0; color: #000; font-weight: bold;' : '';
                    echo '<div style="' . $lesson_style . ' padding: 3px; margin: 2px 0;">';
                    echo '📚 [L' . $lesson_index . '] ' . $lesson_title . ' (ID:' . $lesson_id . ')';
                    
                    // Get topics
                    $topics = learndash_get_topic_list($lesson_id, $course_id);
                    if (!empty($topics)) {
                        echo '<div style="margin-left: 20px; font-size: 10px;">';
                        $topic_index = 0;
                        foreach ($topics as $topic) {
                            $topic_id = $topic->ID;
                            $topic_title = get_the_title($topic_id);
                            $is_current_topic = ($topic_id == $post->ID);
                            
                            $topic_style = $is_current_topic ? 'background: #ff0; color: #000; font-weight: bold;' : '';
                            echo '<div style="' . $topic_style . ' padding: 1px; margin: 1px 0;">└─ [T' . $topic_index . '] ' . $topic_title . ' (ID:' . $topic_id . ')</div>';
                            $topic_index++;
                        }
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    $lesson_index++;
                }
            }
            echo '</div>';
        }
    }
}
