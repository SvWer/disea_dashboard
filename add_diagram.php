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
require_once($CFG->dirroot . '/blocks/disea_dashboard/classes/form/add_diagram_form.php');
require('lib.php');

global $CFG, $PAGE, $OUTPUT, $USER, $DB;

//Get Course ID from url to be able to redirect
$courseid = optional_param('id',NULL, PARAM_INT);
$startdate = optional_param('from', '***', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/blocks/disea_dashboard/add_diagram.php', array('id' => $courseid)));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('pluginname', 'block_disea_dashboard'));

$dashurl = $CFG->wwwroot.'/blocks/disea_dashboard/dashboard.php?id='.$courseid;
$thisurl = $CFG->wwwroot.'/blocks/disea_dashboard/add_diagram.php?id='.$courseid;

$check_diagrams = $DB->get_records('disea_diagrams', array('userid' => $USER->id));
$check_diagrams = array_values($check_diagrams);
$check_diagrams = $check_diagrams[0];
$diagrams = [];

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
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if($check_diagrams->diagram1 == 0) {
    create_diagram($diagrams, 'line', get_string('access_weeks_chart_name', 'block_disea_dashboard'), $access_weeks_label,
        1, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $access_weeks,
        get_string('average', 'block_disea_dashboard'), $access_weeks_average);
}
/*
 * The following part ist just for test purpose with more diagrams.
 */
//lines smooth
if($check_diagrams->diagram11 == 0) {
    create_diagram($diagrams, 'smooth', get_string('access_weeks_chart_name', 'block_disea_dashboard'), $access_weeks_label,
        11, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $access_weeks,
        get_string('average', 'block_disea_dashboard'), $access_weeks_average);
}
//bar
if($check_diagrams->diagram12 == 0) {
    create_diagram($diagrams, 'bar', get_string('access_weeks_chart_name', 'block_disea_dashboard'), $access_weeks_label,
        12, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $access_weeks,
        get_string('average', 'block_disea_dashboard'), $access_weeks_average);
}
//stacked bar
if($check_diagrams->diagram13 == 0) {
    create_diagram($diagrams, 'stacked', get_string('access_weeks_chart_name', 'block_disea_dashboard'), $access_weeks_label,
        13, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $access_weeks,
        get_string('average', 'block_disea_dashboard'), $access_weeks_average);
}
//horizontal bar
if($check_diagrams->diagram14 == 0) {
    create_diagram($diagrams, 'horizontal', get_string('access_weeks_chart_name', 'block_disea_dashboard'), $access_weeks_label,
        14, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $access_weeks,
        get_string('average', 'block_disea_dashboard'), $access_weeks_average);
}
//pie
if($check_diagrams->diagram15 == 0) {
    create_diagram($diagrams, 'pie', get_string('access_weeks_chart_name', 'block_disea_dashboard'), $access_weeks_label,
        15, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $access_weeks);
}
//pie average (Doughnut does not makes any sense)
if($check_diagrams->diagram16 == 0) {
    create_diagram($diagrams, 'pie', get_string('access_weeks_chart_name', 'block_disea_dashboard'), $access_weeks_label,
        16, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('average', 'block_disea_dashboard'), $access_weeks_average);
}

/*
 * End of test
 */

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if($check_diagrams->diagram2 == 0) {
    create_diagram($diagrams, 'line', get_string('klicks_chart_name', 'block_disea_dashboard'), $klick_label,
        2, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $klicks,
        get_string('average', 'block_disea_dashboard'), $klicks_average);
}
/*
 * Second test block for different diagrams
 */
//line smooth
if($check_diagrams->diagram21 == 0) {
    create_diagram($diagrams, 'smooth', get_string('klicks_chart_name', 'block_disea_dashboard'), $klick_label,
        21, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $klicks,
        get_string('average', 'block_disea_dashboard'), $klicks_average);
}
//bar
if($check_diagrams->diagram22 == 0) {
    create_diagram($diagrams, 'bar', get_string('klicks_chart_name', 'block_disea_dashboard'), $klick_label,
        22, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $klicks,
        get_string('average', 'block_disea_dashboard'), $klicks_average);
}
//bar stacked
if($check_diagrams->diagram23 == 0) {
    create_diagram($diagrams, 'stacked', get_string('klicks_chart_name', 'block_disea_dashboard'), $klick_label,
        23, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $klicks,
        get_string('average', 'block_disea_dashboard'), $klicks_average);
}
//bar horizontal
if($check_diagrams->diagram24 == 0) {
    create_diagram($diagrams, 'horizontal', get_string('klicks_chart_name', 'block_disea_dashboard'), $klick_label,
        24, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $klicks,
        get_string('average', 'block_disea_dashboard'), $klicks_average);
}
//pie
if($check_diagrams->diagram25 == 0) {
    create_diagram($diagrams, 'pie', get_string('klicks_chart_name', 'block_disea_dashboard'), $klick_label,
        25, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $klicks);
}
//pie average (doughnut does not make sense)
if($check_diagrams->diagram26 == 0) {
    create_diagram($diagrams, 'pie', get_string('klicks_chart_name', 'block_disea_dashboard'), $klick_label,
        26, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $klicks);
}

/*
 * End Test 2
 */
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


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
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//Normal diagram line 3
if($check_diagrams->diagram3 == 0) {
    create_diagram($diagrams, 'line', get_string('module_access_chart_name', 'block_disea_dashboard'), $module_access_label,
        3, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $module_access,
        get_string('average', 'block_disea_dashboard'), $module_access_average);
}
/*
 * Third test block
 */
//line smooth
if($check_diagrams->diagram31 == 0) {
    create_diagram($diagrams, 'smooth', get_string('module_access_chart_name', 'block_disea_dashboard'), $module_access_label,
        31, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $module_access,
        get_string('average', 'block_disea_dashboard'), $module_access_average);
}
//bar
if($check_diagrams->diagram32 == 0) {
    create_diagram($diagrams, 'bar', get_string('module_access_chart_name', 'block_disea_dashboard'), $module_access_label,
        32, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $module_access,
        get_string('average', 'block_disea_dashboard'), $module_access_average);
}
//bar stacked
if($check_diagrams->diagram33 == 0) {
    create_diagram($diagrams, 'stacked', get_string('module_access_chart_name', 'block_disea_dashboard'), $module_access_label,
        33, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $module_access,
        get_string('average', 'block_disea_dashboard'), $module_access_average);
}
//bar horizontal
if($check_diagrams->diagram34 == 0) {
    create_diagram($diagrams, 'horizontal', get_string('module_access_chart_name', 'block_disea_dashboard'), $module_access_label,
        34, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $module_access,
        get_string('average', 'block_disea_dashboard'), $module_access_average);
}
//pie
if($check_diagrams->diagram35 == 0) {
    create_diagram($diagrams, 'pie', get_string('module_access_chart_name', 'block_disea_dashboard'), $module_access_label,
        35, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $module_access);
}
//pie average (doughnut does not make sense)
if($check_diagrams->diagram36 == 0) {
    create_diagram($diagrams, 'pie', get_string('module_access_chart_name', 'block_disea_dashboard'), $module_access_label,
        36, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('average', 'block_disea_dashboard'), $module_access_average);
}
/*
 * Ende test 3
 */

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
//Normal diagram bar
if($check_diagrams->diagram4 == 0) {
    create_diagram($diagrams, 'bar', get_string('assignment_chart_name', 'block_disea_dashboard'), $assignment_name,
        4, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $assignment_my,
        get_string('assignment_max', 'block_disea_dashboard'), $max_pos_points,
        get_string('average', 'block_disea_dashboard'), $assignment_average);
}

/*
 * This is testblock 4: assignment grades
 */
//line chart
if($check_diagrams->diagram41 == 0) {
    create_diagram($diagrams, 'line', get_string('assignment_chart_name', 'block_disea_dashboard'), $assignment_name,
        41, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $assignment_my,
        get_string('assignment_max', 'block_disea_dashboard'), $max_pos_points,
        get_string('average', 'block_disea_dashboard'), $assignment_average);
}
//lines smooth
if($check_diagrams->diagram42 == 0) {
    create_diagram($diagrams, 'smooth', get_string('assignment_chart_name', 'block_disea_dashboard'), $assignment_name,
        42, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $assignment_my,
        get_string('assignment_max', 'block_disea_dashboard'), $max_pos_points,
        get_string('average', 'block_disea_dashboard'), $assignment_average);
}
//bar stacked
if($check_diagrams->diagram43 == 0) {
    create_diagram($diagrams, 'stacked', get_string('assignment_chart_name', 'block_disea_dashboard'), $assignment_name,
        43, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $assignment_my,
        get_string('assignment_max', 'block_disea_dashboard'), $max_pos_points,
        get_string('average', 'block_disea_dashboard'), $assignment_average);
}
//bar horizontal
if($check_diagrams->diagram44 == 0) {
    create_diagram($diagrams, 'horizontal', get_string('assignment_chart_name', 'block_disea_dashboard'), $assignment_name,
        44, $dashurl, new add_diagram_form($thisurl), $check_diagrams, 1,
        get_string('my_own', 'block_disea_dashboard'), $assignment_my,
        get_string('assignment_max', 'block_disea_dashboard'), $max_pos_points,
        get_string('average', 'block_disea_dashboard'), $assignment_average);
}
/*
 * End of testblock 4: assignment grades
 */

$url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
$templatecontext = (object) [
    'text' => get_string('back', 'block_disea_dashboard'),
    'editurl' => $url
];

$templatecontext2 = (object) [
    'editurl' => $dashurl,
    'text' => get_string('back_to_dashboard', 'block_disea_dashboard')
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