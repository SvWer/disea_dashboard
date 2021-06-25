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

/* Get the number of modules accessed by week */
$accessresults = block_disea_dashboard_get_number_of_modules_access_by_week($courseid, $students, $startdate);

$array = array_fill('0', $maxnumberofweeks, 0);
$lalebs = range('0', $maxnumberofweeks);
foreach ($results as $tuple) {
    var_dump('Das ist ein test, damit ich die verschiedenen Variablen besser unterscheiden kann.!!');
    var_dump('Das ist ein test, damit ich die verschiedenen Variablen besser unterscheiden kann.!!');
    var_dump('Das ist ein test, damit ich die verschiedenen Variablen besser unterscheiden kann.!!');
    var_dump('Das ist ein test, damit ich die verschiedenen Variablen besser unterscheiden kann.!!');
    var_dump('Das ist ein test, damit ich die verschiedenen Variablen besser unterscheiden kann.!!');

    if($tuple->userid === $USER->id) {
        $array[$tuple->week] = intval($tuple->number);
    }
}

var_dump($array);
var_dump($lalebs);


$chart2 = new core\chart_line();
$numbers = new core\chart_series('pageviews', $array);

$chart2->add_series($numbers);
$chart2->set_labels($lalebs);



//var_dump($results);
//var_dump($accessresults);
var_dump('Das ist ein test, damit ich die verschiedenen Variablen besser unterscheiden kann.!!');
//var_dump($results);
//var_dump($students);


$url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
$templatecontext = (object) [
    'text' => get_string('back', 'block_disea_dashboard'),
    'editurl' => $url
];
$footer = $OUTPUT->render_from_template('block_disea_dashboard/more_details', $templatecontext);


echo $OUTPUT->header();
echo '<p>Hello World</p>';
echo $OUTPUT->render_chart($chart2);
echo $footer;
echo $OUTPUT->footer();