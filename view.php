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
 * This page is the entry page into the review UI.
 * Lets students to rate a course, send their review and look through over reviews.
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Entry page into the review UI.
 */
require_once(__DIR__.'/../../config.php');  // Require main config.

$id = required_param('id', PARAM_INT);    // Require Course Module ID.
if (!$cm = get_coursemodule_from_id('review', $id)) {
    print_error(get_string('wrong_cm', 'mod_review'));
} // Course module not found.
if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
    print_error(get_string('wrong_course', 'mod_review'));
}  // Course not found.
if (!$review = $DB->get_record('review', ['id' => $cm->instance])) {
    print_error(get_string('wrong_module', 'mod_review'));
} // Activity not found.
$review->cmid = $cm->id; // Add cmid property to the activity object.

//Requite authorization and course access.
require_course_login($course, false, $cm);

//Get context of current module.
$context = context_module::instance($cm->id);

//Require ability to view reviews.
require_capability('mod/review:view', $context);

// Completion and trigger events.
review_view($review, $course, $cm, $context);

// Make the base url.
$baseurl = new moodle_url('/mod/review/view.php', ['id' => $cm->id]);

// Set page params.
$PAGE->set_url($baseurl); // Set url for a page.
$PAGE->set_pagetype('mod-review-view'); // Set type of the page.
$PAGE->set_context($context); // Set context of the page.
$PAGE->set_pagelayout('incourse'); // Set layout of the page.
$PAGE->set_title($course->shortname . ': ' . format_string($review->name)); // Set title of the page.
$PAGE->set_heading($course->fullname); // Set heading of the page.
$PAGE->requires->js_call_amd('mod_review/review', 'init'); // Call JS-module initializing function.

// Get renderer object for a plugin.
$renderer = $PAGE->get_renderer('mod_review');

// Output page.
ob_start(); // Set output to the buffer.
echo $renderer->header(); // Display page header.
echo $renderer->review_page($review); // Display page content.
echo $renderer->footer(); // Display page footer.
echo ob_get_clean(); // Get page html from buffer and send it to user.

