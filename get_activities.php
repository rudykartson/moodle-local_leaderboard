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

$courseid = required_param('courseid', PARAM_INT);

if (!has_capability('local/leaderboard:manage', context_system::instance())) {
    throw new required_capability_exception(context_system::instance(), 'local/leaderboard:manage', 'nopermissions', '');
}

$modules = get_course_mods($courseid);
$result = [];

if ($modules) {
    foreach ($modules as $cm) {
        // Only show visible modules and modules with names
        if ($cm->visible && !empty($cm->name)) {
            $result[] = [
                'cmid'    => $cm->id,
                'name'    => format_string($cm->name),
                'modname' => $cm->modname,
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode(array_values($result));
exit;
