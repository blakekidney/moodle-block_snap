/*
/////////////////////////////////////////////////////////////////////////////
MODULE DATING TOOL FOR TRACKING PROGRESS
Part of the SNAP block
/////////////////////////////////////////////////////////////////////////////
*/
//jQuery(function($) {
require(['jquery', 'jqueryui'], function($, jqui) {
	
	//add a class to the body enabling the classes associated with
	$('body').addClass('snap-progress-dating');
	
	//--------------------------------------------------
	//OPTIONS
	//--------------------------------------------------
	var SIDEBAR_RIGHT = true;
	
	//--------------------------------------------------
	//BODY WRAP - NECESSARY FOR RIGHT SIDE MENU
	//--------------------------------------------------
	//prevent the code from firing twice when we wrap the body
	if(SIDEBAR_RIGHT) {
		if(!$('#bodywrap').length) {
			//wrap the body so that we can change the layout
			$('body').wrapInner('<div id="bodywrap"><div class="bodywrap-inner">');
		} else {
			//prevent code from firing a second time
			return false;
		}
	}	
	
	//--------------------------------------------------
	//VARIABLES
	//--------------------------------------------------
	var tool = $('#block_snap_dating_tool');
	var sidebar = tool.find('.snap-course-mods');
	var calendar = tool.find('.snap-dating-calendar');
	var inputTime = sidebar.find('input.input-time');
	var inputOpenDate = sidebar.find('input.input-open-date');
	var sideList = sidebar.find('.snap-modlist');
	var calList = calendar.find('.snap-modlist');
	var nomods = sidebar.find('.no-mods');
	var ajaxurl = tool.attr('data-ajaxurl');
	
	//--------------------------------------------------
	//DRAGGING
	//--------------------------------------------------
	var optsDraggable = {
		revert: 'invalid', 					//when stopped, send back to original location if not over a valid drop location
		revertDuration: 200,				//speed of animation when it reverts
		containment: 'document',			//constrains dragging to within the bounds of the specified element or region
		helper: 'clone',					//use a clone of the element instead of the original
		appendTo: 'body',					//where to append helper clone while dragging
		zIndex: 1001,						//set the zindex of the helper clone
		cursorAt: { top:10, left:10 },		//location of the cursor
		cursor: 'move',						//the CSS cursor during the drag operation
		scrollSensitivity: 10,				//distance in pixels from the edge of the viewport after which the viewport should scroll
		scrollSpeed: 10						//speed at which the window should scroll
	};
	sideList.children('.mod-item').draggable(optsDraggable);
	calList.children('.mod-item').draggable(optsDraggable);
	
	//--------------------------------------------------
	//DROPPING
	//--------------------------------------------------
	var optsDroppable = {
		accept: '.mod-item',
		hoverClass: 'drophover',
		tolerance: 'pointer',
		drop: function( event, ui ) {
			var moveFrom = ui.draggable.parent();
			var moveTo = $(this);
			
			//move the item right away to give the user instant feedback
			moveTo.append(ui.draggable);
			checkNumMods();
			
			//make an ajax call to update the dates
			var params = {
				'id' : ui.draggable.attr('data-id'),
				'date' : $(this).attr('data-dts'),
				'time' : inputTime.val(),
				'opendays' : inputOpenDate.val()			
			};
			$.post(ajaxurl, params, function(data) {
				if(/^yes/.test(data)) {					
					//good to go, nothing more to do										
				} else {					
					if(/^<!DOCTYPE/.test(data)) {
						alert('An error occurred.');
					} else {
						alert('An error occurred: ' + data);
					}					
					//move back
					moveFrom.append(ui.draggable);					
				}
			}).fail(function() {
				alert('An error occurred.');
				//move back
				moveFrom.append(ui.draggable);
			});			
		}
	};
	sideList.droppable(optsDroppable);
	calList.droppable(optsDroppable);
	
	//--------------------------------------------------
	//INIT
	//--------------------------------------------------
	checkNumMods();
	
	//--------------------------------------------------
	//METHODS
	//--------------------------------------------------
	function checkNumMods() {
		if(sideList.children('.mod-item').length) {
			nomods.hide();
		} else {
			nomods.show();
		}
	}
	
	
});