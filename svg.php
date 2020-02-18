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

require_once(__DIR__.'/../../config.php');  //require main config
require_once($CFG->dirroot.'/lib/filelib.php');  //require file library

$cachedir=$CFG->localcachedir.'/theme/mod/review/'; //set cache directory
$pix=required_param('pix',PARAM_ALPHANUMEXT); //get file to render
$filename=$pix.'.svg'; //set filename
if (!file_exists($cachedir.'/'.$filename)) { //if file is not cached yet
    $pix_file = $CFG->dirroot . '/mod/review/pix/' . $pix . '.svg'; //get original svg
    if (!is_file($pix_file)) {return null;} //if no such svg in plugin - return null
    $pix_content = file_get_contents($pix_file); //get content of origin svg file
    $main_color = get_config('mod_review', 'colortheme'); //get color setting
    $pix_content = str_replace("#000000", $main_color, $pix_content); //change color in svg
    file_safe_save_content($pix_content, $cachedir . '/' . $filename); //put new svg in cache directory
}
send_file($cachedir.'/'.$filename, $filename); //send svg to user
