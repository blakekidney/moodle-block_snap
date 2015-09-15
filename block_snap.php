<?php
/**
 * Form for editing Snap block instances.
 *
 * @package   block_snap
 * @copyright 2015 Blake Kidney
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_snap extends block_base {
		
	/**
	 * Control which pages the block can be added to.
	 * 
	 * Page formats are formulated from the full path of the script that 
	 * is used to display that page. You should return an array with the 
	 * keys being page format names and the values being booleans (true or false). 
	 * Your block is only allowed to appear in those formats where the 
	 * value is true. Example format names are: course-view, site-index 
	 * (this is an exception, referring front page of the Moodle site), 
	 * course-format-weeks (referring to a specific course format), 
	 * mod-quiz (referring to the quiz module) and all (this will be used 
	 * for those formats you have not explicitly allowed or disallowed). 
	 *
	 */
	public function applicable_formats() {
        return array('course-view' => true);
    }
	/**
	 * Are you going to allow multiple instances of each block?
	 *
	 * @return  boolean 
	 * 
	 */
    public function instance_allow_multiple() {
        return false;
    }
	/**
	 * Whether your block wants to hide its header.
	 *
	 * @return  boolean 
	 * 
	 */
    public function hide_header() {
        return false;
    }
	/**
	 * Denotes whether your block wants to present a configuration interface to site admins or not. 
	 * 
	 * The configuration that this interface offers will impact all instances 
	 * of the block equally. To actually implement the configuration interface, 
	 * you will either need to rely on the default config_print() method or override it. 
	 *
	 * @return  boolean 
	 * 
	 */
    public function has_config() {
        return true;
    }
	/**
	 * Automatically called by the framework immediately after your instance data is loaded from the database.
	 *
	 * This includes the page type and id and all instance configuration data. 
	 * If there is some action that you need to take as soon as this data 
	 * becomes available and which cannot be taken earlier, 
	 * you should override this method. 
	 * 
	 */
    public function specialization() {
        //show the block title in the header
		$this->title = get_string('blocktitle', 'block_snap');	
		
		//add a class to the body indicating snap has initialized on this page
		//if the header hasn't been printed already
		if(!$this->page->headerprinted) {
			$this->page->add_body_class('snap-block-init');
		}
		
	}
	/**
	 * Method calls when a new instance of the block is created.
	 *
	 */
    public function instance_create() {
        global $DB;
		//let's set our own default values for the block
		$DB->update_record('block_instances', 
			array(
				'id' 				=> $this->instance->id,
				'showinsubcontexts' => '1',
				'pagetypepattern' 	=> '*',
				'defaultregion' 	=> 'side-post',
				'defaultweight' 	=> '-10'
			)
		);				
    }
	/**
	 * Assigns meaningful values to the object variables $this->title and $this->version.
	 *
	 */
    public function init() {
        //set the plugin name for the block
		$this->title = get_string('pluginname', 'block_snap');	
    }
	/**
	 * Populates the content of the block.
	 *
	 */
    public function get_content() {
		//if the content was already created, don't waste time
		if ($this->content !== null) {
			return $this->content;
		}
		
		//access the global variables
		global $DB, $USER, $COURSE;
		
		$this->content = new stdClass;
		$this->content->text   = '';
        $this->content->footer = '';
		$menuitems = array();
		
		//pull the context for this course
		$coursecontext = context_course::instance($COURSE->id);
		
		//check to make sure the user is enrolled
		if(!is_siteadmin() && !is_enrolled($coursecontext)) return $this->content; 
		
		//determine access levels
		$canViewStuProg = has_capability('block/snap:viewstudentprogress', $coursecontext);
		
		//pull the admin settings
		$syllUrl = trim(get_config('block_snap', 'syllabus_url'));
		$syllParams = explode(',', get_config('block_snap', 'syllabus_params'));
				
		//pull the different modules in the first section
		$sql = "
		SELECT cm.instance, cm.id AS cmid, s.sequence, m.name
		FROM {course_sections} s
		INNER JOIN {course_modules} cm ON cm.course = s.course 
			AND cm.section = s.id
			AND cm.visible = 1
		INNER JOIN {modules} m ON m.id = cm.module
		WHERE s.section = 0 
			AND s.course = ?
			AND m.name NOT IN('label', 'subheader', 'activitytypeheader')
		";
		$modules = $DB->get_records_sql($sql, array($COURSE->id));
		if(empty($modules)) return $this->content; 

		//we need to pull all the names of the different modules as well
		//lets construct a single statement to do this
		
		$sql = '';		
		$sequence = explode(',', current($modules)->sequence);
		$items = array();
		
		//group ids by their module types 
		$groups = array();
		foreach($modules as $row) {
			if(!isset($group[$row->name])) $group[$row->name] = array();
			$groups[$row->name][] = $row->instance;	
		}
		
		//iterate groups and construct sql
		foreach($groups as $name => $ids) {
			if($sql) $sql .= PHP_EOL.PHP_EOL."UNION ALL".PHP_EOL.PHP_EOL;
			$sql .= "SELECT id, name FROM {".$name."} WHERE id IN(".implode(",", $ids).")";					
		}

		//pull the modules from the database and add them to the modules
		$results = $DB->get_records_sql($sql);
		if(empty($results)) return $this->content;
		
		foreach($results as $row) {
			if(isset($modules[$row->id])) {
				//key the module to the course_module id
				$items[ $modules[$row->id]->cmid ] = array(
					'type' => $modules[$row->id]->name,
					'name' => $row->name,
					'id' => $row->id					
				);
			}				
		}	

		//let's create our navigation menu based upon the sequence
		
		//add a link to the course
		$url = new moodle_url('/course/view.php', array('id' => $COURSE->id));
		$menuitems[] = html_writer::link($url, get_string('button_home', 'block_snap'));		
		
		//add a link for the syllabus	
		if($syllUrl && (!isset($this->config->syllabus) || $this->config->syllabus)) {		
			//add the parameters to the syllabus url
			if(!empty($syllParams)) {			
				$syllUrl .= (strpos($syllUrl, '?')) ? '&' : '?';
				foreach($syllParams as $param) {
					$syllUrl .= $param.'=';
					if($param == 'cid') $syllUrl .= $COURSE->id;
					if($param == 'cnum') $syllUrl .= rawurlencode($COURSE->idnumber);
					if($param == 'cname') $syllUrl .= rawurlencode($COURSE->shortname);
					if($param == 'uid') $syllUrl .= $USER->id;
					if($param == 'uname') $syllUrl .= rawurlencode($USER->username);
					if($param == 'unum') $syllUrl .= rawurlencode($USER->idnumber);
					if($param == 'uemail') $syllUrl .= rawurlencode($USER->email);
					$syllUrl .= '&';
				}
				$syllUrl = rtrim($syllUrl, '&');
			}
			$menuitems[] = html_writer::link($syllUrl, get_string('button_syllabus', 'block_snap'));
		}
		
		//add a link for the schedule - only if completion is enabled
		if($COURSE->enablecompletion) {
			if(!isset($this->config->schedule) || $this->config->schedule) {
				$url = new moodle_url('/blocks/snap/schedule.php', array('id' => $COURSE->id));
				$menuitems[] = html_writer::link($url, get_string('button_schedule', 'block_snap'));
			}
		}
		
		//add a link to each of the modules in the top section (section-0)
		foreach($sequence as $cmid) {
			
			if(!isset($items[$cmid])) continue;
			
			$url = new moodle_url('/mod/'.$items[$cmid]['type'].'/view.php', array('id' => $cmid));
			$menuitems[] = html_writer::link($url, $items[$cmid]['name']);
			
		}			
			
		//add extra links for students
		if(!$canViewStuProg) {
			
			//add a link for classmates
			$url = new moodle_url('/user/index.php', array('id' => $COURSE->id, 'roleid' => '5', 'mode' => '1'));
			$menuitems[] = html_writer::link($url, get_string('button_classmates', 'block_snap'));
			
			//add a link for grades
			$url = new moodle_url('/grade/report/index.php', array('id' => $COURSE->id));
			$menuitems[] = html_writer::link($url, get_string('button_grades', 'block_snap'));
		}
		
		//create the html for the menu
		$this->content->text .= '<ul class="snap-nav">'.PHP_EOL;
		foreach($menuitems as $item) {
			$this->content->text .= '<li>'.$item.'</li>'.PHP_EOL;
		}
		$this->content->text .= '</ul>'.PHP_EOL;
		
		//if course completion is disabled add a link informing that the schedule will not appear unless they enable it
		if($this->page->user_is_editing()) {
			
			if($COURSE->enablecompletion) {
				$this->content->text .= '<div class="edit-controls">'.PHP_EOL;
				$this->content->text .= '<p>'.PHP_EOL;
				$this->content->text .= html_writer::link(
					new moodle_url('/blocks/snap/progress_select.php', array('id' => $COURSE->id)), 
					get_string('button_modselector', 'block_snap'),
					array('class' => 'btn btn-default')
				);
				$this->content->text .= '</p>'.PHP_EOL;
				$this->content->text .= '<p>'.PHP_EOL;
				$this->content->text .= html_writer::link(
					new moodle_url('/blocks/snap/progress_dating.php', array('id' => $COURSE->id)), 
					get_string('button_dateselector', 'block_snap'),
					array('class' => 'btn btn-success')
				);
				$this->content->text .= '</p>'.PHP_EOL;
				$this->content->text .= '</div>'.PHP_EOL;
			} else {
				$this->content->text .= html_writer::tag('p', get_string('no_completion_tracking', 'block_snap'), array('class' => 'alert alert-danger'));
			}		
		
		
		} else {
			
			//COURSE PROGRESS
			
			//determine whether this is a teacher or student
			if($canViewStuProg) {
				
				//TEACHER - provide a button to show the progress.
				$this->content->text .= '<p class="snap-nav-header">'.get_string('header_teacher_tools', 'block_snap').'</p>'.PHP_EOL;
				$this->content->text .= '<ul class="snap-nav">'.PHP_EOL;
				
				//students		
				$this->content->text .= '<li>'.html_writer::link(
					new moodle_url('/user/index.php', array('id' => $COURSE->id, 'roleid' => '5', 'mode' => '1')), 
					get_string('button_students', 'block_snap')
				).'</li>'.PHP_EOL;
				
				//grades
				$this->content->text .= '<li>'.html_writer::link(
					new moodle_url('/grade/report/index.php', array('id' => $COURSE->id)), 
					get_string('button_grades', 'block_snap')
				).'</li>'.PHP_EOL;
				
				//overall student progress
				$this->content->text .= '<li>'.html_writer::link(
					new moodle_url('/blocks/snap/progress_overall.php', array('id' => $COURSE->id)), 
					get_string('button_progress_overall', 'block_snap')
				).'</li>'.PHP_EOL;
				
				//forum postings
				$this->content->text .= '<li>'.html_writer::link(
					new moodle_url('/blocks/snap/forum_posts.php', array('id' => $COURSE->id)), 
					get_string('button_forums', 'block_snap')
				).'</li>'.PHP_EOL;
				$this->content->text .= '</ul>'.PHP_EOL;			
				
			} else {
				
				//STUDENT - create a progress bar
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
				$progdata = $DB->get_record_sql($sql, array($USER->id, $COURSE->id));
				
				//don't show the progress bar unless we have assignments marked for completion
				if($progdata->total > 0) {
				
					//we could have done this in MYSQL, but this works too
					$c = round(($progdata->completed/$progdata->total)*100);  //percent complete
					$n = round(($progdata->needed/$progdata->total)*100);	  //percent needed or how much should be done by now
					$d = abs($progdata->completed - $progdata->needed); 	  //difference from what is needed
					
					$cls = '';
					$note = '';
					if($progdata->completed >= $progdata->needed) {
						$cls = 'progress-ahead';
						if($d > 1) $note = str_replace('{x}', $d, get_string('progress_ahead_note', 'block_snap'));					
					} elseif(($n-$c) > 10) {
						$cls = 'progress-waybehind';
						if($d > 1) $note = str_replace('{x}', $d, get_string('progress_behind_note', 'block_snap'));	
					} else {
						$cls = 'progress-behind';
						if($d > 1) $note = str_replace('{x}', $d, get_string('progress_behind_note', 'block_snap'));	
					}
					//create the progress bar
					$this->content->text .= PHP_EOL.'<div id="snap-progress" class="snap-progress '.$cls.'">'.PHP_EOL;		
					$this->content->text .= '<div class="snap-progress-bar">'.PHP_EOL;	
					$this->content->text .= '<span class="snap-progress-text">'.get_string('progress', 'block_snap').
											': '.$c.'% '.get_string('complete', 'block_snap').'</span>'.PHP_EOL;
					if($n > 1 && $n < 99) $this->content->text .= '<span class="snap-progress-needed" style="width:'.$n.'%"></span>'.PHP_EOL;	
					$this->content->text .= '<span class="snap-progress-completed" style="width:'.$c.'%"></span>'.PHP_EOL;
					$this->content->text .= '</div>'.PHP_EOL; 	//snap-progress-bar	
					if($note) $this->content->text .= '<div class="snap-progress-note">'.$note.'</div>'.PHP_EOL;	
					$this->content->text .= '</div>'.PHP_EOL; //snap-progress
				
				}
				
			}
			
		}		
		
		//pass configuration data to the javascript
		$navLabel = trim(get_config('block_snap', 'navlabel'));
		$this->content->footer .= '<span id="SnapJSData"'.
			' data-topicnav="'.((!isset($this->config->topicnav) || $this->config->topicnav) ? 'yes' : 'no').'"'.
			' data-topicnav-label="'.((isset($this->config->navlabel)) ? $this->config->navlabel : ($navLabel ? $navLabel : get_string('navlabel', 'block_snap'))).'"'.
			'></span>';
		
		//require the snap javascript
		$this->page->requires->js('/blocks/snap/js/snap.js');		
		
		return $this->content;
	}
	/**
	 * Return an associative array of HTML attributes that will be given to your block's container element when Moodle constructs the output HTML.
	 *
	 * @return  array
	 */
    public function html_attributes() {
		//pull the default attributes
		$attrs = parent::html_attributes();
		//add attributes
		// $attrs['width'] = '50%';
		return $attrs;
	}
}
