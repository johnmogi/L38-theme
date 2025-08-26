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
add_action('wp_footer', 'lilac_debug_course_structure');
function lilac_navigation_enhancement_script() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'])) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Lilac Navigation Enhancement Script Loaded');
            
            // Get current post and course IDs from page
            var currentPostId = parseInt($('body').attr('class').match(/postid-(\d+)/)?.[1] || '0');
            var courseId = parseInt($('body').attr('class').match(/learndash-cpt-sfwd-courses-(\d+)-parent/)?.[1] || '0');
            
            console.log('Post ID:', currentPostId, 'Course ID:', courseId);
            
            function enhanceNavigationButtons() {
                console.log('Enhancing navigation buttons');
                
                // Find ALL next navigation buttons and override them
                var nextButtons = $('a').filter(function() {
                    var text = $(this).text().trim();
                    var ldText = $(this).find('.ld-text').text().trim();
                    return text.includes('הבא') || ldText.includes('הבא') || 
                           text.includes('Next') || ldText.includes('Next') ||
                           text.includes('מבחן') || ldText.includes('מבחן');
                });
                
                console.log('Found next buttons:', nextButtons.length);
                
                nextButtons.each(function() {
                    var $btn = $(this);
                    var href = $btn.attr('href');
                    
                    console.log('Processing next button:', $btn.text(), 'href:', href);
                    
                    // Remove href to prevent default navigation
                    $btn.removeAttr('href');
                    $btn.css('cursor', 'pointer');
                    
                    // Override ALL navigation buttons to use our AJAX logic
                    $btn.off('click').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Next button clicked, using AJAX navigation');
                        navigateNext($btn);
                        return false;
                    });
                    
                    // Update button text immediately
                    var $textSpan = $btn.find('.ld-text');
                    if ($textSpan.length) {
                        $textSpan.text('Loading next...');
                    } else {
                        $btn.text('Loading next...');
                    }
                });
                
                // Find ALL back navigation buttons and override them
                var backButtons = $('a').filter(function() {
                    var text = $(this).text().trim();
                    var ldText = $(this).find('.ld-text').text().trim();
                    return text.includes('קודם') || ldText.includes('קודם') || 
                           text.includes('Previous') || ldText.includes('Previous');
                });
                
                console.log('Found back buttons:', backButtons.length);
                
                backButtons.each(function() {
                    var $btn = $(this);
                    console.log('Processing back button:', $btn.text());
                    
                    // Remove href to prevent default navigation
                    $btn.removeAttr('href');
                    $btn.css('cursor', 'pointer');
                    
                    // Override ALL back buttons to use our AJAX logic
                    $btn.off('click').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Back button clicked, using AJAX navigation');
                        navigatePrevious($btn);
                        return false;
                    });
                    
                    // Update button text immediately
                    var $textSpan = $btn.find('.ld-text');
                    if ($textSpan.length) {
                        $textSpan.text('Loading previous...');
                    } else {
                        $btn.text('Loading previous...');
                    }
                });
            }
            
            function navigateNext($button) {
                console.log('navigateNext called with courseId:', courseId, 'currentPostId:', currentPostId);
                
                if (!courseId || !currentPostId) {
                    console.error('Missing courseId or currentPostId');
                    return;
                }
                
                $button.text('Loading...');
                
                console.log('Making AJAX call to get next step');
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
                        console.log('AJAX Response:', response);
                        if (response.success && response.data.next_url) {
                            console.log('SUCCESS - Next URL:', response.data.next_url);
                            console.log('Next Title:', response.data.next_title);
                            console.log('Button Text:', response.data.button_text);
                            console.log('Debug Info:', response.data.debug);
                            
                            if (response.data.button_text) {
                                var $textSpan = $button.find('.ld-text');
                                if ($textSpan.length) {
                                    $textSpan.text(response.data.button_text);
                                } else {
                                    $button.text(response.data.button_text);
                                }
                            }
                            
                            // Navigate to the correct lesson/topic URL
                            console.log('Navigating to:', response.data.next_url);
                            window.location.href = response.data.next_url;
                        } else {
                            console.log('ERROR - No next step found:', response);
                            $button.text('End of course');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        $button.text('Error');
                    }
                });
            }
            
            function navigatePrevious($button) {
                if (!courseId || !currentPostId) return;
                
                $button.text('Loading...');
                
                $.ajax({
                    url: lilac_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'lilac_get_previous_step',
                        course_id: courseId,
                        current_post_id: currentPostId,
                        nonce: lilac_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.next_url) {
                            console.log('Previous:', response.data.next_url);
                            window.location.href = response.data.next_url;
                        } else {
                            console.log('No previous step');
                            $button.text('Start of course');
                        }
                    },
                    error: function() {
                        console.error('Navigation error');
                        $button.text('Error');
                    }
                });
            }
            
            // Initialize
            enhanceNavigationButtons();
            
            // Watch for changes
            var observer = new MutationObserver(function() {
                setTimeout(enhanceNavigationButtons, 100);
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
 * AJAX handler to get next step in course (lesson or topic) with debugging
 */
add_action('wp_ajax_lilac_get_next_step', 'lilac_handle_get_next_step');
add_action('wp_ajax_nopriv_lilac_get_next_step', 'lilac_handle_get_next_step');
function lilac_handle_get_next_step() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'lilac_navigation')) {
        wp_send_json_error('Security check failed');
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
    
    // Get course lessons and build navigation array (skip quizzes)
    $lessons = learndash_get_course_lessons_list($course_id);
    $navigation_steps = [];
    
    if (empty($lessons)) {
        wp_send_json_error([
            'message' => 'No course lessons found',
            'debug' => $debug_info
        ]);
    }
    
    // Build navigation array with lessons and topics only (no quizzes)
    foreach ($lessons as $lesson) {
        $navigation_steps[] = $lesson['post']->ID;
        
        // Get topics for this lesson
        $topics = learndash_get_topic_list($lesson['post']->ID, $course_id);
        if (!empty($topics)) {
            // Sort topics by menu order
            usort($topics, function($a, $b) {
                $order_a = get_post_meta($a->ID, '_ld_topic_order', true);
                $order_b = get_post_meta($b->ID, '_ld_topic_order', true);
                if ($order_a == $order_b) {
                    return $a->menu_order - $b->menu_order;
                }
                return $order_a - $order_b;
            });
            
            foreach ($topics as $topic) {
                $navigation_steps[] = $topic->ID;
            }
        }
    }
    
    $debug_info['total_navigation_steps'] = count($navigation_steps);
    $debug_info['navigation_steps'] = [];
    
    foreach ($navigation_steps as $index => $step_id) {
        $debug_info['navigation_steps'][] = [
            'index' => $index,
            'ID' => $step_id,
            'type' => get_post_type($step_id),
            'title' => get_the_title($step_id)
        ];
    }
    
    // Find current position in navigation steps
    $current_index = array_search($current_post_id, $navigation_steps);
    $debug_info['current_index'] = $current_index;
    
    if ($current_index !== false) {
        // Found current post in navigation, get next step
        $next_index = $current_index + 1;
        
        if (isset($navigation_steps[$next_index])) {
            $next_step_id = $navigation_steps[$next_index];
            $next_step_type = get_post_type($next_step_id);
            
            // Determine button text based on next step type
            $button_text = ($next_step_type === 'sfwd-lessons') ? 'לשיעור הבא' : 'הנושא הבא';
            
            wp_send_json_success([
                'next_url' => home_url('?p=' . $next_step_id),
                'next_title' => get_the_title($next_step_id),
                'button_text' => $button_text,
                'debug' => array_merge($debug_info, [
                    'navigation_type' => 'sequential_navigation',
                    'next_step_type' => $next_step_type,
                    'next_index' => $next_index
                ])
            ]);
        } else {
            // We're at the end of the course
            wp_send_json_success([
                'next_url' => '',
                'next_title' => 'סיום הקורס',
                'button_text' => 'סיום הקורס',
                'debug' => array_merge($debug_info, [
                    'navigation_type' => 'course_completed'
                ])
            ]);
        }
    } else {
        // Current post not found in navigation (shouldn't happen with proper setup)
        wp_send_json_error([
            'message' => 'Current post not found in course navigation',
            'debug' => $debug_info
        ]);
    }
}

/**
 * AJAX handler for previous step navigation
 */
add_action('wp_ajax_lilac_get_previous_step', 'lilac_handle_get_previous_step');
add_action('wp_ajax_nopriv_lilac_get_previous_step', 'lilac_handle_get_previous_step');
function lilac_handle_get_previous_step() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'lilac_navigation')) {
        wp_send_json_error('Security check failed');
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
    
    // Get course lessons and build navigation array (skip quizzes) - same as next step
    $lessons = learndash_get_course_lessons_list($course_id);
    $navigation_steps = [];
    
    if (empty($lessons)) {
        wp_send_json_error([
            'message' => 'No course lessons found',
            'debug' => $debug_info
        ]);
    }
    
    // Build navigation array with lessons and topics only (no quizzes)
    foreach ($lessons as $lesson) {
        $navigation_steps[] = $lesson['post']->ID;
        
        // Get topics for this lesson
        $topics = learndash_get_topic_list($lesson['post']->ID, $course_id);
        if (!empty($topics)) {
            // Sort topics by menu order
            usort($topics, function($a, $b) {
                $order_a = get_post_meta($a->ID, '_ld_topic_order', true);
                $order_b = get_post_meta($b->ID, '_ld_topic_order', true);
                if ($order_a == $order_b) {
                    return $a->menu_order - $b->menu_order;
                }
                return $order_a - $order_b;
            });
            
            foreach ($topics as $topic) {
                $navigation_steps[] = $topic->ID;
            }
        }
    }
    
    $debug_info['total_navigation_steps'] = count($navigation_steps);
    $debug_info['navigation_steps'] = [];
    
    foreach ($navigation_steps as $index => $step_id) {
        $debug_info['navigation_steps'][] = [
            'index' => $index,
            'ID' => $step_id,
            'type' => get_post_type($step_id),
            'title' => get_the_title($step_id)
        ];
    }
    
    // Find current position in navigation steps
    $current_index = array_search($current_post_id, $navigation_steps);
    $debug_info['current_index'] = $current_index;
    
    if ($current_index !== false) {
        // Found current post in navigation, get previous step
        $previous_index = $current_index - 1;
        
        if (isset($navigation_steps[$previous_index])) {
            $previous_step_id = $navigation_steps[$previous_index];
            $previous_step_type = get_post_type($previous_step_id);
            
            // Determine button text based on previous step type
            $button_text = ($previous_step_type === 'sfwd-lessons') ? 'לשיעור הקודם' : 'הנושא הקודם';
            
            wp_send_json_success([
                'next_url' => home_url('?p=' . $previous_step_id),
                'next_title' => get_the_title($previous_step_id),
                'button_text' => $button_text,
                'debug' => array_merge($debug_info, [
                    'navigation_type' => 'sequential_previous_navigation',
                    'previous_step_type' => $previous_step_type,
                    'previous_index' => $previous_index
                ])
            ]);
        } else {
            // We're at the beginning of the course
            wp_send_json_success([
                'next_url' => get_permalink($course_id),
                'next_title' => 'חזרה לקורס',
                'button_text' => 'חזרה לקורס',
                'debug' => array_merge($debug_info, [
                    'navigation_type' => 'course_beginning'
                ])
            ]);
        }
    } else {
        // Current post not found in navigation (shouldn't happen with proper setup)
        wp_send_json_error([
            'message' => 'Current post not found in course navigation',
            'debug' => $debug_info
        ]);
    }
}

/**
 * Legacy AJAX handler - kept for backward compatibility
 */
add_action('wp_ajax_lilac_get_next_lesson', 'lilac_handle_get_next_step');
add_action('wp_ajax_nopriv_lilac_get_next_lesson', 'lilac_handle_get_next_step');

/**
 * Debug function to show course structure at footer
 */
function lilac_debug_course_structure() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'])) {
        global $post;
        $course_id = learndash_get_course_id($post->ID);
        
        if ($course_id) {
            echo '<div style="position: fixed; bottom: 0; left: 0; right: 0; background: #000; color: #0f0; padding: 10px; font-family: monospace; font-size: 11px; max-height: 300px; overflow-y: auto; z-index: 9999; border-top: 2px solid #0f0;">';
            echo '<h4 style="color: #ff0; margin: 0 0 10px 0;">🔍 LEARNDASH COURSE DEBUG - Course ID: ' . $course_id . '</h4>';
            
            // Get course main page
            echo '<div style="color: #0ff; margin-bottom: 10px;"><strong>🏠 COURSE MAIN PAGE: <a href="' . get_permalink($course_id) . '" style="color: #0ff;">' . get_the_title($course_id) . ' (ID:' . $course_id . ')</a></strong></div>';
            
            // Get lessons for this course
            $lessons = learndash_get_course_lessons_list($course_id);
            echo '<div style="margin-bottom: 10px;"><strong>TOTAL LESSONS: ' . count($lessons) . '</strong></div>';
            echo '<div style="margin-bottom: 10px;"><strong>CURRENT POST: ' . $post->ID . ' (' . get_post_type($post->ID) . ') - ' . get_the_title($post->ID) . '</strong></div>';
            
            if (!empty($lessons)) {
                $lesson_index = 0;
                foreach ($lessons as $lesson) {
                    $lesson_id = $lesson['post']->ID;
                    $lesson_title = $lesson['post']->post_title;
                    $lesson_url = home_url('?p=' . $lesson_id);
                    $is_current_lesson = ($lesson_id == $post->ID);
                    
                    $lesson_style = $is_current_lesson ? 'background: #ff0; color: #000; font-weight: bold;' : '';
                    echo '<div style="' . $lesson_style . ' padding: 3px; margin: 2px 0;">';
                    echo '📚 [L' . $lesson_index . '] <a href="' . $lesson_url . '" style="color: inherit; text-decoration: underline;">' . $lesson_title . ' (ID:' . $lesson_id . ')</a>';
                    
                    // Get topics for this lesson
                    $topics = learndash_get_topic_list($lesson_id, $course_id);
                    if (!empty($topics)) {
                        echo '<div style="margin-left: 20px; font-size: 10px;">';
                        $topic_index = 0;
                        foreach ($topics as $topic) {
                            $topic_id = $topic->ID;
                            $topic_title = $topic->post_title;
                            $topic_url = home_url('?p=' . $topic_id);
                            $is_current_topic = ($topic_id == $post->ID);
                            
                            $topic_style = $is_current_topic ? 'background: #ff0; color: #000; font-weight: bold;' : '';
                            echo '<div style="' . $topic_style . ' padding: 1px; margin: 1px 0;">└─ 📄 [T' . $topic_index . '] <a href="' . $topic_url . '" style="color: inherit; text-decoration: underline;">' . $topic_title . ' (ID:' . $topic_id . ')</a></div>';
                            $topic_index++;
                        }
                        echo '</div>';
                    }
                    
                    // Get quizzes for this lesson
                    $quizzes = learndash_get_lesson_quiz_list($lesson_id, null, $course_id);
                    if (!empty($quizzes)) {
                        echo '<div style="margin-left: 20px; font-size: 10px; color: #f90;">';
                        $quiz_index = 0;
                        foreach ($quizzes as $quiz) {
                            $quiz_id = $quiz['post']->ID;
                            $quiz_title = $quiz['post']->post_title;
                            $quiz_url = home_url('?p=' . $quiz_id);
                            $is_current_quiz = ($quiz_id == $post->ID);
                            
                            $quiz_style = $is_current_quiz ? 'background: #ff0; color: #000; font-weight: bold;' : '';
                            echo '<div style="' . $quiz_style . ' padding: 1px; margin: 1px 0;">└─ ❓ [Q' . $quiz_index . '] <a href="' . $quiz_url . '" style="color: inherit; text-decoration: underline;">' . $quiz_title . ' (ID:' . $quiz_id . ')</a></div>';
                            $quiz_index++;
                        }
                        echo '</div>';
                    }
                    
                    // Get topic quizzes
                    if (!empty($topics)) {
                        foreach ($topics as $topic) {
                            // Use the correct LearnDash function to get quizzes for a topic
                            if (function_exists('learndash_course_get_quizzes')) {
                                $topic_quizzes = learndash_course_get_quizzes($course_id, $topic->ID);
                                if (!empty($topic_quizzes)) {
                                    echo '<div style="margin-left: 40px; font-size: 9px; color: #f90;">';
                                    foreach ($topic_quizzes as $topic_quiz_data) {
                                        // Handle both post ID and post object cases
                                        $topic_quiz_id = is_object($topic_quiz_data) ? $topic_quiz_data->ID : $topic_quiz_data;
                                        $topic_quiz = get_post($topic_quiz_id);
                                        if ($topic_quiz) {
                                            $topic_quiz_title = $topic_quiz->post_title;
                                            $topic_quiz_url = home_url('?p=' . $topic_quiz_id);
                                            $is_current_topic_quiz = ($topic_quiz_id == $post->ID);
                                            
                                            $topic_quiz_style = $is_current_topic_quiz ? 'background: #ff0; color: #000; font-weight: bold;' : '';
                                            echo '<div style="' . $topic_quiz_style . ' padding: 1px; margin: 1px 0;">  └─ ❓ <a href="' . $topic_quiz_url . '" style="color: inherit; text-decoration: underline;">' . $topic_quiz_title . ' (ID:' . $topic_quiz_id . ')</a></div>';
                                        }
                                    }
                                    echo '</div>';
                                }
                            }
                        }
                    }
                    
                    echo '</div>';
                    $lesson_index++;
                }
                
                // Show navigation logic
                echo '<div style="margin-top: 10px; border-top: 1px solid #0f0; padding-top: 10px;">';
                echo '<div style="color: #0ff;"><strong>NAVIGATION LOGIC:</strong></div>';
                
                if (get_post_type($post->ID) === 'sfwd-topic') {
                    // Find current topic position and next topic
                    $current_lesson_id = learndash_course_get_single_parent_step($course_id, $post->ID, 'sfwd-lessons');
                    if ($current_lesson_id) {
                        $lesson_topics = learndash_get_topic_list($current_lesson_id, $course_id);
                        $topic_ids = array_map(function($topic) { return $topic->ID; }, $lesson_topics);
                        $current_topic_index = array_search($post->ID, $topic_ids);
                        
                        echo '<div>Current lesson: ' . get_the_title($current_lesson_id) . ' (ID:' . $current_lesson_id . ')</div>';
                        echo '<div>Topic index in lesson: ' . $current_topic_index . ' of ' . count($topic_ids) . '</div>';
                        
                        if (isset($topic_ids[$current_topic_index + 1])) {
                            $next_topic_id = $topic_ids[$current_topic_index + 1];
                            echo '<div style="color: #0f0;">NEXT TOPIC: ' . get_the_title($next_topic_id) . ' (ID:' . $next_topic_id . ') - <a href="' . home_url('?p=' . $next_topic_id) . '" style="color: #0f0;">' . home_url('?p=' . $next_topic_id) . '</a></div>';
                        } else {
                            echo '<div style="color: #f90;">END OF LESSON - Should find next lesson\'s first topic</div>';
                        }
                    }
                }
                
                echo '</div>';
            } else {
                echo '<div style="color: #f00;">NO LESSONS FOUND!</div>';
            }
            
            echo '</div>';
        }
    }
}

/**
 * Enqueue AJAX script localization
 */
add_action('wp_enqueue_scripts', 'lilac_enqueue_navigation_ajax');
function lilac_enqueue_navigation_ajax() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'])) {
        wp_localize_script('jquery', 'lilac_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lilac_navigation')
        ]);
    }
}
