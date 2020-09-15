<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of functions for the review module.
 *
 * This contains functions that are called also from outside the review module
 * Functions that are only called by the review module itself are in locallib.php
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_review\user_review;

defined('MOODLE_INTERNAL') || die; // Internal script.

/**
 * Callback to describe features plugin supports
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool True if review supports feature
 */
function review_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return false; // Grading not supported.
        case FEATURE_BACKUP_MOODLE2:
            return true; // Moodle2 backup supported.
        case FEATURE_COMPLETION_HAS_RULES:
            return true; // Completion with rules supported.
        default:
            return null; // Any others - not supported.
    }
}

/**
 * Callback to extend setting tree
 * @param array $settings tree of settings
 * @param object $reviewnode current setting node
 */
function review_extend_settings_navigation($settings, $reviewnode) {
    global $PAGE;
    $keys = $reviewnode->get_children_key_list(); // Get children nodes keys.
    $i = array_search('modedit', $keys); // Search for Module edit node.
    if ($i === false && array_key_exists(0, $keys)) { // If there is no such node and exists a node with key 0.
        $beforekey = $keys[0]; // Put a new node before it.
    } else if (array_key_exists($i + 1, $keys)) { // If there is such node.
        $beforekey = $keys[$i + 1]; // Put a new node after it.
    }
    // Get course context.
    $coursecontext = context_course::instance($PAGE->course->id);
    // If user have a capability to moderate reviews in course.
    if (has_capability('mod/review:moderate', $coursecontext)) {
        // Make url to the moderate page.
        $url = new moodle_url('/mod/review/moderate.php', ['id' => $PAGE->cm->id]);
        // Make a new navigation node with that url.
        $node = navigation_node::create(get_string('moderate', 'mod_review'), $url,
            navigation_node::TYPE_SETTING, null, 'mod_review_moderate');
        // Add node to the current node.
        $reviewnode->add_node($node, $beforekey);
    }
}

/**
 * Callback to display a course module on the course page
 * @param cm_info $cm object course module
 */
function review_cm_info_view(cm_info $cm) {
    global $PAGE, $DB, $USER;
    $review = $DB->get_record('review', ['id' => $cm->instance]); // Get an activity record from DB.
    if (!$review->coursepage_display) { // If it is empty - nothing to display.
        return;
    }
    $userreview = new user_review($USER, $review); // Get user_review object.
    $renderer = $PAGE->get_renderer('mod_review'); // Get renderer for review.
    $intro = !empty($review->intro) ? html_writer::div($review->intro) : ''; // If intro of activity is not empty - add it.
    // Add rate form for content (let users rate course from course page).
    $cm->set_content($intro.$renderer->user_rate_form($userreview, false, 'coursepage_display'));
    // Call JS-module initialize method.
    $PAGE->requires->js_call_amd('mod_review/review', 'init');
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $review the data that came from the form.
 * @return mixed the id of the new instance on success, false or a string error message on failure.
 */
function review_add_instance($review) {
    global $DB;
    $review->name = get_string('modulename', 'mod_review'); // Set module name.
    $review->timemodified = time(); // Set timemodified.
    $review->id = $DB->insert_record("review", $review); // Insert record in DB.
    // Set new module id in course module table.
    $DB->set_field('course_modules', 'instance', $review->id, ['id' => $review->coursemodule]);
    // Return new module id.
    return $review->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $review the data that came from the form.
 * @return mixed true on success, false or a string error message on failure.
 */
function review_update_instance($review) {
    global $DB;
    $review->name = get_string('modulename', 'mod_review'); // Set module name.
    $review->timemodified = time(); // Set timemodified.
    $review->id = $review->instance; // Set id.
    $DB->update_record("review", $review); // Update record in DB.
    return true; // Return result.
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id the id of the review to delete.
 * @return bool success or failure.
 */
function review_delete_instance($id) {
    global $DB;
    // Get review record.
    if (!$review = $DB->get_record("review", ["id" => $id])) {
        return false;
    }
    // Delete review record.
    if (!$DB->delete_records("review", ["id" => $review->id])) {
        return false;
    }
    // Delete user reviews of this review.
    if (!$DB->delete_records("review_userreviews", ["reviewid" => $review->id])) {
        return false;
    }
    // Return result.
    return true;
}

/**
 * Callback to add custom css settings
 * @return string valid html head content
 */
function mod_review_before_standard_html_head() {
    $maincolor = get_config('mod_review', 'colortheme');
    $output = html_writer::tag('style', ':root {--review-color-main: '.$maincolor.';}');
    return $output;
}

/**
 * Obtains the automatic completion state for this review based on any conditions in review settings.
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return value depends on comparison type)
 */
function review_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Get review details.
    if (!($review = $DB->get_record('review', ['id' => $cm->instance]))) {
        throw new Exception("Can't find review {$cm->instance}");
    }

    $result = $type; // Default return value.
    $sql = "SELECT * FROM {review_userreviews} ur WHERE ur.userid=:userid AND ur.reviewid=:reviewid"; // Base sql request.
    $params = ['userid' => $userid, 'reviewid' => $review->id]; // Params for sql request.

    if ($review->completionrate) { // Check rate given by user.
        $value = $DB->record_exists_sql($sql." AND rate>0", $params);
        $result = ($type == COMPLETION_AND) ? $result && $value : $result || $value;
    }
    if ($review->completionreview) { // Check review given by user - status must be accepted.
        $value = $DB->record_exists_sql($sql." AND text!='' AND status=:status",
            array_merge($params, ['status' => user_review::REVIEW_ACCEPTED]));
        $result = ($type == COMPLETION_AND) ? $result && $value : $result || $value;
    }
    return $result;
}


/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $review     review object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 */
function review_view($review, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = ['context' => $context, 'objectid' => $review->id];

    $event = \mod_review\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('review', $review);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}