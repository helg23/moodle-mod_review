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
 * Class to manipulate user reviews
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_review;
require_once($CFG->dirroot.'/lib/completionlib.php');

/**
 * Class user_review
 * @package mod_review
 */
class user_review {

     /**
    Review status - returned
     */
    const REVIEW_RETURNED=1;

    /**
    Review status - not checked
     */
    const REVIEW_NOTCHECKED=2;

    /**
    Review status - accepted
     */
    const REVIEW_ACCEPTED=3;

    /**
     * maximum length of review text
     */
    const MAX_REVIEW_LENGTH=997;

    /**
     * @var object of user review from db
     */
    public $instance;

    /**
     * @var object of user
     */
    public $user;

    /**
     * @var object of review
     */
    public $review;


    /**
     * user_review constructor.
     * @param $user object of user from DB
     * @param $review object of review from DB
     * @param $user_review object of user_review from DB (could be null)
     */
    public function __construct($user, $review, $user_review=null){
        //if empty user_review object
        if (!$user_review){
            //try to get it from DB
            $user_reviews=self::get_from_db(['userid'=>$user->id,'reviewid'=>$review->id]);
            //if we can't find user_review in DB - create and empty object
            $user_review= (count($user_reviews)>0) ? reset($user_reviews) : $this->empty_object($review->id,$user->id);
        }
        $this->instance=$user_review; //set instance field
        $this->review=$review; //set review field
        $this->user=$user; //set user field
        //user object should contain some fields (to output user picture correctly)
        foreach(self::user_fields() as $field){
            $this->user->$field=property_exists($user,$field) ? $user->$field : '';
        }
        //if we need to save rate from GET-params (when JS is turned off)
        if ($rate=optional_param('rate',0,PARAM_INT)){$this->update(['rate'=>$rate]);}
    }

    /**
     * get field from instance sub object
     * @param $field
     * @return mixed value of field of empty string
     */
    public function __get($field){
        return (isset($this->instance->$field)) ? $this->instance->$field : null;
    }

    /**
     * Handle form to display and save user review
     * @return string HTML of form
     */
    public function review_form(\moodle_page $page){
        //сheck ability to give reviews
        if (!has_capability('mod/review:give', $page->context)) {return;}
        $reviewform = new review_form($page->url);
        //if we get data from form
        if ($data = $reviewform->get_data()) {
            $data->timeadded=time(); //set new timeadded
            $data->status=self::REVIEW_NOTCHECKED; //set unchecked status
            $this->update((array)$data); //save data

            //trigger user review added event
            $cm=get_coursemodule_from_instance('review',$this->instance->reviewid);
            $params = ['context' => \context_module::instance($cm->id),'objectid' => $this->instance->id];
            $event = \mod_review\event\review_added::create($params);
            $event->add_record_snapshot('review_userreviews',$this->instance);
            $event->trigger();
        }
        $reviewform->set_data($this->instance); //set form data
        $reviewform->apply_status($this->status); //update form display based on status
        return $reviewform->render(); //return html of form
    }

    /**
     * Update user review parameters
     * @param $newvalues array of new values for parameters
     * @param $review object of review from DB - to reduce amount of DB requests
     * @return $result bool - result of update
     */
    public function update($newvalues){
        global $DB;
        $completion_state=null; //completion update state

        //filter parameters, skip incorrect values, format data
        foreach($newvalues as $field=>$value){
            //skip incorrect fields to update
            if (!isset($this->instance->$field) || in_array($field,['id','reviewid','userid'])){continue;}

            //skip incorrect rate format or no capability to save it
            if($field=='rate'){
                if (!in_array($value,range(1,5))) {continue;}
                //get course module
                $cm=get_coursemodule_from_instance('review',$this->reviewid);
                //get module context
                $context=\context_module::instance($cm->id);

                if(!has_capability('mod/review:give',  $context)){continue;}
                //set completion status depends on rate
                if($this->review->completionrate){$completion_state=COMPLETION_COMPLETE;}
            }

            //skip incorrect status format or no capability to save it
            if($field=='status') {
                if(!in_array($value,[self::REVIEW_RETURNED,self::REVIEW_NOTCHECKED,self::REVIEW_ACCEPTED])){continue;}
                $context=\context_course::instance($this->review->course);
                if ($value!=self::REVIEW_NOTCHECKED && !has_capability('mod/review:moderate',  $context) &&
                        !has_capability('mod/review_all:moderate', \context_system::instance())) {continue;}
                if($this->review->completionreview){ //set completion status depends on status of review
                    $completion_state= $value==self::REVIEW_ACCEPTED ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
                }
            }
            $this->instance->$field=self::format_field($field,$value);
        }

        //Save user review data in DB
        if (empty($this->instance->id)) { //insert in DB if it doesn't exist yet
            if(!$userreviewid = $DB->insert_record('review_userreviews', $this->instance)){return false;}
            $this->instance->id = $userreviewid;
        } else { //update in DB
            if(!$res=$DB->update_record('review_userreviews',$this->instance)){return false;}
        }

        // Update completion state if needed
        if ($completion_state!==null) {
            list($course, $cm) = get_course_and_cm_from_instance($this->review, 'review');
            $completion = new \completion_info($course);
            if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC) {
                $completion->update_state($cm, $completion_state, $this->instance->userid);
            }
        }
        return true;
    }

    /**
     * get obligatory user object fields
     * @return array of fields
     */
    private static function user_fields() {
        $user_fields=explode(',',\user_picture::fields());
        return array_diff($user_fields,['id']);
    }

    /**
     * get obligatory review object fields
     * @return array of fields
     */
    private static function review_fields() {
        return ['course','completionrate','completionreview'];
    }

    /**
     * Save status from GET-param (in case JS is turned off)
     */
    public static function save_status(){
        $newstatus=optional_param('newstatus',0,PARAM_INT);
        $userreview_id=optional_param('userreviewid',0,PARAM_INT);
        if($newstatus && $user_reviews=user_review::get(['id'=>$userreview_id])){ //get user_review object
            $user_review=reset($user_reviews);
            $user_review->update(['status'=>$newstatus]); //update status

            //trigger user review assessed event
            $cm=get_coursemodule_from_instance('review',$user_review->instance->reviewid);
            $params = ['context' => \context_module::instance($cm->id),'objectid' => $user_review->instance->id];
            $event = \mod_review\event\review_assessed::create($params);
            $event->add_record_snapshot('review_userreviews',$user_review->instance);
            $event->trigger();
        }
    }

    /**
     * Get user_review data from DB
     *
     * @param array $filter array of filters to reviews
     * @param bool $count return count of records only
     * @param int $limitfrom the record to start from
     * @param int $limitnum number of records to take
     * @return mixed count or null or complex object from db
     */
    private static function get_from_db($filter=null,$count=false,$limitfrom=0,$limitnum=0){
        global $DB;

        //======SELECT block of sql-query=====
        $query='SELECT '.($count ? 'COUNT(user_review.id)' : 'user_review.*');
        if (!$count) { //if we should return data
            //add user fields in SELECT block of query
            foreach(self::user_fields() as $ufield){$query.=',user.'.$ufield;}
            //add review fields in SELECT block of query
            foreach(self::review_fields() as $rfield){$query.=',review.'.$rfield;}
            //add coursename and id in SELECT block of query
            $query.=',course.fullname AS coursename,category.name AS categoryname,category.id AS categoryid';
        }

        //======FROM block of sql-query=====
        $query.=' 
        FROM {review_userreviews} user_review
        INNER JOIN {review} review ON review.id=user_review.reviewid         
        INNER JOIN {user} user ON user.id=user_review.userid
        INNER JOIN {course} course ON course.id=review.course
        INNER JOIN {course_categories} category ON category.id=course.category';

        //=======WHERE block of sql-query===
        $params=[]; //params of sql-query
        //tables and their fields that could be used as a filters
        $filter_fields=[
            'user_review'=>['id','reviewid','rate','status','userid'],
            'review'=>['course'],
            'course'=>['fullname'],
            'category'=>['name']
        ];
        $where=[];  //conditions of sql-query
        //add filter conditions to WHERE
        foreach($filter_fields as $table=>$fields) {
            foreach ($fields as $field ) {
                if (empty($filter[$field])) {continue;} //no filter with this field
                //format of filters in query depends on its type
                if (is_array($filter[$field])) {
                    list($field_sql, $field_params) = $DB->get_in_or_equal($filter[$field], SQL_PARAMS_NAMED);
                    $where[] = "$table.$field $field_sql";
                    $params += $field_params;
                } elseif (is_numeric($filter[$field])) {
                    $where[] = "$table.$field = :$field";
                    $params[$field] = $filter[$field];
                } else {
                    $where[] = $DB->sql_like($table . '.' . $field, ':' . $field, false);
                    $params[$field] = '%' . $filter[$field] . '%';
                }
            }
        }
        if (!empty($where)){$query.=' WHERE '.implode(' AND ',$where);}
        //add specific condition if needed only real reviews
        if (!empty($filter['notempty_reviews'])){$query.=" AND user_review.text IS NOT NULL AND user_review.text!=''";}

        //if we should return only count - make a request and return count of records
        if ($count){return $DB->count_records_sql($query,$params);}

        //=======ORDER block of query===
        $query.=" ORDER BY user_review.timeadded DESC";

        //return result of a request
        return $DB->get_records_sql($query,$params,$limitfrom,$limitnum);
    }

    /**
     * Get user_reviews according to filter conditions
     *
     * @param array $filter array of filters to user reviews
     * @param int $page page number (for pagination)
     * @param int $perpage number of records in one page (for pagination)
     * @return mixed null or array of user_review objects
     */
    public static function get($filter,$page=0,$perpage=0){
        $result=[];
        //get array of review data from db
        $user_reviews=self::get_from_db($filter,false,$page*$perpage,$perpage);
        //for each data record create an object of user_review class
        foreach($user_reviews as $ureview){
            //create separate user object from complex record
            $user=new \stdClass;
            $user->id=$ureview->userid;
            foreach(self::user_fields() as $ufield){$user->$ufield=$ureview->$ufield;}
            //create separate review object from complex record
            $review=new \stdClass;
            $review->id=$ureview->reviewid;
            foreach(self::review_fields() as $rfield){$review->$rfield=$ureview->$rfield;}
            //create object user_review from data and add it to result
            $result[]=new user_review($user,$review,$ureview);
        }
        return $result;
    }

    /**
     * Count user_reviews according to filter conditions
     *
     * @param array $filter array of filters to user reviews
     * @return int count of user_reviews in db
     */
    public static function count($filter){
        return self::get_from_db($filter,true);
    }

    /**
     * Get all rates statistics
     * @param $reviewid int or array of ID of review
     * @return object review statistics info
     */
    public static function rates_stat($reviewid){
        global $DB;
        //get review conditions
        list($review_condition, $params) = $DB->get_in_or_equal($reviewid,SQL_PARAMS_NAMED,'param',true,0);
        //complex request to calculate a share of each rate in the total number of rates
        $query='
        SELECT 
            COUNT(id) \'amount\',
            IFNULL(ROUND(AVG(rate),1),\'0\') \'avg\',
            IFNULL(ROUND(SUM(IF(rate=5,1,0))/COUNT(id)*100),\'0\') \'rate5\',  
            IFNULL(ROUND(SUM(IF(rate=4,1,0))/COUNT(id)*100),\'0\') \'rate4\',
            IFNULL(ROUND(SUM(IF(rate=3,1,0))/COUNT(id)*100),\'0\') \'rate3\',
            IFNULL(ROUND(SUM(IF(rate=2,1,0))/COUNT(id)*100),\'0\') \'rate2\',
            IFNULL(ROUND(SUM(IF(rate=1,1,0))/COUNT(id)*100),\'0\') \'rate1\'
        FROM {review_userreviews}
        WHERE rate!=:zero_rate AND reviewid '.$review_condition;
        $params['zero_rate']=0; //add param
        //get the request result
        $stat=$DB->get_record_sql($query,$params);
        return $stat; //return statistics info
    }

    /**
     * get empty user review object (needed when there is no user review records in DB yet)
     * @param $reviewid int ID of review
     * @param $userid int ID of user
     * @return object empty user review object
     */
    private static function empty_object($reviewid, $userid){
        return (object)['id'=>0,'reviewid'=>$reviewid,'userid'=>$userid,'timeadded'=>time(),
            'text'=>'','rate'=>0,'status'=>self::REVIEW_NOTCHECKED];
    }

    /**
     * format user review fields
     * @param $field string fieldname
     * @param $value mixed new value
     * @return string formatted value
     */
    private static function format_field($field, $value){
        if ($field=='text') return mb_strimwidth($value, 0, self::MAX_REVIEW_LENGTH, "...");
        return $value;
    }
}