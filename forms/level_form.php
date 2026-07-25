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

require_once($CFG->libdir.'/formslib.php');

class local_leaderboard_level_form extends moodleform {
    public function definition() {
        global $DB, $PAGE;
        
        $mform = $this->_form;
        $custom = $this->_customdata ?? [];
        $edit = $custom['edit'];

        $leadlevel = $DB->get_record_sql("SELECT * FROM {local_leaderboard_levels} ORDER BY id DESC limit 1");
        // print_r($leadlevel); // value="'.$minpoint.'" disabled
        if($leadlevel){
            $minpoint = $leadlevel->max_points + 1;
        }else{
            $minpoint = 0;
        }
        
        // $mform->addElement('html', '<input type="hidden" name="lid" value="'.($edit->id ? $edit->id : 0).'">');
        if($edit->id > 0){
            $mform->addElement('hidden', 'lid', $edit->id );
        }

        // Name
        $mform->addElement('text', 'name', get_string('levelname', 'local_leaderboard'));
        $mform->setType('name', PARAM_TEXT);
        $mform->setDefault('name', ($edit->name ?? ""));
        $mform->addRule('name', null, 'required', null, 'client');

        // Min points
        $mform->addElement('text', 'min_points', get_string('minpoints', 'local_leaderboard'));
        $mform->setType('min_points', PARAM_INT);
        $mform->setDefault('min_points', ($edit->min_points ?? $minpoint));
        $mform->addRule('min_points', null, 'required', null, 'client');


        // Max points
        $mform->addElement('text', 'max_points', get_string('maxpoints', 'local_leaderboard'));
        $mform->setType('max_points', PARAM_INT);
        $mform->setDefault('max_points', ($edit->max_points ?? ""));
        $mform->addRule('max_points', null, 'required', null, 'client');

        // Color (simple hex or name)
        // $mform->addElement('text', 'color', get_string('color', 'local_leaderboard'));
        // $mform->setDefault('color', ($edit->color ?? ""));
        // $mform->setType('color', PARAM_TEXT);
        
        $mform->addElement('html', ' <div class="mb-3 row"><div class="col-md-3" ><label for="id_color">'. get_string("color", "local_leaderboard") .'</label></div>');
        $mform->addElement('html', '<div class="col-md-9" ><input type="color" id="id_color" name="color" value="'.($edit->color ?? "#778899").'"></div></div>');
      
        // level image
        $mform->addElement('filepicker','levelimage', "Level Image",null,['maxbytes' => $maxbytes,'accepted_types' => '.png',]);

        // Sort order
        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_leaderboard'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', ($edit->sortorder ?? ($leadlevel->sortorder + 1)));
        $mform->addRule('sortorder', null, 'required', null, 'client');

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $sorderval = $data['sortorder'];
        $sorder = $DB->get_record_sql("SELECT * FROM {local_leaderboard_levels} WHERE sortorder = ".$sorderval."");
        if($sorder && $_POST['lid'] == 0){
            $errors['sortorder'] = 'This order already exist.';
        }

        if ($data['max_points'] <= $data['min_points']) {
            $errors['max_points'] = 'You must add greater value.';
        }
        return $errors;
    }
}
