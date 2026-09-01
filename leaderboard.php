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
require_login();
global $DB, $CFG, $USER;

$context = context_system::instance();
require_capability('local/leaderboard:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/leaderboard/leaderboard.php'));
$PAGE->set_title(get_string('leaderboard', 'local_leaderboard'));
// $PAGE->set_heading(get_string('leaderboard', 'local_leaderboard'));

// Constants for default/fallback values (previously hardcoded inline).
define('LOCAL_LEADERBOARD_DEFAULT_TIER_COLOR', '#eee');
define('LOCAL_LEADERBOARD_DEFAULT_ICON', 'fa fa-star');
define('LOCAL_LEADERBOARD_DEFAULT_COLOR', '#fb0');
define('LOCAL_LEADERBOARD_WHITE_COLOR', '#ffffff');

// Admin settings gear (for admins only)
$isadmin = has_capability('local/leaderboard:manage', $context);

require_once(__DIR__ . '/classes/api.php');
$userid = $USER->id;

// Filters from GET
$filter_country = optional_param('country', '', PARAM_ALPHAEXT);
$filter_levelid = optional_param('levelid', 0, PARAM_INT);

$is_country = ($filter_country === $USER->country); // or whatever logic you use

// Get levels
$levels = \local_leaderboard\api::get_levels();
$levelsbyid = [];
foreach ($levels as $l) {
    $levelsbyid[$l->id] = $l;
}

// Leaderboard (top 100, global or filtered)
$leaderboard = \local_leaderboard\api::get_leaderboard(
    array_filter(['country' => $filter_country, 'levelid' => $filter_levelid]), 100
);

// User points and level
$userpoints = \local_leaderboard\api::get_user_points($userid);
$userlevel = \local_leaderboard\api::get_user_level($userid);

$colorcode = get_config('local_leaderboard', 'defaultcertpointscolor');
$icon = get_config('local_leaderboard', 'defaultpointsicon');

$rank = 1;
$userrank = null;
$rows = [];
$i = 1;

foreach ($leaderboard as $u) {
    $tier = $levelsbyid[$u->levelid] ?? null;
    $rows[] = [
        'rank' => sprintf('%02d', $i),
        'fullname' => fullname($u),
        'isuser' => ($u->id == $userid),
        'hastier' => (bool) $tier,
        'tiername' => $tier ? format_string($tier->name) : '',
        'tiercolor' => ($tier && $tier->color) ? $tier->color : LOCAL_LEADERBOARD_DEFAULT_TIER_COLOR,
        'totalpoints' => (int) $u->totalpoints,
    ];
    $i++;
}

foreach ($leaderboard as $u) {
    if ($u->id == $userid) {
        $userrank = $rank;
        break;
    }
    $rank++;
}

$tabs = [[
    'name' => get_string('alllevels', 'local_leaderboard'),
    'active' => !$filter_levelid,
    'url' => '?' . ($filter_country ? 'country=' . urlencode($filter_country) . '&' : ''),
]];
foreach ($levels as $level) {
    $tabs[] = [
        'name' => format_string($level->name),
        'active' => $filter_levelid == $level->id,
        'url' => '?' . ($filter_country ? 'country=' . urlencode($filter_country) . '&' : '') . 'levelid=' . $level->id,
    ];
}

$data = [
    'isadmin' => $isadmin,
    'tabs' => $tabs,
    'cleanpgurltxt' => get_string('cleanpgurltxt', 'local_leaderboard'),
    'globalurl' => 'leaderboard.php',
    'countryurl' => 'leaderboard.php?country=' . $USER->country,
    'is_country' => $is_country,
    'userrank' => $userrank ?: get_string('norank', 'local_leaderboard'),
    'userlevelname' => $userlevel ? format_string($userlevel->name) : get_string('nolevel', 'local_leaderboard'),
    'userlevelcolor' => ($userlevel && $userlevel->color) ? $userlevel->color : LOCAL_LEADERBOARD_DEFAULT_TIER_COLOR,
    'userpoints' => $userpoints,
    'pointsstring' => get_string('points', 'local_leaderboard'),
    'icon' => $icon ?: LOCAL_LEADERBOARD_DEFAULT_ICON,
    'usercolorcode' => ($colorcode != LOCAL_LEADERBOARD_WHITE_COLOR && !empty($colorcode))
        ? $colorcode
        : LOCAL_LEADERBOARD_DEFAULT_COLOR,
    'rows' => $rows,
    'hasrows' => !empty($rows),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_leaderboard/admin_leaderboard', $data);
echo $OUTPUT->footer();