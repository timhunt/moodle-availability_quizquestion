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
 * @copyright 2020 Tim Hunt, Shamim Rezaie, Benjamin Schröder, Benjamin Schröder, Thomas Lattner, Alex Keiller
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_quizquestion;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

use backup;
use core_availability\info_module;

/**
 * Restriction by single quiz question unit tests for backup and restore support.
 */
class backup_testcase extends \advanced_testcase {

    public function test_backup_and_restore_a_course() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enableavailability = true;

        // Make a test course.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $context = \context_course::instance($course->id);

        // Create a quiz with a question.
        $quiz = $generator->create_module('quiz', ['course' => $course->id]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['contextid' => $context->id, 'name' => 'Test questions']);
        $question = $questiongenerator->create_question('multichoice', null,
                ['category' => $category->id]);
        quiz_add_quiz_question($question->id, $quiz);

        // Create a page which depends on that question.
        $page = $generator->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $pagecm = $modinfo->instances['page'][$page->id];
        $restriction = \core_availability\tree::get_root_json(
                [\availability_quizquestion\condition::get_json(
                        $quiz->id, $question->id, \question_state::$gradedwrong)]);
        $DB->set_field('course_modules', 'availability',
                json_encode($restriction), ['id' => $pagecm->id]);
        rebuild_course_cache($course->id, true);

        // Backup the course.
        $backupid = $this->backup_course($course);

        // Restore it.
        $newcourseid = $this->restore_course($backupid);

        // Verify the condition on the restored page.
        $newmodinfo = get_fast_modinfo($newcourseid);
        $newpagecms = $newmodinfo->instances['page'];
        $newpagecm = reset($newpagecms);
        $newquizcms = $newmodinfo->instances['quiz'];
        $newquizcm = reset($newquizcms);

        $newcoursecontext = \context_course::instance($newcourseid);
        $newquestioncategory = $DB->get_record('question_categories',
                ['contextid' => $newcoursecontext->id, 'name' => 'Test questions']);
        $newquestion = $DB->get_record('question',
                ['category' => $newquestioncategory->id]);

        $info = new info_module($newpagecm);
        $this->assertEquals('&(+{quizquestion: quiz:#' . $newquizcm->instance .
                ', question:#' . $newquestion->id . ', gradedwrong})',
                (string) $info->get_availability_tree());
    }

    public function test_duplicate_activity_in_one_course() {
        $this->markTestIncomplete();
    }


    public function test_backup_and_restore_with_reverse_order() {
        // Verifies that the recoding of ids works even if the quiz comes after the page.
        $this->markTestIncomplete();
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
