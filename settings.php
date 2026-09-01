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

// Technical identifiers (NOT translatable — must match the exact profile
// field shortname/name configured in Moodle, so they stay as constants
// rather than get_string() calls).
define('LOCAL_LEADERBOARD_REFERRAL_FIELD_SHORTNAME', 'refuserid');
define('LOCAL_LEADERBOARD_REFERRAL_FIELD_NAME', 'Refuserid');

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

    // Add a color picker setting.
    $settings->add(new admin_setting_configcolourpicker(
        'local_leaderboard/defaultcertpointscolor',
        get_string('default_points_color', 'local_leaderboard'),
        get_string('default_points_color_desc', 'local_leaderboard'),
        get_string('default_points_color_code', 'local_leaderboard'),
    ));

    // Icon setting (let admin enter an icon class name, e.g., 'fa fa-star').
    $settings->add(new admin_setting_configtext(
        'local_leaderboard/defaultpointsicon',
        get_string('default_points_icon', 'local_leaderboard'),
        get_string('default_points_icon_desc', 'local_leaderboard'),
        get_string('default_points_icon_name', 'local_leaderboard'),
    ));

    $settings->add(new admin_setting_configtext(
        'local_leaderboard/referralpoints',
        get_string('referralpnt', 'local_leaderboard'),
        get_string('referralpnt_msg', 'local_leaderboard'),
        get_string('defaultreferralpoints', 'local_leaderboard')
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

    // ------------------------------------------------------------------
    // Referral points setup instructions.
    // ------------------------------------------------------------------
    $referralinfo = html_writer::start_tag('div', ['class' => 'alert alert-info']);

    $referralinfo .= html_writer::tag('p',
        get_string('referralsetup_desc', 'local_leaderboard'));

    $referralinfo .= html_writer::start_tag('ul');
    
    $referralinfo .= html_writer::tag('li',
        get_string('referralsetup_type', 'local_leaderboard',
            html_writer::tag('code', get_string('referralsetup_fieldtype_text', 'local_leaderboard'))));

    $referralinfo .= html_writer::tag('li',
        get_string('referralsetup_shortname', 'local_leaderboard',
            html_writer::tag('code', LOCAL_LEADERBOARD_REFERRAL_FIELD_SHORTNAME)));

    $referralinfo .= html_writer::tag('li',
        get_string('referralsetup_name', 'local_leaderboard',
            html_writer::tag('code', LOCAL_LEADERBOARD_REFERRAL_FIELD_NAME)));

    $referralinfo .= html_writer::tag('li',
        get_string('referralsetup_required', 'local_leaderboard', get_string('no')));

    $referralinfo .= html_writer::tag('li',
        get_string('referralsetup_locked', 'local_leaderboard', get_string('no')));

    $referralinfo .= html_writer::tag('li',
        get_string('referralsetup_unique', 'local_leaderboard', get_string('no')));

    $referralinfo .= html_writer::tag('li',
        get_string('referralsetup_signup', 'local_leaderboard', get_string('yes')));

    $referralinfo .= html_writer::tag('li',
        get_string('referralsetup_visible', 'local_leaderboard',
            get_string('referralsetup_visible_everyone', 'local_leaderboard')));

    $referralinfo .= html_writer::end_tag('ul');

    $referralinfo .= html_writer::tag('p',
        html_writer::link(
            new moodle_url('/user/profile/index.php'),
            get_string('referralsetup_createlink', 'local_leaderboard')
        )
    );
    $referralinfo .= html_writer::end_tag('div');

    $settings->add(new admin_setting_heading(
        'local_leaderboard/referralsetup',
        get_string('referralsetup_heading', 'local_leaderboard'),
        $referralinfo
    ));

}