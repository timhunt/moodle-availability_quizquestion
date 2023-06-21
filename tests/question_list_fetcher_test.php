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

/**
 * Tests for the code that gets a list of questions in a quiz.
 *
 * @package availability_quizquestion
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Martin Hanusch, Thomas Lattner, Alex Keiller
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \availability_quizquestion\question_list_fetcher
 */
class question_list_fetcher_test extends \advanced_testcase {

    public function test_list_questions_in_quiz() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Make a test course.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        // Create a quiz and a category to hold the questions.
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $coursecontext = \context_course::instance($course->id);
        $quiz = $generator->create_module('quiz', ['course' => $course->id]);
        $quizcontext = \context_module::instance($quiz->cmid);
        $category = $questiongenerator->create_question_category(
                ['contextid' => $coursecontext->id, 'name' => 'Test questions']);

        // Add a Description (which should not appear in the list).
        $description = $questiongenerator->create_question('description', null,
                ['category' => $category->id]);
        quiz_add_quiz_question($description->id, $quiz);

        // Add a true-false question.
        $tf = $questiongenerator->create_question('truefalse', null,
                ['category' => $category->id, 'name' => 'True false question']);
        quiz_add_quiz_question($tf->id, $quiz);

        // Add a random question (does not matter that there are not enough questions in the bank).
        quiz_add_random_questions($quiz, 0, $category->id, 1, false);

        // Add an essay question.
        $essay = $questiongenerator->create_question('essay', null,
                ['category' => $category->id, 'name' => 'Write an essay']);
        quiz_add_quiz_question($essay->id, $quiz);

        // Re-load questions to get entry ids.
        $tf = \question_bank::load_question_data($tf->id);
        $essay = \question_bank::load_question_data($essay->id);

        // Verify that the right list is returned.
        $this->assertEquals(
                [
                    (object) ['id' => $tf->questionbankentryid, 'name' => 'Q1) True false question'],
                    (object) ['id' => $essay->questionbankentryid, 'name' => 'Q3) Write an essay'],
                ],
                question_list_fetcher::list_questions_in_quiz($quiz->id, $quizcontext));
    }
}
