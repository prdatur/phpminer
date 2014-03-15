/**
* TableDnD plug-in for JQuery, allows you to drag and drop table rows
* You can set up various options to control how the system will work
* Copyright (c) Denis Howlett <denish@isocra.com>
* Licensed like jQuery, see http://docs.jquery.com/License.
*
* Configuration options:
*
* onDragStyle
* This is the style that is assigned to the row during drag. There are limitations to the styles that can be
* associated with a row (such as you can't assign a border--well you can, but it won't be
* displayed). (So instead consider using onDragClass.) The CSS style to apply is specified as
* a map (as used in the jQuery css(...) function).
* onDropStyle
* This is the style that is assigned to the row when it is dropped. As for onDragStyle, there are limitations
* to what you can do. Also this replaces the original style, so again consider using onDragClass which
* is simply added and then removed on drop.
* onDragClass
* This class is added for the duration of the drag and then removed when the row is dropped. It is more
* flexible than using onDragStyle since it can be inherited by the row cells and other content. The default
* is class is tDnD_whileDrag. So to use the default, simply customise this CSS class in your
* stylesheet.
* onDrop
* Pass a function that will be called when the row is dropped. The function takes 2 parameters: the table
* and the row that was dropped. You can work out the new order of the rows by using
* table.tBodies[0].rows.
* onDragStart
* Pass a function that will be called when the user starts dragging. The function takes 2 parameters: the
* table and the row which the user has started to drag.
* onAllowDrop
* Pass a function that will be called as a row is over another row. If the function returns true, allow
* dropping on that row, otherwise not. The function takes 2 parameters: the dragged row and the row under
* the cursor. It returns a boolean: true allows the drop, false doesn't allow it.
* scrollAmount
* This is the number of pixels to scroll if the user moves the mouse cursor to the top or bottom of the
* window. The page should automatically scroll up or down as appropriate (tested in IE6, IE7, Safari, FF2,
* FF3 beta
* dragHandle
* This is the name of a class that you assign to one or more cells in each row that is draggable. If you
* specify this class, then you are responsible for setting cursor: move in the CSS and only these cells
* will have the drag behaviour. If you do not specify a dragHandle, then you get the old behaviour where
* the whole row is draggable.
* enableIndent
* Will enable the indent feature, for every 25px of moving from the first td-element within a tr-row it will
* try to indent to this offset, so for example you have the main entry with no indent and move the row after
* to 100px it will try to create 4 indents, the row below has no indent so the maximum of 1 indent is allowed.
* This will be than the indent count (1)
* within serialize method you will get an nested array object which respresents the groups
* to have a good looking indent you need to style the css class .menu_tablednd_indent for me it has
* float: left; margin-right: 20px;
*
* Other ways to control behaviour:
*
* Add class="nodrop" to any rows for which you don't want to allow dropping, and class="nodrag" to any rows
* that you don't want to be draggable.
*
* Inside the onDrop method you can also call $.tableDnD.serialize() this returns a string of the form
* <tableID>[]=<rowID1>&<tableID>[]=<rowID2> so that you can send this back to the server. The table must have
* an ID as must all the rows.
*
* Other methods:
*
* $("...").tableDnDUpdate()
* Will update all the matching tables, that is it will reapply the mousedown method to the rows (or handle cells).
* This is useful if you have updated the table rows using Ajax and you want to make the table draggable again.
* The table maintains the original configuration (so you don't have to specify it again).
*
* $("...").tableDnDSerialize()
* Will serialize and return the serialized string as above, but for each of the matching tables--so it can be
* called from anywhere and isn't dependent on the currentTable being set up correctly before calling
*
* Known problems:
* - Auto-scoll has some problems with IE7 (it scrolls even when it shouldn't), work-around: set scrollAmount to 0
*
* Version 0.2: 2008-02-20 First public version
* Version 0.3: 2008-02-07 Added onDragStart option
* Made the scroll amount configurable (default is 5 as before)
* Version 0.4: 2008-03-15 Changed the noDrag/noDrop attributes to nodrag/nodrop classes
* Added onAllowDrop to control dropping
* Fixed a bug which meant that you couldn't set the scroll amount in both directions
* Added serialize method
* Version 0.5: 2008-05-16 Changed so that if you specify a dragHandle class it doesn't make the whole row
* draggable
* Improved the serialize method to use a default (and settable) regular expression.
* Added tableDnDupate() and tableDnDSerialize() to be called when you are outside the table
* Version 0.6: 2011-12-02 Added support for touch devices
*/
// Determine if this is a touch device
var hasTouch = 'ontouchstart' in document.documentElement,
startEvent = hasTouch ? 'touchstart' : 'mousedown',
moveEvent = hasTouch ? 'touchmove' : 'mousemove',
endEvent = hasTouch ? 'touchend' : 'mouseup';

jQuery.tableDnD = {
	/** Keep hold of the current table being dragged */
	currentTable : null,
	/** Keep hold of the current drag object if any */
	dragObject: null,
	/** The current mouse offset */
	mouseOffset: null,

	/** Remember the old value of X and Y so that we don't do too much processing */
	oldY: 0,
	oldX: 0,



	/** Actually build the structure */
	build: function(options) {
		// Set up the defaults if any

		this.each(function() {
			// This is bound to each matching table, set up the defaults and override with user options
			this.tableDnDConfig = jQuery.extend({
				onDragStyle: null,
				onDropStyle: null,
				// Add in the default class for whileDragging
				onDragClass: "tDnD_whileDrag",
				onDrop: null,
				onDragStart: null,
				scrollAmount: 5,

				serializeRegexp: /[^\-]*$/, // The regular expression to use to trim row IDs
				serializeParamName: null, // If you want to specify another parameter name instead of the table ID
				dragHandle: null, // If you give the name of a class here, then only Cells with this class will be draggable
				/** whether we want to enable the indent feature or not */
				enableIndent: false,
				/** whether we want the serialize output as a query param string or the hole object */
				indentSerializeAsObject: false
			}, options || {});

			/** holds the indent groups */
			this.tableDnDConfig.indent_groups = {};
			/** Holds the current indent level for the current dragged row */
			this.tableDnDConfig.current_intent_level = 0;
			/** holds the previous tr-element from dragged element */
			this.tableDnDConfig.prev_tr_from_drag_object = null;

			// Now make the rows draggable
			jQuery.tableDnD.makeDraggable(this);
		});

		// Don't break the chain
		return this;
	},

	/** This function makes all the rows on the table draggable apart from those marked as "NoDrag" */
	makeDraggable: function(table) {

		var config = table.tableDnDConfig;
		if (config.dragHandle) {
			// We only need to add the event to the specified cells
			var cells = jQuery("td."+table.tableDnDConfig.dragHandle, table);
			cells.each(function() {
				// The cell is bound to "this"
				jQuery(this).bind(startEvent, function(ev) {
					var parent_node = this.parentNode;
					if($(this.parentNode)[0]['localName'] != "tr") {
						parent_node = this.parentNode.parentNode;
					}
					jQuery.tableDnD.initialiseDrag(parent_node, table, this, ev, config);
					return false;
				});
			})
		} else {
			// For backwards compatibility, we add the event to the whole row
			var rows = jQuery("> tbody > tr", table); // get all the rows as a wrapped set

			rows.each(function() {
				// Iterate through each row, the row is bound to "this"
				var row = jQuery(this);
				if (! row.hasClass("nodrag")) {
					row.bind(startEvent, function(ev) {
						if (ev.target.tagName == "TD") {
							jQuery.tableDnD.initialiseDrag(this, table, this, ev, config);
							return false;
						}
					}).css("cursor", "move"); // Store the tableDnD object
				}
			});
		}

		//Only init indent if we want it
		if(config.enableIndent == true) {
			//Add to all tr-elements a counter attribute to handle the indent groups
			/*var row_index = 1;
			jQuery("> tbody > tr", table).each(function() {
				$(this).attr("rowid", (row_index++)+"-");
			});*/

			//Build-up our indent groups
			jQuery.tableDnD.init_indent_groups();
		}

	},

	/** Initialize the indent group object array */
	init_indent_groups: function(table) {
		if(table == undefined) {
			table = jQuery.tableDnD.currentTable;
		}
		if(table == null) {
			return;
		}
		var config = table.tableDnDConfig;

		//Only init indent if we want it
		if(config.enableIndent != true) {
			return;
		}

		config.indent_groups = {};
		//We loop through every tr-element and add the current group_id to all parent groups
		$("> tbody > tr", table).each(function() {
			var row_id = $(this).attr("rowid");

			//Create the own group for current group id
			config.indent_groups[row_id+' '] = {};
			config.indent_groups[row_id+' '][row_id+' '] = $(this).attr("id");

			//Adding the current group_id to all parents
			jQuery.tableDnD.get_all_groups(row_id, $(this), $(this).prev(), table);
		});
	},

	get_all_groups: function(row_id_to_add, current_row, prev_row, table) {
		if(empty(prev_row)) {
			return;
		}

		if(table == undefined) {
			table = jQuery.tableDnD.currentTable;
		}
		if(table == null) {
			return;
		}

		//Get the previous and current intent counts
		var prev_intents = $(".intent_container > .menu_tablednd_indent", prev_row).length;
		var current_intents = $(".intent_container > .menu_tablednd_indent", current_row).length;

		//If the current intents are 0 we are on level 0, there will be no parent group, return
		if(current_intents == 0) {
			return;
		}

		//For recrusive search, setup the next current row to the previous row
		var next_current = $(prev_row);

		var config = table.tableDnDConfig;
		/**
		 * If the prev intents are lower than the current one the prev one is a parent of the current entry
		 * So we add this our group id which we want to add to the parent group
		 */
		if(prev_intents < current_intents) {
			config.indent_groups[$(prev_row).attr("rowid")+' '][row_id_to_add+' '] = true;
		}
		/**
		 * We have an invalid previous tr-element which is maybe a direct member of the current parent group but we
		 * do not want to add the direct members to the group, just the parent element.
		 * Override the next current element to the current one so that we can move on.
		 */
		else {
			next_current = $(current_row);
		}
		//process next previous element
		jQuery.tableDnD.get_all_groups(row_id_to_add, $(next_current), $(prev_row).prev(), table);

	},

	initialiseDrag: function(dragObject, table, target, evnt, config) {
		jQuery.tableDnD.dragObject = dragObject;
		jQuery.tableDnD.currentTable = table;
		jQuery.tableDnD.mouseOffset = jQuery.tableDnD.getMouseOffset(target, evnt);
		jQuery.tableDnD.originalOrder = jQuery.tableDnD.serialize();

		//Only init indent if we want it
		if(config.enableIndent == true) {
			//Get all members for the current row id and add our tableDnD_indentgroup class
			jQuery.each(table.tableDnDConfig.indent_groups[$(jQuery.tableDnD.dragObject).attr('rowid')+' '], function(k,v) {
				k = $.trim(k);
				$("tr[rowid='"+k+"']").addClass("tableDnD_indentgroup");
			});
			jQuery.tableDnD.prev_tr_from_drag_object = $(dragObject).prev();
		}

		// Now we need to capture the mouse up and mouse move event
		// We can use bind so that we don't interfere with other event handlers
		jQuery(document)
		.bind(moveEvent, jQuery.tableDnD.mousemove)
		.bind(endEvent, jQuery.tableDnD.mouseup);

		if (config.onDragStart) {
			// Call the onDragStart method if there is one
			config.onDragStart(table, target);
		}
	},

	updateTables: function() {
		this.each(function() {
			// this is now bound to each matching table
			if (this.tableDnDConfig) {
				jQuery.tableDnD.makeDraggable(this);
			}
		})
	},

	/** Get the mouse coordinates from the event (allowing for browser differences) */
	mouseCoords: function(ev){
		if(ev.pageX || ev.pageY){
			return {
				x:ev.pageX,
				y:ev.pageY
				};
		}
		return {
			x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
			y:ev.clientY + document.body.scrollTop - document.body.clientTop
		};
	},

	/** Given a target element and a mouse event, get the mouse offset from that element.
To do this we need the element's position and the mouse position */
	getMouseOffset: function(target, ev) {
		ev = ev || window.event;

		var docPos = this.getPosition(target);
		var mousePos = this.mouseCoords(ev);
		return {
			x:mousePos.x - docPos.x,
			y:mousePos.y - docPos.y
			};
	},

	/** Get the position of an element by going up the DOM tree and adding up all the offsets */
	getPosition: function(e){
		var left = 0;
		var top = 0;
		/** Safari fix -- thanks to Luis Chato for this! */
		if (e.offsetHeight == 0) {
			/** Safari 2 doesn't correctly grab the offsetTop of a table row
this is detailed here:
http://jacob.peargrove.com/blog/2006/technical/table-row-offsettop-bug-in-safari/
the solution is likewise noted there, grab the offset of a table cell in the row - the firstChild.
note that firefox will return a text node as a first child, so designing a more thorough
solution may need to take that into account, for now this seems to work in firefox, safari, ie */
			e = e.firstChild; // a table cell
		}

		while (e.offsetParent){
			left += e.offsetLeft;
			top += e.offsetTop;
			e = e.offsetParent;
		}

		left += e.offsetLeft;
		top += e.offsetTop;

		return {
			x:left,
			y:top
		};
	},

	mousemove: function(ev) {
		if (jQuery.tableDnD.dragObject == null) {
			return;
		}
		if (ev.type == 'touchmove') {
			// prevent touch device screen scrolling
			event.preventDefault();
		}

		var dragObj = jQuery(jQuery.tableDnD.dragObject);
		var config = jQuery.tableDnD.currentTable.tableDnDConfig;
		var mousePos = jQuery.tableDnD.mouseCoords(ev);
		var y = mousePos.y - jQuery.tableDnD.mouseOffset.y;

		//for indent we need x-position
		var x = mousePos.x - jQuery.tableDnD.mouseOffset.x;

		//auto scroll the window
		var yOffset = window.pageYOffset;
		if (document.all) {
			// Windows version
			//yOffset=document.body.scrollTop;
			if (typeof document.compatMode != 'undefined' &&
				document.compatMode != 'BackCompat') {
				yOffset = document.documentElement.scrollTop;
				xOffset = document.documentElement.scrollLeft;
			}
			else if (typeof document.body != 'undefined') {
				yOffset=document.body.scrollTop;
				xOffset=document.body.scrollLeft;
			}

		}

		if (mousePos.y-yOffset < config.scrollAmount) {
			window.scrollBy(0, -config.scrollAmount);
		} else {
			var windowHeight = window.innerHeight ? window.innerHeight
			: document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;
			if (windowHeight-(mousePos.y-yOffset) < config.scrollAmount) {
				window.scrollBy(0, config.scrollAmount);
			}
		}


		if (y != jQuery.tableDnD.oldY || x != jQuery.tableDnD.oldX) {

			// work out if we're going up or down...
			var movingDown = y > jQuery.tableDnD.oldY;



			// update the old value
			jQuery.tableDnD.oldY = y;
			jQuery.tableDnD.oldX = x;
			// update the style to show we're dragging
			if (config.onDragClass) {
				dragObj.addClass(config.onDragClass);
			} else {
				dragObj.css(config.onDragStyle);
			}
			// If we're over a row then move the dragged row to there so that the user sees the
			// effect dynamically
			var currentRow = jQuery.tableDnD.findDropTargetRow(dragObj, y);
			if (currentRow) {

				//At the comment to the top it is written we must self decide to change cursor
				//Will be much easier to enable the cursor for the hole table if we moving, will be disabled
				//on mouseup
				$(jQuery.tableDnD.currentTable).css("cursor", "pointer");

				var change_element = null;
				if(config.enableIndent != true) {
					change_element = $(jQuery.tableDnD.dragObject);
				}
				else {
					change_element = $("tr.tableDnD_indentgroup", jQuery.tableDnD.currentTable);
				}

				//improved call to insert the elment
				if (movingDown && jQuery.tableDnD.dragObject != currentRow) {
					change_element.insertAfter($(currentRow));

				}else if (! movingDown && jQuery.tableDnD.dragObject != currentRow) {
					change_element.insertBefore($(currentRow));
				}
				//We need to reset the current intent level
				jQuery.tableDnD.current_intent_level = 0;
			}

			//Start checking for indent
			jQuery.tableDnD.indent(ev);
		}

		return false;
	},

	/** tries to indent the entry*/
	indent: function(ev) {
		var config = jQuery.tableDnD.currentTable.tableDnDConfig;

		//Only init indent if we want it
		if(config.enableIndent != true) {
			return;
		}
		//The indent template
		var intent_template = '<span class="menu_tablednd_indent">&nbsp;</span>';
		//var prev_from_dragObject = jQuery.tableDnD.prev_tr_from_drag_object;
		var prev_from_dragObject = $(jQuery.tableDnD.dragObject).prev();

		var prev_intents = 0;

		if(!empty(prev_from_dragObject)) {
			prev_intents = $(".intent_container > .menu_tablednd_indent", prev_from_dragObject).length;
		}
		var current_intents = $(".intent_container > .menu_tablednd_indent", jQuery.tableDnD.dragObject).length;
		var handle_cell_offset = jQuery.tableDnD.getMouseOffset(jQuery.tableDnD.dragObject, ev);

		var current_intent = Math.ceil(handle_cell_offset.x/25)-1;
		if(current_intent < 0) {
			return;
		}

		if(prev_from_dragObject.length <= 0) {
			current_intent = 0;
		}

		if(config.current_intent_level != current_intent) {
			config.current_intent_level = current_intent;

			var intent_change = current_intent-current_intents;

			if(current_intents+intent_change > prev_intents+1) {
				intent_change = (prev_intents+1)-current_intents;
			}

			foreach(config.indent_groups[$(jQuery.tableDnD.dragObject).attr('rowid')+' '], function(k,v) {
				k = $.trim(k);
				v = $.trim(v);
				var change_row = $("tr[rowid='"+k+"'] .intent_container");
				var elm_intents_object = $(".menu_tablednd_indent", change_row);
				var elm_intents = elm_intents_object.length+intent_change;

				change_row.html("");
				for(var i = 0; i < elm_intents; i++) {
					change_row.append(intent_template);
				}
			});
		}
	},

	/** We're only worried about the y position really, because we can only move rows up and down */
	findDropTargetRow: function(draggedRow, y) {
		var config = jQuery.tableDnD.currentTable.tableDnDConfig;
		var rows = jQuery.tableDnD.currentTable.tBodies[0].rows;
		var current_rowid = $(draggedRow).attr("rowid");
		for (var i=0; i<rows.length; i++) {
			var row = rows[i];
			var rowY = this.getPosition(row).y;
			var rowHeight = parseInt(row.offsetHeight)/2;
			if (row.offsetHeight == 0) {
				rowY = this.getPosition(row.firstChild).y;
				rowHeight = parseInt(row.firstChild.offsetHeight)/2;
			}
			// Because we always have to insert before, we need to offset the height a bit
			if ((y > rowY - rowHeight) && (y < (rowY + rowHeight))) {
				var row_rowid = $(row).attr("rowid");
				// that's the row we're over
				// If it's the same as the current row, ignore it
				if ((config.enableIndent == true && current_rowid == row_rowid) || row == draggedRow) {
					return null;
				}

				if (config.onAllowDrop) {
					if (config.onAllowDrop(draggedRow, row)) {
						return row;
					} else {
						return null;
					}
				} else {
					//Only init indent if we want it
					//If we are on an indent group element, try to find the next possible row and we have indent enabled
					if(config.enableIndent == true && jQuery(row).hasClass("tableDnD_indentgroup")) {
						return jQuery.tableDnD.findNextDropTargetAfterLastNoDrop(i);
					}
					// If a row has nodrop class, then don't allow dropping (inspired by John Tarr and Famic)
					else if (! jQuery(row).hasClass("nodrop")) {
						return row;
					} else {
						return null;
					}
				}
				return row;
			}
		}
		return null;
	},
	findNextDropTargetAfterLastNoDrop: function(start_index) {
		var rows = jQuery.tableDnD.currentTable.tBodies[0].rows;
		if(start_index+1 > rows.length) {
			return null;
		}
		for (var i=start_index+1; i<rows.length; i++) {
			if (! jQuery(rows[i]).hasClass("nodrop") && ! jQuery(rows[i]).hasClass("tableDnD_indentgroup")) {
				return rows[i];
			}
		}
		return null;
	},
	mouseup: function(e) {
		if (jQuery.tableDnD.currentTable && jQuery.tableDnD.dragObject) {
			// Unbind the event handlers
			jQuery(document)
			.unbind(moveEvent, jQuery.tableDnD.mousemove)
			.unbind(endEvent, jQuery.tableDnD.mouseup);
			var droppedRow = jQuery.tableDnD.dragObject;
			var config = jQuery.tableDnD.currentTable.tableDnDConfig;
			// If we have a dragObject, then we need to release it,
			// The row will already have been moved to the right place so we just reset stuff
			jQuery.tableDnD.current_intent_level = 0;
			if (config.onDragClass) {
				jQuery(droppedRow).removeClass(config.onDragClass);
			} else {
				jQuery(droppedRow).css(config.onDropStyle);
			}
			$(jQuery.tableDnD.currentTable).css("cursor", "inherit");
			//jQuery.tableDnD.init_indent_groups();
			jQuery.tableDnD.prev_tr_from_drag_object = null;
			jQuery.tableDnD.dragObject = null;
			var newOrder = jQuery.tableDnD.serialize();
			//if (config.onDrop && (jQuery.tableDnD.originalOrder != newOrder)) {
			if (config.onDrop) {
				// Call the onDrop method if there is one
				config.onDrop(jQuery.tableDnD.currentTable, droppedRow);
			}

			$("tr", jQuery.tableDnD.currentTable).removeClass("tableDnD_indentgroup");
			jQuery.tableDnD.currentTable = null; // let go of the table too
		}
	},

	serialize: function() {
		if (jQuery.tableDnD.currentTable) {
			if(jQuery.tableDnD.currentTable.tableDnDConfig.enableIndent == true) {
				return jQuery.tableDnD.serializeIndentTable(jQuery.tableDnD.currentTable);
			}
			else {
				return jQuery.tableDnD.serializeTable(jQuery.tableDnD.currentTable);
			}
		} else {
			return "Error: No Table id set, you need to set an id on your table and every row";
		}
	},

	serializeTable: function(table) {
		var result = "";
		var tableId = table.id;
		var rows = table.tBodies[0].rows;
		for (var i=0; i<rows.length; i++) {
			if (result.length > 0) result += "&";
			var rowId = rows[i].id;
			if (rowId && rowId && table.tableDnDConfig && table.tableDnDConfig.serializeRegexp) {
				rowId = rowId.match(table.tableDnDConfig.serializeRegexp)[0];
			}

			result += tableId + '[]=' + rowId;
		}
		return result;
	},

	/** holds all rows which we have already processed */
	rows_processed: {},
	serializeIndentTable: function(table) {
		//End result array
		var result = {};

		//setup the table id as the main parameter index
		result[table.id] = {};

		//Reinit the indent group to get the current correct one
		jQuery.tableDnD.init_indent_groups(table);
		jQuery.tableDnD.rows_processed = [];
		//Process all rows.
		for (var i=0; i<table.tBodies[0].rows.length; i++) {
			var current_row = table.tBodies[0].rows[i];
			var rowid = $(current_row).attr("rowid");
			rowid = $.trim(rowid);
			//Skip already processed rows
			if(jQuery.tableDnD.rows_processed[rowid] != undefined) {
				continue;
			}

			jQuery.tableDnD.rows_processed[rowid] = true;

			//get all childs for the current row
			result[table.id][rowid+' '] = jQuery.tableDnD.getChildsForSerialize(current_row, table);
		}


		//Return as an pure object if we want it.
		if(table.tableDnDConfig.indentSerializeAsObject == true) {

			var returning_results = {};
			$(decodeURIComponent($.param(result)).split("&")).each(function() {
				var line_arr = this.split("=");
				returning_results[line_arr[0]] = line_arr[1];
			});

			return returning_results;
		}

		return result;
	},
	/** Gets all indent child elements for the current row*/
	getChildsForSerialize: function(current_row, table) {
		var group_members = table.tableDnDConfig.indent_groups[$(current_row).attr("rowid")+' '];
		if(jQuery.tableDnD.has_more_elements_as(group_members, 1)) {
			var result = {};
			for (var rowid in group_members) {
				if(!group_members.hasOwnProperty(rowid)) {
					continue;
				}
				rowid = $.trim(rowid);
				if(jQuery.tableDnD.rows_processed[rowid] != undefined) {
					continue;
				}
				jQuery.tableDnD.rows_processed[rowid] = true;

				var current_member = $("tr[rowid='"+rowid+"']", table);
				result[$(current_member).attr("rowid")+' '] = jQuery.tableDnD.getChildsForSerialize(current_member, table);
			}
			return result;
		}
		else {
			return $(current_row).attr("rowid");
		}
	},

	has_more_elements_as: function(object, max_elements) {
		var i = 0;
		for(var x in object) {
			if(object.hasOwnProperty(x)) {
				i++;
			}
			if(i > max_elements) {
				return true;
			}
		}
		return false;
	},

	serializeTables: function() {
		var result = [];

		this.each(function() {
			var config = this.tableDnDConfig;
			// this is now bound to each matching table
			if(config.enableIndent == true) {
				result.push(jQuery.tableDnD.serializeIndentTable(this));
			}
			else {
				result.push(jQuery.tableDnD.serializeTable(this));
			}
		});
		return result;
	}
};


jQuery.fn.extend(
{
	tableDnD : jQuery.tableDnD.build,
	tableDnDUpdate : jQuery.tableDnD.updateTables,
	tableDnDSerialize: jQuery.tableDnD.serializeTables
}
);