<?php

/**
 * The form for turning on completion for modules.
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    block_snap
 * @copyright  2015 Blake Kidney
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');

/**
 * Module completion enablement
 *
 * @package    block_snap
 * @copyright  2015 Blake Kidney
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_snap_progress_form extends moodleform {

    /**
     * Defines forms elements
	 * @see: https://docs.moodle.org/dev/lib/formslib.php_Form_Definition
     */
    public function definition() {

        $mform = $this->_form;
		$modinfo = $this->_customdata;

		//let's create an array of those plugins that support the completion features
		$supportedmods = array();
		foreach($modinfo->get_used_module_names() as $modtype => $modname) {
			if(plugin_supports('mod', $modtype, FEATURE_COMPLETION_TRACKS_VIEWS, 0)) {
				$supportedmods[$modtype] = $modname;
			}
		}
		
		foreach($modinfo->get_section_info_all() as $sectid => $sectinfo) {
			
			//skip the first section because in snap we turn it into a menu
			if(!$sectid) continue;
			
			//skip the section if the id is greater than what is allowed by the course
			//if($sectid > $course->numsections) continue;
			
			//skip the section if not visible or not avilable
			//if(!$sectinfo->visible || !$sectinfo->available) continue;
			
			//skip section if it doesn't have any modules
			if(empty($modinfo->sections[$sectid])) continue;
			
			//add the section name as a header
			$mform->addElement('html', '<h3 class="section-header">'.$sectinfo->name.'</h3>');
			
			//iterate the modules within the section
			foreach($modinfo->sections[$sectid] as $modid) {
				
				//completion = completion
				//completionexpected
				
				$mod = $modinfo->cms[$modid];	
				
				//if this is a subheader, then add it
				if($mod->modname == 'subheader') {
					$mform->addElement('html', '<div class="subheader">'.$mod->name.'</div>');
					continue;
				}
				
				//check to see if this plugin supports completion tracking
				if(!isset($supportedmods[$mod->modname])) continue;
				
				//create the html for the module icon
				$icon = html_writer::empty_tag('img', 
							array(	
								'src' => $mod->get_icon_url(),
								'class' => 'icon', 
								'alt' => ' ', 
								'role' => 'presentation'
							)
						);			
			
				$mform->addElement(
					'advcheckbox',				//type of element 
					'mod_'.$modid, 				//name of the element
					false,						//label on the left
					$icon.' '.$mod->name, 	    //label on the right
					null,						//html attributes
					array('0', $modid)			//checked and unchecked values
				);
				//if completion is already set, then check the checkbox
				if($mod->completion) $mform->setDefault('mod_'.$modid, true);
				
			}
		}
        
		//add the action buttons
		$this->add_action_buttons();
		
    }

}
