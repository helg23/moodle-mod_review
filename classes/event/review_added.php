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
 * The mod_review review added event.
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_review\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The mod_review review added event class.
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class review_added extends \core\event\base {

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_userreview_added', 'mod_review');
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'review_userreviews';
    }

    /**
     * Return object mapping
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'review_userreviews', 'restore' => 'review_userreviews'];
    }

    /**
     * Return legacy log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        $message = $this->get_record_snapshot('review_userreviews', $this->objectid);
        return [$this->courseid, 'review', 'reviewed', 'view.php?id=' . $this->contextinstanceid,
            $message->reviewid, $this->contextinstanceid, $this->userid];
    }
}
