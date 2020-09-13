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
 * Define all the backup steps
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_review_activity_task
 *
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
 
class backup_review_activity_structure_step extends backup_activity_structure_step {

	/**
     * Define structure for backup
     * @return object backup_nested_element
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $review = new backup_nested_element('review', ['id'],
            ['name', 'intro', 'introformat', 'coursepage_display']);

        $userreviews = new backup_nested_element('userreviews');
        $userreview = new backup_nested_element('userreview', ['id'],
            ['userid', 'rate', 'text', 'timeadded', 'status', 'moderatorid', 'timechecked', 'comment']);

        // Make structure.
        $review->add_child($userreviews);
        $userreviews->add_child($userreview);

        // Set DB tables.
        $review->set_source_table('review', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) { // Without userinfo, skip user reviews.
            $userreview->set_source_table('review_userreviews', ['reviewid' => backup::VAR_PARENTID], 'id ASC');
        }

        // Define id annotations.
        $userreview->annotate_ids('user', 'userid');

        // Define file annotations.
        $review->annotate_files('mod_review', 'intro', null);

        // Return the root element (review), wrapped into standard activity structure.
        return $this->prepare_activity_structure($review);
    }
}
