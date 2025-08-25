<?php
/**
 * LearnDash Navigation Enhancements
 * 
 * Fixes navigation buttons and improves user experience
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customize LearnDash navigation buttons
 */
add_action('wp_head', 'lilac_fix_learndash_navigation_styles');
function lilac_fix_learndash_navigation_styles() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'])) {
        ?>
        <style>
        /* Fix "חזרה לשיעור" button visibility */
        .ld-content-actions .ld-button,
        .learndash-content-actions .ld-button,
        .ld-lesson-navigation .ld-button,
        .ld-topic-navigation .ld-button,
        .ld-quiz-navigation .ld-button,
        a[href*="lesson"]:contains("חזרה"),
        a[href*="topic"]:contains("חזרה") {
            background-color: #28a745 !important;
            color: #ffffff !important;
            border: 1px solid #28a745 !important;
            text-decoration: none !important;
            padding: 8px 16px !important;
            border-radius: 4px !important;
            font-weight: 500 !important;
            display: inline-block !important;
        }
        
        /* Hover effects for navigation buttons */
        .ld-content-actions .ld-button:hover,
        .learndash-content-actions .ld-button:hover,
        .ld-lesson-navigation .ld-button:hover,
        .ld-topic-navigation .ld-button:hover,
        .ld-quiz-navigation .ld-button:hover {
            background-color: #218838 !important;
            border-color: #1e7e34 !important;
            color: #ffffff !important;
        }
        
        /* Fix any blue text on blue background issues */
        .ld-content-actions a,
        .learndash-content-actions a,
        .ld-lesson-navigation a,
        .ld-topic-navigation a {
            color: #ffffff !important;
        }
        
        /* Ensure proper contrast for all navigation elements */
        .ld-content-actions,
        .learndash-content-actions,
        .ld-lesson-navigation,
        .ld-topic-navigation {
            background: transparent !important;
        }
        
        /* Style the next/previous navigation specifically */
        .ld-lesson-navigation .ld-lesson-nav,
        .ld-topic-navigation .ld-topic-nav {
            margin: 20px 0;
        }
        
        .ld-lesson-navigation .ld-lesson-nav a,
        .ld-topic-navigation .ld-topic-nav a {
            background-color: #28a745 !important;
            color: #ffffff !important;
            border: 1px solid #28a745 !important;
            padding: 10px 20px !important;
            border-radius: 4px !important;
            text-decoration: none !important;
            margin: 0 5px !important;
            display: inline-block !important;
        }
        </style>
        <?php
    }
}

/**
 * Modify LearnDash navigation to redirect "המבחן הבא" to next lesson
 */
add_filter('learndash_next_post_link', 'lilac_customize_next_post_link', 10, 5);
function lilac_customize_next_post_link($link, $post_id, $course_id, $user_id, $post_type) {
    // Only modify for lessons and topics
    if (!in_array($post_type, ['sfwd-lessons', 'sfwd-topic'])) {
        return $link;
    }
    
    // Get the course steps
    $course_steps = learndash_get_course_steps($course_id);
    if (empty($course_steps)) {
        return $link;
    }
    
    // Find current position in course
    $current_step_id = $post_id;
    $next_step_id = null;
    $found_current = false;
    
    foreach ($course_steps as $step_id => $step) {
        if ($found_current) {
            // Skip quizzes and go to the next lesson/topic
            if (get_post_type($step_id) === 'sfwd-lessons' || get_post_type($step_id) === 'sfwd-topic') {
                $next_step_id = $step_id;
                break;
            }
        }
        
        if ($step_id == $current_step_id) {
            $found_current = true;
        }
    }
    
    // If we found a next lesson/topic, modify the link
    if ($next_step_id) {
        $next_post = get_post($next_step_id);
        if ($next_post) {
            $next_url = get_permalink($next_step_id);
            $next_title = get_the_title($next_step_id);
            
            // Customize the link text based on post type
            $link_text = 'השיעור הבא';
            if (get_post_type($next_step_id) === 'sfwd-topic') {
                $link_text = 'הנושא הבא';
            }
            
            $link = sprintf(
                '<a href="%s" class="ld-button ld-button-next" title="%s">%s</a>',
                esc_url($next_url),
                esc_attr($next_title),
                esc_html($link_text)
            );
        }
    }
    
    return $link;
}

/**
 * Add JavaScript to enhance navigation buttons
 */
add_action('wp_footer', 'lilac_navigation_enhancement_script');
function lilac_navigation_enhancement_script() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'])) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Fix navigation button text and functionality
            function enhanceNavigationButtons() {
                // Find buttons with "המבחן הבא" text and change to next lesson
                $('a:contains("המבחן הבא")').each(function() {
                    var $button = $(this);
                    var href = $button.attr('href');
                    
                    // If this links to a quiz, we need to find the next lesson instead
                    if (href && href.includes('quiz')) {
                        // Get course ID from current page
                        var courseId = $('body').attr('class').match(/course-(\d+)/);
                        if (courseId) {
                            // Change button text
                            $button.text('השיעור הבא');
                            
                            // Add click handler to navigate to next lesson
                            $button.on('click', function(e) {
                                e.preventDefault();
                                
                                // Make AJAX call to get next lesson
                                $.ajax({
                                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                    type: 'POST',
                                    data: {
                                        action: 'lilac_get_next_lesson',
                                        course_id: courseId[1],
                                        current_post_id: <?php echo get_the_ID(); ?>,
                                        nonce: '<?php echo wp_create_nonce('lilac_navigation'); ?>'
                                    },
                                    success: function(response) {
                                        if (response.success && response.data.next_url) {
                                            window.location.href = response.data.next_url;
                                        } else {
                                            // Fallback: go to course page
                                            window.location.href = $button.attr('href').replace(/\/quiz\/.*/, '');
                                        }
                                    },
                                    error: function() {
                                        // Fallback: go to course page
                                        window.location.href = $button.attr('href').replace(/\/quiz\/.*/, '');
                                    }
                                });
                            });
                        }
                    }
                });
                
                // Fix visibility issues for "חזרה לשיעור" buttons
                $('a:contains("חזרה")').each(function() {
                    var $button = $(this);
                    $button.css({
                        'background-color': '#28a745',
                        'color': '#ffffff',
                        'border': '1px solid #28a745',
                        'text-decoration': 'none',
                        'padding': '8px 16px',
                        'border-radius': '4px',
                        'font-weight': '500',
                        'display': 'inline-block'
                    });
                });
            }
            
            // Run enhancement
            enhanceNavigationButtons();
            
            // Re-run after AJAX content loads
            $(document).ajaxComplete(function() {
                setTimeout(enhanceNavigationButtons, 500);
            });
        });
        </script>
        <?php
    }
}

/**
 * AJAX handler to get next lesson in course
 */
add_action('wp_ajax_lilac_get_next_lesson', 'lilac_ajax_get_next_lesson');
add_action('wp_ajax_nopriv_lilac_get_next_lesson', 'lilac_ajax_get_next_lesson');
function lilac_ajax_get_next_lesson() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'lilac_navigation')) {
        wp_die('Security check failed');
    }
    
    $course_id = intval($_POST['course_id']);
    $current_post_id = intval($_POST['current_post_id']);
    
    if (!$course_id || !$current_post_id) {
        wp_send_json_error('Invalid parameters');
    }
    
    // Get course steps
    $course_steps = learndash_get_course_steps($course_id);
    if (empty($course_steps)) {
        wp_send_json_error('No course steps found');
    }
    
    // Find next lesson/topic
    $next_step_id = null;
    $found_current = false;
    
    foreach ($course_steps as $step_id => $step) {
        if ($found_current) {
            // Skip quizzes and go to the next lesson/topic
            $post_type = get_post_type($step_id);
            if ($post_type === 'sfwd-lessons' || $post_type === 'sfwd-topic') {
                $next_step_id = $step_id;
                break;
            }
        }
        
        if ($step_id == $current_post_id) {
            $found_current = true;
        }
    }
    
    if ($next_step_id) {
        wp_send_json_success([
            'next_url' => get_permalink($next_step_id),
            'next_title' => get_the_title($next_step_id)
        ]);
    } else {
        wp_send_json_error('No next lesson found');
    }
}
