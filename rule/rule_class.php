<?php
// This file is part of Moodle - http://moodle.org/.
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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package dataform_rule
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/mod_class.php");

/**
 * Base class for Dataform Rule Types
 */
abstract class dataform_rule_base {

    public $type = 'unknown';  // Subclasses must override the type with their name

    public $df = null;       // The dataform object that this rule belongs to
    public $rule = null;      // The rule object itself, if we know it

    /**
     * Class constructor
     *
     * @param var $df       dataform id or class object
     * @param var $rule    rule id or DB record
     */
    public function __construct($df = 0, $rule = 0) {

        if (empty($df)) {
            throw new coding_exception('Dataform id or object must be passed to view constructor.');
        } else if ($df instanceof dataform) {
            $this->df = $df;
        } else {    // dataform id/object
            $this->df = new dataform($df);
        }

        if (!empty($rule)) {
            // $rule is the rule record
            if (is_object($rule)) {
                $this->rule = $rule;  // Programmer knows what they are doing, we hope

            // $rule is a rule id
            } else if ($ruleobj = $this->df->get_rule_from_id($rule)) {
                $this->rule = $ruleobj->rule;
            } else {
                throw new moodle_exception('invalidrule', 'dataform', null, null, $rule);
            }
        }

        if (empty($this->rule)) {         // We need to define some default values
            $this->set_rule();
        }
    }

    /**
     * Applies the rule according to the data provided
     * @param stdClass $data
     */
    public abstract function trigger(stdClass $data);

    /**
     * Checks if the rule triggers on the given event
     * @param string $eventname
     * @return bool
     */
    public abstract function is_triggered_by($eventname);

    /**
     * Sets up a rule object
     */
    public function set_rule($forminput = null) {
        $this->rule = new stdClass();
        $this->rule->id = !empty($forminput->id) ? $forminput->id : 0;
        $this->rule->type   = $this->type;
        $this->rule->dataid = $this->df->id();
        $this->rule->name = !empty($forminput->name) ? trim($forminput->name) : '';
        $this->rule->description = !empty($forminput->description) ? trim($forminput->description) : '';
        $this->rule->enabled = isset($forminput->enabled) ? $forminput->enabled : 1;
        for ($i = 1; $i <= 10; $i++) {
            $this->rule->{"param$i"} = !empty($forminput->{"param$i"}) ? trim($forminput->{"param$i"}) : null;
        }
    }

    /**
     * Insert a new rule in the database
     */
    public function insert_rule($fromform = null) {
        global $DB, $OUTPUT;

        if (!empty($fromform)) {
            $this->set_rule($fromform);
        }

        if (!$this->rule->id = $DB->insert_record('dataform_rules', $this->rule)){
            echo $OUTPUT->notification('Insertion of new rule failed!');
            return false;
        } else {
            return $this->rule->id;
        }
    }

    /**
     * Update a rule in the database
     */
    public function update_rule($fromform = null) {
        global $DB, $OUTPUT;
        if (!empty($fromform)) {
            $this->set_rule($fromform);
        }

        if (!$DB->update_record('dataform_rules', $this->rule)) {
            echo $OUTPUT->notification('updating of rule failed!');
            return false;
        }
        return true;
    }

    /**
     * Delete a rule completely
     */
    public function delete_rule() {
        global $DB;

        if (!empty($this->rule->id)) {
            $DB->delete_records('dataform_rules', array('id' => $this->rule->id));
        }
        return true;
    }

    /**
     * Returns the rule id
     */
    public function get_id() {
        return $this->rule->id;
    }

    /**
     *
     */
    public function is_enabled() {
        return $this->rule->enabled;
    }

    /**
     * Returns the rule type
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Returns the name of the rule
     */
    public function get_name() {
        return $this->rule->name;
    }

    /**
     * Returns the type name of the rule
     */
    public function typename() {
        return get_string('pluginname', "dataformrule_{$this->type}");
    }

    /**
     *
     */
    public function df() {
        return $this->df;
    }

    /**
     *
     */
    public function get_form() {
        global $CFG;

        if (file_exists($CFG->dirroot. '/mod/dataform/rule/'. $this->type. '/rule_form.php')) {
            require_once($CFG->dirroot. '/mod/dataform/rule/'. $this->type. '/rule_form.php');
            $formclass = 'dataform_rule_'. $this->type. '_form';
        } else {
            require_once($CFG->dirroot. '/mod/dataform/rule/rule_form.php');
            $formclass = 'dataform_rule_form';
        }
        $actionurl = new moodle_url(
            '/mod/dataform/rule/rule_edit.php',
            array('d' => $this->df->id(), 'rid' => $this->get_id(), 'type' => $this->type)
        );
        return new $formclass($this, $actionurl);
    }

    /**
     *
     */
    public function to_form() {
        return $this->rule;
    }

    /**
     *
     */
    public function get_select_sql() {
        if ($this->rule->id > 0) {
            $id = " c{$this->rule->id}.id AS c{$this->rule->id}_id ";
            $content = $this->get_sql_compare_text(). " AS c{$this->rule->id}_content";
            return " $id , $content ";
        } else {
            return '';
        }
    }

    /**
     *
     */
    public function get_sort_from_sql($paramname = 'sortie', $paramcount = '') {
        $ruleid = $this->rule->id;
        if ($ruleid > 0) {
            $sql = " LEFT JOIN {dataform_contents} c$ruleid ON (c$ruleid.entryid = e.id AND c$ruleid.ruleid = :$paramname$paramcount) ";
            return array($sql, $ruleid);
        } else {
            return null;
        }
    }

    /**
     *
     */
    public function get_sort_sql() {
        return '';
    }
}
