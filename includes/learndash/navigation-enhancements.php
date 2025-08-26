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
                
                // Find buttons with "המבחן הבא" text and change to next lesson/topic
                var nextButtons = $('a').filter(function() {
                    return $(this).text().trim() === 'המבחן הבא' || 
                           $(this).find('.ld-text').text().trim() === 'המבחן הבא' ||
                           $(this).text().trim() === 'השיעור הבא' || 
                           $(this).find('.ld-text').text().trim() === 'השיעור הבא';
                });
                
                console.log('Found next buttons:', nextButtons.length);
                
                nextButtons.each(function() {
                    var $button = $(this);
                    var href = $button.attr('href');
                    
                    console.log('Processing button with href:', href);
                    
                    // Check if this button leads to same page (navigation issue)
                    var currentUrl = window.location.href;
                    var buttonUrl = href;
                    
                    // If button leads to same page or to a quiz when we want next topic
                    if (buttonUrl === currentUrl || (href && href.includes('quiz'))) {
                        console.log('Button has navigation issue, fixing...');
                        
                        // Change button text based on context
                        var $textSpan = $button.find('.ld-text');
                        var newText = 'הנושא הבא'; // Default for topics
                        
                        // If we're in a topic, next should be next topic or lesson
                        if (currentUrl.includes('/topics/')) {
                            newText = 'הנושא הבא';
                        } else if (currentUrl.includes('/lessons/')) {
                            newText = 'השיעור הבא';
                        }
                        
                        if ($textSpan.length) {
                            $textSpan.text(newText);
                        } else {
                            $button.text(newText);
                        }
                        
                        // Remove existing handlers and add new one
                        $button.off('click.lilac-nav');
                        $button.on('click.lilac-nav', function(e) {
                            e.preventDefault();
                            console.log('Navigation button clicked, finding next step...');
                            
                            var currentPostId = <?php echo get_the_ID(); ?>;
                            var courseId = null;
                            
                            var bodyClasses = $('body').attr('class');
                            var courseMatch = bodyClasses.match(/learndash-cpt-sfwd-courses-(\d+)-parent/);
                            if (courseMatch) {
                                courseId = courseMatch[1];
                            }
                            
                            console.log('Current Post ID:', currentPostId, 'Course ID:', courseId);
                            
                            if (courseId && currentPostId) {
                                // Show loading state
                                var originalText = $button.text();
                                $button.text('טוען...');
                                $button.prop('disabled', true);
                                
                                $.ajax({
                                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                    type: 'POST',
                                    data: {
                                        action: 'lilac_get_next_step',
                                        course_id: courseId,
                                        current_post_id: currentPostId,
                                        nonce: '<?php echo wp_create_nonce('lilac_navigation'); ?>'
                                    },
                                    success: function(response) {
                                        console.log('=== NAVIGATION DEBUG ===');
                                        console.log('Full AJAX response:', response);
                                        
                                        if (response.success) {
                                            console.log('Next URL:', response.data.next_url);
                                            console.log('Next Title:', response.data.next_title);
                                            
                                            if (response.data.debug) {
                                                console.log('=== COURSE STRUCTURE DEBUG ===');
                                                console.log('Course ID:', response.data.debug.course_id);
                                                console.log('Current Post:', response.data.debug.current_post_title, '(ID: ' + response.data.debug.current_post_id + ')');
                                                console.log('Post Type:', response.data.debug.current_post_type);
                                                
                                                if (response.data.debug.lesson_topics) {
                                                    console.log('=== LESSON TOPICS ===');
                                                    response.data.debug.lesson_topics.forEach(function(topic, index) {
                                                        console.log((index + 1) + '. ' + topic.title + ' (ID: ' + topic.ID + ')');
                                                        console.log('   Permalink: ' + topic.permalink);
                                                        console.log('   Clean URL: ' + topic.clean_url);
                                                    });
                                                }
                                                
                                                if (response.data.debug.course_steps) {
                                                    console.log('=== COURSE STEPS ===');
                                                    response.data.debug.course_steps.forEach(function(step, index) {
                                                        console.log((index + 1) + '. ' + step.title + ' (' + step.type + ', ID: ' + step.ID + ')');
                                                    });
                                                }
                                            }
                                            
                                            // Navigate to next step
                                            window.location.href = response.data.next_url;
                                        } else {
                                            console.log('Navigation failed:', response.data ? response.data.message : 'Unknown error');
                                            if (response.data && response.data.debug) {
                                                console.log('Debug info:', response.data.debug);
                                            }
                                            
                                            // Restore button
                                            $button.text(originalText);
                                            $button.prop('disabled', false);
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.log('AJAX error:', error);
                                        console.log('Status:', status);
                                        console.log('Response:', xhr.responseText);
                                        
                                        // Restore button
                                        $button.text(originalText);
                                        $button.prop('disabled', false);
                                    }
                                });
                            } else {
                                console.log('Missing course ID or post ID');
                            }
                        });
                    }
                });
                
                // Fix back buttons - change text and ensure proper styling
                var backButtons = $('.ld-content-actions a').filter(function() {
                    return $(this).text().indexOf('חזרה') !== -1;
                });
                
                console.log('Found back buttons in content actions:', backButtons.length);
                
                backButtons.each(function() {
                    var $button = $(this);
                    console.log('Processing back button');
                    
                    // Change text from "חזרה לשיעור" to "חזרה לפרק"
                    var currentText = $button.text().trim();
                    if (currentText.includes('חזרה לשיעור')) {
                        $button.text('חזרה לפרק');
                    }
                    
                    // Ensure proper styling
                    $button.css({
                        'font-size': '16px',
                        'padding': '10px 20px',
                        'min-width': '120px'
                    });
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
 * AJAX handler to get next step in course (lesson or topic) with debugging
 */
add_action('wp_ajax_lilac_get_next_step', 'lilac_handle_get_next_step');
add_action('wp_ajax_nopriv_lilac_get_next_step', 'lilac_handle_get_next_step');
function lilac_handle_get_next_step() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'lilac_navigation')) {
        wp_die('Security check failed');
    }
    
    $course_id = intval($_POST['course_id']);
    $current_post_id = intval($_POST['current_post_id']);
    
    if (!$course_id || !$current_post_id) {
        wp_send_json_error('Missing required parameters');
    }
    
    // Debug information
    $debug_info = [
        'course_id' => $course_id,
        'current_post_id' => $current_post_id,
        'current_post_type' => get_post_type($current_post_id),
        'current_post_title' => get_the_title($current_post_id)
    ];
    
    // Get the current post type
    $current_post_type = get_post_type($current_post_id);
    
    // If we're in a topic, find the next topic in the same lesson first
    if ($current_post_type === 'sfwd-topic') {
        $lesson_id = learndash_course_get_single_parent_step($course_id, $current_post_id, 'sfwd-lessons');
        $debug_info['parent_lesson_id'] = $lesson_id;
        
        if ($lesson_id) {
            $lesson_topics = learndash_get_topic_list($lesson_id, $course_id);
            $debug_info['lesson_topics'] = array_map(function($topic) {
                return [
                    'ID' => $topic->ID,
                    'title' => get_the_title($topic->ID),
                    'permalink' => get_permalink($topic->ID),
                    'clean_url' => home_url('?p=' . $topic->ID)
                ];
            }, $lesson_topics);
            
            $found_current = false;
            foreach ($lesson_topics as $topic) {
                if ($found_current) {
                    // Found next topic in same lesson - use clean URL
                    wp_send_json_success([
                        'next_url' => home_url('?p=' . $topic->ID),
                        'next_title' => get_the_title($topic->ID),
                        'debug' => $debug_info
                    ]);
                }
                if ($topic->ID == $current_post_id) {
                    $found_current = true;
                }
            }
        }
    }
    
    // If no next topic found, or we're in a lesson, find next lesson
    $course_steps = learndash_get_course_steps($course_id);
    if (empty($course_steps)) {
        wp_send_json_error('No course steps found');
    }
    
    $debug_info['course_steps'] = [];
    foreach ($course_steps as $step_id => $step) {
        $debug_info['course_steps'][] = [
            'ID' => $step_id,
            'type' => get_post_type($step_id),
            'title' => get_the_title($step_id)
        ];
    }
    
    $found_current = false;
    foreach ($course_steps as $step_id => $step) {
        if ($found_current) {
            // Look for next lesson (skip quizzes)
            if (get_post_type($step_id) === 'sfwd-lessons') {
                // Get first topic of next lesson
                $next_lesson_topics = learndash_get_topic_list($step_id, $course_id);
                if (!empty($next_lesson_topics)) {
                    $first_topic = reset($next_lesson_topics);
                    wp_send_json_success([
                        'next_url' => home_url('?p=' . $first_topic->ID),
                        'next_title' => get_the_title($first_topic->ID),
                        'debug' => $debug_info
                    ]);
                } else {
                    // No topics, go to lesson itself - use clean URL
                    wp_send_json_success([
                        'next_url' => home_url('?p=' . $step_id),
                        'next_title' => get_the_title($step_id),
                        'debug' => $debug_info
                    ]);
                }
                break;
            }
        }
        
        if ($step_id == $current_post_id || 
            ($current_post_type === 'sfwd-topic' && $step_id == learndash_course_get_single_parent_step($course_id, $current_post_id, 'sfwd-lessons'))) {
            $found_current = true;
        }
    }
    
    wp_send_json_error([
        'message' => 'No next step found',
        'debug' => $debug_info
    ]);
}

/**
 * Legacy AJAX handler - kept for backward compatibility
 */
add_action('wp_ajax_lilac_get_next_lesson', 'lilac_handle_get_next_step');
add_action('wp_ajax_nopriv_lilac_get_next_lesson', 'lilac_handle_get_next_step');
