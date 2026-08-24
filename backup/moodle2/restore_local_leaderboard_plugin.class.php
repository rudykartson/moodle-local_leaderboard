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

class restore_local_leaderboard_plugin extends restore_local_plugin {
    protected function define_module_plugin_structure() {
        return [new restore_path_element('point', $this->get_pathfor('/points_list/point'))];
    }

    public function process_point($data) {
        global $DB;
        $data = (object) $data;
        $data->userid   = $this->get_mappingid('user', $data->userid);
        $data->courseid = $this->task->get_courseid();   // new courseid
        $data->cmid     = $this->task->get_moduleid();   // new cmid
        $DB->insert_record('local_leaderboard_points', $data);
    }
}