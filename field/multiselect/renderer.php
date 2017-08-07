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
 * @subpackage multiselect
 * @copyright 2014 Ivan Šakić
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once(dirname(__FILE__) . "/../renderer.php");

/**
 */
class datalynxfield_multiselect_renderer extends datalynxfield_renderer {

    /**
     *
     * @var datalynxfield_multiselect
     */
    protected $_field = null;

    /**
     *
     * {@inheritDoc}
     * @see datalynxfield_renderer::render_edit_mode()
     */
    public function render_edit_mode(MoodleQuickForm &$mform, stdClass $entry, array $options) {
        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_$entryid";
        $required = !empty($options['required']);
        $autocomplete = $field->get('param6');

        $content = !empty($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;

        $selected = array();
        if ($entryid > 0 and $content) {
            $contentprepare = str_replace("#", "", $content);
            $selectedraw = explode(',', $contentprepare);

            foreach ($selectedraw as $item) {
                $selected[$item] = $item;
            }
        }

        // Check for default values.
        if (!$selected and $field->get('param2')) {
            $selected = $field->default_values();
        }

        // Render as autocomplete field (param6 not empty) or select field.
        if ($autocomplete) {
            $menuoptions = $field->options_menu(false, true);
            $select = &$mform->addElement('autocomplete', $fieldname, null, $menuoptions);
        } else {
            $menuoptions = $field->options_menu();
            $select = &$mform->addElement('select', $fieldname, null, $menuoptions);
        }
        $select->setMultiple(true);
        $select->setSelected($selected);
        $mform->setType($fieldname, PARAM_INT);

        if ($required) {
            $mform->addRule($fieldname, null, 'required', null, 'client');
        }
    }

    /**
     * transform the raw database value into HTML suitable for displaying on the entry page
     * (non-PHPdoc)
     *
     * @see datalynxfield_renderer::render_display_mode()
     * @return string HTML
     */
    public function render_display_mode(stdClass $entry, array $params) {
        $field = $this->_field;
        $fieldid = $field->id();

        if (isset($entry->{"c{$fieldid}_content"})) {
            $content = $entry->{"c{$fieldid}_content"};
            $contentprepare = str_replace("#", "", $content);

            $options = $field->options_menu();

            $contents = explode(',', $contentprepare);

            $str = array();
            foreach ($options as $key => $option) {
                $selected = (int) in_array($key, $contents);
                if ($selected) {
                    $str[] = $option;
                }
            }
            $separator = $field->separators[(int) $field->get('param3')]['chr'];
            if ($separator == '</li><li>' && count($str) > 0) {
                $str = '<ul><li>' . implode($separator, $str) . '</li></ul>';
            } else {
                $str = implode($separator, $str);
            }
        } else {
            $str = '';
        }

        return $str;
    }

    /**
     *
     * {@inheritDoc}
     * @see datalynxfield_renderer::render_search_mode()
     */
    public function render_search_mode(MoodleQuickForm &$mform, $i = 0, $value = '') {
        global $CFG;
        HTML_QuickForm::registerElementType('checkboxgroup',
                "$CFG->dirroot/mod/datalynx/checkboxgroup/checkboxgroup.php",
                'HTML_QuickForm_checkboxgroup');

        $field = $this->_field;
        $fieldid = $field->id();

        $selected = $value;

        $options = $field->options_menu();

        $fieldname = "f_{$i}_$fieldid";
        $select = &$mform->createElement('checkboxgroup', $fieldname, null, $options, '');
        $select->setValue($selected);

        $mform->disabledIf($fieldname, "searchoperator$i", 'eq', '');

        return array(array($select), null);
    }

    /**
     *
     * {@inheritDoc}
     * @see datalynxfield_renderer::validate()
     */
    public function validate($entryid, $tags, $formdata) {
        $fieldid = $this->_field->id();
        $formfieldname = "field_{$fieldid}_{$entryid}";

        $errors = array();
        foreach ($tags as $tag) {
            list(, $behavior, ) = $this->process_tag($tag);
            // Variable $behavior datalynx_field_behavior.

            if ($behavior->is_required()) {
                if (empty($formdata->$formfieldname)) {
                    $errors[$formfieldname] = get_string('fieldrequired', 'datalynx');
                } else {
                    $empty = true;
                    foreach ($formdata->$formfieldname as $value) {
                        $empty = $empty && empty($value);
                    }
                    if ($empty) {
                        $errors[$formfieldname] = get_string('fieldrequired', 'datalynx');
                    }
                }
            }
        }

        return $errors;
    }
}
