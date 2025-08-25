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
        /* Only target specific navigation buttons at the bottom */
        .ld-content-actions .ld-content-action a {
            background-color: #28a745 !important;
            color: #ffffff !important;
            border: 1px solid #28a745 !important;
            text-decoration: none !important;
            padding: 8px 16px !important;
            border-radius: 4px !important;
            font-weight: 500 !important;
            display: inline-block !important;
            margin: 5px !important;
        }
        
        /* Specific targeting for the back to lesson button */
        a.ld-course-step-back,
        .ld-primary-color.ld-course-step-back {
            background-color: #28a745 !important;
            color: #ffffff !important;
            border: 1px solid #28a745 !important;
            text-decoration: none !important;
            padding: 8px 16px !important;
            border-radius: 4px !important;
            font-weight: 500 !important;
            display: inline-block !important;
        }
        
        /* Back to course button positioning fix */
        .lilac-back-to-course-button {
            position: fixed !important;
            top: 120px !important;
            right: 20px !important;
            z-index: 1000 !important;
            background-color: #28a745 !important;
            color: #ffffff !important;
            border: 1px solid #28a745 !important;
            text-decoration: none !important;
            padding: 8px 16px !important;
            border-radius: 4px !important;
            font-weight: 500 !important;
            display: inline-block !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }
        
        .lilac-back-to-course-button:hover {
            background-color: #218838 !important;
            border-color: #1e7e34 !important;
            color: #ffffff !important;
        }
        
        /* Hover effects for navigation buttons */
        .ld-content-actions .ld-content-action a:hover {
            background-color: #218838 !important;
            border-color: #1e7e34 !important;
            color: #ffffff !important;
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
            console.log('Lilac Navigation Enhancement Script Loaded');
            
            // Fix navigation button text and functionality
            function enhanceNavigationButtons() {
                console.log('Running enhanceNavigationButtons');
                
                // Find buttons with "המבחן הבא" text and change to next lesson
                // Look for both direct text and text within spans
                var nextButtons = $('a').filter(function() {
                    return $(this).text().trim() === 'המבחן הבא' || 
                           $(this).find('.ld-text').text().trim() === 'המבחן הבא';
                });
                
                console.log('Found next buttons:', nextButtons.length);
                
                nextButtons.each(function() {
                    var $button = $(this);
                    var href = $button.attr('href');
                    
                    console.log('Processing button with href:', href);
                    
                    // If this links to a quiz, we need to find the next lesson instead
                    if (href && (href.includes('quiz') || href.includes('המבחן'))) {
                        console.log('Button links to quiz, modifying...');
                        
                        // Change button text - handle both direct text and span structure
                        var $textSpan = $button.find('.ld-text');
                        if ($textSpan.length) {
                            $textSpan.text('השיעור הבא');
                        } else {
                            $button.text('השיעור הבא');
                        }
                        
                        // Remove any existing click handlers
                        $button.off('click.lilac-nav');
                        
                        // Add click handler to navigate to next lesson
                        $button.on('click.lilac-nav', function(e) {
                            e.preventDefault();
                            console.log('Next lesson button clicked');
                            
                            // Get current post ID from body classes or global variable
                            var currentPostId = <?php echo get_the_ID(); ?>;
                            var courseId = null;
                            
                            // Try to extract course ID from body classes
                            var bodyClasses = $('body').attr('class');
                            var courseMatch = bodyClasses.match(/learndash-cpt-sfwd-courses-(\d+)-parent/);
                            if (courseMatch) {
                                courseId = courseMatch[1];
                            }
                            
                            console.log('Course ID:', courseId, 'Current Post ID:', currentPostId);
                            
                            if (courseId && currentPostId) {
                                // Make AJAX call to get next lesson
                                $.ajax({
                                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                    type: 'POST',
                                    data: {
                                        action: 'lilac_get_next_lesson',
                                        course_id: courseId,
                                        current_post_id: currentPostId,
                                        nonce: '<?php echo wp_create_nonce('lilac_navigation'); ?>'
                                    },
                                    success: function(response) {
                                        console.log('AJAX response:', response);
                                        if (response.success && response.data.next_url) {
                                            window.location.href = response.data.next_url;
                                        } else {
                                            // Fallback: try to find next topic in the same lesson
                                            var fallbackUrl = href.replace(/\/quizzes\/.*/, '/');
                                            if (fallbackUrl !== href) {
                                                window.location.href = fallbackUrl;
                                            } else {
                                                // Last resort: go to course page
                                                window.location.href = '/courses/קורס-מקוון-לרכב-פרטי/';
                                            }
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.log('AJAX error:', error);
                                        // Fallback: try to find next topic in the same lesson
                                        var fallbackUrl = href.replace(/\/quizzes\/.*/, '/');
                                        if (fallbackUrl !== href) {
                                            window.location.href = fallbackUrl;
                                        } else {
                                            // Last resort: go to course page
                                            window.location.href = '/courses/קורס-מקוון-לרכב-פרטי/';
                                        }
                                    }
                                });
                            } else {
                                console.log('Missing course ID or post ID, using fallback');
                                // Fallback navigation
                                var fallbackUrl = href.replace(/\/quizzes\/.*/, '/');
                                if (fallbackUrl !== href) {
                                    window.location.href = fallbackUrl;
                                } else {
                                    window.location.href = '/courses/קורס-מקוון-לרכב-פרטי/';
                                }
                            }
                        });
                    }
                });
                
                // Only target navigation buttons in the content actions area
                var backButtons = $('.ld-content-actions a').filter(function() {
                    return $(this).text().indexOf('חזרה') !== -1;
                });
                
                console.log('Found back buttons in content actions:', backButtons.length);
                
                backButtons.each(function() {
                    var $button = $(this);
                    console.log('Styling back button in content actions');
                    // CSS will handle the styling, just ensure it's properly targeted
                });
            }
            
            // Run enhancement immediately
            enhanceNavigationButtons();
            
            // Re-run after a short delay to catch dynamically loaded content
            setTimeout(enhanceNavigationButtons, 1000);
            
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
