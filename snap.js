/*
/////////////////////////////////////////////////////////////////////////////
SNAP - MOODLE COURSE SIMPLIFIED
This Javascript assists block_snap.
/////////////////////////////////////////////////////////////////////////////
*/
//jQuery(function($) {
require(['jquery'], function($) {	
	
	/********************************************
	INITIALIZE SNAP ON COURSE HOME PAGE
	*********************************************/	
	//if we are in editing mode, then skip
	if($('body').is('.editing')) return false;
	//if we are not on the course home page, then skip
	if(!(/course\/view.php/.test(window.location.pathname))) return false;
	//if we are not using the topics layout, then skip
	//if(!$('#page-course-view-topics').length) return false;
	
	/********************************************
	VARIABLES
	*********************************************/	
	var store = (typeof(localStorage) !== 'undefined' ? window.localStorage : false);
	var main = $('#region-main');
	var sections = main.find('.topics > li.section.main');
	var hascurrent = (sections.filter('.current').length > 0);
	var storedsection = (store) ? store.getItem('snapcurrent') : '';
	var lead = $('#section-0').children('div.content');
	var label = $('#SnapJSData').attr('data-nav-label');
	var html = '';
		
	/********************************************
	CREATE SECTION BUTTONS
	*********************************************/
	//create the section buttons and only show current section
	html = '';
	html += '<div class="snap-sections-nav">';
	html += '<div class="snap-sections-nav-label">'+label+'</div>';
	//iterate sections
	sections.each(function(i, elem) {
		var sect = $(elem);
		//skip the first section
		if(sect.is('#section-0')) return true;
		//setup variable to indicate the current section
		var cls = '';
		//if this is not the current section, then hide it
		if( 
			(storedsection && sect.is(storedsection)) ||
			(!storedsection && hascurrent && sect.is('.current')) ||
			(!storedsection && !hascurrent && sect.is('#section-1'))
		  ) {
			cls = ' snap-current';
			sect.addClass('snap-active');
		} else {
			sect.hide();
		}
		//create a button that targets this section
		html += '<div class="snap-sections-nav-btn'+cls+'">';
		html += '<a href="#'+sect.attr('id')+'">';
		html += sect.attr('id').replace(/\D+/, '');
		html += '</a>';	
		html += '</div>';		
	});
	html += '</div>';
	$(html)
		.appendTo(lead)
		.find('a')
		.click(function(event) {
			event.preventDefault();
			//hide the active element
			sections.filter('.snap-active').removeClass('snap-active').hide();
			//show this new element
			$( $(this).attr('href') ).addClass('snap-active').show();
			//remove the class from the current button
			$(this).closest('.snap-sections-nav').children('.snap-current').removeClass('snap-current');
			//toggle the button
			$(this).parent().addClass('snap-current');
			//store the element in storage so that we can start here on page refresh
			if(store) store.setItem('snapcurrent', $(this).attr('href'));
		});

});