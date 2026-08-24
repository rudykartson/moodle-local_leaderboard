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

$string['pluginname'] = 'Leaderboard';
$string['leaderboard'] = 'Leaderboard';
$string['leaderboard:view'] = 'View leaderboard';
$string['leaderboard:manage'] = 'Manage leaderboard';
$string['privacy:metadata:local_leaderboard_points'] = 'Leaderboard stores user points and related information.';
$string['privacy:metadata:local_leaderboard_points:userid'] = 'User ID of the user whose points are stored.';
$string['privacy:metadata:local_leaderboard_points:courseid'] = 'ID of the course to which the points belong.';
$string['privacy:metadata:local_leaderboard_points:cmid'] = 'ID of the course module to which the points belong.';
$string['privacy:metadata:local_leaderboard_points:points'] = 'The points awarded to the user.';
$string['privacy:metadata:local_leaderboard_points:event'] = 'The event stores the trigger status — either started or completed — that triggered the point award.';
$string['privacy:metadata:local_leaderboard_points(timecreated'] = 'The time when the points were created.';
$string['manage'] = 'Leaderboard Points Rules';
$string['levels'] = 'Leaderboard Levels/Tiers';
$string['toprenker'] = 'Leaderboard Top 10';
$string['add_pts_rule'] = 'Add Points Rule';
$string['referralpnt'] = 'Referral Points';
$string['referralpnt_msg'] = 'Referral user added reward points';
$string['defaultreferralpoints'] = '10';
$string['existingrules'] = 'Existing Points Rules';
$string['points'] = 'Points';
$string['level'] = 'Level/Tier';
$string['cleanpgurltxt'] = 'Clear Filter';
$string['rank'] = 'Rank';
$string['you'] = 'You';
$string['country'] = 'Country';
$string['global'] = 'Global';
$string['actions'] = 'Actions';
$string['delete'] = 'Delete';
$string['edit'] = 'Edit';
$string['addnewlevel'] = 'Add New Level/Tier';
$string['levelname'] = 'Level/Tier Name';
$string['minpoints'] = 'Min Points';
$string['maxpoints'] = 'Max Points';
$string['color'] = 'Color';
$string['sortorder'] = 'Sort Order';
$string['save'] = 'Save';
$string['cancel'] = 'Cancel';
$string['managelevels'] = 'Manage Levels/Tiers';
$string['addpointsrule'] = 'Add Points Rule';
$string['event'] = 'Event';
$string['activitytype'] = 'Activity Type';
$string['scope'] = 'Scope';
$string['course'] = 'Course';
$string['activity'] = 'Activity';
$string['platform'] = 'Platform-wide';
$string['existingrules'] = 'Existing Points Rules';
$string['existinglevels'] = 'Existing Levels/Tiers';
$string['required'] = 'Required';
$string['areyousuredelete'] = 'Are you sure you want to delete?';
$string['exportcsv'] = 'Export CSV';

$string['default_points_color'] = 'Points Color Code';
$string['default_points_color_desc'] = 'Choose color for leaderboard points';
$string['default_points_color_code'] = '#fb0';

$string['default_points_icon'] = 'Points Icon Names';
$string['default_points_icon_desc'] = 'Add Points icon name from Font Awesome Icons';
$string['default_points_icon_name'] = 'fa fa-star';


$string['referralsetup_heading'] = 'Referral points setup';
$string['referralsetup_desc'] = 'To use the referral points functionality, you must first create a custom user profile field with the exact settings below. Referral points will not be calculated until this field exists.';
$string['referralsetup_shortname'] = 'Short name';
$string['referralsetup_type'] = 'Profil Type';
$string['referralsetup_name'] = 'Name';
$string['referralsetup_required'] = 'Is this field required';
$string['referralsetup_locked'] = 'Is this field locked';
$string['referralsetup_unique'] = 'Should the data be unique';
$string['referralsetup_signup'] = 'Display on signup page';
$string['referralsetup_visible'] = 'Who is this field visible to';
$string['referralsetup_visible_everyone'] = 'Visible to everyone';
$string['referralsetup_createlink'] = 'Create this profile field now';