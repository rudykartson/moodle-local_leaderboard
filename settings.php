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

if ($hassiteconfig) {

    // Plugin name from language file.
    $pluginname = get_string('pluginname', 'local_leaderboard');

    // Create a new admin category for the plugin under 'localplugins'.
    $ADMIN->add('localplugins', new admin_category(
        'local_leaderboard_category',
        $pluginname
    ));

    // Create the settings page and add it to the new category.
    $settings = new admin_settingpage('local_leaderboard_settings', $pluginname);

    // // Add a heading to the settings page.
    // $settings->add(new admin_setting_heading(
    //     'local_leaderboard_mainheading',
    //     '',
    //     get_string('pageheading', 'local_leaderboard')
    // ));

    // Add a color picker setting.
    $settings->add(new admin_setting_configcolourpicker(
        'local_leaderboard/defaultcertpointscolor',
        get_string('default_points_color', 'local_leaderboard'),
        get_string('default_points_color_desc', 'local_leaderboard'),
        '#fb0'
    ));

    // Icon setting (let admin enter an icon class name, e.g., 'fa fa-star').
    $settings->add(new admin_setting_configtext(
        'local_leaderboard/defaultpointsicon',
        get_string('default_points_icon', 'local_leaderboard'),
        get_string('default_points_icon_desc', 'local_leaderboard'),
        'fa fa-star' // default icon class
    ));
    
    // Icon setting (let admin enter an icon class name, e.g., 'fa fa-star').
    $settings->add(new admin_setting_configtext(
        'local_leaderboard/referralpoints',
        "Referral Points",
        "Referral user added reward points",
        '10' // default icon class
    ));

    // Add the settings page under the plugin's category.
    $ADMIN->add('local_leaderboard_category', $settings);

    // Add external pages for management and levels.
    $ADMIN->add('local_leaderboard_category', new admin_externalpage(
        'local_leaderboard_manage',
        get_string('manage', 'local_leaderboard'),
        new moodle_url('/local/leaderboard/manage_rules.php'),
        'local/leaderboard:manage'
    ));

    $ADMIN->add('local_leaderboard_category', new admin_externalpage(
        'local_leaderboard_levels',
        get_string('levels', 'local_leaderboard'),
        new moodle_url('/local/leaderboard/manage_level.php'),
        'local/leaderboard:manage'
    ));
}
