/*
/////////////////////////////////////////////////////////////////////////////
SNAP - MOODLE COURSE SIMPLIFIED
This Javascript assists block_snap.
/////////////////////////////////////////////////////////////////////////////
*/
//jQuery(function($) {
require(['jquery'], function($) {	
	
	/********************************************
	PRINT BUTTON FUNCTIONALITY
	*********************************************/
	$('#snap_print_page').click(function(event) {
		//copy the doctype
		var newline = "\r\n";
		var html = "<!DOCTYPE "
				 + document.doctype.name
				 + (document.doctype.publicId ? ' PUBLIC "' + document.doctype.publicId + '"' : '')
				 + (!document.doctype.publicId && document.doctype.systemId ? ' SYSTEM' : '') 
				 + (document.doctype.systemId ? ' "' + document.doctype.systemId + '"' : '')
				 + '>' + newline;
		html += '<html>' + newline + '<head>' + newline;
		//copy tags
		$('head').find('meta,title,link').each(function() {
			var tag = $(this).prop('tagName').toLowerCase();
			html += '<' + tag;	
			//iterate the attributes
			$.each(this.attributes, function(i, a) {
				html += ' ' + a.name.toLowerCase() + '="' + a.value + '"';
			});	
			if($(this).html()) html += ' >' + $(this).html()+'</' + tag + '>';
			else html += ' />';
			html += newline;
		});
		html += '</head>' + newline;
		html += '<body class="'+$('body').attr('class')+' snap-printing">' + newline;
		//copy the main region
		html += '<div id="region-main">' + newline;
		html += $('#region-main').html() + newline;
		html += '</div>' + newline;
		html += '</body>' + newline + '</html>';

		//write to a new window
		var w = 540, h = 600;
		var prop = 'width=' + w + ', height=' + h + ', '
				 + 'left=' + (Math.max(screen.width-w, 0) / 2)
				 + 'top=' + (Math.max(screen.height-h, 0) / 2)
				 + 'location=no, titlebar=no, menubar=no, status=no,'
				 + 'resizable=yes, scrollbars=yes, toolbar=yes';
		var win = window.open('', '_blank', prop);
		win.document.write(html);
		win.document.close();	
		win.onload = function() {
			win.print();			
		}	
	});	
	
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
	var crsid = window.location.search.replace(/^.?id=(\d+).*?$/, '$1');
	var store = (typeof(localStorage) !== 'undefined' ? window.localStorage : false);
	var main = $('#region-main');
	var sections = main.find('.course-content > ul > li.section.main');
	var hascurrent = (sections.filter('.current').length > 0);
	var storedsection = (store) ? store.getItem('snapcurrent-'+crsid) : '';
	var lead = $('#section-0').children('div.content');
	var jsdata = $('#SnapJSData');
	var html = '';
	
	/********************************************
	MOVE PROGRESS BAR
	*********************************************/
	$('#snap-progress').appendTo(lead);
	
	/********************************************
	CREATE SECTION BUTTONS
	*********************************************/
	if(jsdata.attr('data-topicnav') == 'yes') {	
		//create the section buttons and only show current section
		html = '';
		html += '<div class="snap-sections-nav">';
		html += '<div class="snap-sections-nav-label">'+jsdata.attr('data-topicnav-label')+'</div>';
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
				if(store) store.setItem('snapcurrent-'+crsid, $(this).attr('href'));
			});
	}
	
});