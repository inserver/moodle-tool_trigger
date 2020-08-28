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

namespace tool_trigger\steps\lookups;

defined('MOODLE_INTERNAL') || die;

/**
 * A lookup step that takes a user's ID and adds roles of the user.
 *
 * @package    tool_trigger
 * @author     Ilya Tregubov <ilyatregubov@catalyst.net.nz>
 * @copyright  Catalyst IT, 2018
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_lookup_step extends base_lookup_step {

    use \tool_trigger\helper\datafield_manager;

    /**
     * The data field to get the user id from.
     * @var string
     */
    private $useridfield = null;

    /**
     * The data field to get the context id from.
     * @var string
     */
    private $contextidfield = null;


    /**
     * The prefix to put before the new fields added to the workflow data.
     *
     * @var string
     */
    private $outputprefix = null;

    /**
     * The fields supplied by this step.
     * All user roles in all context.
     *
     * @var array
     */
    // private static $stepfields = array('roles');
    private static $stepfields = array('roleid');

    protected function init() {
        $this->useridfield = $this->data['useridfield'];
        $this->contextidfield = $this->data['contextidfield'];
        $this->outputprefix = $this->data['outputprefix'];
    }
    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::execute()
     */
    public function execute($step, $trigger, $event, $stepresults) {
        global $DB;
        $datafields = $this->get_datafields($event, $stepresults);

        switch ($datafields['eventname'])
        {
            case '\core\event\group_member_added':
            case '\core\event\group_member_removed':
                $sql = 'SELECT idnumber FROM {groups} WHERE id = :groupid';
                $params = ['groupid' => $datafields['objectid']];
                $eventObject = $DB->get_field_sql($sql, $params);
            break;

            case '\core\event\role_assigned':
            case '\core\event\role_unassigned':
                $sql = 'SELECT shortname FROM {role} WHERE id = :roleid';
                $params = ['roleid' => $datafields['objectid']];
                $eventObject = $DB->get_field_sql($sql, $params);
            break;
        }
        
        $stepresults[$this->outputprefix . 'event'] = $eventObject;
        
        if (!array_key_exists($this->useridfield, $datafields)) {
            throw new \invalid_parameter_exception("Specified userid field not present in the workflow data: "
                . $this->useridfield);
        }
        
        return [true, $stepresults];
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::form_definition_extra()
     */
    public function form_definition_extra($form, $mform, $customdata) {
        $mform->addElement('text', 'useridfield', get_string('step_lookup_user_useridfield', 'tool_trigger'));
        $mform->setType('useridfield', PARAM_ALPHANUMEXT);
        $mform->addRule('useridfield', get_string('required'), 'required');
        $mform->setDefault('useridfield', 'userid');

        $mform->addElement('text', 'contextidfield', get_string('contextidfield', 'tool_trigger'));
        $mform->setType('contextidfield', PARAM_ALPHANUMEXT);
        $mform->addRule('contextidfield', get_string('required'), 'required');
        $mform->setDefault('contextidfield', 'contextid');

        $mform->addElement('text', 'outputprefix', get_string('outputprefix', 'tool_trigger'));
        $mform->setType('outputprefix', PARAM_ALPHANUMEXT);
        $mform->addRule('outputprefix', get_string('required'), 'required');
        $mform->setDefault('outputprefix', 'user_');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_desc()
     */
    public static function get_step_desc() {
        return get_string('step_lookup_event_desc', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_step_name()
     */
    public static function get_step_name() {
        return get_string('step_lookup_event_name', 'tool_trigger');
    }

    /**
     * {@inheritDoc}
     * @see \tool_trigger\steps\base\base_step::get_privacyfields()
     */
    public static function get_privacyfields() {
        return ['event_lookup_step' => 'step_lookup_event:privacy:userdata_desc'];
    }

    /**
     * Get a list of fields this step provides.
     *
     * @return array $stepfields The fields this step provides.
     */
    public static function get_fields() {
        return self::$stepfields;
    }
}
