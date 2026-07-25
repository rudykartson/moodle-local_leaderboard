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

require_once($CFG->libdir.'/formslib.php');

class local_leaderboard_rule_form extends moodleform {
    public function definition() {
        global $DB;
        $mform = $this->_form;

        // Fetch course and activity data
        $courses = $DB->get_records_menu('course', null, 'fullname', 'id, fullname');
        unset($courses[1]);
        
        $activitymap = [];
        $activitytypemap = [];

        foreach ($courses as $cid => $cname) {
            $modinfo = get_fast_modinfo($cid);
            foreach ($modinfo->get_cms() as $cm) {
                if (!$cm->uservisible) continue;
                $activitymap[$cid][$cm->id] = $cm->name . " ({$cm->modname})";
                $activitytypemap[$cm->id] = $cm->modname;
            }
        }

        // Scope
        $mform->addElement('select', 'scope', 'Scope', [
            // 'platform' => 'Platform-wide',
            'course'   => 'Course',
            'activity' => 'Activity'
        ], ['id' => 'id_scope']);
        $mform->setType('scope', PARAM_TEXT);

        // Course
        $mform->addElement('select', 'courseid', 'Course', [0 => '-- Select --'] + $courses, ['id' => 'id_courseid']);
        $mform->setType('courseid', PARAM_INT);

        // Activity
        $mform->addElement('select', 'cmid', 'Activity', [0 => '-- Select --'], ['id' => 'id_cmid']);
        $mform->setType('cmid', PARAM_INT);

        // Activity Type (readonly)
        $mform->addElement('text', 'activitytype', 'Activity Type', ['readonly' => 'readonly', 'id' => 'id_activitytype']);
        $mform->setType('activitytype', PARAM_TEXT);

        // Event
        $mform->addElement('select', 'event', 'Event', [
            'start' => 'Start/View',
            'complete' => 'Complete'
        ]);
        $mform->setType('event', PARAM_TEXT);

        // Points
        $mform->addElement('text', 'points', 'Points');
        $mform->setType('points', PARAM_INT);


        $this->add_action_buttons();

        // Inject JavaScript
        $this->add_js($courses, $activitymap, $activitytypemap);
    }

    private function add_js($courses, $activitymap, $activitytypemap) {
        global $PAGE;

        $js_courses = json_encode($courses);
        $js_activities = json_encode($activitymap);
        $js_activitytypes = json_encode($activitytypemap);

        $PAGE->requires->js_init_code("
            (function() {
                // Form elements
                const scopeEl = document.getElementById('id_scope');
                const courseEl = document.getElementById('id_courseid');
                const cmidEl = document.getElementById('id_cmid');
                const activityTypeEl = document.getElementById('id_activitytype');

                // Preloaded data from PHP
                const courses = $js_courses;
                const activities = $js_activities;
                const activitytypes = $js_activitytypes;

                /**
                 * Disable or enable a form element
                 */
                function setDisabled(el, disabled) {
                    el.disabled = disabled;
                    if (disabled) el.value = '0';
                }

                /**
                 * Populate a select element with options
                 */
                function populateSelect(select, items) {
                    select.innerHTML = '';
                    const defaultOpt = document.createElement('option');
                    defaultOpt.value = '0';
                    defaultOpt.text = '-- Select --';
                    select.appendChild(defaultOpt);

                    for (const [val, label] of Object.entries(items)) {
                        const opt = document.createElement('option');
                        opt.value = val;
                        opt.text = label;
                        select.appendChild(opt);
                    }
                }

                /**
                 * Update the form inputs based on selected scope
                 */
                function updateFormFromScope() {
                    const scope = scopeEl.value;

                    if (scope === 'platform') {
                        setDisabled(courseEl, true);
                        setDisabled(cmidEl, true);
                        populateSelect(cmidEl, {});
                        activityTypeEl.value = '';
                    } else if (scope === 'course') {
                        setDisabled(courseEl, false);
                        setDisabled(cmidEl, true);
                        populateSelect(cmidEl, {});
                        activityTypeEl.value = '';
                    } else if (scope === 'activity') {
                        setDisabled(courseEl, false);
                        const selectedCourseId = courseEl.value;
                        if (selectedCourseId && activities[selectedCourseId]) {
                            populateSelect(cmidEl, activities[selectedCourseId]);
                            setDisabled(cmidEl, false);
                        } else {
                            setDisabled(cmidEl, true);
                            populateSelect(cmidEl, {});
                        }
                    }
                }

                /**
                 * Update activity type when an activity is selected
                 */
                function updateActivityType() {
                    const selectedCmid = cmidEl.value;
                    activityTypeEl.value = activitytypes[selectedCmid] || '';
                }

                // Event listeners
                scopeEl.addEventListener('change', () => {
                    updateFormFromScope();
                    updateActivityType();
                });

                courseEl.addEventListener('change', () => {
                    if (scopeEl.value === 'activity') {
                        const selectedCourseId = courseEl.value;
                        if (activities[selectedCourseId]) {
                            populateSelect(cmidEl, activities[selectedCourseId]);
                            setDisabled(cmidEl, false);
                        } else {
                            setDisabled(cmidEl, true);
                            populateSelect(cmidEl, {});
                        }
                    }
                    updateActivityType();
                });

                cmidEl.addEventListener('change', () => {
                    updateActivityType();
                });

                // Initialize on page load
                updateFormFromScope();
                updateActivityType();
            })();
        ");
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $data['cmid'] = $_POST['cmid'];

        $levels = $DB->get_records('local_leaderboard_levels');

        if (empty($levels)) {
            $errors['points'] = 'Please create a Level/Tier first before proceeding..';
        }


        if ($data['scope'] === 'course' && (int)$data['courseid'] === 0) {
            $errors['courseid'] = 'You must select a course for this rule.';
        }

        if ($data['scope'] === 'activity') {
            if ((int)$data['courseid'] === 0) {
                $errors['courseid'] = 'You must select a course for this rule.';
            }
            if ((int)$data['cmid'] === 0) {
                $errors['cmid'] = 'You must select an activity for this rule.';
            }
        }

        return $errors;
    }
}
