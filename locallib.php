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
 * This file contains the definition for the library class for file submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_mediagallery
 * @copyright 2014 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/mod/mediagallery/locallib.php');

defined('MOODLE_INTERNAL') || die();

class assign_submission_mediagallery extends assign_submission_plugin {

    public function is_enabled() {
        return $this->get_config('enabled') && $this->is_configurable();
    }

    public function is_configurable() {
        $context = context_course::instance($this->assignment->get_course()->id);
        if ($this->get_config('enabled')) {
            return true;
        }
        if (!has_capability('assignsubmission/mediagallery:use', $context)) {
            return false;
        }
        return parent::is_configurable();
    }

    /**
     * Get the name of the file submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('mediagallery', 'assignsubmission_mediagallery');
    }

    /**
     * Get file submission information from the database
     *
     * @param int $submissionid
     * @return mixed
     */
    private function get_mediagallery_submission($submissionid) {
        global $DB;
        return $DB->get_record('assignsubmission_mg', array('submission' => $submissionid));
    }

    /**
     * Get the default setting for mediagallery submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE, $DB;

        $select = "colltype IN ('assignment', 'peerreviewed') AND course = :course";
        $options = $DB->get_records_select_menu('mediagallery', $select, array('course' => $COURSE->id), 'name ASC', 'id, name');
        $mform->addElement('select', 'assignsubmission_mediagallery_mg',
            get_string('mediagallery', 'assignsubmission_mediagallery'), $options);
        $mform->setDefault('assignsubmission_mediagallery_mg', $this->get_config('mediagallery'));
        // START UCLA MOD: CCLE-7189 - Converted js functionality to Jquery for simplify assignment settings
        // $mform->disabledIf('assignsubmission_mediagallery_mg',
        //                    'assignsubmission_mediagallery_enabled',
        //                    'notchecked');
        $mform->hideIf('assignsubmission_mediagallery_mg',
                           'assignsubmission_mediagallery_enabled',
                           'notchecked');
        // END UCLA MOD: CCLE-7189
        $mform->disabledIf('assignsubmission_mediagallery_enabled',
            'assignsubmission_mediagallery_mg', 'eq', '');
    }

    /**
     * Save the settings for mediagallery submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        if (!empty($data->assignsubmission_mediagallery_mg)) {
            $this->set_config('mediagallery', $data->assignsubmission_mediagallery_mg);
        }
        return true;
    }

    /**
     * Add elements to submission form
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {

        $submissionid = $submission ? $submission->id : 0;

        $mg = $this->get_config('mediagallery');
        try {
            $collection = new \mod_mediagallery\collection($mg);
            $options = array('' => get_string('makeaselection', 'assignsubmission_mediagallery'));
            foreach ($collection->get_my_galleries() as $gallery) {
                $options[$gallery->id] = $gallery->name;
            }
            if (!empty($options)) {
                $mform->addElement('select', 'galleryid', get_string('gallery', 'assignsubmission_mediagallery'), $options);
            } else {
                $url = new moodle_url('/mod/mediagallery/view.php', array('m' => $collection->id));
                $link = html_writer::link($url, get_string('createagallery', 'assignsubmission_mediagallery'));
                $mform->addElement('static', 'nogallery', get_string('gallery', 'assignsubmission_mediagallery'), $link);
            }
            if ($mgsubmission = $this->get_mediagallery_submission($submissionid)) {
                $mform->setDefault('galleryid', $mgsubmission->galleryid);
            }
        } catch (dml_missing_record_exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Save the submission.
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        if (empty($data->galleryid)) {
            $err = get_string('errornoselection', 'assignsubmission_mediagallery');
            $err .= html_writer::empty_tag('br');
            $mg = $this->get_config('mediagallery');
            $url = new moodle_url('/mod/mediagallery/view.php', array('m' => $mg));
            $err .= ' '.html_writer::link($url, get_string('createagallery', 'assignsubmission_mediagallery'));

            $this->set_error($err);
            return false;
        }

        $mgsubmission = $this->get_mediagallery_submission($submission->id);

        if ($mgsubmission) {
            $mgsubmission->galleryid = $data->galleryid;
            return $DB->update_record('assignsubmission_mg', $mgsubmission);
        } else {
            $mgsubmission = new stdClass();
            $mgsubmission->submission = $submission->id;
            $mgsubmission->assignment = $this->assignment->get_instance()->id;
            $mgsubmission->galleryid = $data->galleryid;
            return $DB->insert_record('assignsubmission_mg', $mgsubmission) > 0;
        }
    }

    /**
     * @param stdClass $submission
     * @param bool $showviewlink
     * @return string
     */
    public function view_summary(stdClass $submission, &$showviewlink) {
        global $DB;
        $url = null;
        $record = $this->get_mediagallery_submission($submission->id);

        if (!isset($record->galleryid) || !($galrecord = $DB->get_record('mediagallery_gallery', array('id' => $record->galleryid)))) {
            return '';
        }
        $gallery = new \mod_mediagallery\gallery($galrecord);
        $url = new moodle_url('/mod/mediagallery/view.php', array('g' => $gallery->id));
        return html_writer::link($url, $gallery->name, array('target' => '_blank'));
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type
     * @param int $version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        return false;
    }


    /**
     * Upgrade the settings from the old assignment
     * to the new plugin based one
     *
     * @param context $oldcontext - the old assignment context
     * @param stdClass $oldassignment - the old assignment data record
     * @param string $log record log events here
     * @return bool Was it a success? (false will trigger rollback)
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        return false;
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     *
     * @param context $oldcontext The context of the old assignment
     * @param stdClass $oldassignment The data record for the old oldassignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext,
                            stdClass $oldassignment,
                            stdClass $oldsubmission,
                            stdClass $submission,
                            &$log) {
        return false;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // Will throw exception on failure.
        $DB->delete_records('assignsubmission_mg',
                            array('assignment' => $this->assignment->get_instance()->id));

        return true;
    }

    /**
     * Return true if there is no submitted gallery (or gallery was deleted).
     * @param stdClass $submission
     */
    public function is_empty(stdClass $submission) {
        global $DB;
        if (!$record = $this->get_mediagallery_submission($submission->id)) {
            return true;
        }
        return !$DB->record_exists('mediagallery_gallery', array('id' => $record->galleryid));
    }

}
