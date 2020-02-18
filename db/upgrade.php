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
 * Upgrade script for the quiz module.
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_review_upgrade($oldversion) {
    global $DB;

    $result = TRUE;
    $dbman = $DB->get_manager();

    if ($oldversion < 2019101402) {
        // Define field id to be added to review_userreviews.
        $table = new xmldb_table('review_userreviews');
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {$dbman->add_field($table, $field);}

        // Review savepoint reached.
        upgrade_mod_savepoint(true, 2019101402, 'review');
    }

    if ($oldversion < 2019101403) {
        // Define field intro to be added to review.
        $table = new xmldb_table('review');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'name');
        // Conditionally launch add field intro.
        if (!$dbman->field_exists($table, $field)) {$dbman->add_field($table, $field);}

        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'timemodified');
        // Conditionally launch add field introformat.
        if (!$dbman->field_exists($table, $field)) {$dbman->add_field($table, $field);}

        // Review savepoint reached.
        upgrade_mod_savepoint(true, 2019101403, 'review');
    }

    if ($oldversion < 2019101404) {
        // Define field reviewid to be added to review_userreviews.
        $table = new xmldb_table('review_userreviews');
        $field = new xmldb_field('reviewid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');
        // Conditionally launch add field reviewid.
        if (!$dbman->field_exists($table, $field)) {$dbman->add_field($table, $field);}

        // Review savepoint reached.
        upgrade_mod_savepoint(true, 2019101404, 'review');
    }

    if ($oldversion < 2019101412) {

        // Define field coursepage_display to be added to review.
        $table = new xmldb_table('review');
        $field = new xmldb_field('coursepage_display', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'introformat');

        // Conditionally launch add field coursepage_display.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Review savepoint reached.
        upgrade_mod_savepoint(true, 2019101412, 'review');
    }

    if ($oldversion < 2019101416) {
        //need to change saved statuses in DB because of changing of status constants
        $DB->execute('
            UPDATE {review_userreviews}
            SET status=(CASE status WHEN 1 THEN 2 WHEN 2 THEN 1 WHEN 3 THEN 3 END)');
        // Review savepoint reached.
        upgrade_mod_savepoint(true, 2019101416, 'review');
    }

    if ($oldversion < 2019101417) {

        // Define field completionrate to be added to review.
        $table = new xmldb_table('review');

        // Conditionally launch add field completionrate.
        $field = new xmldb_field('completionrate', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'coursepage_display');
        if (!$dbman->field_exists($table, $field)) {$dbman->add_field($table, $field);}

        // Conditionally launch add field completionreview.
        $field = new xmldb_field('completionreview', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'completionrate');
        if (!$dbman->field_exists($table, $field)) {$dbman->add_field($table, $field);}

        // Review savepoint reached.
        upgrade_mod_savepoint(true, 2019101417, 'review');
    }

    return $result;
}
