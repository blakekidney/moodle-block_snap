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

//let's make sure that we have a logged in user before proceeding
//this is not the conventional method for doing this, but it is much faster
if(!$USER->id) redirect($CFG->wwwroot.'/index.php');

//------------------------------------------------------------
// PARAMETERS
//------------------------------------------------------------
//url parameters
$id = optional_param('id', 0, PARAM_INT);  //course module id
$date = optional_param('date', '', PARAM_RAW);
$time = optional_param('time', '23:59:59', PARAM_RAW);
$opendays = optional_param('opendays', 14, PARAM_INT);

// https://online.westernseminary.edu/olc-dev/blocks/snap/progress_dating_ajax.php?id=41779&date=2015-08-01

//if we don't have an id then exit
if(!$id) exit( get_string('missingparameter', 'error') );

//the database uses timestamps
$duetimestamp = 0;
$opentimestamp = 0;

if($date) {

	//validate the date
	try {
		$duedate = new DateTime($date.' 23:59:59');
	} catch(Exception $e) { 
		exit( get_string('progress_dating_error_date', 'block_snap').' ('.$date.')' );
	}

	//validate the time
	if(!preg_match('/^(\d{1,2}):(\d{2}):?(\d{2})?\s*([pPaA])?\.?\s*([mM])?\.?\s*$/', $time, $m)) {
		exit( get_string('progress_dating_error_time', 'block_snap').' ('.$time.')' );
	}

	//modify the date and time accordingly 
	$duedate->modify( $duedate->format('Y-m-d').' '.$time );
	//convert to a timestamp
	$duetimestamp = $duedate->getTimestamp();
	
	//open date
	$opendate = new DateTime( $duedate->format('Y-m-d') );
	if($opendays) $opendate->modify( '-'.$opendays.' days' );
	//convert to a timestamp
	$opentimestamp = $opendate->getTimestamp();

}

//------------------------------------------------------------
// OBTAIN DATA FROM DATABASE
//------------------------------------------------------------
$sql = "
SELECT cm.id, cm.course, cm.module, cm.instance, m.name AS modname,
	cm.completion, cm.completionview, cm.completionexpected	
FROM {course_modules} cm
INNER JOIN {modules} m ON m.id = cm.module
WHERE cm.id = ?";

$crsmod = $DB->get_record_sql($sql, array($id), IGNORE_MISSING);
if(!$crsmod) exit( get_string('progress_dating_error_activity', 'block_snap') );

//obtain the course record
$course = $DB->get_record('course', array('id' => $crsmod->course), '*', IGNORE_MISSING);

//------------------------------------------------------------
// VERIFY ACCESS TO MAKE CHANGES
//------------------------------------------------------------
if(!isloggedin()) exit('refresh');

//check to see if I have the capability of 
$coursecontext = context_course::instance($crsmod->course);
if(!has_capability('moodle/course:manageactivities', $coursecontext)) {
	exit( get_string('progress_dating_error_access', 'block_snap') );
}

//------------------------------------------------------------
// UPDATE DATABASE
//------------------------------------------------------------

/*
NOTE: Rather than update the database directly, it might be 
better to use the update_moduleinfo() function in /course/modlib.php
*/

$update = new stdClass();
$update->id = $crsmod->id;
$update->completionexpected = $duetimestamp;
$DB->update_record('course_modules', $update);

//update assignment, quiz, activitytask dates as well
if($crsmod->modname == 'assign') {
	$assign = new stdClass();
	$assign->id = $crsmod->instance;
	$assign->duedate = $duetimestamp;
	$assign->allowsubmissionsfromdate = $opentimestamp;
	$DB->update_record('assign', $assign);
}
if($crsmod->modname == 'quiz') {
	$quiz = new stdClass();
	$quiz->id = $crsmod->instance;
	$quiz->timeclose = $duetimestamp;
	$quiz->timeopen = $opentimestamp;
	$DB->update_record('quiz', $quiz);
}
if($crsmod->modname == 'activitytask') {
	$activitytask = new stdClass();
	$activitytask->id = $crsmod->instance;
	$activitytask->duedate = $duetimestamp;
	$DB->update_record('activitytask', $activitytask);
}

//update the cache
rebuild_course_cache($crsmod->course, true);

exit('yes');


