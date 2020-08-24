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
 * Restriction by single quiz question condition main class.
 *
 * @package availability_quizquestion
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Benjamin Schröder, Thomas Lattner, Alex Keiller
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_quizquestion;

defined('MOODLE_INTERNAL') || die();

/**
 * Restriction by single quiz question condition main class.
 */
class condition extends \core_availability\condition {

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {

        if (isset($structure->quizid) && is_int($structure->quizid)) {
            $this->quizid = $structure->quizid;
        } else {
            throw new \coding_exception('Invalid -> quizid for quizquestion condition');
        }

        if (isset($structure->questionid) && is_int($structure->questionid)) {
            $this->questionid = $structure->questionid;
        } else {
            throw new \coding_exception('Invalid -> questionid for quizquestion condition');
        }

        if (isset($structure->requiredstate) && is_bool($structure->requiredstate)) {
            $this->requiredstate = $structure->requiredstate;
        } else {
            throw new \coding_exception('Invalid -> requiredstate for quizquestion condition');
        }
    }

    public function save() {
        return (object)array('type' => 'quizquestion',
                            'quizid' => $this->quizid,
                            'questionid' => $this->questionid,
                            'requiredstate' => $this->requiredstate);

    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {

        $course = $info->get_course();
        $context = \context_course::instance($info->get_course()->id);

        return $this->requirements_fullfilled($userid);
    }

    /**
     * Gets the question result
     *
     * @param int \userid
     * @param int course
     * @return bool
     */
    protected function requirements_fullfilled($userid) {

        $attempts = quiz_get_user_attempts($this->quizid, $userid, 'finished', true);

        if (count($attempts) != 0) {

            $attemptobj = quiz_attempt::create(end($attempts)->id);

            foreach ($attemptobj->get_slots() as $slot) {

                $qa = $attemptobj->get_question_attempt($slot);

                if (!$qa->get_question_id() == $this->$questionid) {
                    // This is the qa we need
                    // Todo
                    //$attemptobj->get_question_mark($slot)
                }
            }
        }

        return false;
    }

    public function get_description($full, $not, \core_availability\info $info) {

        if (!isset($modinfo->instances['quiz'][$this->$quizid])) {
            return '';
        }

        // Todo: Retrieve quiz / question data

        return get_string('requires_quizquestion', 'availibility_quizquestion',
                ['quizid' => $this->quizid, 'questionid' => $this->questionid, 'requiredstate' => $this->requiredstate]);

    }

    protected function get_debug_string() {

        // Todo;
        return "Quiz ID: $this->quizid - Question ID: $this->questionid - Requiredstate: $this->requiredstate ";
    }

    /**
     * Include this condition only if we are including groups in restore, or
     * if it's a generic 'same activity' one.
     *
     * @param int $restoreid The restore Id.
     * @param int $courseid The ID of the course.
     * @param base_logger $logger The logger being used.
     * @param string $name Name of item being restored.
     * @param base_task $task The task being performed.
     *
     * @return Integer groupid
     */
    public function include_after_restore($restoreid, $courseid, \base_logger $logger,
            $name, \base_task $task) {

        // Todo: Check wether conditions after restore are fullfillable.

        return !$this->groupingid || $task->get_setting_value('groups');
    }

    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        global $DB;
        // Todo:
        return true;
    }

    public function update_dependency_id($table, $oldid, $newid) {
        if ($table === 'groupings' && (int)$this->groupingid === (int)$oldid) {
            $this->groupingid = $newid;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Wipes the static cache used to store grouping names.
     */
    public static function wipe_static_cache() {
        self::$groupingnames = array();
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $groupingid Required grouping id (0 = grouping linked to activity)
     * @return stdClass Object representing condition
     */
    public static function get_json($groupingid = 0) {
        $result = (object)array('type' => 'grouping');
        if ($groupingid) {
            $result->id = (int)$groupingid;
        } else {
            $result->activity = true;
        }
        return $result;
    }

}
