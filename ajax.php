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
 * Handles AJAX processing (convert date to timestamp using current calendar).
 *
 * @package   availability_quizquestion
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Benjamin Schröder, Thomas Lattner, Alex Keiller
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

$quizid = required_param('quizid', PARAM_INT);

// To be replaced with correct code.
$questions = [
    (object)['id' => 5, 'name' => 'Q1'],
    (object)['id' => 7, 'name' => 'Q2'],
    (object)['id' => 8, 'name' => 'Q3'],
];

echo json_encode($questions);
