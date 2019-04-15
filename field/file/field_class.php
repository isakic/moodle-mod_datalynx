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
 * @subpackage file
 * @copyright 2013 onwards edulabs.org and associated programmers
 * @copyright based on the work by 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/datalynx/field/field_class.php");

/**
 */
class datalynxfield_file extends datalynxfield_base {

    public $type = 'file';

    /**
     * Can this field be used in fieldgroups? Override if yes.
     * @var boolean
     */
    protected $forfieldgroup = true;

    // Content - file manager.
    // Content1 - alt name.
    // Content2 - download counter.

    /**
     */
    protected function content_names() {
        return array('filemanager', 'alttext', 'delete', 'editor');
    }

    /**
     */
    public function update_content($entry, array $values = null) {
        global $DB, $USER;

        $entryid = $entry->id;
        $fieldid = $this->field->id;

        // This sets variables by resetting every part from the array key.
        $filemanager = $alttext = $delete = $editor = null;
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                if (!empty($name) and !empty($value)) {
                    ${$name} = $value; // Sets $filemanager etc.
                }
            }
        }

        // Update file content.
        if ($editor) {
            return $this->save_changes_to_file($entry, $values);
        }

        // Contentid to locate in table.
        $contentid = isset($entry->{"c{$this->field->id}_id"}) ? $entry->{"c{$this->field->id}_id"} : null;

        $usercontext = context_user::instance($USER->id);

        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $filemanager);

        // Even if we don't store any file info we need to have a contentid to show empty lines.
        $rec = new stdClass();
        $rec->fieldid = $fieldid;
        $rec->entryid = $entryid;
        $rec->content1 = $alttext;

        if (count($files) > 1) {
            $rec->content = 1; // We just store a 1 to show there is something, look for files.
        } else {
            $rec->content = 0; // In case there is no file, add a 0.
        }

        if (!empty($contentid)) {
            $rec->id = $contentid;
            $DB->update_record('datalynx_contents', $rec);
        } else {
            $contentid = $DB->insert_record('datalynx_contents', $rec);
        }

        if (count($files) > 1) {
            // Now save files if we see any.
            $options = array('subdirs' => 0, 'maxbytes' => $this->field->param1,
                    'maxfiles' => $this->field->param2, 'accepted_types' => $this->field->param3
            );

            $contextid = $this->df->context->id;
            file_save_draft_area_files($filemanager, $contextid, 'mod_datalynx', 'content',
                    $contentid, $options);

            $this->update_content_files($contentid);
        }
        return true;
    }

    /**
     */
    protected function format_content($entry, array $values = null) {
        return array(null, null, null);
    }

    /**
     */
    public function get_content_parts() {
        return array('content', 'content1', 'content2');
    }

    /**
     */
    public function prepare_import_content(&$data, $importsettings, $csvrecord = null, $entryid = null) {
        global $USER;

        // Check if not a csv import.
        if (!$csvrecord) {
            return false;
        }

        $fieldid = $this->field->id;
        $fieldname = $this->name();
        $csvname = $importsettings[$fieldname]['name'];
        $fileurl = $csvrecord[$csvname];

        // Check if this is an url.
        if (filter_var($fileurl, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        // Check if we can see the file.
        $headers = get_headers($fileurl);
        if (strpos($headers[0], 'OK') === false) {
            return false;
        }

        // Download this file in the temp folder.
        $filename = basename($fileurl);
        $file = file_get_contents($fileurl);
        file_put_contents("/tmp/$filename", $file);

        // Put the file in a draft area, if this is 0 we generate a new draft area.
        $draftitemid = file_get_submitted_draft_itemid("field_{$fieldid}_{$entryid}_filemanager");

        // For draftareas we use usercontextid for some reason, this is consistent with the ajax call.
        $contextid = context_user::instance($USER->id)->id;

        file_prepare_draft_area($draftitemid, $contextid, 'mod_datalynx', 'content', null);

        $filepath = "/tmp/$filename";
        $filerecord = array(
            'contextid' => $contextid,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => urldecode($filename)
        );
        $fs = get_file_storage();
        $file = $fs->create_file_from_pathname($filerecord, $filepath);

        // Tell the update script what itemid to look for.
        $data->{"field_{$fieldid}_{$entryid}_filemanager"} = $draftitemid;

        // Finally we can return true.
        return true;
    }

    /**
     */
    protected function update_content_files($contentid, $params = null) {
        return true;
    }

    /**
     */
    protected function save_changes_to_file($entry, array $values = null) {
        $fieldid = $this->field->id;
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entry->id}";

        $contentid = isset($entry->{"c{$this->field->id}_id"}) ? $entry->{"c{$this->field->id}_id"} : null;

        $options = array('context' => $this->df->context);
        $data = (object) $values;
        $data = file_postupdate_standard_editor((object) $values, $fieldname, $options,
                $this->df->context, 'mod_datalynx', 'content', $contentid);

        // Get the file content.
        $fs = get_file_storage();
        $file = reset(
                $fs->get_area_files($this->df->context->id, 'mod_datalynx', 'content', $contentid,
                        'sortorder', false));
        $filecontent = $file->get_content();

        // Find content position (between body tags).
        $tmpbodypos = stripos($filecontent, '<body');
        $openbodypos = strpos($filecontent, '>', $tmpbodypos) + 1;
        $sublength = strripos($filecontent, '</body>') - $openbodypos;

        // Replace body content with new content.
        $filecontent = substr_replace($filecontent, $data->$fieldname, $openbodypos, $sublength);

        // Prepare new file record.
        $rec = new stdClass();
        $rec->contextid = $this->df->context->id;
        $rec->component = 'mod_datalynx';
        $rec->filearea = 'content';
        $rec->itemid = $contentid;
        $rec->filename = $file->get_filename();
        $rec->filepath = '/';
        $rec->timecreated = $file->get_timecreated();
        $rec->userid = $file->get_userid();
        $rec->source = $file->get_source();
        $rec->author = $file->get_author();
        $rec->license = $file->get_license();

        // Delete old file.
        $fs->delete_area_files($this->df->context->id, 'mod_datalynx', 'content', $contentid);

        // Create a new file from string.
        $fs->create_file_from_string($rec, $filecontent);
        return true;
    }

    /**
     * Are fields of this field type suitable for use in customfilters?
     * @return bool
     */
    public static function is_customfilterfield() {
        return true;
    }
}
