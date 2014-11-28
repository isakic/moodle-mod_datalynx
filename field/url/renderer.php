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
 * @package datalynxfield
 * @subpackage url
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

require_once("$CFG->dirroot/mod/datalynx/field/renderer.php");

/**
 *
 */
class datalynxfield_url_renderer extends datalynxfield_renderer {

    /**
     *
     */
    public function render_edit_mode(MoodleQuickForm &$mform, stdClass $entry, array $options) {
        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entryid}";

        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        $url = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $alt = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;
        
        $url = empty($url) ? 'http://' : $url;
        $usepicker = empty($field->field->param1) ? false : true;
        $options = array(
            'title' => s($field->field->description),
            'size' => 64
        );        
        $mform->addElement('url', "{$fieldname}_url", null, $options, array('usefilepicker' => $usepicker));
        $mform->setType("{$fieldname}_url", PARAM_URL);
        $mform->setDefault("{$fieldname}_url", s($url));

        // add alt name if not forcing name
        if (empty($field->field->param2)) {
            $mform->addElement('text', "{$fieldname}_alt", get_string('alttext','datalynxfield_url'));
            $mform->setType("{$fieldname}_alt", PARAM_TEXT);
            $mform->setDefault("{$fieldname}_alt", s($alt));
        }
    }

    /**
     *
     */
    public function render_display_mode(stdClass $entry, array $params) {
        global $CFG;

        $field = $this->_field;
        $fieldid = $field->id();
        $types = array_intersect(['link', 'image', 'imageflex', 'media'], array_keys($params));
        $type = isset($types[0]) ? $types[0] : '';
        $attributes = array('class' => $field->class,
                            'target' => $field->target);

        if (isset($entry->{"c{$fieldid}_content"})) {
            $url = $entry->{"c{$fieldid}_content"};
            if (empty($url) or ($url == 'http://')) {
                return '';
            }
            
            // simple url text
            if (empty($type)) {
                return $url;
            }
            
            // param2 forces the text to something
            if ($field->field->param2) {
                $alttext = s($field->field->param2);
            } else {
                $alttext = empty($entry->{"c{$fieldid}_content1"}) ? $url : $entry->{"c{$fieldid}_content1"};
            }

            // linking
            if ($type == 'link') {
                return html_writer::link($url, $alttext, $attributes);
            }
            
            // image
            if ($type == 'image') {
                return html_writer::empty_tag('img', array('src' => $url));
            }

            // image flexible
            if ($type == 'imageflex') {
                return html_writer::empty_tag('img', array('src' => $url, 'style' => 'width:100%'));
            }

            // media
            if ($type == 'media') {
                require_once("$CFG->dirroot/filter/mediaplugin/filter.php");
                $mpfilter = new filter_mediaplugin($field->df()->context, array());
                return $mpfilter->filter(html_writer::link($url, '', $attributes));
            }
        }
        
        return '';
    }

    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname:link]]"] = array(false);
        $patterns["[[$fieldname:image]]"] = array(false);
        $patterns["[[$fieldname:imageflex]]"] = array(false);
        $patterns["[[$fieldname:media]]"] = array(false);

        return $patterns; 
    }
    /**
     * Array of patterns this field supports 
     */
    protected function supports_rules() {
        return array(
            self::RULE_REQUIRED
        );
    }
}
