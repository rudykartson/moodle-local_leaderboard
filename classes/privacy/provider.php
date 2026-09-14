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

namespace local_leaderboard\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata about this plugin's stored data.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection
     */

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_leaderboard_points',
            [
                'userid' => 'privacy:metadata:local_leaderboard_points:userid',
                'courseid' => 'privacy:metadata:local_leaderboard_points:courseid',
                'cmid' => 'privacy:metadata:local_leaderboard_points:cmid',
                'points' => 'privacy:metadata:local_leaderboard_points:points',
                'event' => 'privacy:metadata:local_leaderboard_points:event',
                'timecreated' => 'privacy:metadata:local_leaderboard_points:timecreated',
            ],
            'privacy:metadata:local_leaderboard_points'
        );

        return $collection;
    }
    // public static function get_metadata(collection $collection): collection {
    //     $tables = [
    //             'local_leaderboard_points' => [
    //                 'userid',
    //                 'courseid',
    //                 'cmid',
    //                 'points',
    //                 'event',
    //                 'timecreated',
    //             ],
    //     ];

    //     foreach ($tables as $table => $fields) {
    //         $fielddata = [];
    //         foreach ($fields as $field) {
    //             $fielddata[$field] = get_string('privacy:metadata:' . $table . ':' . $field, 'local_leaderboard');
    //         }
    //         $collection->add_database_table(
    //             $table,
    //             $fielddata,
    //             get_string('privacy:metadata:' . $table, 'local_leaderboard')
    //         );
    //     }

    //     return $collection;
    // }



    /**
     * Get the contexts where this user has data.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {

        global $DB;

        $contextlist = new contextlist();

        $sql = "
            SELECT DISTINCT ctx.id
              FROM {local_leaderboard_points} lp
              JOIN {context} ctx
                ON ctx.contextlevel = :contextlevel
               AND ctx.instanceid = lp.courseid
             WHERE lp.userid = :userid
        ";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data.
     */
    public static function export_user_data(
        approved_contextlist $contextlist
    ) {

        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {

            $records = $DB->get_records(
                'local_leaderboard_points',
                [
                    'userid' => $userid,
                    'courseid' => $context->instanceid,
                ]
            );

            if (!$records) {
                continue;
            }

            writer::with_context($context)->export_data(
                [],
                (object) [
                    'leaderboard_points' => array_values($records),
                ]
            );
        }
    }

    /**
     * Delete all data for a user.
     */
    public static function delete_data_for_user(
        approved_contextlist $contextlist
    ) {

        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {

            $DB->delete_records(
                'local_leaderboard_points',
                [
                    'userid' => $userid,
                    'courseid' => $context->instanceid,
                ]
            );
        }
    }

    /**
     * Delete all data for all users in a context.
     */
    public static function delete_data_for_all_users_in_context(
        \context $context
    ) {

        global $DB;

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $DB->delete_records(
            'local_leaderboard_points',
            [
                'courseid' => $context->instanceid,
            ]
        );
    }

    /**
     * Find users who have data in this context.
     */
    public static function get_users_in_context(
        userlist $userlist
    ) {

        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $sql = "
            SELECT userid
              FROM {local_leaderboard_points}
             WHERE courseid = :courseid
        ";

        $params = [
            'courseid' => $context->instanceid,
        ];

        $userlist->add_from_sql(
            'userid',
            $sql,
            $params
        );
    }

    /**
     * Delete data for multiple users in a context.
     */
    public static function delete_data_for_users(
        approved_userlist $userlist
    ) {

        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal(
            $userlist->get_userids(),
            SQL_PARAMS_NAMED
        );

        $params = array_merge(
            ['courseid' => $context->instanceid],
            $userparams
        );

        $DB->delete_records_select(
            'local_leaderboard_points',
            "courseid = :courseid AND userid {$usersql}",
            $params
        );
    }


    // Privacy API methods here.
}