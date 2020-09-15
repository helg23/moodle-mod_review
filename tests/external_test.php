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
 * External mod_review functions unit tests
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_review\external as mod_review_external;
use mod_review\user_review;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * External mod_review functions unit tests
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_review_external_testcase extends externallib_advanced_testcase {

    /**
     * Test for mod_review external save_rate
     */
    public function test_mod_review_save_rate(){
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();

        $student = self::getDataGenerator()->create_user();
        self::setUser($student);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        $record = new stdClass();
        $record->course = $course->id;
        $review = $this->getDataGenerator()->get_plugin_generator('mod_review')->create_instance($record);

        $rate = mt_rand(1,5);
        $returndescription = mod_review_external::save_rate_returns();
        $rawresult = mod_review_external::save_rate($review->id, $rate);
        $result = external_api::clean_returnvalue($returndescription, $rawresult);

        $this->assertEquals(1, $result['result']);
        $this->assertNotEmpty($result['stat']);
        $this->assertNotEmpty($result['userreview_id']);

        $userreview = $DB->get_record('review_userreviews', ['id' => $result['userreview_id']]);
        $this->assertEquals($rate, $userreview->rate);
    }

    /**
     * Test for mod_review external save_status
     * @param object $userreview user review
     * @depends test_mod_review_save_rate
     */
    public function test_mod_review_save_status($userreview) {
        global $USER, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $record = new stdClass();
        $record->course = $course->id;
        $review = $this->getDataGenerator()->get_plugin_generator('mod_review')->create_instance($record);

        $record = new stdClass();
        $record->reviewid = $review->id;
        $record->userid = $USER->id;
        $userreview = $this->getDataGenerator()->get_plugin_generator('mod_review')->create_user_review($record);

        $status = mt_rand(user_review::REVIEW_RETURNED, user_review::REVIEW_ACCEPTED);
        $returndescription = mod_review_external::save_status_returns();
        $rawresult = mod_review_external::save_status($userreview->id, $status);
        $result = external_api::clean_returnvalue($returndescription, $rawresult);

        $this->assertEquals(1, $result['result']);
        $this->assertNotEmpty($result['switcher']);

        $userreview = $DB->get_record('review_userreviews', ['id' => $userreview->id]);
        $this->assertEquals($status, $userreview->status);
    }
}

