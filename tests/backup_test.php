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
 * Restriction by single quiz question unit tests for backup and restore support.
 *
 * @package availability_quizquestion
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Martin Hanusch, Thomas Lattner, Alex Keiller
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_quizquestion;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use backup;
use core_availability\info_module;

/**
 * Restriction by single quiz question unit tests for backup and restore support.
 */
class backup_testcase extends \advanced_testcase {

    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enableavailability = true;
    }

    public function test_backup_and_restore_a_course() {

        // Make a test course.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        // Create a quiz with a question.
        [$quiz, $question] = $this->create_a_quiz_with_one_question($course);

        // Create a page which depends on that question.
        $page = $generator->create_module('page', ['course' => $course->id]);
        $this->make_activity_depend_on_quiz_question($course, $page->cmid, $quiz, $question);

        // Backup and restore the course.
        $backupid = $this->backup_course($course);
        $newcourseid = $this->restore_course($backupid);

        // Verify the condition on the restored page.
        $this->assert_page_depends_on_quiz_in_course($newcourseid);
    }

    public function test_duplicate_activity_in_one_course() {

        // Make a test course.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        // Create a quiz with a question.
        [$quiz, $question] = $this->create_a_quiz_with_one_question($course);

        // Create a page which depends on that question.
        $page = $generator->create_module('page', ['course' => $course->id]);
        $this->make_activity_depend_on_quiz_question($course, $page->cmid, $quiz, $question);

        // Duplicate the page.
        $newpagecm = duplicate_module($course, get_fast_modinfo($course)->get_cm($page->cmid));

        // Verify the condition on the duplicated page.
        $this->assertEquals('&(+{quizquestion: quiz:#' . $quiz->id .
                ', question:#' . $question->id . ', gradedwrong})',
                (string) (new info_module($newpagecm))->get_availability_tree());

    }

    public function test_backup_and_restore_with_reverse_order() {
        // Verifies that the recoding of ids works even if the quiz comes after the page.

        // Make a test course.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        // Create a page first.
        $page = $generator->create_module('page', ['course' => $course->id]);

        // Create a quiz with a question.
        [$quiz, $question] = $this->create_a_quiz_with_one_question($course);

        // Make the page depend on that question.
        $this->make_activity_depend_on_quiz_question($course, $page->cmid, $quiz, $question);

        // Backup and restore the course.
        $backupid = $this->backup_course($course);
        $newcourseid = $this->restore_course($backupid);

        // Verify the condition on the restored page.
        $this->assert_page_depends_on_quiz_in_course($newcourseid);
    }

    /**
     * In the given course, create a quiz with one question.
     *
     * The question is created in a question category named 'Test questions'.
     *
     * @param \stdClass $course
     * @return array
     */
    protected function create_a_quiz_with_one_question(\stdClass $course): array {
        $generator = $this->getDataGenerator();
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $context = \context_course::instance($course->id);
        $quiz = $generator->create_module('quiz', ['course' => $course->id]);
        $category = $questiongenerator->create_question_category(
                ['contextid' => $context->id, 'name' => 'Test questions']);
        $question = $questiongenerator->create_question('multichoice', null,
                ['category' => $category->id]);
        quiz_add_quiz_question($question->id, $quiz);

        return [$quiz, $question];
    }

    /**
     * In a course, make an activity depend on getting this question wrong in the given quiz.
     *
     * @param \stdClass $course
     * @param int $cmid
     * @param \stdClass $quiz
     * @param \stdClass $question
     */
    protected function make_activity_depend_on_quiz_question(
            \stdClass $course, int $cmid, \stdClass $quiz, \stdClass $question): void {
        global $DB;

        $restriction = \core_availability\tree::get_root_json(
                [\availability_quizquestion\condition::get_json(
                        $quiz->id, $question->id, \question_state::$gradedwrong)]);
        $DB->set_field('course_modules', 'availability',
                json_encode($restriction), ['id' => $cmid]);
        rebuild_course_cache($course->id, true);
    }

    /**
     * Assert that, in a course, the first page depends on the only question in the first quiz.
     *
     * @param int $courseid course id.
     */
    protected function assert_page_depends_on_quiz_in_course(int $courseid): void {
        $newpagecm = $this->find_activity($courseid, 'page');
        $newquizcm = $this->find_activity($courseid, 'quiz');
        $newquestion = $this->find_test_question_from_course($courseid);
        $this->assertEquals('&(+{quizquestion: quiz:#' . $newquizcm->instance .
                ', question:#' . $newquestion->id . ', gradedwrong})',
                (string) (new info_module($newpagecm))->get_availability_tree());
    }

    /**
     * Get the first activity of a given type from a course.
     *
     * @param int $courseid id of the course to look in.
     * @param string $modname type of activity, e.g. 'quiz'.
     * @return \cm_info row from the question DB table.
     */
    protected function find_activity(int $courseid, string $modname): \cm_info {
        $newmodinfo = get_fast_modinfo($courseid);
        $cms = $newmodinfo->instances[$modname];
        return reset($cms);
    }

    /**
     * Get the only question in a category called 'Test questions' in a given course.
     *
     * @param int $courseid id of the course to look in.
     * @return \stdClass row from the question DB table.
     */
    protected function find_test_question_from_course(int $courseid): \stdClass {
        global $DB;
        $coursecontext = \context_course::instance($courseid);
        $questioncategory = $DB->get_record('question_categories',
                ['contextid' => $coursecontext->id, 'name' => 'Test questions']);
        return $DB->get_record('question',
                ['category' => $questioncategory->id]);
    }

    /**
     * Makes a backup of the course.
     *
     * @param \stdClass $course The course object.
     * @return string Unique identifier for this backup.
     */
    protected function backup_course(\stdClass $course): string {
        global $CFG, $USER;

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just
        // create the directory and not zip it.
        $bc = new \backup_controller(backup::TYPE_1COURSE, $course->id,
                backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_IMPORT,
                $USER->id);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        return $backupid;
    }

    /**
     * Restores a backup that has been made earlier.
     *
     * @param string $backupid The unique identifier of the backup.
     * @return int The new course id.
     */
    protected function restore_course($backupid) {
        global $CFG, $DB, $USER;

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        $defaultcategoryid = $DB->get_field('course_categories', 'id',
                ['parent' => 0], IGNORE_MULTIPLE);

        // Do restore to new course with default settings.
        $newcourseid = \restore_dbops::create_new_course('Restored course', 'R1', $defaultcategoryid);
        $rc = new \restore_controller($backupid, $newcourseid,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id,
                backup::TARGET_NEW_COURSE);

        $precheck = $rc->execute_precheck();
        $this->assertTrue($precheck);

        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }
}
