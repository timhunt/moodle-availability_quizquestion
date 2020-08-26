<?php
// This file is part of Moodle - https://moodle.org/
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
 * Restriction by single quiz question language strings.
 *
 * @package   availability_quizquestion
 * @category  string
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Martin Hanusch, Thomas Lattner, Alex Keiller
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['ajaxerror'] = 'Error contacting server to obtain quiz questions';
$string['description'] = 'This plugin allows you to limit access to another Moodle activity based just on the outcome of a single question in a quiz.';
$string['error_selectquiz'] = 'You must select a quiz.';
$string['error_selectquestion'] = 'You must select a question.';
$string['error_selectstate'] = 'You must select a state.';
$string['label_state'] = 'Required state';
$string['label_question'] = 'Which question in the selected quiz';
$string['pluginname'] = 'Restriction by single quiz question';
$string['privacy:metadata'] = 'The Restriction by single quiz question plugin does not store any personal data.';
$string['questionnumberandname'] = 'Q{$a->number}) {$a->name}';
$string['title'] = 'Quiz question';
$string['requires_quizquestion'] = 'The question <b>{$a->questiontext}</b> in <b><a href="{$a->quizurl}">{$a->quizname}</a></b> is <b>{$a->requiredstate}</b>';
$string['requires_quizquestionnot'] = 'The question <b>{$a->questiontext}</b> in <b><a href="{$a->quizurl}">{$a->quizname}</a></b> is not <b>{$a->requiredstate}</b>';
