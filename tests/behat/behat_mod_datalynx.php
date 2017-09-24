<?php
// This file is part of mod_datalynx for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Steps definitions related with the datalynx activity.
 *
 * @package mod_datalynx
 * @category test
 * @copyright 2014 Ivan Šakić
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including
// /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_files.php');
require_once(__DIR__ . '/../../mod_class.php');

use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Datalynx-related steps definitions.
 *
 * @package mod_datalynx
 * @category test
 * @copyright 2014 Ivan Šakić
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_datalynx extends behat_files {


    /**
     * Sets up fields for the given datalynx instance.
     * Optional, but must be used after instance declaration.
     *
     * @Given /^"(?P<activityname_string>(?:[^"]|\\")*)" has following fields:$/
     *
     * @param string $activityname
     * @param TableNode $table
     */
    public function has_following_fields($activityname, TableNode $table) {
        $fields = $table->getHash();

        $instance = $this->get_instance_by_name($activityname);
        foreach ($fields as $field) {
            $field['dataid'] = $instance->id;
            $this->create_field($field);
        }
    }

    /**
     * Sets up filters for the given datalynx instance.
     * Optional, but must be used after field setup.
     *
     * @Given /^"(?P<activityname_string>(?:[^"]|\\")*)" has following filters:$/
     *
     * @param string $activityname
     * @param TableNode $table
     */
    public function has_following_filters($activityname, TableNode $table) {
        $filters = $table->getHash();

        $instance = $this->get_instance_by_name($activityname);
        foreach ($filters as $filter) {
            $filter['dataid'] = $instance->id;
            $this->create_filter($filter);
        }
    }

    /**
     * Sets up filters for the given datalynx instance.
     * Optional, but must be called after field and filter setup.
     *
     * @Given /^"(?P<activityname_string>(?:[^"]|\\")*)" has following views:$/
     *
     * @param string $activityname
     * @param TableNode $table
     */
    public function has_following_views($activityname, TableNode $table) {
        $views = $table->getHash();

        $instance = $this->get_instance_by_name($activityname);
        $names = array();
        $newviews = array();
        foreach ($views as $view) {
            $view['dataid'] = $instance->id;
            $statuses = explode(',', isset($view['status']) ? $view['status'] : '');
            $options = array();
            foreach ($statuses as $status) {
                $options[trim($status)] = true;
            }

            $view['id'] = $this->create_view($view, $options);
            $names[$view['name']] = $view['id'];
            $newviews[] = $view;
        }

        $this->map_view_names_for_redirect($newviews, $names);
    }

    private function get_instance_by_name($name) {
        global $DB;
        return $DB->get_record('datalynx', array('name' => $name));
    }

    private function create_field($record = null) {
        global $DB;

        $record = (object) (array) $record;

        $defaults = array('description' => '', 'visible' => 2, 'edits' => -1, 'label' => null,
                'param1' => null,
                'param2' => ($record->type == "teammemberselect") ? "[1,2,3,4,5,6,7,8]" : null,
                'param3' => null, 'param4' => ($record->type == "teammemberselect") ? "4" : null,
                'param5' => null, 'param6' => null, 'param7' => null, 'param8' => null, 'param9' => null,
                'param10' => null);

        foreach ($defaults as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        if (isset($record->param1) && $record->type == "select") {
            $record->param1 = preg_replace('/,[ ]?/', "\n", $record->param1);
        }

        if (!isset($record->param2) && ($record->type == "file" || $record->type == "picture")) {
            $record->param2 = -1;
        }

        if (!isset($record->param3) && ($record->type == "file" || $record->type == "picture")) {
            $record->param3 = '*';
        }

        $DB->insert_record('datalynx_fields', $record);
    }

    private function create_view($record = null, array $options = null) {
        global $DB;

        $record = (object) (array) $record;
        $options = (array) $options;

        $defaults = array('description' => '', 'visible' => 7, 'perpage' => '', 'groupby' => null,
                'filter' => 0, 'patterns' => null, 'section' => null, 'sectionpos' => 0,
                'param1' => null, 'param2' => null, 'param3' => ($record->type == "tabular") ? 1 : null,
                'param4' => null, 'param5' => null, 'param6' => null, 'param7' => null, 'param8' => null,
                'param9' => null, 'param10' => null);

        foreach ($defaults as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        if ($record->filter) {
            $record->filter = $DB->get_field('datalynx_filters', 'id', array('name' => $record->filter));
        }

        $id = $DB->insert_record('datalynx_views', $record);

        $datalynx = new datalynx($record->dataid);
        $datalynx->process_views('reset', $id, true);

        if ($record->param2) {
            $DB->set_field('datalynx_views', 'param2', $record->param2, array('id' => $id));
        }

        if ($record->section) {
            $DB->set_field('datalynx_views', 'section', $record->section, array('id' => $id));
        }

        if (isset($options['default'])) {
            $DB->set_field('datalynx', 'defaultview', $id, array('id' => $record->dataid));
        }
        if (isset($options['edit'])) {
            $DB->set_field('datalynx', 'singleedit', $id, array('id' => $record->dataid));
        }
        if (isset($options['more'])) {
            $DB->set_field('datalynx', 'singleview', $id, array('id' => $record->dataid));
        }

        return $id;
    }

    private function create_filter($record = null) {
        global $DB;

        $record = (object) (array) $record;

        $defaults = array('description' => '', 'visible' => 1, 'perpage' => 0, 'selection' => 0,
                'groupby' => null, 'search' => null, 'customsort' => null, 'customsearch' => null);

        foreach ($defaults as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        $DB->insert_record('datalynx_filters', $record);
    }

    private function map_view_names_for_redirect($views, $names) {
        global $DB;
        foreach ($views as $view) {
            $DB->set_field('datalynx_views', 'param10', $names[$view['redirect']],
                    array('id' => $view['id']));
        }
    }

    /**
     * Selects entry for editing
     *
     * @Given /^I edit "(?P<entrynumber_string>(?:[^"]|\\")*)" entry$/
     *
     * @param string $entrynumber
     */
    public function i_edit_entry($entrynumber) {
        switch ($this->escape($entrynumber)) {
            case "first":
            case "1st":
                $number = 1;
                break;
            case "second":
            case "2nd":
                $number = 2;
                break;
            case "third":
            case "3rd":
                $number = 3;
                break;
            case "fourth":
            case "4th":
                $number = 4;
                break;
            case "fifth":
            case "5th":
                $number = 5;
                break;
            default:
                $number = $this->escape($entrynumber);
                break;
        }

        $session = $this->getSession(); // Get the mink session.
        $element = $session->getPage()->find('xpath', '//div[@class="entry"][' . $number . ']//i[@title="Edit"]//ancestor::a');
        $element->click();
    }

    /**
     * Select an option from a select.
     *
     * @Given /^I select option "(?P<option_string>(?:[^"]|\\")*)" from the "(?P<select_string>(?:[^"]|\\")*)" select$/

     * @param string $option
     * @param string $select
     */
    public function i_select_option_from_the_select($option, $select) {

        $session = $this->getSession(); // Get the mink session.
        $element = $session->getPage()->find('xpath', './/div[@data-field-name="' . $select . '"]//select[1]');
        $element->selectOption($option);
    }

    /**
     * Select an radio button option.
     *
     * @Given /^I click option "(?P<option_string>(?:[^"]|\\")*)" from a radio$/

     * @param string $option
     */
    public function i_click_option_from_a_radio($option) {

        $session = $this->getSession(); // Get the mink session.
        $element = $session->getPage()->find('xpath',
                '//input[@type="radio"][../following-sibling::*[position()=1][contains(., "' . $option . '")]]');
        $element->click();
    }

    /**
     * Select an radio button option.
     *
     * @Given /^I click option "(?P<option_string>(?:[^"]|\\")*)" from a checkbox$/

     * @param string $option
     */
    public function i_click_option_from_a_checkbox($option) {

        $session = $this->getSession(); // Get the mink session.
        $element = $session->getPage()->find('xpath',
                '//input[@type="checkbox"]/following::*[contains(text()[normalize-space()], "' . $option . '")]');
        $element->click();
    }
}
