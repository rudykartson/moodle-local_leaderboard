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
 * Dynamic behaviour for the points rule form (scope/course/activity selects).
 *
 * @module     local_leaderboard/rule_form
 * @copyright  2026 Rudraksh Batra <batra.rudraksh@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {

    let scopeEl;
    let courseEl;
    let cmidEl;
    let activityTypeEl;
    let activities;
    let activitytypes;

    /**
     * Disable or enable a form element, resetting its value when disabled.
     *
     * @param {HTMLElement} el
     * @param {Boolean} disabled
     */
    const setDisabled = (el, disabled) => {
        el.disabled = disabled;
        if (disabled) {
            el.value = '0';
        }
    };

    /**
     * Populate a select element with options.
     *
     * @param {HTMLSelectElement} select
     * @param {Object} items Map of value => label.
     */
    const populateSelect = (select, items) => {
        select.replaceChildren();

        const defaultOpt = document.createElement('option');
        defaultOpt.value = '0';
        defaultOpt.text = '-- Select --';
        select.appendChild(defaultOpt);

        Object.entries(items).forEach(([val, label]) => {
            const opt = document.createElement('option');
            opt.value = val;
            opt.text = label;
            select.appendChild(opt);
        });
    };
    /**
     * Update the course/activity inputs based on the selected scope.
     */
    const updateFormFromScope = () => {
        const scope = scopeEl.value;

        if (scope === 'platform') {
            setDisabled(courseEl, true);
            setDisabled(cmidEl, true);
            populateSelect(cmidEl, {});
            activityTypeEl.value = '';
        } else if (scope === 'course') {
            setDisabled(courseEl, false);
            setDisabled(cmidEl, true);
            populateSelect(cmidEl, {});
            activityTypeEl.value = '';
        } else if (scope === 'activity') {
            setDisabled(courseEl, false);
            const selectedCourseId = courseEl.value;
            if (selectedCourseId && activities[selectedCourseId]) {
                populateSelect(cmidEl, activities[selectedCourseId]);
                setDisabled(cmidEl, false);
            } else {
                setDisabled(cmidEl, true);
                populateSelect(cmidEl, {});
            }
        }
    };

    /**
     * Update the hidden activity type field when an activity is selected.
     */
    const updateActivityType = () => {
        const selectedCmid = cmidEl.value;
        activityTypeEl.value = activitytypes[selectedCmid] || '';
    };

    /**
     * Initialise the module.
     *
     * @param {Object} activitymap Map of course id => {cmid: name}.
     * @param {Object} activitytypemap Map of cmid => modname.
     */
    const init = (activitymap, activitytypemap) => {
        scopeEl = document.getElementById('id_scope');
        courseEl = document.getElementById('id_courseid');
        cmidEl = document.getElementById('id_cmid');
        activityTypeEl = document.getElementById('id_activitytype');

        activities = activitymap;
        activitytypes = activitytypemap;

        scopeEl.addEventListener('change', () => {
            updateFormFromScope();
            updateActivityType();
        });

        courseEl.addEventListener('change', () => {
            if (scopeEl.value === 'activity') {
                const selectedCourseId = courseEl.value;
                if (activities[selectedCourseId]) {
                    populateSelect(cmidEl, activities[selectedCourseId]);
                    setDisabled(cmidEl, false);
                } else {
                    setDisabled(cmidEl, true);
                    populateSelect(cmidEl, {});
                }
            }
            updateActivityType();
        });

        cmidEl.addEventListener('change', () => {
            updateActivityType();
        });

        // Set the correct initial state on page load.
        updateFormFromScope();
        updateActivityType();
    };

    return {
        init: init
    };
});