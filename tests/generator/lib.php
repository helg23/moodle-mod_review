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
 * mod_review data generator
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_review\user_review;

defined('MOODLE_INTERNAL') || die();


/**
 * Review module data generator class
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_review_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        $record = (array)$record;
        $defaultsettings = ['copletionrate'=> 1,'completionreview'=> 1];
        $this->apply_settings($record,$defaultsettings);
        return parent::create_instance($record, $options);
    }

    public function create_user_review($record = null) {
        global $DB;
        $record = (array)$record;
        if (!isset($record['reviewid'])) {
            throw new coding_exception('reviewid must be present in phpunit_util::create_user_review() $record');
        }
        $defaultsettings = ['status'=> user_review::REVIEW_NOTCHECKED];
        $this->apply_settings($record,$defaultsettings);
        $record=(object)$record;
        $record->id=$DB->insert_record('review_userreviews',$record);
        return $record;
    }

    private function apply_settings(&$record,$settings){
        foreach ($settings as $name => $value) {
            if (!isset($record[$name])) {$record[$name] = $value;}
        }
    }
}