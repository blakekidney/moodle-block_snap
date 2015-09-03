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

//require that a user is logged in an has access to this course
require_login($course);

//set the url for this page (which will be used in several places)
$pageurl = new moodle_url('/blocks/snap/progress_select.php', array('id' => $courseid));

//------------------------------------------------------------
// LANGUAGE STRINGS
//------------------------------------------------------------
$schedheader = get_string('progress_select_header', 'block_snap');


//------------------------------------------------------------
// PAGE OPTIONS
//------------------------------------------------------------
//set the url for this page
$PAGE->set_url($pageurl);
//set the page theme layout
$PAGE->set_pagelayout('incourse');  
//set the <title> of the page
$PAGE->set_title($schedheader);
//set the heading for the page
$PAGE->set_heading($schedheader);
//add a class to the body
$PAGE->add_body_class('snap-progress-select');

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

//check to see if user has ability to manage activities within the course
$coursecontext = context_course::instance($course->id);
if(!has_capability('moodle/course:manageactivities', $coursecontext)) {
	
	echo get_string('progress_dating_error_access', 'block_snap');

//check to see if completion is enabled for the course
} elseif(!$course->enablecompletion) {

	echo html_writer::tag('p', get_string('no_completion_tracking', 'block_snap'), array('class' => 'alert alert-danger'));

//otherwise, display the selection tool
} else {


	echo html_writer::tag('p', get_string('progress_select_description', 'block_snap'), array('class' => 'alert alert-info'));

	//pull the modules
	$modinfo = get_fast_modinfo($course);

	//instantiate the form and pass the module information as custom data
	$mform = new block_snap_progress_form($pageurl, get_fast_modinfo($course));

	//form cancel
	if($mform->is_cancelled()) {
		//if the form was canceled, return to the moodle course view page
		redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);
		
	} 

	//form submission
	if($formdata = $mform->get_data()) {
			
		$count = 0;
		
		/*
		//pull all the gradeitems for quizes in this course
		require_once($CFG->libdir.'/gradelib.php');
		$quizitems = grade_item::fetch_all(array('itemtype' => 'mod', 'itemmodule' => 'quiz', 'courseid' => $course->id));
		*/
		
		//iterate every parameter
		foreach($formdata as $key => $value) {
			
			//does the key match what we are seeking
			if(!preg_match('/^mod_(\d+)$/', $key, $m)) continue;
			$modid = intval($m[1]);
			
			//if we don't have a module id, then skip
			if(!$modid) continue;
			
			//we have a match, not see if it exists in the list of modules
			if(empty($modinfo->cms[$modid])) continue;
			
			//it does exist, so update the record
			$mod = $modinfo->cms[$modid];
						
			//check to see if the value has changed
			$completed = $value ? '2' : '0';
			if($mod->completion == $completed) continue;
			
			//the value has changed, so let's update this
			$cm_record = new stdClass();
			$cm_record->id = $modid;
			$cm_record->completion = $completed;
			$cm_record->completiongradeitemnumber = null;
			$cm_record->completionview = $completed ? 1 : 0;
			//$cm_record->completionexpected = 0;
			
			/*
			assign
				- Change completionsubmit on the assign table to 1.
			quiz
				- Change completiongradeitemnumber on course_modules 
				  to the itemnumber from grade_items. Almost always 0.
			forum
				- Change completionposts on quiz to 1.
			activitytask
				- Change completiondone on the activitytask table to 1.
			*/
					
			if($mod->modname == 'assign') {
				$cm_record->completionview = 0;
				//update the assign table
				$assign = new stdClass();
				$assign->id = $mod->instance;
				$assign->completionsubmit = 1;
				$DB->update_record('assign', $assign);
			}
			if($mod->modname == 'quiz') {
				$cm_record->completionview = 0;
				$cm_record->completiongradeitemnumber = 0;
			}
			if($mod->modname == 'forum') {
				$cm_record->completionview = 0;
				//update the forum table
				$forum = new stdClass();
				$forum->id = $mod->instance;
				$forum->completionposts = 1;	
				$DB->update_record('forum', $forum);			
			}
			if($mod->modname == 'activitytask') {
				$cm_record->completionview = 0;
				//update the activitytask table
				$task = new stdClass();
				$task->id = $mod->instance;
				$task->completiondone = 1;
				$DB->update_record('activitytask', $task);
			}	
			
			$DB->update_record('course_modules', $cm_record);
			$count++;
			
		}
		
		//update the cache
		rebuild_course_cache($courseid, true);

		//inform the user of how many changes were made
		echo html_writer::div($count.' '.get_string('progress_num_updated', 'block_snap'), 'alert alert-success');
			
		//add a button to continue to course or to continue to date selector
		echo html_writer::tag('hr', '');
		echo html_writer::link(
			new moodle_url('/course/view.php', array('id' => $courseid)), 
			get_string('button_to_course', 'block_snap'), 
			array('class' => 'btn btn-default', 'style' => 'margin-right:20px')
		);
		echo html_writer::link(
			new moodle_url('/blocks/snap/progress_dating.php', array('id' => $courseid)), 
			get_string('button_to_dateselector', 'block_snap'), 
			array('class' => 'btn btn-success')
		);
		

	} else {
		//display the form
		$mform->display();
	}

}

//------------------------------------------------------------
// PAGE FOOTER
//------------------------------------------------------------
//display the end of the page
echo $OUTPUT->footer();