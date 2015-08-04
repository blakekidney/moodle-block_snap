<?php
/**
 * Settings for the Snap block
 *
 * @package   block_snap
 * @copyright 2015 Blake Kidney
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    //function admin_setting($name, $visiblename, $description, $defaultsetting, $paramtype, $size)	
	$settings->add(new admin_setting_configtext(
		'block_snap/navlabel',										
		get_string('admin_config_navlabel', 'block_snap'),
		get_string('admin_config_navlabel_desc', 'block_snap'),
		get_string('navlabel', 'block_snap'),
		PARAM_TEXT,
		15
	));
	$settings->add(new admin_setting_configtext(
		'block_snap/syllabus_url',										
		get_string('admin_config_syllabus_url', 'block_snap'),
		get_string('admin_config_syllabus_url_desc', 'block_snap'),
		'',
		PARAM_URL
	));
	$settings->add(new admin_setting_configmulticheckbox(
		'block_snap/syllabus_params',										
		get_string('admin_config_syllabus_params', 'block_snap'),
		get_string('admin_config_syllabus_params_desc', 'block_snap'),
		null,
		array(
			'cid' => get_string('admin_config_syllabus_params_cid', 'block_snap'), 
			'cnum' => get_string('admin_config_syllabus_params_cnum', 'block_snap'), 
			'cname' => get_string('admin_config_syllabus_params_cname', 'block_snap'), 
			'uid' => get_string('admin_config_syllabus_params_uid', 'block_snap'), 
			'uname' => get_string('admin_config_syllabus_params_uname', 'block_snap'), 
			'unum' => get_string('admin_config_syllabus_params_unum', 'block_snap'),
			'uemail' => get_string('admin_config_syllabus_params_uemail', 'block_snap'),
		)
	));
}


