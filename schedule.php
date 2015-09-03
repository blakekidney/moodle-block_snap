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
//-------------------------------------------------------------
//load the Moodle framework and necessary classes
require_once('../../config.php');

//url parameters
$courseid = required_param('id', PARAM_INT); 
$download = optional_param('download', '', PARAM_ALPHA); 

//obtain the course record
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

//require that a user is logged in an has access to this course
require_login($course);

//determine access levels
$isteacher = has_capability('block/snap:viewstudentprogress', context_course::instance($COURSE->id));

//------------------------------------------------------------
// ICAL DOWNLOAD OPTION
//------------------------------------------------------------
if($download == 'ical' && $course->startdate && $course->enablecompletion) {
	
	//load the iCalendar class
	require_once __DIR__.'/classes/iCal/autoload.php';
	
	//create new calendar
	$ical = new \Eluceo\iCal\Component\Calendar('Moodle Snap - Course Schedule ('.$_SERVER['HTTP_HOST'].')');
	
	$ical->setName($course->shortname);
	$ical->setDescription($course->fullname);
	
	//pull the information for all the modules
	$modinfo = get_fast_modinfo($course);
	foreach($modinfo->cms as $mod) {
	
		//if completion is not enabled, then skip it
		if(!$mod->completion || !$mod->completionexpected) continue;
		
		//if hidden then skip it
		if(!$mod->visible) continue;
		
		$dt = new DateTime();
		$dt->setTimestamp($mod->completionexpected);
				
		//create an event
		$ievent = new \Eluceo\iCal\Component\Event();
		$ievent->setDtStart($dt);
		$ievent->setDtEnd($dt);
		$ievent->setNoTime(true);
		$ievent->setSummary($mod->name);

		//add Timezone (optional)
		$ievent->setUseTimezone(true);

		//add event to calendar
		$ical->addComponent($ievent);
	
	}
	
	//set headers
	$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $course->shortname).'_Calendar.ics';
	header('Content-Type: text/calendar; charset=utf-8');
	header('Content-Disposition: attachment; filename="'.$filename.'"');

	//output
	echo $ical->render();
	exit();
	
}


//------------------------------------------------------------
// PAGE OPTIONS
//------------------------------------------------------------
//obtain the header for the page
$schedheader = get_string('schedule_header', 'block_snap');
//set the url for this page
$PAGE->set_url(new moodle_url('/blocks/snap/schedule.php'), array('id' => $courseid));
//set the page theme layout
$PAGE->set_pagelayout('incourse');  
//set the <title> of the page
$PAGE->set_title($schedheader);
//set the heading for the page
$PAGE->set_heading($schedheader);

//tell the navbar to ignore the active page and add our module name 
//https://docs.moodle.org/dev/Navigation_API#Navbar
$PAGE->navbar->add($schedheader);

//------------------------------------------------------------
// PAGE HEADER
//------------------------------------------------------------
//display the beginning of the page
echo $OUTPUT->header();
//display the heading
echo $OUTPUT->heading($schedheader);

//------------------------------------------------------------
// PAGE CONTENTS
//------------------------------------------------------------

//show course title
echo PHP_EOL.'<h5 class="course-name">'.$course->fullname.'</h5>';

if(!$course->startdate) {

	echo html_writer::tag('p', get_string('no_start_date', 'block_snap'), array('class' => 'alert alert-danger'));

//check to see if completion is enabled for the course
} elseif(!$course->enablecompletion) {

	echo html_writer::tag('p', get_string('no_completion_tracking', 'block_snap'), array('class' => 'alert alert-danger'));
	
} else {
	
	//show the description for the page
	echo html_writer::tag('p', get_string('schedule_description', 'block_snap'), array('class' => 'alert alert-info'));
	
	//pull the information for all the modules
	$modinfo = get_fast_modinfo($course);
	
	//if I am a student, pull completion information for me
	$completed = array();
	if(!$isteacher) {		
		
		//pull those the user has completed successfully
		$sql = "SELECT p.id, p.coursemoduleid, p.timemodified
				FROM {course_modules_completion} p
				INNER JOIN {course_modules} cm ON cm.id = p.coursemoduleid				
				WHERE cm.visible = 1
					AND cm.completionexpected > 0
					AND p.completionstate > 0
					AND p.userid = ? 
					AND cm.course = ?
				ORDER BY p.timemodified DESC";
		$results = $DB->get_records_sql($sql, array($USER->id, $course->id));
		if(!empty($results)) foreach($results as $row) {
			$completed[$row->coursemoduleid] = $row->timemodified;
		}			
		
		//create a progress overview
		$sql = "SELECT '1' AS id, 
					COUNT(cm.id) AS total, 
					COUNT(p.id) AS completed,
					SUM(CASE WHEN cm.completionexpected < UNIX_TIMESTAMP() THEN 1 ELSE 0 END) AS needed
				FROM {course_modules} cm
				LEFT JOIN {course_modules_completion} p ON p.coursemoduleid = cm.id 
					AND p.completionstate > 0
					AND p.userid = ?
				WHERE cm.visible = 1
					AND cm.completion > 0 
					AND cm.completionexpected > 0
					AND cm.course = ?
				";
		$progdata = $DB->get_record_sql($sql, array($USER->id, $course->id));
		$s = ($progdata->completed - $progdata->needed);
		
		echo '<table border="0" class="snap-progress-overview table table-condensed table-bordered table-striped">'.PHP_EOL;
		echo '  <tr>'.PHP_EOL;
		echo '    <th>'.get_string('progress_table_total', 'block_snap').'</th>'.PHP_EOL;
		echo '    <th>'.get_string('progress_table_completed', 'block_snap').'</th>'.PHP_EOL;
		echo '    <th>'.get_string('progress_table_needed', 'block_snap').'</th>'.PHP_EOL;
		echo '    <th>'.get_string($s < 0 ? 'progress_table_behind' : 'progress_table_ahead', 'block_snap').'</th>'.PHP_EOL;
		echo '  </tr>'.PHP_EOL;
		echo '  <tr>'.PHP_EOL;
		echo '    <td>'.$progdata->total.'</td>'.PHP_EOL;
		echo '    <td>'.$progdata->completed.'</td>'.PHP_EOL;
		echo '    <td>'.$progdata->needed.'</td>'.PHP_EOL;
		echo '    <td>'.abs($s).'</td>'.PHP_EOL;
		echo '  </tr>'.PHP_EOL;
		echo '</table>'.PHP_EOL;
		
	}
	
	//establish the start date of the course	
	$courseStart = new DateTime();
	$courseStart->setTimestamp($course->startdate);
	$sundayStart = clone $courseStart;
	if($sundayStart->format('D') != 'Sun') $sundayStart->modify('last Sunday');
	
	//show when the course starts	
	echo '<h3 class="course-starts">'.
		 (($courseStart <= new DateTime()) ? get_string('started_on', 'block_snap') : get_string('starts_on', 'block_snap')).
		 ' <span>'.$courseStart->format('l, F j, Y').'</span></h3>'.PHP_EOL;
	
	//create the schedule
	echo '<div class="snap-schedule">'.PHP_EOL;
	
	//reorganize the activities into weeks and days
	$acts = array();
		
	//pull language strings
	$str_due = get_string('due', 'block_snap');
	$str_week = get_string('week', 'block_snap');
	$str_late = get_string('late', 'block_snap');
	$str_cmplon = get_string('completed_on', 'block_snap');
	
	//iterate all the modules and create each item for dragging around
	$lastweek = 0;
	foreach($modinfo->cms as $mod) {
		
		//if completion is not enabled, then skip it
		if(!$mod->completion || !$mod->completionexpected) continue;
		
		//if hidden then skip it
		if(!$mod->visible) continue;
		
		$dt = new DateTime();
		$dt->setTimestamp($mod->completionexpected);
		
		//calculate the week number
		$wk = intval(floor($sundayStart->diff($dt)->days / 7) + 1);
		
		//format the date
		$dts = $dt->format('l, F j, Y g:ia');
		
		//add the activity accordingly
		if(!isset($acts[$wk])) $acts[$wk] = array();
		if(!isset($acts[$wk][$dts])) $acts[$wk][$dts] = array();
		
		$acts[$wk][$dts][] = $mod;
		
		//set the last weekin the series
		if($wk > $lastweek) $lastweek = $wk;
		
	}
	
	//sort by weeks
	ksort($acts);
	
	//create the calendar based upon the number of weeks
	for($w = 1; $w <= $lastweek; $w++) {
		
		//what is the start date and end date for the week
		$wks = clone $sundayStart;
		$wks->modify('+'.(($w-1)*7).' days');
		$wke = clone $wks;
		$wke->modify('+6 days');
		
		//create the container for the week
		echo '<div class="week-block week-'.$w.' '.(empty($acts[$w]) ? ' no-activities' : '').'">'.PHP_EOL;
		
		//create the header for the week
		echo '<div class="week-header">'.PHP_EOL;
		echo '<span class="week-num">'.$str_week.' '.$w.'</span> ';
		echo '<span class="week-dates">';
		echo  '('.$wks->format('M jS');
		echo ' &ndash; ';
		echo (($wks->format('M') != $wke->format('M')) ? $wke->format('M jS') : $wke->format('jS')).')';
		echo '</span>'; //week-dates
		echo '</div>'.PHP_EOL; //end week-header
		
		if(empty($acts[$w])) {
			echo '<div class="date-block">'.get_string('schedule_nothing_due', 'block_snap').'</div>';
			continue;
		}
		
		//sort by the date first
		ksort($acts[$w]);
		
		//iterate each day and add the entries
		foreach($acts[$w] as $dts => $mods) {
			
			$date = new DateTime($dts);
			
			//create the day header
			echo '<div class="date-block">';
			echo '<div class="date-header">';
			echo $str_due.' on '.$dts;
			echo '</div>'.PHP_EOL; //end date-header
			
			foreach($mods as $mod) {
				
				//create the status indicators
				$statusIcon = '';
				$statusIconName = '';
				$statusText = '';
				if(!$isteacher) {
					if(isset($completed[$mod->id])) {
						//pull the date the assignment was completed and create the text
						$datedone = new DateTime();
						$datedone->setTimestamp($completed[$mod->id]);
						$statusText = $str_cmplon.' '.$datedone->format('M jS g:ia');
						if($completed[$mod->id] > $mod->completionexpected) 
							$statusText .= ' <span class="late">('.$str_late.')</span>';
						//set the icon name
						$statusIconName = 'i/completion-auto-y';
					} else {
						//set the icon name
						$statusIconName = 'i/completion-auto-n';						
					}
					//create the html for the icon
					$statusIcon = html_writer::empty_tag('img', 
						array(	
							'src' => $OUTPUT->pix_url($statusIconName),
							'class' => 'icon', 
							'alt' => ' ', 
							'role' => 'presentation'
						)
					);					
				}
				
				//create the html for the module icon
				$icon = html_writer::empty_tag('img', 
					array(	
						'src' => $mod->get_icon_url(),
						'class' => 'icon', 
						'alt' => ' ', 
						'role' => 'presentation'
					)
				);
								
				//create the row of data for the module
				echo '<div class="mod mod-'.$mod->modname.'">';
				if($statusIcon) echo '<span class="status-icon">'.$statusIcon.'</span>';
				echo '<a href="'.$mod->url.'"><span class="mod-name">'. $icon.' '.$mod->name.'</span></a>';
				if($statusText) echo '<span class="status">'.$statusText.'</span>';
				echo '</div>'.PHP_EOL; //end mod
				
			}
			
			echo '</div>'.PHP_EOL; //end date-block
			
		}
		
		echo '</div>'.PHP_EOL; //end week
	}
	
	//add buttons for ical download and printing
	echo '<hr>';
	echo '<p class="out-controls">';
	echo '<a href="'.$PAGE->url.'&download=ical" class="btn btn-primary">';
	echo '<span class="icon-white icon-calendar"></span>'.get_string('button_ical', 'block_snap').'</a>';
	echo '&nbsp;';
	echo $OUTPUT->help_icon('button_ical', 'block_snap');
	echo ' &nbsp; ';
	echo '<a id="snap_print_page" href="#" onclick="return false;" class="btn btn-default btn-cancel">';
	echo '<span class="icon-white icon-print"></span>'.get_string('button_print', 'block_snap').'</a>';
	echo '</p>';
	
	echo '</div>'.PHP_EOL; //end schedule
	
}

//------------------------------------------------------------
// PAGE FOOTER
//------------------------------------------------------------
//display the end of the page
echo $OUTPUT->footer();