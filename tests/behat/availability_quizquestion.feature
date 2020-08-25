@availability @availability_quizquestion
Feature: Restriction by single quiz question
  In order provide specific resources based on detailed quiz performance
  As a teacher
  I need to set conditions for student access based on the result of individual quiz questions

  Background:
    Given the following "courses" exist:
      | fullname     | shortname | format | enablecompletion |
      | Study skills | C1        | topics | 1                |
    And the following "users" exist:
      | username |
      | teacher  |
      | student  |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name    | questiontext          |
      | Test questions   | truefalse   | Reading | I am good at reading? |
      | Test questions   | truefalse   | Writing | I am good at writing? |
    And the following "activities" exist:
      | activity   | name            | course | idnumber |
      | quiz       | Diagnostic quiz | C1     | diag     |
    And quiz "Diagnostic quiz" contains the following questions:
      | question | page | maxmark |
      | Reading  | 1    | 1       |
      | Writing  | 1    | 1       |

  @javascript
  Scenario: Test basic use
    # Set up as teacher.
    Given I am on the "C1" "Course" page logged in as "teacher"
    And I turn editing mode on
    When I add a "Page" to section "1"
    And I set the following fields to these values:
      | Name         | Help with reading |
      | Page content | Open your eyes!   |
    And I click on "Add restriction..." "button"
    And I click on "Quiz question" "button" in the "Add restriction..." "dialogue"
    And I set the field "Quiz question" to "Diagnostic quiz"
    And I set the field "Which question in the selected quiz" to "Q1) Reading"
    And I set the field "Required state" to "Incorrect"
    And I click on "Displayed greyed-out if user does not meet this condition" "link"
    And I click on "Save and return to course" "button"

    # Try it as student - no access yet.
    And I log out
    And I am on the "C1" "Course" page logged in as "student"
    Then I should not see "Help with reading"

    # Now attempt the quiz.
    And I follow "Diagnostic quiz"
    And I press "Attempt quiz now"
    And I click on "False" "radio" in the "I am good at reading?" "question"
    And I click on "False" "radio" in the "I am good at writing?" "question"
    And I follow "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    And I am on the "C1" "Course" page
    And I follow "Help with reading"
    And I should see "Open your eyes!"

  @javascript
  Scenario: Display on course page when access is blocked
    # Set up as teacher.
    Given I am on the "C1" "Course" page logged in as "teacher"
    And I turn editing mode on
    When I add a "Page" to section "1"
    And I set the following fields to these values:
      | Name         | Help with reading |
      | Page content | Open your eyes!   |
    And I click on "Add restriction..." "button"
    And I click on "Quiz question" "button" in the "Add restriction..." "dialogue"
    And I set the field "Quiz question" to "Diagnostic quiz"
    And I set the field "Which question in the selected quiz" to "Q2) Writing"
    And I set the field "Required state" to "Incorrect"
    And I click on "Save and return to course" "button"
    And I log out
    And I am on the "C1" "Course" page logged in as "student"
    Then I should see "Help with reading"
    And I should see "Not available unless: The question I am good at writing? in Diagnostic quiz is Incorrect"
