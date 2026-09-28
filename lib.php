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

function local_leaderboard_extend_navigation(global_navigation $nav) {
    $context = context_system::instance();
    if (has_capability('local/leaderboard:view', $context)) {
        $leaderboardurl = new moodle_url('/local/leaderboard/leaderboard.php');
        $nav->add(
            get_string('leaderboard', 'local_leaderboard'),
            $leaderboardurl,
            navigation_node::TYPE_CUSTOM,
            null, null, new pix_icon('i/rank', '')
        );
    }
}


/**
 * Serve level images stored in the 'levelimage' filearea through pluginfile.php.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if file not served (Moodle sends 404 itself)
 */
function local_leaderboard_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // This plugin stores level images at system context.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea !== 'levelimage') {
        return false;
    }

    // No login/capability gate here — level badge images are shown on a
    // public-facing leaderboard. Tighten this with require_login()/
    // require_capability() if that's not the case for your site.

    $itemid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_leaderboard', 'levelimage', $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    // Level images rarely change once uploaded; cache for a day.
    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}