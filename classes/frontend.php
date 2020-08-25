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
 * Restriction by single quiz question front-end class.
 *
 * @package availability_quizquestion
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Benjamin Schröder, Thomas Lattner, Alex Keiller
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_quizquestion;

defined('MOODLE_INTERNAL') || die();

/**
 * Restriction by single quiz question front-end class.
 */
class frontend extends \core_availability\frontend {
    /** @var array Array of quiz info for course */
    protected $allquizzes;
    /** @var int Course id that $allquizzes is for */
    protected $allquizzescourseid;

    protected function get_javascript_strings() {
        return ['label_state', 'label_question', 'ajaxerror'];
    }

    protected function get_javascript_init_params($course, \cm_info $cm = null,
            \section_info $section = null) {
        // Get all quizzes for course.
        $quizzes = $this->get_all_quizzes($course->id);

        $context = \context_course::instance($course->id);

        array_walk($quizzes, function($quiz) use ($context) {
            $quiz->name = format_string($quiz->name, true, ['context' => $context]);
        });

        $states = [
            'gradedright' => get_string('correct', 'quiz'),
            'gradedpartial' => get_string('partiallycorrect', 'quiz'),
            'gradedwrong' => get_string('incorrect', 'quiz'),
        ];

        return [
            array_values($quizzes),
            self::convert_associative_array_for_js($states, 'shortname', 'displayname'),
        ];
    }

    /**
     * Gets all the quizzes on the course.
     *
     * @param int $courseid Course id
     * @return array Array of quiz objects
     */
    protected function get_all_quizzes($courseid) {
        global $DB;
        if ($courseid != $this->allquizzescourseid) {
            $this->allquizzes = $DB->get_records('quiz', ['course' => $courseid], 'name', 'id, name');
            $this->allquizzescourseid = $courseid;
        }
        return $this->allquizzes;
    }

    protected function allow_add($course, \cm_info $cm = null,
            \section_info $section = null) {

        // Only show this option if there are some quizzes in the course.
        return count($this->get_all_quizzes($course->id)) > 0;
    }
}
