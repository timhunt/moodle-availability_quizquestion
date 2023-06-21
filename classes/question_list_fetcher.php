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

namespace availability_quizquestion;

use mod_quiz\question\bank\qbank_helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/questionlib.php');
if ($CFG->release < 4.2) {
    require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
}

/**
 * Helper class to get the list of question options to show on the settings form.
 *
 * @package availability_quizquestion
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Martin Hanusch, Thomas Lattner, Alex Keiller
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_list_fetcher {

    /**
     * Return a list of the questions in a quiz.
     *
     * Ignores things that are not really questions (e.g. descriptions)
     * and random questions (for now).
     *
     * @param int $quizid quiz id.
     * @return object[] array of objects with fields id (questionbankentryid) and name.
     */
    public static function list_questions_in_quiz(int $quizid, \context_module $context): array {

        $slotinfo = qbank_helper::get_question_structure($quizid, $context);

        $choices = [];
        $questionnumber = 0;
        foreach ($slotinfo as $slot) {
            if ($slot->length == 0) {
                // Not real question (e.g. description).
                continue;
            }
            $questionnumber += 1;
            if ($slot->qtype == 'random') {
                continue;
            }

            $a = (object) ['number' => $questionnumber, 'name' => format_string($slot->name)];
            $choices[] = (object) ['id' => (int) $slot->questionbankentryid,
                    'name' => get_string('questionnumberandname', 'availability_quizquestion', $a)];
        }

        return $choices;
    }
}
