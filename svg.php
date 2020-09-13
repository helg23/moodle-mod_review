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

$cachedir = $CFG->localcachedir.'/theme/mod/review/'; // Set cache directory.
$pix = required_param('pix', PARAM_ALPHANUMEXT); // Get file to render.
$filename = $pix.'.svg'; // Set filename.
if (!file_exists($cachedir.'/'.$filename)) { // If file is not cached yet.
    $pixFile = $CFG->dirroot . '/mod/review/pix/' . $pix . '.svg'; // Get original svg.
    if (!is_file($pixFile)) { // If no such svg in plugin - return null.
		return null;
	} 
    $pixContent = file_get_contents($pixFile); // Get content of origin svg file.
    $mainColor = get_config('mod_review', 'colortheme'); // Get color setting.
    $pixContent = str_replace("#000000", $mainColor, $pixContent); // Change color in svg.
    file_safe_save_content($pixContent, $cachedir . '/' . $filename); // Put new svg in cache directory.
}
send_file($cachedir.'/'.$filename, $filename); // Send svg to user.
