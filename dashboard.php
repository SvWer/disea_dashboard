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

/*
 * This is an index of the differend diagrams, so that you know, which 
 * diagram contains to which ID in the database
 * 1 = access per week
 * 11 = access per week -line-smooth
 * 12 = access per week - bar
 * 13 = access per week - bar-stacked
 * 14 = access per week - bar-horizontal
 * 15 = access per week - pie
 * 16 = access per week - doughnut
 * 2 = klicks in course
 * 21 = klicks in course - line-smooth
 * 22 = klicks in course - bar
 * 23 = klicks in course - bar-stacked
 * 24 = klicks in course - bar-horizontal
 * 25 = klicks in course - pie
 * 26 = klicks in course - doughnut
 * 3 = accessed modules
 * 31 = accessed modules - line-smooth
 * 32 = accessed modules - bar 
 * 33 = accessed modules - bar-stacked 
 * 34 = accessed modules - bar-horizontal 
 * 35 = accessed modules - pie
 * 36 = accessed modules - doughnut
 * 4 = assignment grades
 * 41 = assignment grades - lines
 * 42 = assignment grades - lines smooth
 * 43 = assignment grades - bar-stacked
 * 44 = assignment grades - bar-horizontal
 * 5 = 
 * 6 = 
 * 6 = 
 * 7 = 
 * 8 = 
 * 9 = 
 * 
 */




/**
 * Strings for component 'block_disea_dashboard'
 *
 * @package   block_disea_dashboard
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/disea_dashboard/classes/form/remove_and_compare_form.php');
require('lib.php');

global $CFG, $PAGE, $OUTPUT, $USER, $DB;

//Get Course ID from url to be able to redirect
$courseid = optional_param('id',NULL, PARAM_INT);
$startdate = optional_param('from', '***', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/blocks/disea_dashboard/dashboard.php', array('id' => $courseid)));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('pluginname', 'block_disea_dashboard'));



/* Access control */
require_login($courseid);

$check_diagrams = $DB->get_records('disea_diagrams', array('userid' => $USER->id));
if(!$check_diagrams) {
    $newrecord = new stdClass();
    $newrecord->userid = $USER->id;
    $newrecord->diagram1 = 1;
    $newrecord->diagram11 = 1;
    $newrecord->diagram12 = 1;
    $newrecord->diagram13 = 0;
    $newrecord->diagram14 = 1;
    $newrecord->diagram15 = 0;
    $newrecord->diagram16 = 0;
    $newrecord->diagram2 = 1;
    $newrecord->diagram21 = 1;
    $newrecord->diagram22 = 1;
    $newrecord->diagram23 = 0;
    $newrecord->diagram24 = 1;
    $newrecord->diagram25 = 0;
    $newrecord->diagram26 = 0;
    $newrecord->diagram3 = 1;
    $newrecord->diagram31 = 1;
    $newrecord->diagram32 = 1;
    $newrecord->diagram33 = 0;
    $newrecord->diagram34 = 1;
    $newrecord->diagram35 = 0;
    $newrecord->diagram36 = 0;
    $newrecord->diagram4 = 1;
    $newrecord->diagram41 = 0;
    $newrecord->diagram42 = 0;
    $newrecord->diagram43 = 0;
    $newrecord->diagram44 = 1;
    $newrecord->diagram5 = 0;
    $newrecord->diagram6 = 0;
    $DB->insert_record('disea_diagrams', $newrecord);
    $check_diagrams = $DB->get_records('disea_diagrams', array('userid' => $USER->id));
}
$check_diagrams = array_values($check_diagrams);
$check_diagrams = $check_diagrams[0];
$diagrams = [];

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
        $maxnumberofweeks = $tuple->week+1;
    }
}


// Chart with access to course per week
$access_weeks = array_fill('0', $maxnumberofweeks+1, 0);
$access_weeks_label = range('0', $maxnumberofweeks);
$access_weeks_average = array_fill('0', $maxnumberofweeks+1, 0);
//Chart with klicks in course per week
$klicks = array_fill('0', $maxnumberofweeks+1, 0);
$klick_label = range('0', $maxnumberofweeks);
$klicks_average = array_fill('0', $maxnumberofweeks+1, 0);
foreach ($results as $tuple) {
    if($tuple->userid === $USER->id) {
        $access_weeks[$tuple->week] = intval($tuple->number);
        $klicks[$tuple->week] = intval($tuple->numberofpageviews);
    }
    $access_weeks_average[$tuple->week] = $access_weeks_average[$tuple->week] + intval($tuple->number);
    $klicks_average[$tuple->week] = $klicks_average[$tuple->week] + intval($tuple->numberofpageviews);
}

for ($i = 0; $i < count($access_weeks_average); $i++) {
    $access_weeks_average[$i] = $access_weeks_average[$i]/count($students);
    $klicks_average[$i] = $klicks_average[$i] / count($students);
}
$thisurl = $CFG->wwwroot.'/blocks/disea_dashboard/dashboard.php?id='.$courseid;

if($check_diagrams->diagram1 == 1) {
    // Chart with access to course per week
    $access_weeks_chart = new core\chart_line();
    $access_weeks_chart->set_title(get_string('access_weeks_chart_name', 'block_disea_dashboard'));
    $numbers = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $access_weeks);
    $access_weeks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $access_weeks_average);
    $access_weeks_chart->add_series($numbers);
    $access_weeks_chart->add_series($access_weeks_average_s);
    $access_weeks_chart->set_labels($access_weeks_label);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 1));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram1 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($access_weeks_chart, false), 'b' => $mform->render()]);
}
/*
 * The following part ist just for test purpose with more diagrams.
 */
//lines smooth
if($check_diagrams->diagram11 == 1) {
    // Chart with access to course per week
    $access_weeks_chart = new core\chart_line();
    $access_weeks_chart->set_title(get_string('access_weeks_chart_name', 'block_disea_dashboard'));
    $numbers = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $access_weeks);
    $access_weeks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $access_weeks_average);
    $access_weeks_chart->add_series($numbers);
    $access_weeks_chart->add_series($access_weeks_average_s);
    $access_weeks_chart->set_labels($access_weeks_label);
    $access_weeks_chart->set_smooth(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 11));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram11 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($access_weeks_chart, false), 'b' => $mform->render()]);
}
//bar
if($check_diagrams->diagram12 == 1) {
    // Chart with access to course per week
    $access_weeks_chart = new core\chart_bar();
    $access_weeks_chart->set_title(get_string('access_weeks_chart_name', 'block_disea_dashboard'));
    $numbers = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $access_weeks);
    $access_weeks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $access_weeks_average);
    $access_weeks_chart->add_series($numbers);
    $access_weeks_chart->add_series($access_weeks_average_s);
    $access_weeks_chart->set_labels($access_weeks_label);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 12));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram12 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($access_weeks_chart, false), 'b' => $mform->render()]);
}
//stacked bar
if($check_diagrams->diagram13 == 1) {
    // Chart with access to course per week
    $access_weeks_chart = new core\chart_bar();
    $access_weeks_chart->set_title(get_string('access_weeks_chart_name', 'block_disea_dashboard'));
    $numbers = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $access_weeks);
    $access_weeks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $access_weeks_average);
    $access_weeks_chart->add_series($numbers);
    $access_weeks_chart->add_series($access_weeks_average_s);
    $access_weeks_chart->set_labels($access_weeks_label);
    $access_weeks_chart->set_stacked(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 13));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram13 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($access_weeks_chart, false), 'b' => $mform->render()]);
}
//horizontal bar
if($check_diagrams->diagram14 == 1) {
    // Chart with access to course per week
    $access_weeks_chart = new core\chart_bar();
    $access_weeks_chart->set_title(get_string('access_weeks_chart_name', 'block_disea_dashboard'));
    $numbers = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $access_weeks);
    $access_weeks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $access_weeks_average);
    $access_weeks_chart->add_series($numbers);
    $access_weeks_chart->add_series($access_weeks_average_s);
    $access_weeks_chart->set_labels($access_weeks_label);
    $access_weeks_chart->set_horizontal(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 14));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram14 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($access_weeks_chart, false), 'b' => $mform->render()]);
}
//pie
if($check_diagrams->diagram15 == 1) {
    // Chart with access to course per week
    $access_weeks_chart = new core\chart_pie();
    $access_weeks_chart->set_title(get_string('access_weeks_chart_name', 'block_disea_dashboard'));
    $numbers = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $access_weeks);
    $access_weeks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $access_weeks_average);
    $access_weeks_chart->add_series($numbers);
    //$access_weeks_chart->add_series($access_weeks_average_s);
    $access_weeks_chart->set_labels($access_weeks_label);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 15));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram15 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($access_weeks_chart, false), 'b' => $mform->render()]);
}
//Doughnut
if($check_diagrams->diagram16 == 1) {
    // Chart with access to course per week
    $access_weeks_chart = new core\chart_pie();
    $access_weeks_chart->set_title(get_string('access_weeks_chart_name', 'block_disea_dashboard'));
    $numbers = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $access_weeks);
    $access_weeks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $access_weeks_average);
    //$access_weeks_chart->add_series($numbers);
    $access_weeks_chart->add_series($access_weeks_average_s);
    $access_weeks_chart->set_labels($access_weeks_label);
    //$access_weeks_chart->set_doughnut(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 16));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram16 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($access_weeks_chart, false), 'b' => $mform->render()]);
}


/*
 * End of test
 */

if($check_diagrams->diagram2 == 1) {
    //Chart with klicks in course per week
    $klicks_chart = new core\chart_line();
    $klicks_chart->set_title(get_string('klicks_chart_name', 'block_disea_dashboard'));
    $klicks_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $klicks);
    $klicks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $klicks_average);
    $klicks_chart->add_series($klicks_s);
    $klicks_chart->add_series($klicks_average_s);
    $klicks_chart->set_labels($klick_label);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 2));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram2 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($klicks_chart, false), 'b' => $mform->render()]);
}

/*
 * Second test block for different diagrams
 */
//line smooth
if($check_diagrams->diagram21 == 1) {
    //Chart with klicks in course per week
    $klicks_chart = new core\chart_line();
    $klicks_chart->set_title(get_string('klicks_chart_name', 'block_disea_dashboard'));
    $klicks_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $klicks);
    $klicks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $klicks_average);
    $klicks_chart->add_series($klicks_s);
    $klicks_chart->add_series($klicks_average_s);
    $klicks_chart->set_labels($klick_label);
    $klicks_chart->set_smooth(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 21));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram21 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($klicks_chart, false), 'b' => $mform->render()]);
}
//bar
if($check_diagrams->diagram22 == 1) {
    //Chart with klicks in course per week
    $klicks_chart = new core\chart_bar();
    $klicks_chart->set_title(get_string('klicks_chart_name', 'block_disea_dashboard'));
    $klicks_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $klicks);
    $klicks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $klicks_average);
    $klicks_chart->add_series($klicks_s);
    $klicks_chart->add_series($klicks_average_s);
    $klicks_chart->set_labels($klick_label);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 22));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram22 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($klicks_chart, false), 'b' => $mform->render()]);
}
//bar stacked
if($check_diagrams->diagram23 == 1) {
    //Chart with klicks in course per week
    $klicks_chart = new core\chart_bar();
    $klicks_chart->set_title(get_string('klicks_chart_name', 'block_disea_dashboard'));
    $klicks_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $klicks);
    $klicks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $klicks_average);
    $klicks_chart->add_series($klicks_s);
    $klicks_chart->add_series($klicks_average_s);
    $klicks_chart->set_labels($klick_label);
    $klicks_chart->set_stacked(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 23));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram23 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($klicks_chart, false), 'b' => $mform->render()]);
}
//bar horizontal
if($check_diagrams->diagram24 == 1) {
    //Chart with klicks in course per week
    $klicks_chart = new core\chart_bar();
    $klicks_chart->set_title(get_string('klicks_chart_name', 'block_disea_dashboard'));
    $klicks_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $klicks);
    $klicks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $klicks_average);
    $klicks_chart->add_series($klicks_s);
    $klicks_chart->add_series($klicks_average_s);
    $klicks_chart->set_labels($klick_label);
    $klicks_chart->set_horizontal(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 24));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram24 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($klicks_chart, false), 'b' => $mform->render()]);
}
//pie
//bar horizontal
if($check_diagrams->diagram25 == 1) {
    //Chart with klicks in course per week
    $klicks_chart = new core\chart_pie();
    $klicks_chart->set_title(get_string('klicks_chart_name', 'block_disea_dashboard'));
    $klicks_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $klicks);
    $klicks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $klicks_average);
    $klicks_chart->add_series($klicks_s);
    //$klicks_chart->add_series($klicks_average_s);
    $klicks_chart->set_labels($klick_label);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 25));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram25 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($klicks_chart, false), 'b' => $mform->render()]);
}
//doughnut
//bar horizontal
if($check_diagrams->diagram26 == 1) {
    //Chart with klicks in course per week
    $klicks_chart = new core\chart_pie();
    $klicks_chart->set_title(get_string('klicks_chart_name', 'block_disea_dashboard'));
    $klicks_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $klicks);
    $klicks_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $klicks_average);
    //$klicks_chart->add_series($klicks_s);
    $klicks_chart->add_series($klicks_average_s);
    $klicks_chart->set_labels($klick_label);
    //$klicks_chart->set_doughnut(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 26));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram26 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($klicks_chart, false), 'b' => $mform->render()]);
}

/*
 * End Test 2
 */

if($check_diagrams->diagram3 == 1) {
    /* Get the number of modules accessed by week */
    $accessresults = block_disea_dashboard_get_number_of_modules_access_by_week($courseid, $students, $startdate);
    
    // Chart with days of access of modules per week
    $module_access = array_fill('0', $maxnumberofweeks+1, 0);
    $module_access_label = range('0', $maxnumberofweeks);
    $module_access_average = array_fill('0', $maxnumberofweeks+1, 0);
    foreach ($accessresults as $tuple) {
        if($tuple->userid === $USER->id) {
            $module_access[$tuple->week] = intval($tuple->number);
        }
        $module_access_average[$tuple->week] = $module_access_average[$tuple->week] + intval($tuple->number);
    }
    
    for ($i = 0; $i < count($module_access_average); $i++) {
        $module_access_average[$i] = $module_access_average[$i]/count($students);
    }
    
    $module_access_chart = new core\chart_line();
    $module_access_chart->set_title(get_string('module_access_chart_name', 'block_disea_dashboard'));
    $module_access_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $module_access);
    $module_access_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $module_access_average);
    $module_access_chart->add_series($module_access_s);
    $module_access_chart->add_series($module_access_average_s);
    $module_access_chart->set_labels($module_access_label);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 3));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram3 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($module_access_chart, false), 'b' => $mform->render()]);
}
/*
 * Third test block
 */
//line smooth
if($check_diagrams->diagram31 == 1) {
    /* Get the number of modules accessed by week */
    $accessresults = block_disea_dashboard_get_number_of_modules_access_by_week($courseid, $students, $startdate);
    
    // Chart with days of access of modules per week
    $module_access = array_fill('0', $maxnumberofweeks+1, 0);
    $module_access_label = range('0', $maxnumberofweeks);
    $module_access_average = array_fill('0', $maxnumberofweeks+1, 0);
    foreach ($accessresults as $tuple) {
        if($tuple->userid === $USER->id) {
            $module_access[$tuple->week] = intval($tuple->number);
        }
        $module_access_average[$tuple->week] = $module_access_average[$tuple->week] + intval($tuple->number);
    }
    
    for ($i = 0; $i < count($module_access_average); $i++) {
        $module_access_average[$i] = $module_access_average[$i]/count($students);
    }
    
    $module_access_chart = new core\chart_line();
    $module_access_chart->set_title(get_string('module_access_chart_name', 'block_disea_dashboard'));
    $module_access_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $module_access);
    $module_access_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $module_access_average);
    $module_access_chart->add_series($module_access_s);
    $module_access_chart->add_series($module_access_average_s);
    $module_access_chart->set_labels($module_access_label);
    $module_access_chart->set_smooth(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 31));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram31 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($module_access_chart, false), 'b' => $mform->render()]);
}
//bar
if($check_diagrams->diagram32 == 1) {
    /* Get the number of modules accessed by week */
    $accessresults = block_disea_dashboard_get_number_of_modules_access_by_week($courseid, $students, $startdate);
    
    // Chart with days of access of modules per week
    $module_access = array_fill('0', $maxnumberofweeks+1, 0);
    $module_access_label = range('0', $maxnumberofweeks);
    $module_access_average = array_fill('0', $maxnumberofweeks+1, 0);
    foreach ($accessresults as $tuple) {
        if($tuple->userid === $USER->id) {
            $module_access[$tuple->week] = intval($tuple->number);
        }
        $module_access_average[$tuple->week] = $module_access_average[$tuple->week] + intval($tuple->number);
    }
    
    for ($i = 0; $i < count($module_access_average); $i++) {
        $module_access_average[$i] = $module_access_average[$i]/count($students);
    }
    
    $module_access_chart = new core\chart_bar();
    $module_access_chart->set_title(get_string('module_access_chart_name', 'block_disea_dashboard'));
    $module_access_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $module_access);
    $module_access_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $module_access_average);
    $module_access_chart->add_series($module_access_s);
    $module_access_chart->add_series($module_access_average_s);
    $module_access_chart->set_labels($module_access_label);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 32));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram32 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($module_access_chart, false), 'b' => $mform->render()]);
}
//bar stacked
if($check_diagrams->diagram33 == 1) {
    /* Get the number of modules accessed by week */
    $accessresults = block_disea_dashboard_get_number_of_modules_access_by_week($courseid, $students, $startdate);
    
    // Chart with days of access of modules per week
    $module_access = array_fill('0', $maxnumberofweeks+1, 0);
    $module_access_label = range('0', $maxnumberofweeks);
    $module_access_average = array_fill('0', $maxnumberofweeks+1, 0);
    foreach ($accessresults as $tuple) {
        if($tuple->userid === $USER->id) {
            $module_access[$tuple->week] = intval($tuple->number);
        }
        $module_access_average[$tuple->week] = $module_access_average[$tuple->week] + intval($tuple->number);
    }
    
    for ($i = 0; $i < count($module_access_average); $i++) {
        $module_access_average[$i] = $module_access_average[$i]/count($students);
    }
    
    $module_access_chart = new core\chart_bar();
    $module_access_chart->set_title(get_string('module_access_chart_name', 'block_disea_dashboard'));
    $module_access_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $module_access);
    $module_access_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $module_access_average);
    $module_access_chart->add_series($module_access_s);
    $module_access_chart->add_series($module_access_average_s);
    $module_access_chart->set_labels($module_access_label);
    $module_access_chart->set_stacked(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 33));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram33 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($module_access_chart, false), 'b' => $mform->render()]);
}
//bar
if($check_diagrams->diagram34 == 1) {
    /* Get the number of modules accessed by week */
    $accessresults = block_disea_dashboard_get_number_of_modules_access_by_week($courseid, $students, $startdate);
    
    // Chart with days of access of modules per week
    $module_access = array_fill('0', $maxnumberofweeks+1, 0);
    $module_access_label = range('0', $maxnumberofweeks);
    $module_access_average = array_fill('0', $maxnumberofweeks+1, 0);
    foreach ($accessresults as $tuple) {
        if($tuple->userid === $USER->id) {
            $module_access[$tuple->week] = intval($tuple->number);
        }
        $module_access_average[$tuple->week] = $module_access_average[$tuple->week] + intval($tuple->number);
    }
    
    for ($i = 0; $i < count($module_access_average); $i++) {
        $module_access_average[$i] = $module_access_average[$i]/count($students);
    }
    
    $module_access_chart = new core\chart_bar();
    $module_access_chart->set_title(get_string('module_access_chart_name', 'block_disea_dashboard'));
    $module_access_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $module_access);
    $module_access_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $module_access_average);
    $module_access_chart->add_series($module_access_s);
    $module_access_chart->add_series($module_access_average_s);
    $module_access_chart->set_labels($module_access_label);
    $module_access_chart->set_horizontal(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 34));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram34 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($module_access_chart, false), 'b' => $mform->render()]);
}
//pie
if($check_diagrams->diagram35 == 1) {
    /* Get the number of modules accessed by week */
    $accessresults = block_disea_dashboard_get_number_of_modules_access_by_week($courseid, $students, $startdate);
    
    // Chart with days of access of modules per week
    $module_access = array_fill('0', $maxnumberofweeks+1, 0);
    $module_access_label = range('0', $maxnumberofweeks);
    $module_access_average = array_fill('0', $maxnumberofweeks+1, 0);
    foreach ($accessresults as $tuple) {
        if($tuple->userid === $USER->id) {
            $module_access[$tuple->week] = intval($tuple->number);
        }
        $module_access_average[$tuple->week] = $module_access_average[$tuple->week] + intval($tuple->number);
    }
    
    for ($i = 0; $i < count($module_access_average); $i++) {
        $module_access_average[$i] = $module_access_average[$i]/count($students);
    }
    
    $module_access_chart = new core\chart_pie();
    $module_access_chart->set_title(get_string('module_access_chart_name', 'block_disea_dashboard'));
    $module_access_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $module_access);
    $module_access_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $module_access_average);
    $module_access_chart->add_series($module_access_s);
    //$module_access_chart->add_series($module_access_average_s);
    $module_access_chart->set_labels($module_access_label);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 35));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram35 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($module_access_chart, false), 'b' => $mform->render()]);
}
//doughnut
if($check_diagrams->diagram36 == 1) {
    /* Get the number of modules accessed by week */
    $accessresults = block_disea_dashboard_get_number_of_modules_access_by_week($courseid, $students, $startdate);
    
    // Chart with days of access of modules per week
    $module_access = array_fill('0', $maxnumberofweeks+1, 0);
    $module_access_label = range('0', $maxnumberofweeks);
    $module_access_average = array_fill('0', $maxnumberofweeks+1, 0);
    foreach ($accessresults as $tuple) {
        if($tuple->userid === $USER->id) {
            $module_access[$tuple->week] = intval($tuple->number);
        }
        $module_access_average[$tuple->week] = $module_access_average[$tuple->week] + intval($tuple->number);
    }
    
    for ($i = 0; $i < count($module_access_average); $i++) {
        $module_access_average[$i] = $module_access_average[$i]/count($students);
    }
    
    $module_access_chart = new core\chart_pie();
    $module_access_chart->set_title(get_string('module_access_chart_name', 'block_disea_dashboard'));
    $module_access_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $module_access);
    $module_access_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $module_access_average);
    //$module_access_chart->add_series($module_access_s);
    $module_access_chart->add_series($module_access_average_s);
    $module_access_chart->set_labels($module_access_label);
    //$module_access_chart->set_doughnut(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 36));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram36 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($module_access_chart, false), 'b' => $mform->render()]);
}

/*
 * Ende test 3
 */

if($check_diagrams->diagram4 == 1) {
    //Chart for assignenment grades
    $assign_grades = block_disea_dashboard_get_assignment_grades($courseid, $students);
    
    $diffquizzes = array_unique(array_map(function ($i) { return $i->name; }, $assign_grades));
    $numberquizzes = count($diffquizzes);
    
    $max_pos_points = array_fill(0, $numberquizzes, 0);
    $assignment_average = array_fill(0, $numberquizzes, 0);
    $assignment_my = array_fill(0, $numberquizzes, 0);
    $succeded_assignments = array_fill(0, $numberquizzes, 0);
    $assignment_name = array();
    
    
    foreach ($assign_grades as $tuple) {
        if(in_array($tuple->name, $assignment_name)) {
            $index = array_search($tuple->name, $assignment_name);
            $assignment_average[$index] = $assignment_average[$index] + $tuple->points;
            $succeded_assignments[$index] += 1;
            if($tuple->userid === $USER->id) {
                $assignment_my[$index] = $tuple->points;
            }
        } else {
            array_push($assignment_name, $tuple->name);
            $index = array_search($tuple->name, $assignment_name);
            $max_pos_points[$index] = $tuple->maxpoints;
            $assignment_average[$index] = $tuple->points;
            $succeded_assignments[$index] += 1;
            if($tuple->userid === $USER->id) {
                $assignment_my[$index] = $tuple->points;
            }
        }
    }
    for ($i = 0; $i < count($assignment_average); $i++) {
        $assignment_average[$i] = $assignment_average[$i]/$succeded_assignments[$i];
    }
    
    $assignment_chart = new core\chart_bar();
    $assignment_chart->set_title(get_string('assignment_chart_name', 'block_disea_dashboard'));
    $assignment_my_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $assignment_my);
    $assignment_chart->add_series($assignment_my_s);
    $assignment_max_s = new core\chart_series(get_string('assignment_max', 'block_disea_dashboard'), $max_pos_points);
    $assignment_chart->add_series($assignment_max_s);
    $assignment_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $assignment_average);
    $assignment_chart->add_series($assignment_average_s);
    $assignment_chart->set_labels($assignment_name);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 4));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram4 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($assignment_chart, false), 'b' => $mform->render()]);
}

/*
 * This is testblock 4: assignment grades
 */
//line chart
if($check_diagrams->diagram41 == 1) {
    //Chart for assignenment grades
    $assign_grades = block_disea_dashboard_get_assignment_grades($courseid, $students);
    
    $diffquizzes = array_unique(array_map(function ($i) { return $i->name; }, $assign_grades));
    $numberquizzes = count($diffquizzes);
    
    $max_pos_points = array_fill(0, $numberquizzes, 0);
    $assignment_average = array_fill(0, $numberquizzes, 0);
    $assignment_my = array_fill(0, $numberquizzes, 0);
    $succeded_assignments = array_fill(0, $numberquizzes, 0);
    $assignment_name = array();
    
    
    foreach ($assign_grades as $tuple) {
        if(in_array($tuple->name, $assignment_name)) {
            $index = array_search($tuple->name, $assignment_name);
            $assignment_average[$index] = $assignment_average[$index] + $tuple->points;
            $succeded_assignments[$index] += 1;
            if($tuple->userid === $USER->id) {
                $assignment_my[$index] = $tuple->points;
            }
        } else {
            array_push($assignment_name, $tuple->name);
            $index = array_search($tuple->name, $assignment_name);
            $max_pos_points[$index] = $tuple->maxpoints;
            $assignment_average[$index] = $tuple->points;
            $succeded_assignments[$index] += 1;
            if($tuple->userid === $USER->id) {
                $assignment_my[$index] = $tuple->points;
            }
        }
    }
    for ($i = 0; $i < count($assignment_average); $i++) {
        $assignment_average[$i] = $assignment_average[$i]/$succeded_assignments[$i];
    }
    
    $assignment_chart = new core\chart_line();
    $assignment_chart->set_title(get_string('assignment_chart_name', 'block_disea_dashboard'));
    $assignment_my_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $assignment_my);
    $assignment_chart->add_series($assignment_my_s);
    $assignment_max_s = new core\chart_series(get_string('assignment_max', 'block_disea_dashboard'), $max_pos_points);
    $assignment_chart->add_series($assignment_max_s);
    $assignment_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $assignment_average);
    $assignment_chart->add_series($assignment_average_s);
    $assignment_chart->set_labels($assignment_name);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 41));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram41 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($assignment_chart, false), 'b' => $mform->render()]);
}
//lines smooth
if($check_diagrams->diagram42 == 1) {
    //Chart for assignenment grades
    $assign_grades = block_disea_dashboard_get_assignment_grades($courseid, $students);
    
    $diffquizzes = array_unique(array_map(function ($i) { return $i->name; }, $assign_grades));
    $numberquizzes = count($diffquizzes);
    
    $max_pos_points = array_fill(0, $numberquizzes, 0);
    $assignment_average = array_fill(0, $numberquizzes, 0);
    $assignment_my = array_fill(0, $numberquizzes, 0);
    $succeded_assignments = array_fill(0, $numberquizzes, 0);
    $assignment_name = array();
    
    
    foreach ($assign_grades as $tuple) {
        if(in_array($tuple->name, $assignment_name)) {
            $index = array_search($tuple->name, $assignment_name);
            $assignment_average[$index] = $assignment_average[$index] + $tuple->points;
            $succeded_assignments[$index] += 1;
            if($tuple->userid === $USER->id) {
                $assignment_my[$index] = $tuple->points;
            }
        } else {
            array_push($assignment_name, $tuple->name);
            $index = array_search($tuple->name, $assignment_name);
            $max_pos_points[$index] = $tuple->maxpoints;
            $assignment_average[$index] = $tuple->points;
            $succeded_assignments[$index] += 1;
            if($tuple->userid === $USER->id) {
                $assignment_my[$index] = $tuple->points;
            }
        }
    }
    for ($i = 0; $i < count($assignment_average); $i++) {
        $assignment_average[$i] = $assignment_average[$i]/$succeded_assignments[$i];
    }
    
    $assignment_chart = new core\chart_line();
    $assignment_chart->set_title(get_string('assignment_chart_name', 'block_disea_dashboard'));
    $assignment_my_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $assignment_my);
    $assignment_chart->add_series($assignment_my_s);
    $assignment_max_s = new core\chart_series(get_string('assignment_max', 'block_disea_dashboard'), $max_pos_points);
    $assignment_chart->add_series($assignment_max_s);
    $assignment_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $assignment_average);
    $assignment_chart->add_series($assignment_average_s);
    $assignment_chart->set_labels($assignment_name);
    $assignment_chart->set_smooth(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 42));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram42 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($assignment_chart, false), 'b' => $mform->render()]);
}
//bar stacked
if($check_diagrams->diagram43 == 1) {
    //Chart for assignenment grades
    $assign_grades = block_disea_dashboard_get_assignment_grades($courseid, $students);
    
    $diffquizzes = array_unique(array_map(function ($i) { return $i->name; }, $assign_grades));
    $numberquizzes = count($diffquizzes);
    
    $max_pos_points = array_fill(0, $numberquizzes, 0);
    $assignment_average = array_fill(0, $numberquizzes, 0);
    $assignment_my = array_fill(0, $numberquizzes, 0);
    $succeded_assignments = array_fill(0, $numberquizzes, 0);
    $assignment_name = array();
    
    
    foreach ($assign_grades as $tuple) {
        if(in_array($tuple->name, $assignment_name)) {
            $index = array_search($tuple->name, $assignment_name);
            $assignment_average[$index] = $assignment_average[$index] + $tuple->points;
            $succeded_assignments[$index] += 1;
            if($tuple->userid === $USER->id) {
                $assignment_my[$index] = $tuple->points;
            }
        } else {
            array_push($assignment_name, $tuple->name);
            $index = array_search($tuple->name, $assignment_name);
            $max_pos_points[$index] = $tuple->maxpoints;
            $assignment_average[$index] = $tuple->points;
            $succeded_assignments[$index] += 1;
            if($tuple->userid === $USER->id) {
                $assignment_my[$index] = $tuple->points;
            }
        }
    }
    for ($i = 0; $i < count($assignment_average); $i++) {
        $assignment_average[$i] = $assignment_average[$i]/$succeded_assignments[$i];
    }
    
    $assignment_chart = new core\chart_bar();
    $assignment_chart->set_title(get_string('assignment_chart_name', 'block_disea_dashboard'));
    $assignment_my_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $assignment_my);
    $assignment_chart->add_series($assignment_my_s);
    $assignment_max_s = new core\chart_series(get_string('assignment_max', 'block_disea_dashboard'), $max_pos_points);
    $assignment_chart->add_series($assignment_max_s);
    $assignment_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $assignment_average);
    $assignment_chart->add_series($assignment_average_s);
    $assignment_chart->set_labels($assignment_name);
    $assignment_chart->set_stacked(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 43));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram43 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($assignment_chart, false), 'b' => $mform->render()]);
}
//bar horizontal
if($check_diagrams->diagram44 == 1) {
    //Chart for assignenment grades
    $assign_grades = block_disea_dashboard_get_assignment_grades($courseid, $students);
    
    $diffquizzes = array_unique(array_map(function ($i) { return $i->name; }, $assign_grades));
    $numberquizzes = count($diffquizzes);
    
    $max_pos_points = array_fill(0, $numberquizzes, 0);
    $assignment_average = array_fill(0, $numberquizzes, 0);
    $assignment_my = array_fill(0, $numberquizzes, 0);
    $succeded_assignments = array_fill(0, $numberquizzes, 0);
    $assignment_name = array();
    
    
    foreach ($assign_grades as $tuple) {
        if(in_array($tuple->name, $assignment_name)) {
            $index = array_search($tuple->name, $assignment_name);
            $assignment_average[$index] = $assignment_average[$index] + $tuple->points;
            $succeded_assignments[$index] += 1;
            if($tuple->userid === $USER->id) {
                $assignment_my[$index] = $tuple->points;
            }
        } else {
            array_push($assignment_name, $tuple->name);
            $index = array_search($tuple->name, $assignment_name);
            $max_pos_points[$index] = $tuple->maxpoints;
            $assignment_average[$index] = $tuple->points;
            $succeded_assignments[$index] += 1;
            if($tuple->userid === $USER->id) {
                $assignment_my[$index] = $tuple->points;
            }
        }
    }
    for ($i = 0; $i < count($assignment_average); $i++) {
        $assignment_average[$i] = $assignment_average[$i]/$succeded_assignments[$i];
    }
    
    $assignment_chart = new core\chart_bar();
    $assignment_chart->set_title(get_string('assignment_chart_name', 'block_disea_dashboard'));
    $assignment_my_s = new core\chart_series(get_string('my_own', 'block_disea_dashboard'), $assignment_my);
    $assignment_chart->add_series($assignment_my_s);
    $assignment_max_s = new core\chart_series(get_string('assignment_max', 'block_disea_dashboard'), $max_pos_points);
    $assignment_chart->add_series($assignment_max_s);
    $assignment_average_s = new core\chart_series(get_string('average', 'block_disea_dashboard'), $assignment_average);
    $assignment_chart->add_series($assignment_average_s);
    $assignment_chart->set_labels($assignment_name);
    $assignment_chart->set_horizontal(true);
    $mform = new remove_and_compare_form($thisurl);
    $mform->set_data((object)array('diagram'=> 44));
    if ($fromform = $mform->get_data()){
        $di = $_POST ['diagram'];
        $check_diagrams->diagram44 = 0;
        $DB->update_record('disea_diagrams', $check_diagrams);
        redirect($thisurl);
    }
    array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($assignment_chart, false), 'b' => $mform->render()]);
}

/*
 * End of testblock 4: assignment grades
 */


// //Testchart 3 just for fun
// $nums = new core\chart_series('Random Stuff', [1, 2, 6, 4, 5, 2, 7, 8, 5, 10, 1, 4, 7, 3]);
// $labs = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14');
// $chart4 = new core\chart_line();
// $chart4->set_smooth(true);
// $chart4->add_series($nums);
// $chart4->set_labels($labs);
// array_push($diagrams,  (object)['d' => $OUTPUT->render_chart($chart4, false)]);

$url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
$templatecontext = (object) [
    'text' => get_string('back', 'block_disea_dashboard'),
    'editurl' => $url
];
$addurl = new moodle_url('/blocks/disea_dashboard/add_diagram.php', array('id' => $PAGE->course->id));
$templatecontext2 = (object) [
    'editurl' => $addurl,
    'text' => get_string('add_dashboard', 'block_disea_dashboard')
];

$templatecontext_diagrams = (object) [
    'diagrams' => $diagrams,
];


echo $OUTPUT->header();
echo $OUTPUT->heading('DiSEA Dashboard');
echo $OUTPUT->render_from_template('block_disea_dashboard/diagrams', $templatecontext_diagrams);
echo $OUTPUT->render_from_template('block_disea_dashboard/more_details', $templatecontext2);
echo $OUTPUT->render_from_template('block_disea_dashboard/more_details', $templatecontext);
echo $OUTPUT->footer();



