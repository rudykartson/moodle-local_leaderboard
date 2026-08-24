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

class api {

    /**
     * Find the most specific points rule for an activity.
     */
    public static function find_points_rule($cmid, $courseid, $activitytype, $event) {
        global $DB;
        
        // echo '<pre>------';
        // print_r([$cmid, $courseid, $activitytype, $event]);
        // die;
        
        // Activity level rule (most specific)
        $rule = $DB->get_record('local_leaderboard_rules', [
            'scope' => 'activity', 'scopeid' => $cmid, 'activitytype' => $activitytype, 'event' => $event
        ]);
        if ($rule) return $rule;

        // Course level
        $rule = $DB->get_record('local_leaderboard_rules', [
            'scope' => 'course', 'scopeid' => $courseid, 'activitytype' => $activitytype, 'event' => $event
        ]);
        if ($rule) return $rule;

        // Platform level (scopeid = 0)
        $rule = $DB->get_record('local_leaderboard_rules', [
            'scope' => 'platform', 'scopeid' => 0, 'activitytype' => $activitytype, 'event' => $event
        ]);
        return $rule;
    }

    /**
     * Get total points for a user.
     */
    public static function get_user_points($userid) {
        global $DB;
        $refcode = 'refusrid'.$userid;
        $refuser = $DB->get_record_sql('SELECT COUNT(DISTINCT uid.userid) AS total_users FROM {user_info_data} uid JOIN {user_info_field} uif ON uif.id = uid.fieldid WHERE uif.shortname = :uifshortname AND uid.data = :uidata',["uifshortname"=>"refuserid","uidata"=>$refcode]);
        
        $sum = $DB->get_field_sql("SELECT SUM(points) FROM {local_leaderboard_points} WHERE userid = ?", [$userid]);
        
        if($refuser->total_users > 0){
            $pntvalue = get_config('local_leaderboard', 'referralpoints');
            $pnt = $refuser->total_users * $pntvalue;
            return $sum ? intval($sum + $pnt) : 0;
        }else{
            return $sum ? intval($sum) : 0;
        }
    }

    /**
     * Get user's current level/tier object (from local_leaderboard_levels).
     */
    public static function get_user_level($userid) {
        global $DB;
        $points = self::get_user_points($userid);
        return $DB->get_record_sql(
            "SELECT * FROM {local_leaderboard_levels} WHERE :pts BETWEEN min_points AND max_points ORDER BY sortorder DESC",
            ['pts' => $points]
        );
    }

    /**
     * Get all level/tier definitions.
     */
    public static function get_levels() {
        global $DB;
        return $DB->get_records('local_leaderboard_levels', null, 'sortorder');
    }

    /**
     * Get leaderboard array: [{id, firstname, lastname, country, totalpoints, levelid}]
     * Filters: ['country'=>, 'levelid'=>] (all optional)
     */
    public static function get_leaderboard($filters = [], $limit = 100) {
        global $DB;

        $params = [];
        $where = "u.deleted = 0";
        if (!empty($filters['country'])) {
            $where .= " AND u.country = :country";
            $params['country'] = $filters['country'];
        }

        // Aggregate points per user in one query.
        $sql = "SELECT u.id, u.firstname, u.lastname, u.country,
                       SUM(lp.points) AS totalpoints,
                       MAX(lp.timecreated) AS latesttime
                FROM {user} u
                JOIN {local_leaderboard_points} lp ON lp.userid = u.id
                WHERE $where
                GROUP BY u.id, u.firstname, u.lastname, u.country
                HAVING SUM(lp.points) > 0
                ORDER BY totalpoints DESC, u.firstname, u.lastname";

        $users = $DB->get_records_sql($sql, $params);

        // Fetch referral counts for ALL users in a single query (fixes N+1).
        $referralcounts = self::get_referral_counts();
        $referralpointsvalue = (int) get_config('local_leaderboard', 'referralpoints');

        foreach ($users as $user) {
            if ($referralpointsvalue > 0 && !empty($referralcounts[$user->id])) {
                $user->totalpoints += $referralcounts[$user->id] * $referralpointsvalue;
            }
        }

        // Attach level for each user (for tier filter).
        $levels = self::get_levels();
        foreach ($users as $user) {
            $user->levelid = null;
            foreach ($levels as $level) {
                if ($user->totalpoints >= $level->min_points && $user->totalpoints <= $level->max_points) {
                    $user->levelid = $level->id;
                    break;
                }
            }
        }

        // Level filter.
        if (!empty($filters['levelid'])) {
            $users = array_filter($users, function($u) use ($filters) {
                return $u->levelid == $filters['levelid'];
            });
        }

        // Referral points can change totals, so re-sort after adding them.
        $userlist = array_values($users);
        usort($userlist, function($a, $b) {
            if ($b->totalpoints != $a->totalpoints) {
                return $b->totalpoints <=> $a->totalpoints;
            }
            return $a->latesttime <=> $b->latesttime;
        });

        if ($limit) {
            $userlist = array_slice($userlist, 0, $limit);
        }

        return $userlist;
    }

    /**
     * Get referral counts for every referrer in a single query.
     *
     * Custom profile field 'refuserid' stores values like "refusridX" on the
     * referred user's profile, where X is the referring user's id.
     *
     * @return array Map of referrer userid => number of users they referred.
     */
    private static function get_referral_counts() {
        global $DB;

        $sql = "SELECT uid.data AS refcode, COUNT(DISTINCT uid.userid) AS total_users
                FROM {user_info_data} uid
                JOIN {user_info_field} uif ON uif.id = uid.fieldid
                WHERE uif.shortname = :shortname
                GROUP BY uid.data";

        $records = $DB->get_records_sql($sql, ['shortname' => 'refuserid']);

        $counts = [];
        foreach ($records as $record) {
            if (preg_match('/^refusrid(\d+)$/', $record->refcode, $matches)) {
                $counts[(int) $matches[1]] = (int) $record->total_users;
            }
        }

        return $counts;
    }
    
}