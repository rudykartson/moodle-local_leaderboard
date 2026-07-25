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

require(__DIR__ . '/../../config.php');
require_login();
global $DB, $CFG, $USER, $PAGE, $OUTPUT;
// require_capability('local/leaderboard:view', context_system::instance());
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/leaderboard/index.php'));
$PAGE->set_title('Leaderboard Top 10');
$PAGE->set_heading('Leaderboard Top 10');
$PAGE->requires->css('/local/leaderboard/styles.css');

require_once($CFG->dirroot.'/local/leaderboard/classes/api.php');

$userid = $USER->id;
$filter_levelid = optional_param('levelid', 0, PARAM_INT);
$filter_country = optional_param('country', '', PARAM_ALPHA);

// Get all levels/tiers
$levels = \local_leaderboard\api::get_levels();
$levelsbyid = [];
foreach ($levels as $l) $levelsbyid[$l->id] = $l;

// Toggle for global/country
$is_country = !empty($filter_country);
$filters = [];
if ($is_country) $filters['country'] = $USER->country;
if ($filter_levelid) $filters['levelid'] = $filter_levelid;

// Leaderboard
$leaderboard = \local_leaderboard\api::get_leaderboard($filters, 10);

// User info for progress bar
$userpoints = \local_leaderboard\api::get_user_points($userid);
$userlevel = \local_leaderboard\api::get_user_level($userid);
$userlevel_id = $userlevel ? $userlevel->id : null;
$level_sort = $userlevel ? $userlevel->sortorder : 0;
$levels_sorted = array_values($levels); // already sorted if sortorder in DB

// Next tier info
$nextlevel = null;
foreach ($levels as $l) {
    if ($l->sortorder > $level_sort) {
        $nextlevel = $l;
        break;
    }
}
$points_to_next = $nextlevel ? ($nextlevel->min_points - $userpoints) : 0;
$progress_pct = 0;
if ($userlevel && $nextlevel) {
    $range = $nextlevel->min_points - $userlevel->min_points;
    $progress_pct = $range ? round(100 * ($userpoints - $userlevel->min_points) / $range) : 100;
} elseif ($userlevel && !$nextlevel) {
    $progress_pct = 100;
}

// Prepare data for Mustache
$tiers = [];
foreach ($levels as $key => $level) {
    if($level->img){
        $img = "assets/".$level->img;
    }else{
        $img = "assets/img".($key - 1).".png";
    }
    $tiers[] = [
        'id' => $level->id,
        'name' => $level->name,
        'icon' => $img, // path or FontAwesome class or SVG (handle in template)
        'active' => ($filter_levelid == $level->id),
        'color' => $level->color,
    ];
}
 $colorcode = get_config('local_leaderboard', 'defaultcertpointscolor');
//  echo $colorcode;
//  die;
 $icon = get_config('local_leaderboard', 'defaultpointsicon');
$users = [];
$rank = 1;
foreach ($leaderboard as $u) {
    $is_you = ($u->id == $userid);
    // Get the user's level/tier for display
    $user_tier = \local_leaderboard\api::get_user_level($u->id);
    $users[] = [
        'rank' => sprintf('%02d', $rank++),
        'name' => fullname($u),
        'points' => '<i class="'.($icon ? $icon :'fa fa-star').'" style="color: '. (($colorcode != "#ffffff" && !empty($colorcode)) ? $colorcode : "#fb0") .'"></i> '.$u->totalpoints.' Points',
        'country' => isset($u->country) ? $u->country : '',  // ensure your query returns this
        'tier' => $user_tier ? $user_tier->name : '-',
        'is_you' => $is_you,
    ];
}
$firstFiveUsers = array_slice($users, 0, 5);
$templatecontext = [
    'tiers' => $tiers,
    'users' => $firstFiveUsers,
    'user_rank' => array_search(true, array_column($firstFiveUsers, 'is_you')) !== false ? array_search(true, array_column($firstFiveUsers, 'is_you')) + 1 : '--',
    'user_points' => '<i class="'.($icon ? $icon :'fa fa-star').'" style="color: '. (($colorcode != "#ffffff" && !empty($colorcode)) ? $colorcode : "#fb0") .';"></i> '.$userpoints.' Points',
    'next_level' => $nextlevel ? $nextlevel->name : null,
    'points_to_next' => $points_to_next > 0 ? $points_to_next : 0,
    'progress_pct' => $progress_pct,
    'userlevel' => $userlevel ? $userlevel->name : '—',
    'is_country' => $is_country,
];

// Toggle links (retain current tier if selected)

$baseurl = new moodle_url('/local/leaderboard/index.php', $filter_levelid ? ['levelid' => $filter_levelid] : []);

$countryurl = new moodle_url('/local/leaderboard/index.php', array_merge($filter_levelid ? ['levelid' => $filter_levelid] : [], ['country' => $USER->country]));
 
$templatecontext['toggle_global_url'] = $baseurl->out();
 
$templatecontext['toggle_country_url'] = $countryurl->out();
 

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_leaderboard/leaderboard', $templatecontext);
echo $OUTPUT->footer();