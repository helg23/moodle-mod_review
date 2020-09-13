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
 * Defines form to send review
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_review;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php'); // Require form library.
require_once($CFG->dirroot.'/mod/review/lib.php'); // Require module library.


/**
 * Form to send review *
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class review_form extends \moodleform {

    /**
     * Define form fields
     */
    public function definition() {
        $mform = $this->_form; // Initialize form object.
        $mform->addElement('html', \html_writer::tag('h4', get_string('your_review', 'mod_review')));
        $mform->addElement('textarea', 'text', '', ['rows' => 5, 'cols' => 50]);
        $this->add_action_buttons(false, get_string('submit'));
    }
    
    /**
     * Apply logic based on review status
	 * @param int $status current status of user review
     */
    public function apply_status($status){
        $mform = $this->_form; //initialize form object
        //for not empty reviews add note about review status
        if ($mform->getElementValue('text') != '') {
            $mform->insertElementBefore($mform->createElement('html',
                \html_writer::div(get_string('status'.$status, 'mod_review'), 'review_status review_status'.$status)), 'submitbutton');
        }
        // User can't change accepted review.
        if ($status == user_review::REVIEW_ACCEPTED) {
            $mform->updateElementAttr('text', ['disabled' => 1]);
            $mform->removeElement('submitbutton');
        }
    }
}