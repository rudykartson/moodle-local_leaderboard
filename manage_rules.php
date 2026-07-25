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
$PAGE->set_title('Add Points Rule');
$PAGE->set_heading('Add Points Rule');

global $DB;

// Delete rule if requested
if ($deleteid = optional_param('delete', 0, PARAM_INT)) {
    require_sesskey();
    $DB->delete_records('local_leaderboard_rules', ['id' => $deleteid]);
    redirect('manage_rules.php', 'Rule deleted.', 1);
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
    $data->cmid = $_POST['cmid']; 

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
    redirect('manage_rules.php', 'Rule added!', 2);
}

echo $OUTPUT->header();
echo '<div class="relatebtn"><a href="manage_level.php" class="btn btn-primary" >Manage Level</a></div>';
$mform->display();

// --- Show existing rules ---
$rules = $DB->get_records_sql('SELECT lr.* FROM {local_leaderboard_rules} lr JOIN {course_modules} cm ON lr.scopeid = cm.id');


echo html_writer::tag('h2', 'Existing Points Rules');
echo '<table class="generaltable" style="background-color: #fff;"><tr>
<th>Scope</th>
<th>ScopeID</th>
<th>Course</th>
<th>Activity</th>
<th>Activity Type</th>
<th>Event</th>
<th>Points</th>
<th>Actions</th>
</tr>';

foreach ($rules as $rule) {
    if (($rule->scope == 'activity' || $rule->scope == 'course') && empty($rule->scopeid)) {
        continue;
    }
    $coursename = '';
    if (($rule->scope == 'course' || $rule->scope == 'activity') && $rule->scopeid) {
        if ($rule->scope == 'course') {
            $course = $DB->get_record('course', ['id' => $rule->scopeid], 'fullname');
            $coursename = $course ? $course->fullname : '';
        }
        if ($rule->scope == 'activity') {
            $cm = get_coursemodule_from_id(null, $rule->scopeid, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', ['id' => $cm->course], 'fullname');
            $coursename = $course ? $course->fullname : '';
        }
    }
    $activityname = '';
    if ($rule->scope == 'activity' && $rule->scopeid) {
        $cm = get_coursemodule_from_id(null, $rule->scopeid, 0, false, MUST_EXIST);
        $activityname = $cm ? format_string($cm->name) : '';
    }
    $delurl = new moodle_url('/local/leaderboard/manage_rules.php', ['delete' => $rule->id, 'sesskey' => sesskey()]);
    echo '<tr>';
    echo '<td>' . s($rule->scope) . '</td>';
    echo '<td>' . (int)$rule->scopeid . '</td>';
    echo '<td>' . s($coursename) . '</td>';
    echo '<td>' . s($activityname) . '</td>';
    echo '<td>' . s($rule->activitytype) . '</td>';
    echo '<td>' . s($rule->event) . '</td>';
    echo '<td>' . (int)$rule->points . '</td>';
    echo '<td>
      <a href="' . $delurl . '" onclick="return confirm(\'Delete this rule?\')">Delete</a>
      </td>';
    echo '</tr>';
}
echo '</table>';
echo $OUTPUT->footer();