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

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/leaderboard/manage_level.php'));
$PAGE->set_title(get_string('levels', 'local_leaderboard'));
$PAGE->set_heading(get_string('levels', 'local_leaderboard'));

global $DB;

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
    $editid = $_POST['lid'];
    $data->color = $_POST['color'];
    $content = $mform->get_file_content('levelimage');

    if($content){
        $filen = $mform->get_new_filename('levelimage');
        $filename = "img_".bin2hex(random_bytes(8))."".$filen;
        $destination = __DIR__ . '/assets/' . $filename; 

        if ($content && $filename) {
            $dir = dirname($destination);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            if (! file_put_contents($destination, $content)) {
                throw new moodle_exception('Failed to save the file');
            }
        }
    }
    

    if($editid > 0){

        $level = (object)[ 
            'id' => $editid,
            'name' => $data->name,
            'min_points' => (int)$data->min_points,
            'max_points' => (int)$data->max_points,
            'color' => $data->color,
            'sortorder' => (int)$data->sortorder
        ];


        if (!empty($filename)) {
            if($editimg = $DB->get_record('local_leaderboard_levels', ['id' => $editid])){
                $imgpath = __DIR__ . '/assets/' . $editimg->img;
                if (file_exists($imgpath)) {
                    unlink($imgpath);
                }
            }
            $level->img = $filename;
        }else{
            if($editimg = $DB->get_record('local_leaderboard_levels', ['id' => $editid])){
                $level->img = $editimg->img ?? '';
            }

        }

        $DB->update_record('local_leaderboard_levels', $level);


    }else{

        $level = (object)[ 
            'name' => $data->name,
            'min_points' => (int)$data->min_points,
            'max_points' => (int)$data->max_points,
            'color' => $data->color,
            'img' => $filename ?? "",
            'sortorder' => (int)$data->sortorder
        ];
        $DB->insert_record('local_leaderboard_levels', $level);
    }
    
    redirect('manage_level.php', get_string('addnewlevel', 'local_leaderboard'), 2);

}

echo $OUTPUT->header();
echo '<div class="relatebtn"><a href="manage_rules.php" class="btn btn-primary" >Manage Rules</a></div>';
echo html_writer::tag('h3', get_string('addnewlevel', 'local_leaderboard'));
$mform->display();

// Show existing levels/tiers
$levels = $DB->get_records('local_leaderboard_levels', null, 'sortorder');
echo html_writer::tag('h3', get_string('existinglevels', 'local_leaderboard'));
echo '<table class="generaltable" style="background-color: #fff;"><tr>
<th>'.get_string('levelname', 'local_leaderboard').'</th>
<th>'.get_string('minpoints', 'local_leaderboard').'</th>
<th>'.get_string('maxpoints', 'local_leaderboard').'</th>
<th>'.get_string('color', 'local_leaderboard').'</th>
<th>'.get_string('sortorder', 'local_leaderboard').'</th>
<th>'.get_string('actions', 'local_leaderboard').'</th>
</tr>';

foreach ($levels as $level) {
    $delurl = new moodle_url('/local/leaderboard/manage_level.php', ['delete' => $level->id, 'sesskey' => sesskey()]);
    $editurl = new moodle_url('/local/leaderboard/manage_level.php', ['edit' => $level->id]);
    echo '<tr>';
    echo '<td>' . s($level->name) . '</td>';
    echo '<td>' . (int)$level->min_points . '</td>';
    echo '<td>' . (int)$level->max_points . '</td>';
    echo '<td><span style="color:'.s($level->color).';font-weight:bold;">'.s($level->color).'</span></td>';
    echo '<td>' . (int)$level->sortorder . '</td>';
    echo '<td>
      <a class="btn btn-primary" href="' . $editurl . '" >'.get_string('edit', 'local_leaderboard').'</a>
      <a class="btn btn-danger text-white" href="' . $delurl . '" onclick="return confirm(\''.get_string('areyousuredelete', 'local_leaderboard').'\')">'.get_string('delete', 'local_leaderboard').'</a>
      </td>';
    echo '</tr>';
}
echo '</table>';
echo $OUTPUT->footer();