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

function local_leaderboard_extends_navigation(global_navigation $nav) {
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
