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
 * Handles AJAX requests to get the list of questions in a quiz.
 *
 * @package   availability_quizquestion
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Martin Hanusch, Thomas Lattner, Alex Keiller
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use availability_quizquestion\question_list_fetcher;

define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

$quizid = required_param('quizid', PARAM_INT);

// Check login and permissions.
$quiz = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $quiz->course));
$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/quiz:viewreports', $context);

echo json_encode(question_list_fetcher::list_questions_in_quiz($quizid));
