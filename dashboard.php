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
 * Strings for component 'block_disea_dashboard'
 *
 * @package   block_disea_dashboard
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $CFG, $PAGE, $OUTPUT;

$PAGE->set_url(new moodle_url('/blocks/disea_dashboard/dashboard.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('pluginname', 'block_disea_dashboard'));

//Get Course ID from url to be able to redirect
$courseid = optional_param('id',NULL, PARAM_INT);


$url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
$templatecontext = (object) [
    'text' => get_string('back', 'block_disea_dashboard'),
    'editurl' => $url
];
$footer = $OUTPUT->render_from_template('block_disea_dashboard/more_details', $templatecontext);


echo $OUTPUT->header();
echo '<p>Hello World</p>';
echo $footer;
echo $OUTPUT->footer();