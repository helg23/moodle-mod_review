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
 * Structure step to restore review activity
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class structure for restore
 */
class restore_review_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define structure for restore
     * @return object restore_path_element
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('review', '/activity/review');
        if ($userinfo) {
            $paths[] = new restore_path_element('review_userreviews', '/activity/review/userreviews/userreview');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process review restore
     * @param object $data data of review
     */
    protected function process_review($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        // Insert record.
        $newitemid = $DB->insert_record('review', $data);
        // Update new activity information.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process user reviews restore
     * @param object $data data of user review
     */
    protected function process_review_userreviews($data) {
        global $DB;

        $data = (object)$data;
        $data->reviewid = $this->get_new_parentid('review');

        $DB->insert_record('review_userreviews', $data);
    }

    /**
     * Other actions after restore
     */
    protected function after_execute() {
        $this->add_related_files('mod_review', 'intro', null);
    }
}
