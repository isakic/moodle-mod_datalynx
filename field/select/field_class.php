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
 *
 * @package datalynxfield
 * @subpackage select
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot . "/mod/datalynx/field/field_class.php");

class datalynxfield_select extends datalynxfield_option_single {

    public $type = 'select';

    /**
     *
     * {@inheritDoc}
     * @see datalynxfield_base::get_sql_compare_text()
     */
    protected function get_sql_compare_text($column = 'content') {
        global $DB;
        return $DB->sql_compare_text("c{$this->field->id}.$column", 255);
    }

    /**
     *
     * {@inheritDoc}
     * @see datalynxfield_base::get_search_value()
     */
    public function get_search_value($value) {
        $options = $this->options_menu();
        if ($key = array_search($value, $options)) {
            return $key;
        } else {
            return '';
        }
    }

    /**
     *
     * {@inheritDoc}
     * @see datalynxfield_base::prepare_import_content()
     */
    public function prepare_import_content(&$data, $importsettings, $csvrecord = null, $entryid = null) {
        // Import only from csv.
        if ($csvrecord) {
            $fieldid = $this->field->id;
            $fieldname = $this->name();
            $csvname = $importsettings[$fieldname]['name'];
            $allownew = !empty($importsettings[$fieldname]['allownew']) ? true : false;
            $label = !empty($csvrecord[$csvname]) ? $csvrecord[$csvname] : null;

            if ($label) {
                $options = $this->options_menu();
                if ($optionkey = array_search($label, $options)) {
                    $data->{"field_{$fieldid}_{$entryid}_selected"} = $optionkey;
                } else {
                    if ($allownew) {
                        $data->{"field_{$fieldid}_{$entryid}_newvalue"} = $label;
                    }
                }
            }
        }
        return true;
    }

}
