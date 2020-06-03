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
 * GDPR information
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_review\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider
{
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'review_userreviews',
            [
                'userid' => 'privacy:metadata:review_userreviews:userid',
                'rate' => 'privacy:metadata:review_userreviews:rate',
                'text' => 'privacy:metadata:review_userreviews:text',
                'timeadded' => 'privacy:metadata:review_userreviews:timeadded',
            ],
            'privacy:metadata:timeadded'
        );
        return $collection;
    }

    private static $modid;
    private static function get_modid() {
        global $DB;
        if (self::$modid === null) {
            self::$modid = $DB->get_field('modules', 'id', ['name' => 'review']);
        }
        return self::$modid;
    }

    /**
     * Get the list of contexts that have been rated or reviewed by the user
     *
     * @param int userid ID of the user
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        $modid = self::get_modid();
        if (!$modid) {return $contextlist;} // review module not installed.

        $params = [
            'modid' => $modid,
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        // Items that have been rated or reviewed by the user
        $sql = '
           SELECT c.id
             FROM {context} c
             JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                                      AND cm.module = :modid
             JOIN {review} rw ON rw.id = cm.instance
             JOIN {review_userreviews} rwu ON rwu.reviewid = rw.id
            WHERE rwu.userid = :userid
        ';
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!is_a($context, \context_module::class)) {return;}
        $modid = self::get_modid();
        if (!$modid) {return;} // Review module not installed.

        $params = [
            'modid' => $modid,
            'contextlevel' => CONTEXT_MODULE,
            'contextid'    => $context->id,
        ];

        // Items reviewed or rated by user
        $sql = "
            SELECT rwu.userid
              FROM {review_userreviews} rwu
              JOIN {review} rw ON rw.id = rwu.reviewid
              JOIN {course_modules} cm ON cm.instance = rw.id AND cm.module = :modid
              JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
             WHERE ctx.id = :contextid
        ";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export the supplied personal data for all review activities in contextlist
     *
     * @param approved_contextlist $contextlist the list of contexts for which the plugin stores data about the user
     * @param int $cmid
     * @param \stdClass $user
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {return;}
        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       rwu.userid,
                       rwu.rate,
                       rwu.text,
                       rwu.timeadded
                       
                 FROM {context} c
                 JOIN {course_modules} cm ON cm.id = c.instanceid
                 JOIN {review} rw ON rw.id = cm.instance
                 JOIN {review_userreviews} rwu ON rwu.reviewid = rw.id
                 
                WHERE c.id $contextsql
                  AND rwu.userid = :userid
                ORDER BY cm.id, ci.position, ci.id
        ";
        $params = ['userid' => $user->id] + $contextparams;
        $lastcmid = null;
        $itemdata = [];

        $user_reviews = $DB->get_recordset_sql($sql, $params);
        foreach ($user_reviews as $user_review) {
            if ($lastcmid !== $user_review->cmid) {
                if ($itemdata) {
                    self::export_checklist_data_for_user($itemdata, $lastcmid, $user);
                }
                $itemdata = [];
                $lastcmid = $user_review->cmid;
            }

            $reviewdata[] = (object)[
                'userid' => $user_review->userid,
                'rate' => $user_review->rate ? $user_review->rate : '',
                'text' => $user_review->text ? $user_review->text : '',
                'timeadded' => $user_review->timeadded ? transform::datetime($user_review->timeadded) : '',
            ];
        }
        $user_reviews->close();

        if ($reviewdata) {
            self::export_review_data_for_user($reviewdata, $lastcmid, $user);
        }
    }

    /**
     * Export the supplied personal data for a single review activity, along with any generic data
     *
     * @param array $userreviews the data for each of the user_reviews in the review
     * @param int $cmid
     * @param \stdClass $user
     */
    protected static function export_review_data_for_user(array $userreviews, int $cmid, \stdClass $user) {
        // Fetch the generic module data for the choice.
        $context = \context_module::instance($cmid);
        $contextdata = helper::get_context_data($context, $user);

        // Merge with checklist data and write it.
        $contextdata = (object)array_merge((array)$contextdata, ['user_reviews' => $userreviews]);
        writer::with_context($context)->export_data([], $contextdata);
    }

    /**
     * Delete data for all users in a single context
     *
     * @param \context $context The context to delete information for.
     */

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context) {return;}
        if ($context->contextlevel != CONTEXT_MODULE) {return;}
        if (!$cm = get_coursemodule_from_id('review', $context->instanceid)) {return;}

        $user_reviews_ids = $DB->get_fieldset_select('review_userreviews', 'id', 'reviewid = ?',
            [$cm->instance]);
        if ($user_reviews_ids) {
            $DB->delete_records_list('review_userreviews', 'id', $user_reviews_ids);
        }
    }

    /**
     * Delete data for users within a contextlist
     *
     * @param approved_contextlist $contextlist The approved contextlist to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if (!$contextlist->count()) {return;}
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {continue;}
            if (!$cm = get_coursemodule_from_id('review', $context->instanceid)) {continue;}
            $user_reviews_ids = $DB->get_fieldset_select('review_userreviews', 'id', 'reviewid = ?',
                [$cm->instance]);
            if ($user_reviews_ids) {
                list($isql, $params) = $DB->get_in_or_equal($user_reviews_ids, SQL_PARAMS_NAMED);
                $params['userid'] = $userid;
                $DB->delete_records_select('review_userreviews', "item $isql AND userid = :userid", $params);
            }
        }
    }

    /**
     * Delete multiple users data within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!is_a($context, \context_module::class)) {return;}
        $modid = self::get_modid();
        if (!$modid) {return;} // Review module not installed.

        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
        $review = $DB->get_record('review', ['id' => $cm->instance]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['reviewid' => $review->id], $userinparams);
        $sql = "reviewid = :reviewid AND userid {$userinsql}";

        $DB->delete_records_select('review_userreviews', $sql, $params);
    }
}