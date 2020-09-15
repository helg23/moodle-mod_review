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
 * This script displays review moderator interface
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_review\user_review;
require_once(__DIR__.'/../../config.php'); // Require main config.

$id = required_param('id', PARAM_INT);    // Require Course Module ID.
// Get cm.
if (!$cm = get_coursemodule_from_id('review', $id)) {
    print_error(get_string('wrong_cm', 'mod_review'));
}
// Get course.
if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
    print_error(get_string('wrong_course', 'mod_review'));
}
// Get module.
if (!$review = $DB->get_record('review', ['id' => $cm->instance])) {
    print_error(get_string('wrong_module', 'mod_review'));
}
$review->cmid = $cm->id; // Add cmid property to the activity object.

// Requite authorization and course access.
require_course_login($course, false, $cm);
// Get context of module.
$context = context_module::instance($cm->id);
// Require capability to moderate reviews.
if (!has_capability('mod/review:moderate', $context) &&
    !has_capability('mod/review_all:moderate', context_system::instance())) {
    throw new required_capability_exception($context, $capability, 'nopermissions', '');
}

$baseurl = new moodle_url('/mod/review/moderate.php', ['id' => $cm->id]); // Make page url.
$PAGE->set_url($baseurl); // Set page url.
$PAGE->set_title($course->shortname . ': ' . format_string($review->name)); // Set page title.
$PAGE->set_heading($course->fullname); // Set page heading.
$PAGE->requires->js_call_amd('mod_review/review', 'init'); // Call JS-module initializtion method.
$renderer = $PAGE->get_renderer('mod_review'); // Get renderer for a page.

ob_start(); // Set output to the buffer.
echo $renderer->header(); // Display page header.
echo $renderer->moderate_page($review); // Display page content.
echo $renderer->footer(); // Display page footer.
echo ob_get_clean(); // Get page html from buffer and send it to user.