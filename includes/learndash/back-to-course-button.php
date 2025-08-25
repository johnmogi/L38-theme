<?php
/**
 * Back to Course Button for LearnDash Navigation
 * 
 * Adds dynamic "Back to Course" button on quiz, topic, and lesson pages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Track if button has been displayed
$lilac_button_displayed = false;

// Enqueue minimal styles - most styling is now inline
add_action('wp_enqueue_scripts', function() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-courses'])) {
        $css = '
        /* Back to course button styles */
        .lilac-back-to-course-wrapper {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 9999;
        }
        
        .continue-button:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #fff;
            text-decoration: none;
        }';
        
        wp_add_inline_style('hello-theme-style', $css);
    }
});

/**
 * Add back to course button to LearnDash content
 */
function lilac_add_back_to_course_button($content = '') {
    global $post, $lilac_button_displayed;
    
    // Only run on LearnDash post types and only once
    if (!is_singular(['sfwd-lessons', 'sfwd-topic', 'sfwd-quiz']) || $lilac_button_displayed) {
        return $content;
    }
    
    // Get course ID
    $course_id = learndash_get_course_id($post->ID);
    if (empty($course_id)) {
        return $content;
    }
    
    // Get course URL and title
    $course_url = get_permalink($course_id);
    $course_title = get_the_title($course_id);
    
    // Generate button HTML with updated styling to match 'המשך ללמוד' button
    $button = sprintf(
        '<div class="lilac-back-to-course-wrapper" style="position: fixed; bottom: 20px; left: 20px; z-index: 1000;">
            <a href="%s" class="continue-button" title="%s" style="display: inline-block; padding: 6px 12px; border-radius: 4px; background-color: #28a745; color: #fff; text-decoration: none; border: 1px solid #28a745; font-size: 14px; font-weight: 500; transition: all 0.3s ease;">
                <span>← חזרה לקורס</span>
            </a>
        </div>',
        esc_url($course_url),
        esc_attr($course_title)
    );
    
    // Mark as displayed
    $lilac_button_displayed = true;
    
    // If called as a filter, prepend to content
    if (doing_filter("the_content")) {
        return $button . $content;
    } 
    // Otherwise, output directly
    else {
        echo $button;
    }
}

// Hook into multiple LearnDash template locations to ensure button appears
add_action('learndash-content-tabs-before', 'lilac_add_back_to_course_button');
add_action('learndash-topic-before', 'lilac_add_back_to_course_button');
add_action('learndash-lesson-before', 'lilac_add_back_to_course_button');
add_action('learndash-quiz-before', 'lilac_add_back_to_course_button');

// Remove footer addition to prevent duplicates

// Removed duplicate navigation modification to prevent multiple buttons
