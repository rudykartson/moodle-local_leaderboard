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

class local_leaderboard_renderer extends plugin_renderer_base {

    /**
     * Render the leaderboard page using a Mustache template.
     * @param array $data
     * @return string
     */
    public function render_leaderboard($data) {
        return $this->render_from_template('local_leaderboard/leaderboard', $data);
    }

    /**
     * Render the user popup (level up).
     * @param array $data
     * @return string
     */
    public function render_userpopup($data) {
        return $this->render_from_template('local_leaderboard/userpopup', $data);
    }
}