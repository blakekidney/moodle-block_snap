<?php
/**
 * Form for editing HTML block instances.
 *
 * @package   block_snap
 * @copyright 2015 Blake Kidney
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_snap_edit_form extends block_edit_form {
    
	protected function specific_definition($mform) {

		//section header
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
		
		//topics navigation
		$mform->addElement('select', 'config_topicnav', get_string('config_label_topicnav', 'block_snap'), array('1' => 'enabled', '0' => 'disabled'));
		$mform->setDefault('config_topicnav', '1');
		$mform->setType('config_topicnav', PARAM_INT);
		$mform->addHelpButton('config_topicnav', 'config_help_topicnav', 'block_snap');
		
		//topics navigation label
		$mform->addElement('text', 'config_navlabel', get_string('config_label_navlabel', 'block_snap'), 'maxlength="30" size="15" ');
		$mform->setDefault('config_navlabel', get_string('navlabel', 'block_snap'));
		$mform->setType('config_navlabel', PARAM_TEXT);
		$mform->addHelpButton('config_navlabel', 'config_help_navlabel', 'block_snap');
		
		//syllabus button option
		$mform->addElement('select', 'config_syllabus', get_string('config_label_syllabus', 'block_snap'), array('1' => 'enabled', '0' => 'disabled'));
		$mform->setDefault('config_syllabus', '1');
		$mform->setType('config_syllabus', PARAM_INT); 
		$mform->addHelpButton('config_syllabus', 'config_help_syllabus', 'block_snap');
		
		//schedule button option
		$mform->addElement('select', 'config_schedule', get_string('config_label_schedule', 'block_snap'), array('1' => 'enabled', '0' => 'disabled'));
		$mform->setDefault('config_schedule', '1');
		$mform->setType('config_schedule', PARAM_INT); 
		$mform->addHelpButton('config_schedule', 'config_help_schedule', 'block_snap');

	}
}
