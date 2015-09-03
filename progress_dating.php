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

//url parameters
$courseid = required_param('id', PARAM_INT); 

//obtain the course record
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

//require that a user is logged in an has access to this course
require_login($course);

//set the url for this page (which will be used in several places)
$pageurl = new moodle_url('/blocks/snap/progress_dating.php', array('id' => $courseid));
$ajaxurl = (string) new moodle_url('/blocks/snap/progress_dating_ajax.php');
$courseurl = (string) new moodle_url('/course/view.php', array('id' => $courseid));

//------------------------------------------------------------
// PAGE OPTIONS
//------------------------------------------------------------
$headertext = get_string('progress_dating_header', 'block_snap');
//set the url for this page
$PAGE->set_url($pageurl);
//set the page theme layout
$PAGE->set_pagelayout('base');  
//set the <title> of the page
$PAGE->set_title($headertext);
//set the heading for the page
$PAGE->set_heading($headertext);

//tell the navbar to ignore the active page and add our module name 
//https://docs.moodle.org/dev/Navigation_API#Navbar
$PAGE->navbar->add($headertext);

//add the required javascript
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js('/blocks/snap/js/progress-dating.js');

//$CFG->additionalhtmlhead .= PHP_EOL.'<link href="https://online.westernseminary.edu/olc-dev/blocks/snap/styles.css" rel="stylesheet" type="text/css">';

//------------------------------------------------------------
// PAGE HEADER
//------------------------------------------------------------
//display the beginning of the page
echo $OUTPUT->header();
//display the heading
echo $OUTPUT->heading($headertext);

//------------------------------------------------------------
// PAGE CONTENTS
//------------------------------------------------------------

//check to see if user has ability to manage activities within the course
$coursecontext = context_course::instance($course->id);
if(!has_capability('moodle/course:manageactivities', $coursecontext)) {
	
	echo get_string('progress_dating_error_access', 'block_snap');

//check the start dates for the course
} elseif(!$course->startdate) {
	
	echo html_writer::tag('p', get_string('progress_dating_error', 'block_snap'), array('class' => 'alert alert-danger'));

//check to see if completion is enabled for the course
} elseif(!$course->enablecompletion) {

	echo html_writer::tag('p', get_string('no_completion_tracking', 'block_snap'), array('class' => 'alert alert-danger'));

//otherwise, display the dating tool
} else {

	echo html_writer::tag('p', get_string('progress_dating_description', 'block_snap'), array('class' => 'alert alert-info'));
	
	//create a warning about having javascript disabled that we will remove with javascript
	echo '<noscript>'.html_writer::tag('p', get_string('progress_dating_nojs', 'block_snap'), array('class' => 'snap-nojs alert alert-danger')).'</noscript>';
	
	//pull the modules
	course_modinfo::clear_instance_cache($course);
	$modinfo = get_fast_modinfo($course);
	$modlist = array();
	$callist = array();
	
	//establish the start date of the course	
	$start = new DateTime();
	$start->setTimestamp($course->startdate);
	
	//show when the course starts
	echo '<h3 class="course-starts">'.get_string('starts_on', 'block_snap').' <span>'.$start->format('l, F j, Y').'</span></h3>'.PHP_EOL;

	//iterate all the modules and create each item for dragging around
	foreach($modinfo->cms as $mod) {
		
		//if completion is not enabled, then skip it
		if(!$mod->completion) continue;
		
		//create the html for the module icon
		$icon = html_writer::empty_tag('img', 
					array(	
						'src' => $mod->get_icon_url(),
						'class' => 'icon', 
						'alt' => ' ', 
						'role' => 'presentation'
					)
				);
		//create the html for the module item
		$item = '<div class="mod-item" '.
					'data-id="'.$mod->id.'" '.
					'data-mod="'.$mod->modname.'" '.
					'data-modid="'.$mod->instance.'" '.
				'>'.
				'<span class="mod-icon">'.$icon.'</span> '.
				'<span class="mod-name">'.$mod->name.'</span>'.
				'</div>';
		
		
		//does this module have a date or not?
		if($mod->completionexpected) {
			
			$dt = new DateTime();
			$dt->setTimestamp($mod->completionexpected);
			
			//if the date is before the start date, then list as not dated
			if($dt < $start) {
				$modlist[] = $item;
			} else {
				
				//group by the date
				$dts = $dt->format('Y-m-d');
				if(!isset($callist[$dts])) $callist[$dts] = array();			
				$callist[$dts][] = $item;
			
			}
			
		} else {
			$modlist[] = $item;
		}
	}
	
	echo PHP_EOL.'<div id="block_snap_dating_tool" data-ajaxurl="'.$ajaxurl.'">'.PHP_EOL;
	
	////////////////////////////////////////////////
	//SIDEBAR
	echo '<div class="snap-course-mods">'.PHP_EOL;
	echo '<div class="snap-course-mods-inner">'.PHP_EOL;
	
	echo '<div class="snap-course-mods-bar"></div>'.PHP_EOL;
	
	echo '<div class="snap-controls">'.PHP_EOL;
	echo '<div class="control-header">'.get_string('progress_dating_options', 'block_snap').'</div>';
	echo '<hr>';
	echo '<div class="control-time"><input type="text" class="input-time" maxlength="10" value="11:59 PM" />'.
		 '<span>'.get_string('progress_dating_label_time', 'block_snap').'</span></div>'.PHP_EOL;
	echo '<hr>';
	echo '<div class="control-open-date"><input type="text" class="input-open-date" maxlength="2" value="14" />'.
		 '<span>'.get_string('progress_dating_label_opendate', 'block_snap').'</span></div>'.PHP_EOL;
	echo '</div>'.PHP_EOL; //snap-controls
	
	//create the list of course modules
	echo '<div class="snap-modlist" data-dts="">'.PHP_EOL;
	if(!empty($modlist)) echo implode(PHP_EOL, $modlist).PHP_EOL;
	echo '</div>'.PHP_EOL; //snap-modlist
	echo '<div class="no-mods alert alert-info">'.get_string('progress_dating_no_mods', 'block_snap').'</div>';
	
	echo '</div>'.PHP_EOL; //snap-course-mods-inner
	echo '</div>'.PHP_EOL; //snap-course-mods
	
	////////////////////////////////////////////////
	//CALENDAR
	
	//create the calendar items
	echo '<div class="snap-dating-calendar">'.PHP_EOL;
	
	//create sixteen weeks
	$current = new DateTime();
	$current->setTimestamp($course->startdate);
	
	//check to see if the date starts on a Sunday, if not rewind
	if($current->format('D') != 'Sun') {
		//rewind to Sunday
		$current->modify('last Sunday');		
	}
	
	echo '<div class="snap-calendar">'.PHP_EOL;
	echo '<table border="0">'.PHP_EOL;
	
	for($w = 1; $w <= 16; $w++) {
		
		//create a new row for the week
		echo '<tr>'.PHP_EOL;		
		echo '<td class="week-num"><span>'.$w.'</span></td>';
		
		for($d = 0; $d < 7; $d++) {
			
			//pull the current date and time and format like SQL
			$dts = $current->format('Y-m-d');
			
			$active = ($current >= $start);
						
			//check to see if the current date falls on or after the start date
			$cls = 'day '.($active ? 'active' : 'inactive');
			$cls .= ' '.strtolower($current->format('D'));
			
			
			echo '<td class="'.$cls.'">'.PHP_EOL;
						
			//insert the date in the corner
			if($active) {
				echo '<div class="snap-calendar-date">'.$current->format('D, M j').'</div>';
				echo '<div class="snap-modlist" data-dts="'.$dts.'">'.PHP_EOL;
				//pull any calendar items and display them			
				if(!empty($callist[$dts])) {
					echo implode(PHP_EOL, $callist[$dts]);
				}
				echo '</div>'.PHP_EOL; //snap-modlist
			}
			
			echo '</td>'.PHP_EOL;
			
			//increase by one date
			$current->modify('+1 day');
		}	
		
		echo '</tr>'.PHP_EOL;
		
	}
	
	echo '</table>'.PHP_EOL;
	echo '</div>'.PHP_EOL;		
	echo '</div>'.PHP_EOL;
	echo '</div>'.PHP_EOL;
	
}

echo '<p class="backbutton"><a href="'.$courseurl.'" class="btn btn-default">'.get_string('button_back_to_course', 'block_snap').'</a></p>';

//echo '<script type="text/javascript" src="https://online.westernseminary.edu/olc-dev/blocks/snap/js/progress-dating.js"></script>';

//------------------------------------------------------------
// PAGE FOOTER
//------------------------------------------------------------
//display the end of the page
echo $OUTPUT->footer();