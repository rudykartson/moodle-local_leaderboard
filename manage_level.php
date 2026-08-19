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
require_once(__DIR__ . '/forms/level_form.php');
require_login();
require_capability('local/leaderboard:manage', context_system::instance());
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/leaderboard/manage_level.php'));
$PAGE->set_title(get_string('levels', 'local_leaderboard'));
$PAGE->set_heading(get_string('levels', 'local_leaderboard'));

global $DB, $CFG;
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot."/lib/filelib.php");

// Delete level if requested
if ($deleteid = optional_param('delete', 0, PARAM_INT)) {
    require_sesskey();
    if($dellevel = $DB->get_record('local_leaderboard_levels', ['id' => $deleteid])){
        $imgpath = __DIR__ . '/assets/' . $dellevel->img;
        if (file_exists($imgpath)) {
            unlink($imgpath);
        }
        $DB->delete_records('local_leaderboard_levels', ['id' => $deleteid]); 
        redirect('manage_level.php', get_string('delete', 'local_leaderboard'), 1);
    }
}

$ditdata = '';
// edit level if requested
if ($editid = optional_param('edit', 0, PARAM_INT)) {
    if($editlevel = $DB->get_record('local_leaderboard_levels', ['id' => $editid])){
        $ditdata = $editlevel;
    }
}

$customdata = [
    'edit' => $ditdata,
];

$mform = new local_leaderboard_level_form(null, $customdata);


if ($mform->is_cancelled()) {
    redirect('manage_level.php');
} else if ($data = $mform->get_data()) {
    $clr = optional_param('color', '#778899', PARAM_TEXT);
    $data->color = $clr;

    $editid = $data->lid;

    $fileoptions = [
        'subdirs' => 0,
        'maxbytes' => 10485760,
        'maxfiles' => 1,
        'accepted_types' => ['.png'],
    ];

    if ($editid > 0) {

        $imageitemid = $DB->get_record('files', ['itemid'=>$data->levelimage]);

        // --- Update existing level. ---
        $level = (object) [
            'id' => $editid,
            'name' => $data->name,
            'min_points' => (int) $data->min_points,
            'max_points' => (int) $data->max_points,
            'color' => $data->color,
            'sortorder' => (int) $data->sortorder,
            'img' => $imageitemid ? $editid : "", // Itemid == record id, by convention.
        ];

        $DB->update_record('local_leaderboard_levels', $level);

        // file_save_draft_area_files reconciles added/removed files on its
        // own — replacing the old png with the new one automatically.
        // No manual unlink() needed.
        file_save_draft_area_files(
            $data->levelimage,
            $context->id,
            'local_leaderboard',
            'levelimage',
            $editid,
            $fileoptions
        );

    } else {
        // --- Insert new level. ---
        $level = (object) [
            'name' => $data->name,
            'min_points' => (int) $data->min_points,
            'max_points' => (int) $data->max_points,
            'color' => $data->color,
            'sortorder' => (int) $data->sortorder,
            'img' => 0, // Placeholder — record id isn't known yet.
        ];

        $newid = $DB->insert_record('local_leaderboard_levels', $level);

        // Now that we have the record's id, use it as the file area itemid.
        file_save_draft_area_files(
            $data->levelimage,
            $context->id,
            'local_leaderboard',
            'levelimage',
            $newid,
            $fileoptions
        );

        $DB->set_field('local_leaderboard_levels', 'img', $newid, ['id' => $newid]);
    }

    redirect(
        new moodle_url('/local/leaderboard/manage_level.php'),
        get_string('addnewlevel', 'local_leaderboard'),
        2
    );
}

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/leaderboard/manage_rules.php'),
        'Manage Rules',
        ['class' => 'btn btn-primary']
    ),
    'relatebtn'
);
echo html_writer::tag('h3', get_string('addnewlevel', 'local_leaderboard'));
$mform->display();

// Show existing levels/tiers.
$levels = $DB->get_records('local_leaderboard_levels', null, 'sortorder');

$rows = [];
foreach ($levels as $level) {
    $delurl = new moodle_url('/local/leaderboard/manage_level.php', [
        'delete' => $level->id,
        'sesskey' => sesskey(),
    ]);
    $editurl = new moodle_url('/local/leaderboard/manage_level.php', ['edit' => $level->id]);

    $rows[] = [
        'name' => $level->name,
        'minpoints' => (int) $level->min_points,
        'maxpoints' => (int) $level->max_points,
        'color' => $level->color,
        'sortorder' => (int) $level->sortorder,
        'editurl' => $editurl->out(false),
        'deleteurl' => $delurl->out(false),
    ];
}

$data = [
    'existinglevelsstr' => get_string('existinglevels', 'local_leaderboard'),
    'levelnamestr' => get_string('levelname', 'local_leaderboard'),
    'minpointsstr' => get_string('minpoints', 'local_leaderboard'),
    'maxpointsstr' => get_string('maxpoints', 'local_leaderboard'),
    'colorstr' => get_string('color', 'local_leaderboard'),
    'sortorderstr' => get_string('sortorder', 'local_leaderboard'),
    'actionsstr' => get_string('actions', 'local_leaderboard'),
    'editstr' => get_string('edit', 'local_leaderboard'),
    'deletestr' => get_string('delete', 'local_leaderboard'),
    'areyousuredeletestr' => get_string('areyousuredelete', 'local_leaderboard'),
    'rows' => $rows,
    'haslevels' => !empty($rows),
];

echo $OUTPUT->render_from_template('local_leaderboard/levels_table', $data);
echo $OUTPUT->footer();