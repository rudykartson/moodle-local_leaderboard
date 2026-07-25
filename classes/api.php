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
        $refuser = $DB->get_record_sql('SELECT COUNT(DISTINCT uid.userid) AS total_users FROM mdl_user_info_data uid JOIN mdl_user_info_field uif ON uif.id = uid.fieldid WHERE uif.shortname = :uifshortname AND uid.data = :uidata',["uifshortname"=>"refuserid","uidata"=>$refcode]);
        
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
        // if (!empty($filters['country'])) {
        //     $where .= " AND u.country = ?";
        //     $params[] = $filters['country'];
        // }

        // Get points
        $sql = "SELECT u.id, u.firstname, u.lastname, u.country,
                       SUM(lp.points) AS totalpoints
                FROM {user} u
                JOIN {local_leaderboard_points} lp ON lp.userid = u.id
                WHERE $where
                GROUP BY u.id, u.firstname, u.lastname, u.country
                HAVING SUM(lp.points) > 0
                ORDER BY totalpoints DESC, u.firstname, u.lastname";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $users = $DB->get_records_sql($sql, $params);

        // Attach level for each user (for tier filter)
        $levels = self::get_levels();
        foreach ($users as &$user) {
            $user->levelid = null;
            foreach ($levels as $level) {
                if ($user->totalpoints >= $level->min_points && $user->totalpoints <= $level->max_points) {
                    $user->levelid = $level->id;
                    break;
                }
            }
        }
        unset($user);

        // Level filter
        if (!empty($filters['levelid'])) {
            $users = array_filter($users, function($u) use ($filters) {
                return $u->levelid == $filters['levelid'];
            });
        }


        $sqll = "SELECT * FROM {local_leaderboard_points}";
        $rows = $DB->get_records_sql($sqll);
        
        $sorusers = [];

        foreach ($rows as $row) {
            $row = (array)$row;
            $uid = $row['userid'];
            $points = $row['points'];
            $time = $row['timecreated'];

            if (!isset($sorusers[$uid])) {
                $sorusers[$uid] = [
                    'userid' => $uid,
                    'total_points' => $points,
                    'latest_time' => $time
                ];
            } else {
                $sorusers[$uid]['total_points'] += $points;
                if ($time > $sorusers[$uid]['latest_time']) {
                    $sorusers[$uid]['latest_time'] = $time;
                }
            }
            
            
        }
        
        foreach ($sorusers as $val) {
            $uiid = $val['userid'];
            $uiidpnt = $val['total_points'];
            
            $refcode = 'refusrid'.$uiid;
            $refuser = $DB->get_record_sql('SELECT COUNT(DISTINCT uid.userid) AS total_users FROM mdl_user_info_data uid JOIN mdl_user_info_field uif ON uif.id = uid.fieldid WHERE uif.shortname = :uifshortname AND uid.data = :uidata',["uifshortname"=>"refuserid","uidata"=>$refcode]);
                 
            if (!empty($refuser) && !empty($refuser->total_users) && $refuser->total_users > 0) {
            
                $pntvalue = (int) get_config('local_leaderboard', 'referralpoints');
                $pnt      = (int) $refuser->total_users * $pntvalue;
                
                $sorusers[$uiid]['total_points'] =  ($uiidpnt + $pnt);
                
            }
            
        }
        
        $userList = array_values($sorusers);



        // Step 3: Sort by total_points DESC, then latest_time ASC (for desired 17,16,3 order)
        usort($userList, function($a, $b) {
            if ($b['total_points'] != $a['total_points']) {
                return $b['total_points'] <=> $a['total_points'];
            }
            return $a['latest_time'] <=> $b['latest_time']; // prefer earlier time
        });

        
        
        foreach ($users as $val) {
            
            
            $uiid1 = $val->id;
            $uiidpnt1 = $val->totalpoints;
            
            $refcode1 = 'refusrid'.$uiid1;
            $refuser1 = $DB->get_record_sql('SELECT COUNT(DISTINCT uid.userid) AS total_users FROM mdl_user_info_data uid JOIN mdl_user_info_field uif ON uif.id = uid.fieldid WHERE uif.shortname = :uifshortname AND uid.data = :uidata',["uifshortname"=>"refuserid","uidata"=>$refcode1]);
                 
            if (!empty($refuser1) && !empty($refuser1->total_users) && $refuser1->total_users > 0) {
            
                $pntvalue1 = (int) get_config('local_leaderboard', 'referralpoints');
                $pnt1      = (int) $refuser1->total_users * $pntvalue1;
                
                $users[$uiid1]->totalpoints =  ($uiidpnt1 + $pnt1);
                
            }
        
        }

        $newsorusers = [];
        if($users){
            foreach ($userList as $uList) {
                if($index = $uList['userid']){
                    if($users[$index]){
                        if (!empty($filters['country']) && $users[$index]->country == $filters['country']) {
                            $newsorusers[$index] = $users[$index];
                        }
                        if (empty($filters['country'])) {
                            $newsorusers[$index] = $users[$index];
                        }
                    }
                }
            }
        }



        

        return $newsorusers;
        // return $users;
    }
}