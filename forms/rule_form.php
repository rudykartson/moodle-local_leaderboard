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
 * @package     local_leaderboard
 * @copyright   2026 Rudraksh Batra <batra.rudraksh@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_login();
require_capability('local/leaderboard:manage', context_system::instance());

class local_leaderboard_rule_form extends moodleform {
    public function definition() {
        global $DB;
        $mform = $this->_form;

        // Fetch course and activity data.
        $courses = $DB->get_records_menu('course', null, 'fullname', 'id, fullname');
        unset($courses[SITEID]);

        $activitymap = [];
        $activitytypemap = [];

        foreach ($courses as $cid => $cname) {
            $modinfo = get_fast_modinfo($cid);
            foreach ($modinfo->get_cms() as $cm) {
                if (!$cm->uservisible) {
                    continue;
                }
                $activitymap[$cid][$cm->id] = $cm->name . " ({$cm->modname})";
                $activitytypemap[$cm->id] = $cm->modname;
            }
        }

        // Scope.
        $mform->addElement('select', 'scope', get_string('scope', 'local_leaderboard'), [
            'course'   => get_string('course', 'local_leaderboard'),
            'activity' => get_string('activity', 'local_leaderboard'),
        ], ['id' => 'id_scope']);
        $mform->setType('scope', PARAM_TEXT);

        // Course.
        $mform->addElement('select', 'courseid', get_string('course', 'local_leaderboard'), [0 => get_string('selectag', 'local_leaderboard')] + $courses, ['id' => 'id_courseid']);
        $mform->setType('courseid', PARAM_INT);

        // Activity.
        $mform->addElement('select', 'cmid', get_string('activity', 'local_leaderboard'), [0 => get_string('selectag', 'local_leaderboard')], ['id' => 'id_cmid']);
        $mform->setType('cmid', PARAM_INT);

        // Activity Type (readonly).
        $mform->addElement('text', 'activitytype', get_string('activitytype', 'local_leaderboard'), ['readonly' => 'readonly', 'id' => 'id_activitytype']);
        $mform->setType('activitytype', PARAM_TEXT);

        // Event.
        $mform->addElement('select', 'event', get_string('event', 'local_leaderboard'), [
            'start' => get_string('startview', 'local_leaderboard'),
            'complete' => get_string('complete', 'local_leaderboard'),
        ]);
        $mform->setType('event', PARAM_TEXT);

        // Points.
        $mform->addElement('text', 'points', get_string('points', 'local_leaderboard'));
        $mform->setType('points', PARAM_INT);

        $this->add_action_buttons();

        // Wire up the scope/course/activity cascade via the AMD module.
        $this->add_js($activitymap, $activitytypemap);
    }

    /**
     * Initialise the AMD module that drives the scope/course/activity selects.
     *
     * @param array $activitymap Map of course id => {cmid: "name (modname)"}.
     * @param array $activitytypemap Map of cmid => modname.
     */
    private function add_js($activitymap, $activitytypemap) {
        global $PAGE;

        $PAGE->requires->js_call_amd('local_leaderboard/rule_form', 'init', [
            $activitymap,
            $activitytypemap,
        ]);
    }

    public function validation($data, $files) {
        global $DB;
        $opcmid = optional_param('cmid', 0, PARAM_INT);

        $errors = parent::validation($data, $files);
        $data['cmid'] = $opcmid;

        $levels = $DB->get_records('local_leaderboard_levels');

        if (empty($levels)) {
            $errors['points'] = get_string('rulepoint_error', 'local_leaderboard');
        }

        if ($data['scope'] === 'course' && (int)$data['courseid'] === 0) {
            $errors['courseid'] = get_string('rulecid_error', 'local_leaderboard');
        }

        if ($data['scope'] === 'activity') {
            if ((int)$data['courseid'] === 0) {
                $errors['courseid'] = get_string('rulecid_error', 'local_leaderboard');
            }
            if ((int)$opcmid === 0) {
                $errors['cmid'] = get_string('rulecmid_error', 'local_leaderboard');
            }
        }

        return $errors;
    }
}