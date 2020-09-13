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
 * Mod Review general settings *
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Color_theme setting. Default value - moodle boost theme blue color.
    $setting = new admin_setting_configcolourpicker(
        'mod_review/colortheme',
        get_string('color_theme', 'mod_review'),
        get_string('color_theme_desc', 'mod_review'),
        '#1f7fd3'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Number of user reviews on review page. Default value - 5.
    $settings->add(new admin_setting_configtext(
        'mod_review/perpage_review',
        get_string('perpage_review', 'mod_review'),
        get_string('perpage_review_desc', 'mod_review'),
        5)
    );

    // Number of user reviews on moderate page. Default value - 20.
    $settings->add(new admin_setting_configtext('mod_review/perpage_moderate',
        get_string('perpage_moderate', 'mod_review'),
        get_string('perpage_moderate_desc', 'mod_review'),
        20));
}