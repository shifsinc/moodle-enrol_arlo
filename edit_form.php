<?php
//
// This file is part of Moodle - http://moodle.org/
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
 * Adds new instance of enrol_arlo to specified course
 * or edits current instance.
 *
 * @author    Troy Williams
 * @author    Corey Davis
 * @package   local_arlo {@link https://docs.moodle.org/dev/Frankenstyle}
 * @copyright 2015 LearningWorks Ltd {@link http://www.learningworks.co.nz}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/arlo/lib.php');

class enrol_arlo_edit_form extends moodleform {
    public function definition() {
        global $DB;
        $mform = $this->_form;

        list($instance, $plugin, $course) = $this->_customdata;
        $context = context_course::instance($course->id);

        $enrol = enrol_get_plugin('arlo');

        $groups = array(0 => get_string('none'));
        if (has_capability('moodle/course:managegroups', $context)) {
            $groups[ARLO_CREATE_GROUP] = get_string('creategroup', 'enrol_arlo');
        }

        foreach (groups_get_all_groups($course->id) as $group) {
            $groups[$group->id] = format_string($group->name, true, array('context' => $context));
        }

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
            ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_arlo'), $options);

        // Build event options group.
        foreach (\local_arlo\arlo::get_active_events() as $event) {
            $events[ARLO_TYPE_EVENT . '-' . $event->eventid] = $event->code . ' ' . $event->name;
        }
        // Build online activity options group.
        foreach (\local_arlo\arlo::get_active_online_activities() as $onlineactivity) {
            $onlineactivities[ARLO_TYPE_ONLINEACTIVITY. '-' . $onlineactivity->onlineactivityid]
                = $onlineactivity->code . ' ' . $onlineactivity->name;
        }

        // @TODO this is need to build selector.
        if ($instance->id) {
            // Platform name.
            $arloinstance = get_config('local_arlo', 'setting_arlo_orgname');
            // Get Resource type and id.
            $type = $instance->customint3;
            $identifier = $instance->customint4;
            if ($type == ARLO_TYPE_EVENT) {
                $table = 'local_arlo_events';
                $field = 'eventid';
            } else if ($type == ARLO_TYPE_ONLINEACTIVITY) {
                $table = 'local_arlo_onlineactivities';
                $field = 'onlineactivityid';
            }

            // Get Resource record either Event or Online Activity.
            $params = array($field => $identifier, 'arloinstance' => $arloinstance);
            $resource = $DB->get_record($table, $params, '*', MUST_EXIST);
            if (! $resource) {
                $options = array(get_string('error') => array());
            } else {
                $options = array(get_string('events', 'enrol_arlo') => $events,
                    get_string('onlineactivities', 'enrol_arlo') => $onlineactivities);

            }

            $key = $type . '-' . $identifier;
            $mform->addElement('selectgroups', 'event', get_string('event', 'enrol_arlo'), $options);
            $mform->setConstant('event', $key);
            $mform->hardFreeze('event', $key);

        } else {

            $options = array(get_string('events', 'enrol_arlo') => $events,
                             get_string('onlineactivities', 'enrol_arlo') => $onlineactivities);

            $mform->addElement('selectgroups', 'event', get_string('event', 'enrol_arlo'), $options);

        }

        $mform->addElement('select', 'customint2', get_string('addgroup', 'enrol_arlo'), $groups);

        $mform->addElement('advcheckbox', 'customint8', get_string('sendcoursewelcomemessage', 'enrol_arlo'));
        $mform->addHelpButton('customint8', 'sendcoursewelcomemessage', 'enrol_arlo');
        $mform->setDefault('customint8', 1);

        $mform->addElement('textarea', 'customtext1',
            get_string('customwelcomemessage', 'enrol_arlo'),
            array('cols'=>'60', 'rows'=>'8'));
        $mform->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_arlo');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
/*
        if (enrol_accessing_via_instance($instance)) {
            $mform->addElement('static', 'selfwarn',
                get_string('instanceeditselfwarning', 'core_enrol'),
                get_string('instanceeditselfwarningtext', 'core_enrol'));
        }
*/
        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
