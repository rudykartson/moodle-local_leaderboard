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

/**
 * Cleanup all leaderboard tables and data.
 */
function xmldb_local_leaderboard_uninstall() {
    global $DB;

    // Drop points, rules, levels tables if they exist.
    $tables = [
        'local_leaderboard_points',
        'local_leaderboard_rules',
        'local_leaderboard_levels'
    ];

    foreach ($tables as $table) {
        if ($DB->get_manager()->table_exists($table)) {
            $DB->get_manager()->drop_table(new xmldb_table($table));
        }
    }

    return true;
}
