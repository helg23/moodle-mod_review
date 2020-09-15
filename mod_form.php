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
 * Defines the review module setting form.
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die; // Internal script.

require_once($CFG->dirroot.'/course/moodleform_mod.php'); // Require form library.
require_once($CFG->dirroot.'/mod/review/lib.php'); // Require module library.
/**
 * Settings form for the review module.
 *
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_review_mod_form extends moodleform_mod {
    /**
     * Define form fields
     */
    public function definition() {
        global $DB, $COURSE;

        $mform = $this->_form; // Initialize form object.

        $courseid = $COURSE->id; // Get course ID param.
        $update = optional_param('update', 0, PARAM_INT); // Get update ID param.

        $alreadyadded = false;
        // Check if the element is already added to the course.
        $cmsinstances = get_fast_modinfo($courseid)->instances;
        if (!$update && array_key_exists('review', $cmsinstances)) {
            $reviewcms = $cmsinstances['review'];
            foreach ($reviewcms as $cm) {
                // Deletion of element could be in progress.
                if ($cm->deletioninprogress != 1) {
                    $alreadyadded = true;
                    break;
                }
            }
        }
        // If review module already added to this course.
        if ($alreadyadded) {
            // Show information to user (we can add only one element).
            $mform->addElement('static', 'alreadyexists', '', get_string('already_exists', 'mod_review'));
            // Add hidden elements to display form correctly.
            $mform->addElement('hidden', 'update', 0);
            $mform->setType('update', PARAM_INT);
            $mform->addElement('hidden', 'completionunlocked', 0);
            $mform->setType('completionunlocked', PARAM_INT);
        } else { // If no review module added to course.
            $mform->addElement('header', 'generalhdr', get_string('general')); // Add header.
            $this->standard_intro_elements(); // Add intro field.
            // Add display on coursepage setting.
            $mform->addElement('advcheckbox', 'coursepage_display', get_string('coursepage_display', 'mod_review'), '', [], [0, 1]);
            // Add help button for display on coursepage field.
            $mform->addHelpButton('coursepage_display', 'coursepage_display', 'mod_review');
            // Add other standard settings.
            $this->standard_coursemodule_elements();
            // Add submit and cancel buttons.
            $this->add_action_buttons(true, false, null);
        }
    }

    /**
     * Define completion rules
     */
    public function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('advcheckbox', 'completionrate',
            get_string('completionrate', 'mod_review'), '', ['group' => 0], [0, 1]);
        $mform->addElement('advcheckbox', 'completionreview',
            get_string('completionreview', 'mod_review'), '', ['group' => 0], [0, 1]);

        return array('completionrate', 'completionreview');
    }

   /**
    * Check whether one of module's completion rules has been selected
    * @param array $data form data
    */
    public function completion_rule_enabled($data) {
        return (!empty($data['completionrate']) || !empty($data['completionreview']));
    }
}