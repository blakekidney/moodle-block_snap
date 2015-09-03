# moodle-block_snap
A block for Moodle that enhances usability for navigating through a course. Snap has several features that include:

 * Taking all the activities and resources in the intro section of the course and converting them into a menu called "Course Navigation". This makes the menu customizable and persistent through the course. 
 * Using Javascript, toggle buttons are created at the top allowing users to quickly switch between the different sections.
 * A "Syllabus" button can be added to dynamically pull the syllabus from an external system. You can add parameters from the course to the end of a url to pull the syllabus from another system.
 * A progress bar and overview can be added to the course by enhancing the course completion feature within Moodle. 
 * An iCal export feature that allows student's to import activities into their personal calendars.

## Installing Snap
Download the plugin. Copy the "block_snap" directory into the "blocks" directory in Moodle. Login and navigate to the "Site administration -> Notifications" page. You should see the block listed for installation.
 
## Enabling Snap
To enable snap, just add the block to a course. 

## Course Navigation Menu
Snap adds a "Course Navigation" menu that defaults to the top of the right side. The first link on the menu is a home button for quickly coming back to the main page for the course. If the configuration options are set, "Syllabus" and "Schedule and Progress" links are added as well. Then, any modules created in the intro or first section of the course are added as links. This makes it easy to customize the menu and add sub-pages that persist throughout the course. This could include an overview of the course, a biography, or steps for success. After that, several other links are added depending on whether you are a student or teacher. 

For students, "Classmates" and "Grades" buttons are added. For teachers, a sub-menu named "Teacher Tools" that includes buttons for "Students", "Grades", "Review Student Progress", and "Review Student Forum Posts". The "Classmates", "Students", and "Grades" buttons link to pages already built into Moodle but makes them more accessible by having them on the course navigation menu. The "Review Student Progress" button opens a new pages that allows the teacher to see the course progress for all the tracked activities in the course. The "Review Student Forum Posts" provides a dropdown of the students that, when selected, shows a list of all the student's forum posts.

Snap differentiates between a student or teacher based upon the block/snap:viewstudentprogress capability. 

## Topics Navigation
The topics navigation uses Javascript to hide all the different sections (topics) in a course and then creates a list of numbered buttons for each topic at the top. Since this uses Javascript to display each topic, moving between topics is fast and convenient. Additionally, the last viewed topic is saved in the local storage in the browser so that when the person visits the page again, it will display that topic.

This feature can be turned on or off in the block configuration. Additionally, the label that appears before the numbered buttons can also be modified. This allows you to reference these difference for each course in case the teacher likes to divide up the course by "units", "lessons", "topics", or "weeks".

## Syllabus Button
The syllabus button provides a way to pull the syllabus in from an external source. A specific url can be defined that provides the source for pulling syllabi into the system. Different url parameters can be attached to the end of the url. 

For example, let's say you have all your syllabi linked to courses in a database. Each course record has an id that has been pushed into Moodle's idnumber field on the course. You then write a script that will pull the syllabus reference from the database based upon the course id. You add the url to Snap's configuration and select the url parameter "Course idnumber". Snap will then create the final url like this: http://www.example-site.edu/syllabi-download.php?cnum=5555  

This feature can be disabled on each instance of Snap in the block's individual configuration.

## Schedule and Progress
The "Schedule and Progress" feature leverages Moodle's course completion feature in order to date to any module (activity or resource) and also track progress on it.  

This feature relies on Moodle's completion tracking system. As such, to use the schedule and progress, you have to enable completion tracking both on the site and on the course. To enable it on the site, goto "Site administration -> Advanced features". Click the checkbox next to the "Enable completion tracking". Once this is done, it also needs to be enabled on the course. For this, Click to "Edit settings" under "Course administration". Under "Completion tracking", choose "Yes" from the dropdown.

Once completion is enabled, you can provide a date on any module by clicking on "Edit Settings" and then under "Activity completion" enabling completion tracking along with the "Expect completed on" date. In order to make this process a little faster, two other tools have been provided: the "Activity Completion Selector" and "Completion Dates Selector". These two buttons only appear when editing has been turned on. (See more about those below).

Additionally, for progress to be appropriate tracked, you must also set a start date for the course in the course settings.

### Schedule
The schedule provides a quick overview of those modules (activities or resources) that have completion enabled along with a date so that students can see at a glance what is due in each week. Additionally, the schedule includes a button for exporting the schedule to an iCalendar file which is support by most calendar systems. This allows student to import their activities into their calendar so they can easily see what is coming due.

The schedule also shows the progress for each item. When a item has been marked as complete, they will see the appropriate checkbox along with date it was completed.

## Progress
Progress helps to show how far the student has come and whether the student is ahead or behind. 

Snap shows how many modules have been completed out of the total and displays this as a percentage visually represented by a progress bar. The color of the progress bar changes depending on whether the student is ahead or behind. If the student is ahead, the bar is green. If the student is behind by 10 activities, the bar is yellow. If greater than 10, the bar is red. Additionally, the number ahead or behind is displayed below the progress bar. 

Teachers can also view an overview of all the students within the class by clicking on the "Review Student Progress". The progress bar for each student will be displayed along with a button to view a breakdown of the student's progress (almost identitical to what the student sees on their schedule).

### Activity Completion Selector
The "Activity Completion Selector" is only available when editing is turned on. This tools lists all the modules in the course allowing the teacher to quickly choose which ones they want added. This will enable completion tracking on those modules. Please note, however, that it will enable conditional tracking (automatic tracking) based on specific criteria. For most, this enables the "view" options which means the module will be marked complete once the student views it. For assignments, completion is marked when a student submits the assignment. For quizzes, completion is marked when the quiz is graded. For forums, completion is marked when they have posted once. 

You can easily open individual modules individually and change these options. It might be useful to go through and check which modules you want with this tool, and then go back on the few you want to be different and modify them individually. You don't have to use this tool at all. It is just to make work a little easier. 

### Completion Dates Selector
The "Completion Dates Selector" is only available when editing is turned on. This tools uses Javascript to allow teachers to drag-n-drop modules onto a calendar in order to date them. In order for a module to appear in the list, it must have completion tracking enabled. 

When a module is dragged onto the calendar, the "expected" date is set for the module's completion tracking. Additionally, the time is set to whatever the time option is on the side bar. You can easily change the time at any point to have a different time set for different modules. Lastly, when adding an activity, it will also set the open and close date. The close date will be set to the same date as the expected date. The open date will be set a certain number of days before the close date depending on the setting you set within the tool. You can change this for different modules at any time by modifying the date. The time or open days settings do not change for previously set modules. It only applies to the current module being dragged onto the calendar. 

(PLEASE NOTE: If for some reason you click on the tool and it does not appear, just press "refresh" on the browser. Slower Moodle sites run into delayed caching issues.)





