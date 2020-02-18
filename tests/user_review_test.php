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
use mod_review\user_review;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Provides unit tests for user_review class common methods
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_review_user_review_testcase extends advanced_testcase {

    /**
     * Test for user_review get method
     */
    public function test_user_review_get(){
        global $DB;

        $this->resetAfterTest(true);
        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category'=>$category->id]);

        $student = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        $record = new stdClass();
        $record->course = $course->id;
        $review=$this->getDataGenerator()->get_plugin_generator('mod_review')->create_instance($record);

        $record = new stdClass();
        $record->reviewid=$review->id;
        $record->userid=$student->id;
        $user_review=$this->getDataGenerator()->get_plugin_generator('mod_review')->create_user_review($record);

        $results=user_review::get(['id'=>$user_review->id,'course'=>$course->id,
            'fullname'=>$course->fullname,'name'=>$category->name]);
        $result=reset($results);

        $this->assertEquals($user_review->id, $result->instance->id);
        $this->assertEquals($student->id, $result->user->id);
        $this->assertEquals($review->id, $result->review->id);
    }

    /**
     * Test for mod_review rates_stat method
     */
    public function test_user_review_rates_stat(){

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = self::getDataGenerator()->create_user();

        $record = new stdClass();
        $record->course = $course->id;
        $review=$this->getDataGenerator()->get_plugin_generator('mod_review')->create_instance($record);

        $rate=mt_rand(1,5);
        $record = new stdClass();
        $record->reviewid=$review->id;
        $record->userid=$user->id;
        $record->rate=$rate;
        $this->getDataGenerator()->get_plugin_generator('mod_review')->create_user_review($record);

        $result=user_review::rates_stat($review->id);
        $this->assertEquals(1, $result->amount);
        $this->assertEquals($rate, $result->avg);
        $this->assertEquals(100, $result->{'rate'.$rate});
    }
}

