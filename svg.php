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
 * Dynamic svg renderer
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');  // Require main config.
require_once($CFG->dirroot.'/lib/filelib.php');  // Require file library.
require_course_login($SITE); //should be here, no login needed for this script

$cachedir = $CFG->localcachedir.'/theme/mod/review/'; // Set cache directory.
$pix = required_param('pix', PARAM_ALPHANUMEXT); // Get file to render.
$filename = $pix.'.svg'; // Set filename.
if (!file_exists($cachedir.'/'.$filename)) { // If file is not cached yet.
    $pixfile = $CFG->dirroot . '/mod/review/pix/' . $pix . '.svg'; // Get original svg.
    if (!is_file($pixfile)) { // If no such svg in plugin - return null.
        return null;
    }
    $pixcontent = file_get_contents($pixfile); // Get content of origin svg file.
    $maincolor = get_config('mod_review', 'colortheme'); // Get color setting.
    $pixcontent = str_replace("#000000", $maincolor, $pixcontent); // Change color in svg.
    file_safe_save_content($pixcontent, $cachedir . '/' . $filename); // Put new svg in cache directory.
}
send_file($cachedir.'/'.$filename, $filename); // Send svg to user.
