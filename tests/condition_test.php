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
 * Restriction by single quiz question unit tests for the condition class.
 *
 * @package availability_quizquestion
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Martin Hanusch, Thomas Lattner, Alex Keiller
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_quizquestion;
defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the condition class.
 */
class condition_testcase extends \advanced_testcase {
    /**
     * Load required classes.
     */
    public function setUp(): void {
        // Load the mock info class so that it can be used.
        global $CFG;
        parent::setUp();
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
    }

    public function test_constructor_working_case() {
        $cond = new condition((object) [
                'quizid' => 123, 'questionid' => 456, 'requiredstate' => 'gradedwrong']);
        $this->assertEquals('{quizquestion: quiz:#123, question:#456, gradedwrong}', (string) $cond);
    }

    public function test_constructor_invalid_quizid() {
        $this->expectExceptionMessage('Invalid quizid for quizquestion condition');
        new condition((object) [
                'quizid' => 'wrong', 'questionid' => 456, 'requiredstate' => 'gradedwrong']);
    }

    public function test_constructor_invalid_questionid() {
        $this->expectExceptionMessage('Invalid questionid for quizquestion condition');
        new condition((object) [
                'quizid' => 123, 'questionid' => 'wrong', 'requiredstate' => 'gradedwrong']);
    }

    public function test_constructor_invalid_state() {
        $this->expectExceptionMessage('Invalid requiredstate for quizquestion condition');
        new condition((object) [
                'quizid' => 123, 'questionid' => 456, 'requiredstate' => 'todo']);
    }

    public function test_save() {
        $structure = (object) ['quizid' => 123, 'questionid' => 456, 'requiredstate' => 'gradedwrong'];
        $cond = new condition($structure);
        $structure->type = 'quizquestion';
        $this->assertEquals($structure, $cond->save());
    }

    public function test_usage() {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enableavailability = true;

        // Make a test course and user.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $context = \context_course::instance($course->id);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $info = new \core_availability\mock_info($course, $user->id);

        // Create a quiz with a question.
        $quiz = $generator->create_module('quiz', ['course' => $course->id]);
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['contextid' => $context->id]);
        $question = $questiongenerator->create_question('numerical', null,
                ['category' => $category->id]);
        quiz_add_quiz_question($question->id, $quiz);
        quiz_update_sumgrades($quiz);

        // Do test (user has not attempted the quiz yet).
        $cond = new condition((object) [
                'quizid' => (int) $quiz->id, 'questionid' => (int) $question->id,
                'requiredstate' => (string) \question_state::$gradedwrong]);

        // Check if available (when not available).
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $this->assertContains('The question <b>What is pi to two d.p.?</b> in', $information);
        $this->assertContains('>Quiz 1</a></b> is <b>Incorrect</b>', $information);

        // Check with not.
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $this->assertContains('The question <b>What is pi to two d.p.?</b> in', $information);
        $this->assertContains('>Quiz 1</a></b> is not <b>Incorrect</b>', $information);

        // User attempts the quiz and get the question right.
        $timenow = time();
        $quizobj = \quiz::create($quiz->id, $user->id);
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $attempt = quiz_create_attempt($quizobj, 1, null, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $tosubmit = array(1 => array('answer' => '3.14'));
        $attemptobj->process_submitted_actions(time(), false, $tosubmit);
        $attemptobj->process_finish(time(), false);

        // Recheck.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $this->assertContains('The question <b>What is pi to two d.p.?</b> in', $information);
        $this->assertContains('>Quiz 1</a></b> is <b>Incorrect</b>', $information);

        // User attempts the quiz and get the question wrong.
        $timenow = time();
        $quizobj = \quiz::create($quiz->id, $user->id);
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $attempt = quiz_create_attempt($quizobj, 2, null, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $tosubmit = array(1 => array('answer' => '42'));
        $attemptobj->process_submitted_actions(time(), false, $tosubmit);
        $attemptobj->process_finish(time(), false);

        // Recheck.
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $this->assertContains('The question <b>What is pi to two d.p.?</b> in', $information);
        $this->assertContains('>Quiz 1</a></b> is <b>Incorrect</b>', $information);
    }
}
