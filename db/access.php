<?php

/**
 * Snap block caps.
 *
 * @package   block_snap
 * @copyright 2015 Blake Kidney
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
	
	/*
    'block/snap:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
	*/
	
    'block/snap:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
	
	'block/snap:viewstudentprogress' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
	
	/*
	INSERT INTO capabilities (name, captype, contextlevel, component, riskbitmask)
	VALUES ('block/snap:viewstudentprogress', 'read', 50, 'block_snap', 8)
	
	INSERT INTO role_capabilities (contextid, roleid, capability, permission, timemodified, modifierid)
	VALUES 
		(1, 1, 'block/snap:viewstudentprogress', 1, UNIX_TIMESTAMP(), 0),
		(1, 3, 'block/snap:viewstudentprogress', 1, UNIX_TIMESTAMP(), 0),
		(1, 4, 'block/snap:viewstudentprogress', 1, UNIX_TIMESTAMP(), 0),
		(1, 9, 'block/snap:viewstudentprogress', 1, UNIX_TIMESTAMP(), 0)
	*/
	
);