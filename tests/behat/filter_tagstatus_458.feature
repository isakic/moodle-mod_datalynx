@mod @mod_datalynx @dev @_file_upload @wip3 @mink:selenium2
Feature:A Status Tag can be inserted into a view and should not throw any exceptions or failures during saving

  Background:
    Given the following "courses" exist:
      | fullname  | shortname   | category  | groupmode   |
      | Course 1  | C1          | 0         | 1           |
    And the following "users" exist:
      | username  | firstname   | lastname  | email                   |
      | teacher1  | Teacher     | 1         | teacher1@mailinator.com |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C1      | editingteacher  |
    And the following "activities" exist:
      | activity | course | idnumber | name                   | approval |
      | datalynx | C1     | 12345    | Datalynx Test Instance | 1        |
  And "Datalynx Test Instance" has following views:
      | type    | name                   | status       | redirect           | filter        | param2                                                                                                |
      | grid    | Default view           | default      | Default view       |               | <div ><table><tbody><tr><td>Hi.</td></tr><tr><td>##edit##  ##delete##</td></tr></tbody></table></div> |
  And "Datalynx Test Instance" has following entries:
      | author   | approved | status |
      | teacher1 | 1        | 1      |
      
Scenario: Login and insert a status tag into view edit template
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Datalynx Test Instance"
    And I follow "Manage"
    And I follow "Views"
    And I click "Edit" button of "Default view" item
    And I follow "Entry template"
    And I click inside "id_eparam2_editoreditable"
    And I set the field "eparam2_editor_field_tag_menu" to "##status##"
    And I press "Save changes"
    Then I follow "Browse"
    And I should see "DraftDraft"
    But I should not see "Undefined index"