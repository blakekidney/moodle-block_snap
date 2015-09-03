<?php

/**
 * Displays a schedule for a course.
 *
 * @package   block_snap
 * @copyright 2015 Blake Kidney
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//------------------------------------------------------------
// INITALIZATION
//------------------------------------------------------------
//load the Moodle framework and necessary classes
require_once('../../config.php');
require_once('progress_form.php');

//url parameters
$courseid = required_param('id', PARAM_INT); 

//obtain the course record
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

//require that a user is logged in an has access to this course
require_login($course);
require_capability('block/snap:viewstudentprogress', $coursecontext);

//set the url for this page (which will be used in several places)
$pageurl = new moodle_url('/blocks/snap/progress_overall.php', array('id' => $courseid));

//------------------------------------------------------------
// PAGE OPTIONS
//------------------------------------------------------------
$pageheader = get_string('progress_overall_header', 'block_snap');
//set the url for this page
$PAGE->set_url($pageurl);
//set the page theme layout
$PAGE->set_pagelayout('incourse');  
//set the <title> of the page
$PAGE->set_title($pageheader);
//set the heading for the page
$PAGE->set_heading($pageheader);
//add a class to the body
$PAGE->add_body_class('snap-progress-overall');

//tell the navbar to ignore the active page and add our module name 
//https://docs.moodle.org/dev/Navigation_API#Navbar
$PAGE->navbar->add($pageheader);

//------------------------------------------------------------
// PAGE HEADER
//------------------------------------------------------------
//display the beginning of the page
echo $OUTPUT->header();
//display the heading
echo $OUTPUT->heading($pageheader);

//------------------------------------------------------------
// PAGE CONTENTS
//------------------------------------------------------------

//show course title
echo PHP_EOL.'<h5 class="course-name">'.$course->fullname.'</h5>';

//obtain the progress data for the course modules in the course
$sql = "SELECT '1' AS id, 
			COUNT(cm.id) AS total, 
			SUM(CASE WHEN cm.completionexpected < UNIX_TIMESTAMP() THEN 1 ELSE 0 END) AS needed
		FROM {course_modules} cm
		WHERE cm.visible = 1
			AND cm.completion > 0 
			AND cm.completionexpected > 0
			AND cm.course = ?
";
$progdata = $DB->get_record_sql($sql, array($course->id));

//if we don't have any progress data, then report that
if(!$progdata) {
	//if there are none, then display a message indicating this
	//@param #1: message to display   //@param #2: link to use for the continue button
	notice(get_string('noprogressdata', 'block_snap'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

//obtain a list of all the students
/*
$sql = "
SELECT a.userid, u.firstname, u.lastname, u.email, u.picture, u.firstnamephonetic, 
	u.lastnamephonetic, u.middlename, u.alternatename, u.imagealt,
	a.id AS roleid, r.name AS rolename,
	COUNT(cm.id) AS total, COUNT(p.id) AS completed
FROM {role_assignments} a
INNER JOIN {user} u ON u.id = a.userid
INNER JOIN {context} x ON x.id = a.contextid
INNER JOIN {course} c ON c.id = x.instanceid 
	AND x.contextlevel = 50
INNER JOIN {role} r ON r.id = a.roleid
INNER JOIN {enrol} l ON l.courseid = c.id AND l.enrol = 'manual'
INNER JOIN {user_enrolments} e ON e.enrolid = l.id
	AND e.userid = a.userid
LEFT JOIN {course_modules} cm ON cm.course = c.id
	AND cm.visible = 1
	AND cm.completion > 0 
	AND cm.completionexpected > 0
LEFT JOIN {course_modules_completion} p ON p.userid = a.userid
	AND p.coursemoduleid = cm.id 
WHERE r.archetype = 'student' AND c.id = ?
GROUP BY a.userid
ORDER BY u.lastname, u.firstname
";
*/
$sql = "
SELECT u.id, u.firstname, u.lastname, u.email, u.picture, u.firstnamephonetic, 
	u.lastnamephonetic, u.middlename, u.alternatename, u.imagealt,
	a.id AS roleid, r.name AS rolename,
	COUNT(cm.id) AS total, COUNT(p.id) AS completed
FROM {role_assignments} a
INNER JOIN {user} u ON u.id = a.userid
INNER JOIN {context} x ON x.id = a.contextid
INNER JOIN {course} c ON c.id = x.instanceid 
	AND x.contextlevel = 50
INNER JOIN {role} r ON r.id = a.roleid
LEFT JOIN {course_modules} cm ON cm.course = c.id
	AND cm.visible = 1
	AND cm.completion > 0 
	AND cm.completionexpected > 0
LEFT JOIN {course_modules_completion} p ON p.coursemoduleid = cm.id
	AND p.completionstate > 0
	AND p.userid = a.userid
WHERE r.archetype = 'student' AND c.id = ?
GROUP BY a.userid
ORDER BY u.lastname, u.firstname
";
$students = $DB->get_records_sql($sql, array($course->id));

if(!$students) {
	//if there are none, then display a message indicating this
	//@param #1: message to display   //@param #2: link to use for the continue button
	notice(get_string('nostudents', 'block_snap'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

//create a new html table
$table = new html_table();
//add the class to the table
$table->attributes['class'] = 'generaltable';

//set the table header row along with alignment
$table->head  = array(
	get_string('progress_overall_tbl_stu', 'block_snap'), 
	get_string('progress_overall_tbl_prog', 'block_snap'), 
	get_string('progress_overall_tbl_bd', 'block_snap')
);
$table->align = array('left');

//iterate students and create the table
foreach($students as $stu) {
	//setup an array to save the table row data
	$row = array();	
	$pic = '<span class="stupic">'.$OUTPUT->user_picture($stu, array('courseid' => $course->id, 'link' => true)).'</span>';
	$name = '<span class="stuname">'.$stu->firstname.' '.$stu->lastname.'</span>';	
	$row[] = $pic.$name;
	
	//calculate percentages
	//we could have done this in MYSQL, but this works too
	$c = round(($stu->completed/$progdata->total)*100);  //percent complete
	$n = round(($progdata->needed/$progdata->total)*100);	  //percent needed or how much should be done by now
	$d = abs($stu->completed - $progdata->needed); 	  //difference from what is needed
	
	//add the progress bar
	$cls = '';
	$note = '';
	if($stu->completed >= $progdata->needed) {
		$cls = 'progress-ahead';				
	} elseif(($n-$c) > 10) {
		$cls = 'progress-waybehind';	
	} else {
		$cls = 'progress-behind';	
	}
	//create the progress bar
	$bar = PHP_EOL.'<div class="snap-progress '.$cls.'">'.PHP_EOL;		
	$bar .= '<div class="snap-progress-bar">'.PHP_EOL;	
	$bar .= '<span class="snap-progress-text">'.get_string('progress', 'block_snap').
			': '.$c.'% '.get_string('complete', 'block_snap').
			'&nbsp; ('.$stu->completed.'/'.$progdata->total.')'.
			'</span>'.PHP_EOL;
	if($n > 1 && $n < 99) $bar .= '<span class="snap-progress-needed" style="width:'.$n.'%"></span>'.PHP_EOL;	
	$bar .= '<span class="snap-progress-completed" style="width:'.$c.'%"></span>'.PHP_EOL;
	$bar .= '</div>'.PHP_EOL; 	//snap-progress-bar	
	$bar .= '</div>'.PHP_EOL;   //snap-progress
	$row[] = $bar;
	
	//add the breakdown button
	$row[] = html_writer::link(
		new moodle_url('/blocks/snap/progress_student.php', array('id' => $COURSE->id, 'user' => $stu->id)), 
		get_string('button_view', 'block_snap'),
		array('class' => 'btn btn-default')
	).'</li>'.PHP_EOL;
	
	
	//add the row to the table
	$table->data[] = $row;
}
//display the table
echo html_writer::table($table);

//display not about what should be done
echo PHP_EOL.'<p>'.str_replace('{x}', $progdata->needed, get_string('progress_overall_text_needed', 'block_snap')).'</p>'.PHP_EOL;


//------------------------------------------------------------
// PAGE FOOTER
//------------------------------------------------------------
//display the end of the page
echo $OUTPUT->footer();