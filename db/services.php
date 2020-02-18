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
 * External functions and service definitions for the review module.
 *
 * @package    mod_review
 * @copyright  2019 Oleg Kovalenko ©HSE University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
    'mod_review_save_rate' => [
        'classname'    => 'mod_review\external',
        'methodname'   => 'save_rate',
        'classpath'    => '',
        'description'  => "Save user's rate for a course ",
        'type'         => 'write',
        'capabilities' => '',
        'ajax'         => true,
        'services'     => [],
        'loginrequired'=> false
    ],
    'mod_review_save_status' => [
        'classname'    => 'mod_review\external',
        'methodname'   => 'save_status',
        'classpath'    => '',
        'description'  => "Save user review status ",
        'type'         => 'write',
        'capabilities' => '',
        'ajax'         => true,
        'services'     => [],
        'loginrequired'=> false
    ]
];
