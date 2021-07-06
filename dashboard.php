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
require('lib.php');

global $CFG, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(new moodle_url('/blocks/disea_dashboard/dashboard.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('pluginname', 'block_disea_dashboard'));

//Get Course ID from url to be able to redirect
$courseid = optional_param('id',NULL, PARAM_INT);
$startdate = optional_param('from', '***', PARAM_TEXT);

/* Access control */
require_login($courseid);
$context = context_course::instance($courseid);

/*
 * The following code is from the moodle plugin analytics graphs
 */
$courseparams = get_course($courseid);
if ($startdate === '***') {
    $startdate = $courseparams->startdate;
} else {
    $datetoarray = explode('-', $startdate);
    $starttime = new DateTime("now", core_date::get_server_timezone_object());
    $starttime->setDate((int)$datetoarray[0], (int)$datetoarray[1], (int)$datetoarray[2]);
    $starttime->setTime(0, 0, 0);
    $startdate = $starttime->getTimestamp();
}
$students = block_disea_dashboard_get_students($courseid);

$numberofstudents = count($students);
if ($numberofstudents == 0) {
    echo(get_string('no_students', 'block_disea_dashboard'));
    exit;
}
foreach ($students as $tuple) {
    $arrayofstudents[] = array('userid' => $tuple->id ,
        'nome' => $tuple->firstname.' '.$tuple->lastname,
        'email' => $tuple->email);
}

/* Get the number of days with access by week */
$results = block_disea_dashboard_get_number_of_days_access_by_week($courseid, $students, $startdate);

$maxnumberofweeks = 0;
foreach ($results as $tuple) {
    $arrayofaccess[] = array('userid' => $tuple->userid ,
        'nome' => $tuple->firstname.' '.$tuple->lastname,
        'email' => $tuple->email);
    if ($tuple->week > $maxnumberofweeks) {
        $maxnumberofweeks = $tuple->week;
    }
}

// Chart with access to course per week
$access_weeks = array_fill('0', $maxnumberofweeks, 0);
$access_weeks_label = range('0', $maxnumberofweeks);
foreach ($results as $tuple) {
    if($tuple->userid === $USER->id) {
        $access_weeks[$tuple->week] = intval($tuple->number);
    }
}
$access_weeks_chart = new core\chart_line();
$numbers = new core\chart_series('pageviews', $access_weeks);
$access_weeks_chart->add_series($numbers);
$access_weeks_chart->set_labels($access_weeks_label);

//Chart with klicks in course per week
$klicks = array_fill('0', $maxnumberofweeks, 0);
$klick_label = range('0', $maxnumberofweeks);
foreach ($results as $tuple) {
    if($tuple->userid === $USER->id) {
        $klicks[$tuple->week] = intval($tuple->numberofpageviews);
    }
}
$klicks_chart = new core\chart_line();
$klicks_s = new core\chart_series('pageviews', $klicks);
$klicks_chart->add_series($klicks_s);
$klicks_chart->set_labels($klick_label);

/* Get the number of modules accessed by week */
$accessresults = block_disea_dashboard_get_number_of_modules_access_by_week($courseid, $students, $startdate);

// Chart with days of access of modules per week
$module_access = array_fill('0', $maxnumberofweeks, 0);
$module_access_label = range('0', $maxnumberofweeks-1);
foreach ($accessresults as $tuple) {
    if($tuple->userid === $USER->id) {
        $module_access[$tuple->week] = intval($tuple->number);
    }
}
$module_access_chart = new core\chart_line();
$module_access_s = new core\chart_series('module views', $module_access);
$module_access_chart->add_series($module_access_s);
$module_access_chart->set_labels($module_access_label);


//Testchart 3 just for fun
$nums = new core\chart_series('numbers2', [1, 2, 6, 4, 5, 2, 7, 8, 5, 10, 1, 4, 7, 3]);
$labs = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14');
$chart4 = new core\chart_line();
$chart4->add_series($nums);
$chart4->set_labels($labs);

$url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
$templatecontext = (object) [
    'text' => get_string('back', 'block_disea_dashboard'),
    'editurl' => $url
];

$diagrams = [
    (object)['d' => $OUTPUT->render_chart($access_weeks_chart, false)],  
    (object)['d' => $OUTPUT->render_chart($module_access_chart, false)],
    (object)['d' => $OUTPUT->render_chart($klicks_chart, false)], 
    (object)['d' => $OUTPUT->render_chart($chart4, false)]];

$templatecontext_diagrams = (object) [
    'diagrams' => $diagrams,
    'test' => $OUTPUT->render_chart($klicks_chart),
];


echo $OUTPUT->header();
echo $OUTPUT->heading('DiSEA Dashboard');
/*echo '<div class="container"> <div class="row"> <div class="col">';
echo $OUTPUT->render_chart($access_weeks_chart, false);
echo '</div>  <div class="col">';
echo $OUTPUT->render_chart($module_access_chart, false);
echo '</div>  <div class="col">';
echo $OUTPUT->render_chart($klicks_chart);
echo '</div> </div> <div class="row">';
echo $OUTPUT->render_chart($chart4, false);
echo '</div> </div> </div>';
echo '<hr><hr><hr><hr>';
echo '<h2>Hier folgt ein test</h2>';
*/
echo $OUTPUT->render_from_template('block_disea_dashboard/diagrams', $templatecontext_diagrams);
echo $OUTPUT->render_from_template('block_disea_dashboard/more_details', $templatecontext);
echo $OUTPUT->footer();