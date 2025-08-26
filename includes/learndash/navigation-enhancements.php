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
                                        
                                        // Fallback to original link if AJAX fails
                                        console.log('AJAX failed, using original link as fallback');
                                        var originalHref = $button.attr('href');
                                        if (originalHref && originalHref !== '#' && originalHref !== window.location.href) {
                                            window.location.href = originalHref;
                                        } else {
                                            // If no valid original link, restore button
                                            $button.text(originalText);
                                            $button.prop('disabled', false);
                                            alert('Navigation failed. Please try again.');
                                        }
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
    
    // Get course steps using the correct LearnDash function
    $course_steps = learndash_get_course_steps($course_id);
    
    if (empty($course_steps)) {
        wp_send_json_error([
            'message' => 'No course steps found',
            'debug' => $debug_info
        ]);
    }
    
    // Convert course steps array to indexed array for easier processing
    $steps_array = array_keys($course_steps);
    $debug_info['total_steps'] = count($steps_array);
    $debug_info['steps_list'] = [];
    
    foreach ($steps_array as $index => $step_id) {
        $debug_info['steps_list'][] = [
            'index' => $index,
            'ID' => $step_id,
            'type' => get_post_type($step_id),
            'title' => get_the_title($step_id)
        ];
    }
    
    // Find current position in course steps
    $current_index = array_search($current_post_id, $steps_array);
    
    if ($current_index === false) {
        // If current post not found directly, it might be a topic - find parent lesson
        if ($current_post_type === 'sfwd-topic') {
            $parent_lesson = learndash_course_get_single_parent_step($course_id, $current_post_id, 'sfwd-lessons');
            $debug_info['parent_lesson_id'] = $parent_lesson;
            
            if ($parent_lesson) {
                $current_index = array_search($parent_lesson, $steps_array);
                
                // Get topics for this lesson
                $lesson_topics = learndash_get_topic_list($parent_lesson, $course_id);
                $debug_info['lesson_topics'] = [];
                
                if (!empty($lesson_topics)) {
                    foreach ($lesson_topics as $topic) {
                        $debug_info['lesson_topics'][] = [
                            'ID' => $topic->ID,
                            'title' => get_the_title($topic->ID)
                        ];
                    }
                    
                    // Find next topic in same lesson
                    $topic_ids = array_map(function($topic) { return $topic->ID; }, $lesson_topics);
                    $current_topic_index = array_search($current_post_id, $topic_ids);
                    
                    if ($current_topic_index !== false && isset($topic_ids[$current_topic_index + 1])) {
                        $next_topic_id = $topic_ids[$current_topic_index + 1];
                        wp_send_json_success([
                            'next_url' => home_url('?p=' . $next_topic_id),
                            'next_title' => get_the_title($next_topic_id),
                            'debug' => $debug_info
                        ]);
                    }
                }
            }
        }
    }
    
    $debug_info['current_index'] = $current_index;
    
    // If we found the current position, look for next step
    if ($current_index !== false && isset($steps_array[$current_index + 1])) {
        $next_step_id = $steps_array[$current_index + 1];
        $next_step_type = get_post_type($next_step_id);
        
        // If next step is a lesson, get its first topic
        if ($next_step_type === 'sfwd-lessons') {
            $lesson_topics = learndash_get_topic_list($next_step_id, $course_id);
            if (!empty($lesson_topics)) {
                $first_topic = reset($lesson_topics);
                $next_step_id = $first_topic->ID;
            }
        }
        
        wp_send_json_success([
            'next_url' => home_url('?p=' . $next_step_id),
            'next_title' => get_the_title($next_step_id),
            'debug' => $debug_info
        ]);
    }
    
    // If we're at the end of current lesson but there are more lessons, go to next lesson's first topic
    if ($current_index !== false) {
        // Look for the next lesson after current position
        for ($i = $current_index + 1; $i < count($steps_array); $i++) {
            $step_id = $steps_array[$i];
            $step_type = get_post_type($step_id);
            
            if ($step_type === 'sfwd-lessons') {
                // Found next lesson, get its first topic
                $lesson_topics = learndash_get_topic_list($step_id, $course_id);
                if (!empty($lesson_topics)) {
                    $first_topic = reset($lesson_topics);
                    wp_send_json_success([
                        'next_url' => home_url('?p=' . $first_topic->ID),
                        'next_title' => get_the_title($first_topic->ID),
                        'debug' => $debug_info
                    ]);
                } else {
                    // No topics in next lesson, go to lesson itself
                    wp_send_json_success([
                        'next_url' => home_url('?p=' . $step_id),
                        'next_title' => get_the_title($step_id),
                        'debug' => $debug_info
                    ]);
                }
            }
        }
    }
    
    // If we're at the last step, return the course URL
    $course_url = get_permalink($course_id);
    if ($course_url) {
        wp_send_json_success([
            'next_url' => $course_url,
            'next_title' => __('Back to Course', 'learndash'),
            'is_course_complete' => true,
            'debug' => $debug_info
        ]);
    }
    
    // If we get here, no next step was found
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
                        foreach ($quizzes as $quiz) {
                            $quiz_id = $quiz['post']->ID;
                            $quiz_title = $quiz['post']->post_title;
                            $quiz_url = home_url('?p=' . $quiz_id);
                            $is_current_quiz = ($quiz_id == $post->ID);
                            
                            $quiz_style = $is_current_quiz ? 'background: #ff0; color: #000; font-weight: bold;' : '';
                            echo '<div style="' . $quiz_style . ' padding: 1px; margin: 1px 0;">└─ ❓ <a href="' . $quiz_url . '" style="color: inherit; text-decoration: underline;">' . $quiz_title . ' (ID:' . $quiz_id . ')</a></div>';
                        }
                        echo '</div>';
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
