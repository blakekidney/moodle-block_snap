<?php
/**
 * Form for editing Snap block instances.
 *
 * @package   block_snap
 * @copyright 2015 Blake Kidney
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_snap extends block_list {
		
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
		$this->page->add_body_class('snap-block-init');
		
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
		$this->content->items  = array();
		$this->content->icons  = array();
        $this->content->footer = '';
		
		//pull the admin settings
		$syllUrl = trim(get_config('block_snap', 'syllabus_url'));
		$syllParams = explode(',', get_config('block_snap', 'syllabus_params'));
		$navLabel = trim(get_config('block_snap', 'navlabel'));
				
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
		$this->content->items[] = HTML_WRITER::link($url, get_string('button_home', 'block_snap'));		
		
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
			$this->content->items[] = HTML_WRITER::link($syllUrl, get_string('button_syllabus', 'block_snap'));
		}
		
		//add a link for the schedule	
		if(!isset($this->config->schedule) || $this->config->schedule) {
			$url = new moodle_url('/blocks/snap/schedule.php', array('id' => $COURSE->id));
			$this->content->items[] = HTML_WRITER::link($url, get_string('button_schedule', 'block_snap'));
		}
		
		//add a link to each of the modules in the top section (section-0)
		foreach($sequence as $cmid) {
			
			if(!isset($items[$cmid])) continue;
			
			$url = new moodle_url('/mod/'.$items[$cmid]['type'].'/view.php', array('id' => $cmid));
			$this->content->items[] = HTML_WRITER::link($url, $items[$cmid]['name']);
			
		}		
		
		//add a link for classmates
		$url = new moodle_url('/user/index.php', array('id' => $COURSE->id, 'roleid' => '5', 'mode' => '1'));
		$this->content->items[] = HTML_WRITER::link($url, get_string('button_classmates', 'block_snap'));
			
		//add a link for grades
		$url = new moodle_url('/grade/report/index.php', array('id' => $COURSE->id));
		$this->content->items[] = HTML_WRITER::link($url, get_string('button_grades', 'block_snap'));
		
		//require the snap javascript
		if(!isset($this->config->topicnav) || $this->config->topicnav) {
			$this->page->requires->js('/blocks/snap/snap.js');		
			$label = (isset($this->config->navlabel)) ? $this->config->navlabel : ($navLabel ? $navLabel : get_string('navlabel', 'block_snap'));
			$this->content->footer .= '<span id="SnapJSData" data-nav-label="'.$label.'"></span>';
		}
		
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
