@availability @availability_quizquestion
Feature: Restriction by single quiz question only shows when relevant
  In order efficiently set up my courses
  As a teacher
  I need the single quiz question to not appear if there are no quizzes in the course

  Background:
    Given the following "courses" exist:
      | fullname     | shortname | format | enablecompletion |
      | Study skills | C1        | topics | 1                |
    And the following "users" exist:
      | username |
      | teacher  |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |

  @javascript
  Scenario: If there are no quizzes in the course, this condition should not appear.
    Given I am on the "C1" "Course" page logged in as "teacher"
    And I turn editing mode on
    When I add a "Page" to section "1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Quiz question" "button" should not exist in the "Add restriction..." "dialogue"
