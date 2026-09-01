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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/forms/rule_form.php');
require_login();
require_capability('local/leaderboard:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/leaderboard/manage_rules.php'));
$PAGE->set_title(get_string('add_pts_rule', 'local_leaderboard'));
$PAGE->set_heading(get_string('add_pts_rule', 'local_leaderboard'));

global $DB;

// Delete rule if requested
if ($deleteid = optional_param('delete', 0, PARAM_INT)) {
    require_sesskey();
    $DB->delete_records('local_leaderboard_rules', ['id' => $deleteid]);
    redirect('manage_rules.php', get_string('ruledeleted', 'local_leaderboard'), 1);
}

// --- Pick up POST or GET params to allow dynamic refresh ---
$selectedscope = optional_param('scope', 'platform', PARAM_ALPHA);
$selectedcourseid = optional_param('courseid', 0, PARAM_INT);
$selectedcmid = optional_param('cmid', 0, PARAM_INT);

// Pass current selections to form as customdata.
$customdata = [
    'scope' => $selectedscope,
    'courseid' => $selectedcourseid,
    'cmid' => $selectedcmid,
];

// Instantiate form with current customdata
$mform = new local_leaderboard_rule_form(null, $customdata);

// --- Form processing ---
if ($mform->is_cancelled()) {
    redirect('manage_rules.php');
} else if ($data = $mform->get_data()) {
    $opcmid = optional_param('cmid', 0, PARAM_INT);
    $data->cmid = $opcmid;

    // Save rule!
    $activitytype = $data->activitytype;
    if ($data->scope == 'activity' && $data->cmid) {
        $cm = get_coursemodule_from_id(null, $data->cmid);
        $activitytype = $cm ? $cm->modname : '';
    }
    $newrule = (object)[
        'scope' => $data->scope,
        'scopeid' => $data->scope == 'platform' ? 0 : ($data->scope == 'course' ? $data->courseid : $data->cmid),
        'activitytype' => $activitytype,
        'event' => $data->event,
        'points' => $data->points
    ];
    $DB->insert_record('local_leaderboard_rules', $newrule);
    redirect('manage_rules.php', get_string('ruleadded', 'local_leaderboard'), 2);
}

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/leaderboard/manage_level.php'),
        get_string('managelevel', 'local_leaderboard'),
        ['class' => 'btn btn-primary']
    ),
    'relatebtn'
);
$mform->display();

// --- Show existing rules ---
$rules = $DB->get_records_sql(
    'SELECT lr.* FROM {local_leaderboard_rules} lr JOIN {course_modules} cm ON lr.scopeid = cm.id'
);

    // --- Pass 1: collect every id we'll need to look up. ---
    $courseids = [];
    $cmids = [];

    foreach ($rules as $rule) {
        if (($rule->scope == 'activity' || $rule->scope == 'course') && empty($rule->scopeid)) {
            continue;
        }
        if ($rule->scope == 'course') {
            $courseids[$rule->scopeid] = true;
        }
        if ($rule->scope == 'activity') {
            $cmids[$rule->scopeid] = true;
        }
    }

    // --- Batch-fetch all course_modules for activity-scope rules: ONE query. ---
    $cms = [];
    if ($cmids) {
        $cms = $DB->get_records_list('course_modules', 'id', array_keys($cmids));
        foreach ($cms as $cm) {
            $courseids[$cm->course] = true; // We'll need these course names too.
        }
    }

    // --- Batch-fetch all course fullnames needed (both scopes): ONE query. ---
    $coursenames = [];
    if ($courseids) {
        $coursenames = $DB->get_records_list('course', 'id', array_keys($courseids), '', 'id, fullname');
    }

    // --- Resolve activity names via get_fast_modinfo, once per DISTINCT course
    //     (not once per rule) — modinfo is cached, so repeats are free. ---
    $activitynames = [];
    $modinfocache = [];
    foreach ($cms as $cmid => $cm) {
        if (!isset($modinfocache[$cm->course])) {
            $modinfocache[$cm->course] = get_fast_modinfo($cm->course);
        }
        $cminfo = $modinfocache[$cm->course]->get_cm($cmid);
        $activitynames[$cmid] = $cminfo ? format_string($cminfo->name) : '';
    }

    // --- Pass 2: build display rows. No queries in this loop. ---
    $rows = [];
    foreach ($rules as $rule) {
        if (($rule->scope == 'activity' || $rule->scope == 'course') && empty($rule->scopeid)) {
            continue;
        }

        $coursename = '';
        $activityname = '';

        if ($rule->scope == 'course' && $rule->scopeid) {
            $coursename = isset($coursenames[$rule->scopeid]) ? $coursenames[$rule->scopeid]->fullname : '';
        }

        if ($rule->scope == 'activity' && $rule->scopeid && isset($cms[$rule->scopeid])) {
            $cm = $cms[$rule->scopeid];
            $coursename = isset($coursenames[$cm->course]) ? $coursenames[$cm->course]->fullname : '';
            $activityname = $activitynames[$rule->scopeid] ?? '';
        }

        $delurl = new moodle_url('/local/leaderboard/manage_rules.php', [
            'delete' => $rule->id,
            'sesskey' => sesskey(),
        ]);

        $rows[] = [
            'scope' => $rule->scope,
            'scopeid' => (int) $rule->scopeid,
            'coursename' => $coursename,
            'activityname' => $activityname,
            'activitytype' => $rule->activitytype,
            'event' => $rule->event,
            'points' => (int) $rule->points,
            'deleteurl' => $delurl->out(false),
        ];
    }

$data = [
    'existingrulesstr' => get_string('existingrules', 'local_leaderboard'),
    'rows' => $rows,
    'hasrules' => !empty($rows),
];

echo $OUTPUT->render_from_template('local_leaderboard/rules_table', $data);
echo $OUTPUT->footer();