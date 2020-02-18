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
 * Review external API
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_review; //set namespace

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir."/externallib.php"); //require external api library
require_once($CFG->dirroot."/mod/review/lib.php"); //require review library
require_once($CFG->dirroot.'/lib/modinfolib.php'); //require course modules library

//using other namespaces
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * Class review external API
 */
class external extends external_api {
    /**
     * Check parameters for save_rate method
     * @return external_function_parameters
     */
    public static function save_rate_parameters(){
        return new external_function_parameters([
            'reviewid' => new external_value(PARAM_INT, 'Review ID', VALUE_REQUIRED),
            'rate' => new external_value(PARAM_INT, 'Rate', VALUE_REQUIRED),
        ]);
    }

    /**
     * Save new rate
     * @param $reviewid int ID of review module
     * @param $rate int value of rate (1 to 5)
     * @return object result
     */
    public static function save_rate($reviewid,$rate){
        global $DB,$USER,$PAGE;
        //check params correctness
        $param= self::validate_parameters(self::save_rate_parameters(),['reviewid' => $reviewid,'rate'=>$rate]);
        $empty_answer=(object)['result'=>0,'stat'=>'','userreview_id'=>0]; //init empty answer
        //get review element
        if (!$review = $DB->get_record('review', ['id'=> $param['reviewid']])) {return $empty_answer;}
        $user_review=new user_review($USER,$review); //get user_review object
        $user_review->update(['rate'=>$rate]); //update data
        $cm=get_coursemodule_from_instance('review',$user_review->reviewid); //course module object
        $PAGE->set_context(\context_module::instance($cm->id)); //set context of page
        $renderer = $PAGE->get_renderer('mod_review'); //get renderer object for a plugin
        $stat=user_review::rates_stat($reviewid); //get rates statistics
        //send result with rendered statistics
        return (object)['result'=>1,'stat'=>$renderer->display_all_rates_stat($stat),'userreview_id'=>$user_review->id];
    }

    /**
     * Check results of save_rate method
     * @return external_single_structure
     */
    public static function save_rate_returns(){
        return new external_single_structure([
            'result'=>new external_value(PARAM_INT, 'Execution result'), //result of save_rate (1 - correct, others - errors)
            'stat'=>new external_value(PARAM_RAW, 'HTML of stat widget'), //HTML of statistics block
            'userreview_id'=>new external_value(PARAM_RAW, 'ID of user review') //ID of user review
        ]);
    }


    /**
     * Check parameters for save_status method
     * @return external_function_parameters
     */
    public static function save_status_parameters(){
        return new external_function_parameters([
            'user_reviewid' => new external_value(PARAM_INT, 'Review ID', VALUE_REQUIRED),
            'status' => new external_value(PARAM_INT, 'Status', VALUE_REQUIRED),
        ]);
    }

    /**
     * Save new status of review
     * @param $reviewid int ID of review module
     * @param $status int new status
     * @return object result
     */
    public static function save_status($user_reviewid,$status){
        global $PAGE;
        //check params
        $param= self::validate_parameters(self::save_status_parameters(),['user_reviewid' => $user_reviewid,'status'=>$status]);
        $empty_answer=(object)['result'=>0,'switcher'=>'']; //init empty answer
        //get user reviews
        if(!$user_reviews=user_review::get(['id'=>$param['user_reviewid']])){return $empty_answer;}
        $user_review=reset($user_reviews);
        $user_review->update(['status'=>$param['status']]); //update status

        //trigger user review assessed event
        $cm=get_coursemodule_from_instance('review',$user_review->instance->reviewid);
        $params = ['context' => \context_module::instance($cm->id),'objectid' => $user_review->instance->id];
        $event = \mod_review\event\review_assessed::create($params);
        $event->add_record_snapshot('review_userreviews',$user_review->instance);
        $event->trigger();

        $PAGE->set_context(\context_course::instance($user_review->review->course));
        $renderer = $PAGE->get_renderer('mod_review'); //get renderer object for a plugin
        return (object)['result'=>1,'switcher'=>$renderer->status_switcher($user_review)]; //send result with  status switcher HTML
    }

    /**
     * Check result of save_status method
     * @return external_single_structure
     */
    public static function save_status_returns(){
        return new external_single_structure([
            'result'=>new external_value(PARAM_INT, 'Execution result'), //result of saving (1 - correct, others - errors)
            'switcher'=>new external_value(PARAM_RAW, 'HTML of switcher widget') //HTML of status switcher
        ]);
    }
}

