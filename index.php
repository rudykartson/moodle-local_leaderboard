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
require_capability('local/leaderboard:view', context_system::instance());
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot."/lib/filelib.php");
require_once($CFG->dirroot.'/local/leaderboard/classes/api.php');

// Constants for default/fallback values (previously hardcoded inline).
define('LOCAL_LEADERBOARD_IMG_PATH_PREFIX', 'assets/img');
define('LOCAL_LEADERBOARD_IMG_PATH_SUFFIX', '.png');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/leaderboard/index.php'));
$PAGE->set_title(get_string('toprenker', 'local_leaderboard'));
$PAGE->set_heading(get_string('toprenker', 'local_leaderboard'));

$userid = $USER->id;
$filter_levelid = optional_param('levelid', 0, PARAM_INT);
$filter_country = optional_param('country', '', PARAM_ALPHA);

// Get all levels/tiers
$levels = \local_leaderboard\api::get_levels();
$levelsbyid = [];
foreach ($levels as $l) {
    $levelsbyid[$l->id] = $l;
}

// Toggle for global/country
$is_country = !empty($filter_country);
$filters = [];
if ($is_country) {
    $filters['country'] = $USER->country;
}
if ($filter_levelid) {
    $filters['levelid'] = $filter_levelid;
}

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
} else if ($userlevel && !$nextlevel) {
    $progress_pct = 100;
}

// Prepare data for Mustache
$tiers = [];
foreach ($levels as $key => $level) {
    if ($level->img) {

        $fs = get_file_storage();

        $files = $fs->get_area_files(
            $context->id,
            'local_leaderboard',
            'levelimage',
            $level->img,     // itemid you stored on the record
            'filepath, filename',
            false            // exclude directory placeholder entries
        );

        $imageurl = '';
        if ($files) {
            $file = reset($files); // maxfiles was 1, so there's only ever one.
            $imageurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);
        }
        $img = $imageurl;

    } else {
        $img = LOCAL_LEADERBOARD_IMG_PATH_PREFIX . ($key - 1) . LOCAL_LEADERBOARD_IMG_PATH_SUFFIX;
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
$icon = get_config('local_leaderboard', 'defaultpointsicon');

$pointslabel = get_string('points', 'local_leaderboard');
$displayicon = $icon ? $icon : \local_leaderboard\api::DEFAULT_POINTS_ICON;
$displaycolor = (!empty($colorcode)) ? $colorcode : \local_leaderboard\api::DEFAULT_POINTS_COLOR;

/**
 * Build the HTML markup for a points badge.
 *
 * @param string $iconclass FontAwesome icon class to use.
 * @param string $color Colour (hex) for the icon.
 * @param int $points Number of points to display.
 * @param string $label Localised "Points" label.
 * @return string
 */
function local_leaderboard_get_points_badge($iconclass, $color, $points, $label) {
    
    if (!preg_match('/^[a-z0-9 _-]+$/i', (string)$iconclass)) {
        $iconclass = \local_leaderboard\api::DEFAULT_POINTS_ICON;
    }

    if (!preg_match('/^#[0-9a-f]{3,8}$/i', (string)$color)) {
        $color = \local_leaderboard\api::DEFAULT_POINTS_COLOR;
    }
    return [
        'icon'  => $iconclass,
        'color' => $color,
        'value' => (int)$points,
        'label' => $label,
    ];
}

$users = [];
$rank = 1;
foreach ($leaderboard as $u) {
    $is_you = ($u->id == $userid);
    // Get the user's level/tier for display
    $user_tier = \local_leaderboard\api::get_user_level($u->id);
    $users[] = [
        'rank' => sprintf('%02d', $rank++),
        'name' => fullname($u),
        'points' => local_leaderboard_get_points_badge($displayicon, $displaycolor, $u->totalpoints, $pointslabel),
        'country' => isset($u->country) ? $u->country : '',  // ensure your query returns this
        'tier' => $user_tier ? $user_tier->name : get_string('notier', 'local_leaderboard'),
        'is_you' => $is_you,
    ];
}
$first_five_users = array_slice($users, 0, 10);
$templatecontext = [
    'pgurl' => $PAGE->url,
    'cleanpgurltxt' => get_string('cleanpgurltxt', 'local_leaderboard'),
    'tiers' => $tiers,
    'users' => $first_five_users,
    'user_rank' => array_search(true, array_column($first_five_users, 'is_you')) !== false
        ? array_search(true, array_column($first_five_users, 'is_you')) + 1
        : get_string('norank', 'local_leaderboard'),
    'user_points' => local_leaderboard_get_points_badge($displayicon, $displaycolor, $userpoints, $pointslabel),
    'next_level' => $nextlevel ? $nextlevel->name : null,
    'points_to_next' => $points_to_next > 0 ? $points_to_next : 0,
    'progress_pct' => $progress_pct,
    'userlevel' => $userlevel ? $userlevel->name : get_string('nolevel', 'local_leaderboard'),
    'is_country' => $is_country,
];

// Toggle links (retain current tier if selected)

$baseurl = new moodle_url('/local/leaderboard/index.php', $filter_levelid ? ['levelid' => $filter_levelid] : []);

$countryurl = new moodle_url(
    '/local/leaderboard/index.php',
    array_merge($filter_levelid ? ['levelid' => $filter_levelid] : [], ['country' => $USER->country])
);

$templatecontext['toggle_global_url'] = $baseurl->out();

$templatecontext['toggle_country_url'] = $countryurl->out();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_leaderboard/leaderboard', $templatecontext);
echo $OUTPUT->footer();