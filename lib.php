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

defined('MOODLE_INTERNAL') || die; //internal script


/**
 * Callback to describe features plugin supports
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool True if review supports feature
 */
function review_supports($feature){
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE: return false; // grading not supported
        case FEATURE_BACKUP_MOODLE2:  return true; // moodle2 backup supported
        case FEATURE_COMPLETION_HAS_RULES: return true; //completion with rules supported
        default:  return null; //any others - not supported
    }
}

/**
 * Callback to extend setting tree
 * @param $settings array tree of settings
 * @param $reviewnode object current setting node
 */
function review_extend_settings_navigation($settings, $reviewnode) {
    global $PAGE;
    $keys = $reviewnode->get_children_key_list(); //get children nodes keys
    $i = array_search('modedit', $keys); //search for Module edit node
    if ($i === false && array_key_exists(0, $keys)) { //if there is no such node and exists a node with key 0
        $beforekey = $keys[0]; //put a new node before it
    } else if (array_key_exists($i + 1, $keys)) { //if there is such node
        $beforekey = $keys[$i + 1]; //put a new node after it
    }
    //get course context
    $course_context=context_course::instance($PAGE->course->id);
    //if user have a capability to moderate reviews in course
    if (has_capability('mod/review:moderate', $course_context)){
        //make url to the moderate page
        $url = new moodle_url('/mod/review/moderate.php', array('id'=>$PAGE->cm->id));
        //make a new navigation node with that url
        $node = navigation_node::create(get_string('moderate','mod_review'), $url,
            navigation_node::TYPE_SETTING, null, 'mod_review_moderate');
        //add node to the current node
        $reviewnode->add_node($node, $beforekey);
    }
}

/**
 * Callback to display a course module on the course page
 * @param cm_info $cm object course module
 */
function review_cm_info_view(cm_info $cm){
    global $PAGE,$DB,$USER;
    $review=$DB->get_record('review',['id'=>$cm->instance]); //get an activity record from DB
    if(!$review->coursepage_display){return;} //if it is empty - nothing to display
    $user_review=new user_review($USER,$review); //get user_review object
    $renderer = $PAGE->get_renderer('mod_review'); //get renderer for review
    $intro= !empty($review->intro) ? html_writer::div($review->intro) : ''; //if intro of activity is not empty - add it
    //add rate form for content (let users rate course from course page_
    $cm->set_content($intro.$renderer->user_rate_form($user_review,false,'coursepage_display'));
    //call JS-module initialize method
    $PAGE->requires->js_call_amd('mod_review/review', 'init'); //подключаем используемый js-модуль
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
function review_add_instance($review){
    global $DB;
    $review->name = get_string('modulename', 'mod_review'); //set module name
    $review->timemodified = time();     //set timemodified
    $review->id = $DB->insert_record("review", $review); //insert record in DB
    //set new module id in course module table
    $DB->set_field('course_modules', 'instance', $review->id, array('id' => $review->coursemodule));
    //return new module id
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
function review_update_instance($review){
    global $DB;
    $review->name = get_string('modulename','mod_review'); //set module name
    $review->timemodified = time(); //set timemodified
    $review->id = $review->instance; //set id
    $DB->update_record("review", $review); //update record in DB
    return true; //return result
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id the id of the review to delete.
 * @return bool success or failure.
 */
function review_delete_instance($id){
    global $DB;
    //get review record
    if (!$review = $DB->get_record("review", array("id" => $id))) {return false;}
    //delete review record
    if (!$DB->delete_records("review", array("id" => $review->id))) {return false;}
    //delete user reviews of this review
    if (!$DB->delete_records("review_userreviews", array("reviewid" => $review->id))) {return false;}
    //return result
    return true;
}

/**
 * Callback to add custom css settings
 * @return string valid html head content
 */
function mod_review_before_standard_html_head() {
    $main_color=get_config('mod_review','colortheme');
    $output=html_writer::tag('style',':root {--review-color-main: '.$main_color.';}');
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

function review_get_completion_state($course,$cm,$userid,$type) {
    global $DB;

    // Get review details
    if (!($review=$DB->get_record('review',array('id'=>$cm->instance)))) {
        throw new Exception("Can't find review {$cm->instance}");
    }

    $result=$type; // Default return value
    $sql="SELECT * FROM {review_userreviews} ur WHERE ur.userid=:userid AND ur.reviewid=:reviewid"; //base sql request
    $params=array('userid'=>$userid,'reviewid'=>$review->id);//params for sql request

    if ($review->completionrate) { //check rate given by user
        $value = $DB->record_exists_sql($sql." AND rate>0",$params);
        $result = ($type == COMPLETION_AND) ? $result && $value : $result || $value;
    }
    if ($review->completionreview) { //check review given by user - status must be accepted
        $value = $DB->record_exists_sql($sql." AND text!='' AND status=:status",
            array_merge($params,['status'=>user_review::REVIEW_ACCEPTED]));
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
    $params = ['context' => $context,'objectid' => $review->id];

    $event = \mod_review\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('review', $review);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}