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

defined('MOODLE_INTERNAL') || die();


/*
 * This function is used from moodle plugin analytics graphs
 */
function block_disea_dashboard_get_number_of_days_access_by_week($course, $students, $startdate) {
    global $DB;
    $timezone = new DateTimeZone(core_date::get_server_timezone());
    $timezoneadjust   = $timezone->getOffset(new DateTime);
    foreach ($students as $tuple) {
        $inclause[] = $tuple->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($timezoneadjust, $timezoneadjust, $startdate, $course, $startdate), $inparams);
    
    $sql = "SELECT temp2.userid+(week*1000000) as id, temp2.userid, firstname, lastname, email, week,
            number, numberofpageviews
            FROM (
                SELECT temp.userid, week, COUNT(*) as number, SUM(numberofpageviews) as numberofpageviews
                FROM (
                    SELECT MIN(log.id) as id, log.userid,
                        FLOOR((log.timecreated + ?)/ 86400)   as day,
                        FLOOR( (((log.timecreated  + ?) / 86400) - (?/86400))/7) as week,
                        COUNT(*) as numberofpageviews
                    FROM {logstore_standard_log} log
                    WHERE courseid = ? AND action = 'viewed' AND target = 'course'
                        AND log.timecreated >= ? AND log.userid $insql
                    GROUP BY userid, day, week
                ) as temp
                GROUP BY week, temp.userid
            ) as temp2
            LEFT JOIN {user} usr ON usr.id = temp2.userid
            ORDER BY LOWER(firstname), LOWER(lastname),userid, week";
   
    $results = $DB->get_records_sql($sql, $params);
    return($results);
}

/*
 * This function is used from moodle plugin analytics graphs
 */
function block_disea_dashboard_get_students($course) {
    global $DB;
    $students = array();
    $context = context_course::instance($course);
    $allstudents = get_enrolled_users($context, 'block/analytics_graphs:bemonitored', 0,
        'u.id, u.firstname, u.lastname, u.email, u.suspended', 'firstname, lastname');
    foreach ($allstudents as $student) {
        if ($student->suspended == 0) {
            if (groups_user_groups_visible($DB->get_record('course', array('id' =>  $course), '*', MUST_EXIST), $student->id)) {
                $students[] = $student;
            }
        }
    }
    return($students);
}


function block_disea_dashboard_get_number_of_modules_access_by_week($course, $students, $startdate) {
    global $DB;
    $timezone = new DateTimeZone(core_date::get_server_timezone());
    $timezoneadjust   = $timezone->getOffset(new DateTime);
    foreach ($students as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($timezoneadjust, $startdate, $course, $startdate), $inparams);
    $sql = "SELECT userid+(week*1000000), userid, firstname, lastname, email, week, number
            FROM (
                SELECT  userid, week, COUNT(*) as number
                FROM (
                    SELECT log.userid, objecttable, objectid,
                    FLOOR((((log.timecreated + ?) / 86400) - (?/86400))/7) as week
                    FROM {logstore_standard_log} log
                    WHERE courseid = ? AND action = 'viewed' AND target = 'course_module'
                    AND log.timecreated >= ? AND log.userid $insql
                    GROUP BY userid, week, objecttable, objectid
                ) as temp
                GROUP BY userid, week
            ) as temp2
            LEFT JOIN {user} usr ON usr.id = temp2.userid
            ORDER by LOWER(firstname), LOWER(lastname), userid, week";
    
    $results = $DB->get_records_sql($sql, $params);
    return($results);
}

function block_disea_dashboard_get_assignment_grades($course, $students) {
    global $DB;
    foreach ($students as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($course), $inparams);
    $sql = "SELECT qa.id, q.id as quizid, q.name, qa.userid, q.sumgrades as maxpoints, MAX(qa.sumgrades) as points, qa.timefinish
        	FROM mdl_quiz q
        	LEFT JOIN mdl_quiz_attempts qa on q.id = qa.quiz AND qa.state = 'finished'
            WHERE q.course = ? 
            GROUP BY q.id, qa.userid;";
    $results = $DB->get_records_sql($sql, $params);
    return($results);
}

/**
 * This funktion should be able to create different types of diagrams with min 1 data series and max 3 data series.
 * It also creates a form für deleting or adding the diagram from/to the dashboard.
 * @Param array &$ds for the finished diagrams with buttons
 * @Param int $chart type of the chart as string (bar, stacked, horizontal, line, smooth, pie, doughnut)
 * @Param string $title The title of the diagram
 * @Param array $labels An Array with all the labels for the diagram
 * @Param int $fieldnr Number that is equivalet to database field name
 * @Param string $url The link to with you will be redirected, when you delete or add the diagram
 * @Param mixed $form Object of the form for the add/remove button
 * @Param mixed &$d_obj Object from database which contains values if diagrams should be displayed or not for the student
 * @Param mixed &$d_obj_field The specific field which should be used for this diagram
 * @Param int $value 0 or 1 depending on if diagram should be displayed (1) or not (0)
 * @Param string $s1_t Name of the first series
 * @Param array $s1_d Data of the first series
 * @Param string $s2_t [optional] Name of the second series
 * @Param array $s2_d [optional] Data of the second series
 * @Param string $s3_t [optional] Name of the third series
 * @Param array $s3_d [optional] Data of the third series
 */
function create_diagram (&$ds, $chart, $title, $labels, $fieldnr, $url, $form, $d_obj, $value, $s1_t, $s1_d, $s2_t=NULL, $s2_d=NULL, $s3_t=NULL, $s3_d=NULL) {
    global $DB, $OUTPUT;
    if($chart === 'bar') {
        $c = new core\chart_bar();
    } else if ($chart === 'stacked') {
        $c = new core\chart_bar();
        $c->set_stacked(true);
    } else if ($chart === 'horizontal') {
        $c = new core\chart_bar();
        $c->set_horizontal(true);
    } else if ($chart === 'line') {
        $c = new core\chart_line();
    } else if ($chart === 'smooth') {
        $c = new core\chart_line();
        $c->set_smooth(true);
    } else if ($chart === 'pie') {
        $c = new core\chart_pie();
    } else if ($chart === 'doughnut') {
        $c = new core\chart_pie();
        $c->set_doughnut(true);
    }
    $c->set_title($title);
    $c->set_labels($labels);
    $s1 = new core\chart_series($s1_t, $s1_d);
    $c->add_series($s1);
    if($s2_t) {
        $s2 = new core\chart_series($s2_t, $s2_d);
        $c->add_series($s2);
    }
    if($s3_t) {
        $s3 = new core\chart_series($s3_t, $s3_d);
        $c->add_series($s3);
    }
    //Now make the form
    $form->set_data((object)array('diagram'=> $fieldnr));
    if ($fromform = $form->get_data()){
        $di = $_POST ['diagram'];
        $test = 'diagram'.$di;
        $d_obj->$test = $value;
        $DB->update_record('disea_diagrams', $d_obj);
        redirect($url, $di.' Deleted');
    }
    array_push($ds,  (object)['d' => $OUTPUT->render_chart($c, false), 'b' => $form->render()]);
}