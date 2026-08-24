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

namespace local_leaderboard;

defined('MOODLE_INTERNAL') || die();

class observer {
    /**
     * Award points when an activity is completed.
     */
    public static function activity_completed($event) {
        global $DB;
        $userid = $event->relateduserid ?: $event->userid;
        $cmid = $event->contextinstanceid;
        $courseid = $event->courseid;
        $cm = get_coursemodule_from_id(null, $cmid, $courseid);
        if (!$cm) return;
        $activitytype = $cm->modname;

        $rule = api::find_points_rule($cmid, $courseid, $activitytype, 'complete');
        $assignpoint = $DB->get_record('local_leaderboard_points',["userid"=>$userid,"courseid"=>$courseid,'cmid' => $cmid,'event' => 'complete']);
        if(!$assignpoint){
            if ($rule && $rule->points > 0) {
                $DB->insert_record('local_leaderboard_points', [
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'cmid' => $cmid,
                    'points' => $rule->points,
                    'event' => 'complete',
                    'timecreated' => time(),
                ]);
            }
        }


    }

    /**
     * Award points when an activity is started/viewed.
     */
    public static function activity_started($event) {
        global $DB;
        $userid = $event->userid;
        $cmid = $event->contextinstanceid;
        $courseid = $event->courseid;
        $cm = get_coursemodule_from_id(null, $cmid, $courseid);
        if (!$cm) return;
        $activitytype = $cm->modname;

        $rule = api::find_points_rule($cmid, $courseid, $activitytype, 'start');

        $assignpoint = $DB->get_record('local_leaderboard_points',["userid"=>$userid,"courseid"=>$courseid,'cmid' => $cmid,'event' => 'start']);
        if(!$assignpoint){
            if ($rule && $rule->points > 0) {
                $DB->insert_record('local_leaderboard_points', [
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'cmid' => $cmid,
                    'points' => $rule->points,
                    'event' => 'start',
                    'timecreated' => time(),
                ]);
            }
        }

    }

    /**
     * Called when a course is viewed.
     */
    public static function course_viewed(\core\event\course_viewed $event) {
        global $DB, $USER;

        $courseid = $event->courseid;
        $userid = $event->userid;
        $eventname = 'start';
        $activitytype = null;
        $cmid = null;

        $rule = api::find_points_rule($cmid, $courseid, $activitytype, $eventname);


        $assignpoint = $DB->get_record('local_leaderboard_points',["userid"=>$userid,"courseid"=>$courseid,'cmid' => $cmid,'event' => $eventname]);
        if(!$assignpoint){
             if ($rule && $rule->points > 0) {
                $DB->insert_record('local_leaderboard_points', [
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'cmid' => $cmid,
                    'points' => $rule->points,
                    'event' => $eventname,
                    'timecreated' => time(),
                ]);
            }
        }
    }

    /**
     * Called when a course is completed.
     */
    public static function course_completed(\core\event\course_completed $event) {
        global $DB, $USER;
        //-------------log for completion-----------------
        // $data = print_r($event->get_data(), true);
        // $logfile = '/var/www/html/moodle/local/leaderboard/logs/course_complete.log';
        // // Make sure directory exists and is writable
        // if (!file_exists(dirname($logfile))) {
        //     mkdir(dirname($logfile), 0777, true);
        // }
        // file_put_contents($logfile, date('Y-m-d H:i:s') . " - Course Completed Event:\n" . $data . "\n\n", FILE_APPEND);
        
        $userid = $event->relateduserid;
        $courseid = $event->courseid;
        $event = 'complete';
        $activitytype = '';
        $cmid = '';
        $rule = api::find_points_rule($cmid, $courseid, $activitytype, $event);

        $assignpoint = $DB->get_record('local_leaderboard_points',["userid"=>$userid,"courseid"=>$courseid,'cmid' => "",'event' => $event]);
        if(!$assignpoint){
             if ($rule && $rule->points > 0) {
                $DB->insert_record('local_leaderboard_points', [
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'cmid' => $cmid,
                    'points' => $rule->points,
                    'event' => $event,
                    'timecreated' => time(),
                ]);
            }
        }

    }

    // public static function acticity_h5p_view(\core_h5p\event\h5p_viewed $event) {
    //     global $DB, $USER;
    //     //-------------log for completion-----------------
    //     $data = print_r($event->get_data(), true);
    //     $logfile = '/var/www/html/moodle/local/leaderboard/logs/course_complete.log';
    //     // Make sure directory exists and is writable
    //     if (!file_exists(dirname($logfile))) {
    //         mkdir(dirname($logfile), 0777, true);
    //     }
    //     file_put_contents($logfile, date('Y-m-d H:i:s') . " - Course Completed Event:\n" . $data . "\n\n", FILE_APPEND);
    // }
    
    public static function loggedinn(\core\event\user_loggedin $event) {
        global $DB;
    
        $userid = $event->get_data()['userid'];
    
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    
        // Detect first login via OAuth2
        if ($user->lastlogin == 0 && $user->auth === 'oauth2') {
    
            // Auto-accept all required policies
            if (class_exists('\tool_policy\api')) {
                \tool_policy\api::accept_policies($userid);
            }
    
            redirect(
                '/myaccount',
                "Please complete your profile.",
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }
    }

    
}
