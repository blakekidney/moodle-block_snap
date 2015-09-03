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
$userid = optional_param('user', '0', PARAM_INT); 

//obtain the course record
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

//require that a user is logged in an has access to this course
require_login($course);
require_capability('block/snap:viewstudentprogress', $coursecontext);

//set the url for this page (which will be used in several places)
$pageurl = new moodle_url('/blocks/snap/forum_posts.php', array('id' => $courseid));

//------------------------------------------------------------
// PAGE OPTIONS
//------------------------------------------------------------
$pageheader = get_string('forum_posts_header', 'block_snap');
//set the url for this page
$PAGE->set_url($pageurl);
//set the page theme layout
$PAGE->set_pagelayout('incourse');  
//set the <title> of the page
$PAGE->set_title($pageheader);
//set the heading for the page
$PAGE->set_heading($pageheader);
//add a class to the body
$PAGE->add_body_class('snap-forum-posts');

//tell the navbar to ignore the active page and add our module name 
//https://docs.moodle.org/dev/Navigation_API#Navbar
$PAGE->navbar->add($pageheader);

//------------------------------------------------------------
// PAGE HEADER
//------------------------------------------------------------
//display the beginning of the page
echo $OUTPUT->header();
//display the heading
echo $OUTPUT->heading($pageheader);

//------------------------------------------------------------
// PAGE CONTENTS
//------------------------------------------------------------

//show course title
echo PHP_EOL.'<h5 class="course-name">'.$course->fullname.'</h5>';

//create the dropdown to select the student
$sql = "
SELECT u.id, u.firstname, u.lastname
FROM {role_assignments} a
INNER JOIN {user} u ON u.id = a.userid
INNER JOIN {context} x ON x.id = a.contextid
INNER JOIN {course} c ON c.id = x.instanceid 
	AND x.contextlevel = 50
INNER JOIN {role} r ON r.id = a.roleid
WHERE r.archetype = 'student' AND c.id = ?
GROUP BY a.userid
ORDER BY u.lastname, u.firstname
";
$results = $DB->get_records_sql($sql, array($course->id));
$options = array();
foreach($results as $row) {
	$options[$row->id] = $row->lastname.', '.$row->firstname;
}

// create the select
echo html_writer::start_tag('div', array('class' => 'studentchooser'));
$select = new single_select(
	new moodle_url('/blocks/snap/forum_posts.php', array('id' => $courseid)),
	'user',							//name
	$options,						//options
	$userid							//selected
);
$select->method = 'get';
$select->set_label(get_string('label_student_select', 'block_snap').':');
echo $OUTPUT->render($select);
echo html_writer::end_tag('div'); // end studentchooser

//show the progress for the student
if(!$userid) {

	echo PHP_EOL.'<p class="alert alert-warning">'.get_string('forum_posts_no_userid', 'block_snap').'</p>'.PHP_EOL;
	
} else {
	
	//obtain the user record and show the photo at the top
	$sql = "
	SELECT u.id, u.firstname, u.lastname, u.email, u.picture, u.firstnamephonetic, 
		u.lastnamephonetic, u.middlename, u.alternatename, u.imagealt
	FROM {user} u
	WHERE u.id = ?
	";
	$student = $DB->get_record_sql($sql, array($userid), MUST_EXIST);
	
	//show the user picture and the user's name at the top
	echo '<hr>';
	echo '<h3 class="stuheader">';
	echo '<span class="stupic">'.$OUTPUT->user_picture($student, array('courseid' => $course->id, 'link' => true)).'</span>'.PHP_EOL;
	echo '<span class="stuname">'.$student->firstname.' '.$student->lastname.'</span>'.PHP_EOL;
	echo '</h3>'.PHP_EOL;
	
	//obtain the forum posts for the student in this course
	$sql = "SELECT @rownum:=@rownum+1 AS id,
				f.course AS courseid, f.id AS forumid, d.id AS discussid, p.id AS postid,
				f.name AS forumname, d.name AS discussname, 
				u.id AS userid, u.firstname, u.lastname, u.email,
				p.parent, p.modified, p.subject, p.message, p.attachment,
				t.id AS parentid, t.modified AS parent_modified, t.subject AS parent_subject, t.message AS parent_message,
				x.id AS contextid, i.filename
			FROM {forum} f
			INNER JOIN (SELECT @rownum := 0) r
			INNER JOIN {forum_discussions} d ON d.forum = f.id 
			INNER JOIN {forum_posts} p ON p.discussion = d.id
			INNER JOIN {user} u ON u.id = p.userid
			INNER JOIN {course_modules} m ON m.course = f.course AND m.instance = f.id AND m.module = 9
			INNER JOIN {context} x ON x.instanceid = m.id AND contextlevel = '70'
			LEFT JOIN {files} i ON i.contextid = x.id 
				AND i.itemid = p.id
				AND i.component = 'mod_forum'
				AND i.filearea = 'attachment'
				AND i.filesize > 0
				AND i.filename != '.'
			LEFT JOIN {forum_posts} t ON t.id = p.parent
			WHERE f.course = ? 
				AND f.type = 'general'
				AND u.id = ?
			ORDER BY p.modified DESC";
	$entries = $DB->get_records_sql($sql, array($courseid, $userid));
	
	if(empty($entries)) {
	
		//if no results, then we need to look for the forum in a meta course
		$sql = "SELECT @rownum:=@rownum+1 AS id,
					n.courseid AS courseid, f.id AS forumid, d.id AS discussid, p.id AS postid,
					f.name AS forumname, d.name AS discussname, 
					u.id AS userid, u.firstname, u.lastname, u.email,
					p.parent, p.modified, p.subject, p.message, p.attachment,
					t.id AS parentid, t.modified AS parent_modified, t.subject AS parent_subject, t.message AS parent_message,
					x.id AS contextid, i.filename
				FROM enrol n
				INNER JOIN (SELECT @rownum := 0) r
				INNER JOIN {forum} f ON f.course = n.courseid AND f.type = 'general'
				INNER JOIN {forum_discussions} d ON d.forum = f.id 
				INNER JOIN {forum_posts} p ON p.discussion = d.id
				INNER JOIN {user} u ON u.id = p.userid
				INNER JOIN {course_modules} m ON m.course = n.courseid AND m.instance = f.id AND m.module = 9
				INNER JOIN {context} x ON x.instanceid = m.id AND contextlevel = '70'
				LEFT JOIN {files} i ON i.contextid = x.id 
					AND i.itemid = p.id
					AND i.component = 'mod_forum'
					AND i.filearea = 'attachment'
					AND i.filesize > 0
					AND i.filename != '.'
				LEFT JOIN {forum_posts} t ON t.id = p.parent
				WHERE n.enrol = 'meta' 
					AND n.customint1 = ?
					AND u.id = ?
				ORDER BY p.modified DESC";
		$entries = $DB->get_records_sql($sql, array($courseid, $userid));
	}
	
	if(empty($entries)) {
		
		echo PHP_EOL.'<p class="alert alert-warning">'.get_string('forum_posts_no_posts', 'block_snap').'</p>'.PHP_EOL;
		
	} else {
		
		//group the posts as their may be more than one for each file that was attached
		$formposts = array();
		
		foreach($entries as $entry) {
			//group by forum			
			$f = $entry->forumid;
			if(!isset($formposts[$f])) {
				$formposts[$f] = array(); 
				$formposts[$f]['name'] = $entry->forumname;
				$formposts[$f]['posts'] = array();
			}
			
			//group by post
			$p = $entry->postid;
			if(!isset($formposts[$f]['posts'][$p])) $formposts[$f]['posts'][$p] = array();
			
			//add the entry
			$formposts[$f]['posts'][$p][] = $entry;		
		}		
		
		echo '<div class="forumposts">'.PHP_EOL;
		
		//iterate the forum posts
		foreach($formposts as $forum) {
			
			//create the header for the forum
			echo '<hr><h2 class="forum-name">'.$forum['name'].'</h2>'.PHP_EOL;
			
			foreach($forum['posts'] as $posts) {
				//we only need to pull the first post for the data concerning the post
				$post = reset($posts);
				
				//item
				echo '<div class="forum-item">'.PHP_EOL;
				
				//post
				echo '<div class="forum-post">'.PHP_EOL;
				
				//subject
				echo '<div class="forum-subject">'.PHP_EOL;
				echo '<span class="forum-subject-name">'.$post->subject.'</span>';
				echo html_writer::link(
					new moodle_url('/mod/forum/discuss.php', array('d' => $post->discussid)), 
					get_string('button_view', 'block_snap'),
					array('class' => 'btn btn-default', 'target' => '_blank')
				).PHP_EOL;
				echo '</div>'.PHP_EOL; //end .forum-subject
				
				//date
				echo '<div class="forum-date">'.date('l, F j, Y', $post->modified).'</div>'.PHP_EOL;
				
				//message
				echo '<div class="forum-message">'.trim($post->message).'</div>'.PHP_EOL;
				
				//attachments
				if($post->attachment) {
					foreach($posts as $file) {
						echo '<div class="forum-file">'.PHP_EOL;
						echo html_writer::link(
							new moodle_url(
								'pluginfile.php/'.$file->contextid.
								'/mod_forum/attachment/'.$file->postid.
								'/'.rawurlencode($file->filename)
							), 
							$file->filename
						).PHP_EOL;
						echo '</div>'.PHP_EOL; //end .forum-file
					}				
				}
				
				echo '</div>'.PHP_EOL; //end .forum-post
				
				//parent post
				if($post->parentid) {
					echo '<div class="forum-parent-divider">'.PHP_EOL;
					echo get_string('forum_posts_reply', 'block_snap').' ';
					echo date('D, M j, Y', $post->parent_modified).':';
					echo '</div>'.PHP_EOL;  //end .forum-parent-divider
					echo '<div class="forum-parent-message">'.trim($post->parent_message).'</div>';				
				}

				echo '</div>'.PHP_EOL; //end .forum-item
				
			}	
		}

		echo '</div>'.PHP_EOL; //end .forumposts		
		
	}
	
}

//------------------------------------------------------------
// PAGE FOOTER
//------------------------------------------------------------
//display the end of the page
echo $OUTPUT->footer();