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

use core_question\local\bank\question_version_status;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

/**
 * Restriction by single quiz question condition main class.
 *
 * @package availability_quizquestion
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Martin Hanusch, Thomas Lattner, Alex Keiller
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var array these are the types of state we recognise. */
    const STATES_USED = ['gradedright', 'gradedpartial', 'gradedwrong'];

    /** @var int the id of the quiz this depends on. */
    protected $quizid;

    /** @var int the id of the question bank entry in the quiz that this depends on. */
    protected $questionbankentryid;

    /**
     * @var int the id of the question in the quiz that this depends on in legacy data.
     *
     * This is only used when restoring backups or as part of updating old data.
     * It gets updated by {@lins update_question_id_to_question_bank_entry_id_if_required()}
     * before use, but because of how backup works, that cannot be done in the constructor.
     */
    protected $questionid;

    /** @var \question_state the state the target question must be in. */
    protected $requiredstate;

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
            throw new \coding_exception('Invalid quizid for quizquestion condition');
        }

        if (isset($structure->questionbankentryid)) {
            // Might not be set if this is old data from before 4.0.
            if (is_int($structure->questionbankentryid)) {
                $this->questionbankentryid = $structure->questionbankentryid;
            } else {
                throw new \coding_exception('Invalid questionbankentryid for quizquestion condition');
            }
        }

        if (isset($structure->questionid)) {
            // This is an old backup, but we can't update it yet, because of how backup works.
            if (is_int($structure->questionid)) {
                $this->questionid = $structure->questionid;
            } else {
                throw new \coding_exception('Invalid questionid for quizquestion condition');
            }
        }

        if (!isset($structure->questionbankentryid) && !isset($structure->questionid)) {
            throw new \coding_exception('One of questionbankentryid or questionid must be set for quizquestion condition');
        }

        if (isset($structure->requiredstate)) {
            $state = \question_state::get($structure->requiredstate);
            if ($state && in_array((string) $state, self::STATES_USED)) {
                $this->requiredstate = $state;
            }
        }
        if (!isset($this->requiredstate)) {
            throw new \coding_exception('Invalid requiredstate for quizquestion condition');
        }

    }

    public function save(): \stdClass {
        $this->update_question_id_to_question_bank_entry_id_if_required();
        return self::get_json($this->quizid, $this->questionbankentryid, $this->requiredstate);
    }

    public static function get_json(int $quizid, int $questionbankentryid,
            \question_state $requiredstate): \stdClass {
        return (object)[
            'type' => 'quizquestion',
            'quizid' => $quizid,
            'questionbankentryid' => $questionbankentryid,
            'requiredstate' => (string) $requiredstate
        ];
    }

    protected function get_debug_string(): string {
        if ($this->questionbankentryid) {
            return " quiz:#$this->quizid, questionbankentry:#$this->questionbankentryid, $this->requiredstate";
        } else {
            // Legacy case.
            return " quiz:#$this->quizid, question:#$this->questionid, $this->requiredstate";
        }
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid): bool {

        $allow = $this->requirements_fulfilled($userid);

        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    /**
     * Determine if the target question is in the expected state.
     *
     * @param int $userid id of the user we are checking for.
     * @return bool true if the question is in the expected state. Else false.
     */
    protected function requirements_fulfilled(int $userid): bool {
        $this->update_question_id_to_question_bank_entry_id_if_required();

        $attempts = quiz_get_user_attempts($this->quizid, $userid, 'finished', true);

        if (count($attempts) > 0) {

            if (class_exists('\\mod_quiz\\quiz_attempt')) {
                $attemptobj = \mod_quiz\quiz_attempt::create(end($attempts)->id);
            } else {
                $attemptobj = \quiz_attempt::create(end($attempts)->id);
            }

            foreach ($attemptobj->get_slots() as $slot) {
                $qa = $attemptobj->get_question_attempt($slot);
                $question = \question_bank::load_question_data($qa->get_question_id());

                if ($question->questionbankentryid == $this->questionbankentryid) {
                    return $qa->get_state() === $this->requiredstate ||
                            // If the teacher has manually graded, the state will acutally be something like
                            // mangrright, so handle that case too by comparing CSS class strings.
                            $qa->get_state()->get_feedback_class() === $this->requiredstate->get_feedback_class();
                }
            }
        }

        return false;
    }

    public function get_description($full, $not, \core_availability\info $info): string {
        global $DB;
        $this->update_question_id_to_question_bank_entry_id_if_required();

        $quiz = $DB->get_record('quiz', ['id' => $this->quizid]);
        $question = $DB->get_record_sql("
                SELECT q.*
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                            AND qv.version = (
                                SELECT MAX(v.version)
                                  FROM {question_versions} v
                                 WHERE v.questionbankentryid = qbe.id
                                   AND v.status <> ?)
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qbe.id = ?
                ", [question_version_status::QUESTION_STATUS_DRAFT, $this->questionbankentryid], IGNORE_MISSING);

        if ($quiz && $question) {
            $a = [
                'quizurl' => (new \moodle_url('/mod/quiz/view.php', ['q' => $quiz->id]))->out(),
                'quizname' => format_string($quiz->name),
                'questiontext' => shorten_text(\question_utils::to_plain_text($question->questiontext,
                        $question->questiontextformat,
                        ['noclean' => true, 'para' => false, 'filter' => false])),
                'requiredstate' => $this->requiredstate->default_string(true),
            ];
            if ($not) {
                return  get_string('requires_quizquestionnot', 'availability_quizquestion', $a);
            } else {
                return  get_string('requires_quizquestion', 'availability_quizquestion', $a);
            }
        }

        return '';
    }

    /**
     * If this was set up under Moodle 3.x (that is, before upgrade, or from a backup)
     * upgrade it now.
     */
    protected function update_question_id_to_question_bank_entry_id_if_required(): void {
        if ($this->questionbankentryid) {
            // Nothing to do, really.
            $this->questionid = null;
            return;
        }

        $questiondata = \question_bank::load_question_data($this->questionid);
        $this->questionbankentryid = $questiondata->questionbankentryid;
        $this->questionid = null;
    }

    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name): bool {
        global $DB;

        // Recode question bank entry id.
        // If we don't find the new questionid, it is not ideal, but for
        // now do nothing. The check below will probably generate a warning
        // about the situation.
        $questionidchanged = false;
        if ($this->questionbankentryid) {
            // Modern backup being restored.
            $rec = \restore_dbops::get_backup_ids_record($restoreid, 'question_bank_entry', $this->questionbankentryid);
            if ($rec && $rec->newitemid) {
                // New question id found.
                $this->questionbankentryid = (int) $rec->newitemid;
                $questionidchanged = true;
            }

        } else {
            // Restonrign 3.x backup. Work out questionbankentryid from old question id.
            $rec = \restore_dbops::get_backup_ids_record($restoreid, 'question', $this->questionid);
            if ($rec && $rec->newitemid) {
                // New question id found.
                $this->questionid = (int) $rec->newitemid;
                $questionidchanged = true;

                $this->update_question_id_to_question_bank_entry_id_if_required();
            }
        }

        // Recode quiz id.
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'quiz', $this->quizid);
        if ($rec && $rec->newitemid) {
            // New quiz id found.
            $this->quizid = (int) $rec->newitemid;
            return true;
        }

        // If we are on the same course (e.g. duplicate) then we can just
        // use the existing one.
        if ($DB->record_exists('quiz',
                ['id' => $this->quizid, 'course' => $courseid])) {
            return $questionidchanged;
        }

        // Otherwise it's a warning.
        $this->quizid = 0;
        $logger->process('Restored item (' . $name .
                ') has availability condition on module that was not restored',
                \backup::LOG_WARNING);
        return $questionidchanged;
    }
}
