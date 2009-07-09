/*
  Some init code to run when page is created
 */
window.onload = function(evt) {
    document.onkeypress = function(evt) {
	evt = (evt) ? evt : ((window.event) ? event : null);
	if (evt && evt.keyCode == 27) {
	    TimeSafe.sidebarHide();
	}
    };
    
    $('body')[0].onclick = function (event) {
	TimeSafe.sidebarHide();
    };

    $('body')[0].onmousedown = function(e){
	//debug('down');
	TimeSafe.dragStart = null;

	if(e.target.className== "time" && (e.shiftKey || e.ctrlKey) && e.target.value != "" ) {
	    //debug("DND start");
	    var copying = e.ctrlKey;
	    TimeSafe.dragIsCopy = copying;
	    TimeSafe.dragStart = e.target;
	    TimeSafe.sidebarHide();
	    $('body').addClass(copying?'copying':'dragging');
	    
	    $('body')[0].onmousemove = function(e) {
		old = TimeSafe.dragOver;
		//debug('move');
		
		TimeSafe.dragOver = TimeSafe.getElementAtPosition($('input.time'), e.getCoordinate());
		
		if(old != TimeSafe.dragOver){
		    if(old)
			$(old).removeClass("dnd_drop");
		    $(TimeSafe.dragOver).addClass("dnd_drop");
		}
		return false;
	    }
	    return false;
	}
	
    }
    /*
    $('body')[0].onmouseout = function(e){
	if(TimeSafe.dragStart)
	    debug('out');
    }
    */
    $('body')[0].onmouseup = function(e){
	$('body').removeClass('dragging copying');
	$('body')[0].onmousemove = null;
	dragStart = TimeSafe.dragStart;
	TimeSafe.dragOver = TimeSafe.getElementAtPosition($('input.time'), e.getCoordinate());
	
	if(TimeSafe.dragOver){
	    $(TimeSafe.dragOver).removeClass("dnd_drop");
	}

	if(dragStart) {
	    var dragStop = TimeSafe.getElementAtPosition($('input.time'), e.getCoordinate());
	    if(dragStop && dragStop.className=="time" ) {
		if(dragStop != TimeSafe.dragStart) {
		    TimeSafe.copyCellContent(TimeSafe.dragStart,dragStop);
		    if(!TimeSafe.dragIsCopy)
			TimeSafe.stripCellContent(TimeSafe.dragStart);

		} else {
		    sidebarId = 'sidebar_' + dragStart.idStr;
		    TimeSafe.sidebarShow(sidebarId);
		}
	    }
	}
	TimeSafe.dragStart = null;
    }

}

/**
   Add a few nice-to-have prototype functions
 */
String.prototype.stripHTML = function () {
    return this.replace(/<[^>]*>/g, "");
}
		
String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g,"");
};

String.prototype.parseTimeNazi = function () 
{
    str = new String(this);
    str_arr = str.split(':');
    if (str_arr.length == 2) {
	if (str_arr[0].match(/^[0-9]*$/) && str_arr[1].match(/^[0-9]+$/))
	    return 60*(str_arr[0].length>0?parseInt(str_arr[0]):0) + parseInt(str_arr[1]);
	return NaN;
    }
    var str2 = str.replace(/,/g,'.');
    
    if (str2.match(/^[0-9]*\.?[0-9]+$/))
	return (60.0*parseFloat(str2));
    return NaN;
};

Event.prototype.getCoordinate = function() {
    if (this.pageX) 	{
	return [this.pageX, this.pageY];
    }
    else if (e.clientX || e.clientY) 	{
	return [ e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft,
		 e.clientY + document.body.scrollTop + document.documentElement.scrollTop ];
    }
    else return null;
}

Node.prototype.getAbsolutePosition = function(){
    var obj = this;
    var x=0;
    var y=0;
    //    debug("Find pos of " + this);
    //debug("Start with " + x + ", " + y);
    do {
	x += obj.offsetLeft;
	y += obj.offsetTop;
	//	debug("Add " + obj.offsetLeft + ", " + obj.offsetTop);
    } while(obj=obj.offsetParent);
    //debug("Return " + x + ", " + y);
    return [x, y];
};

function debug(t){
    $('#debug')[0].innerHTML += t + "<br/>\n";
};

/**
   We put everything in a TimeSafe namespace, to minimize risk of name clashes
 */
var TimeSafe = {

    projectLines: 0,
    error: {},
    warning: {},
    currentSidebar: null,
    entryIdLookup: {},
    dragStart:null,

    getElementAtPosition: function(el, pos){

	//debug("find thing at position " + pos);
	var i=0;
	for(var i=0; i<el.length; i++) {
		value = el[i];
		/*
		if(i > 300) {
		    debug("Too much work, giving up...");
		    break;
		}
		*/
		var p = value.getAbsolutePosition();
	
		if(p[0] == 0) {
		    continue;
		}
	
		//debug("next element has position " + p);
		var s = [value.offsetWidth, value.offsetHeight];
		var inside = true;
		inside &= pos[0] > p[0];
		inside &= pos[0] < (p[0]+s[0]);
		inside &= pos[1] > p[1];
		inside &= pos[1] < (p[1]+s[1]);
		if(inside) {
		    return value;
		}
	    };
	return null;
    },

    copyCellContent: function(from, to) {
	//debug("move stuff from " +from.id + " to " + to.id);
	var toSidebar = $("#sidebar_" + to.idStr)[0];
	var fromSidebar = $("#sidebar_" + from.idStr)[0];

	/*
	  Make sure we are initialized at both ends
	 */
	fromSidebar.doInit();
	toSidebar.doInit();

	/*
	  Move hours
	*/

	to.value=from.value;

	/*
	  Move description
	 */
	toSidebar.description.value = fromSidebar.description.value;

	/*
	  Try to move over the tags
	 */
	fromTag = fromSidebar.tag.options;
	toTag = toSidebar.tag.options;
	for(var i=0; i<toTag.length; i++) {
	    toTag[i].selected=false;
	}
	for(var i=0; i<fromTag.length; i++) {
	    if(fromTag[i].selected) {
		for(var j=0; j<toTag.length; j++) {
		    if(toTag[j].value == fromTag[i].value) {
			toTag[j].selected=true;
			break;
		    }
		}
	    }	    
	}

	/*
	  Validate
	 */
	TimeSafe.validate(to.idStr);
	TimeSafe.showSum(to.idStr.split('_')[2]);
    },
    
    stripCellContent: function(from) {
	var fromSidebar = $("#sidebar_" + from.idStr)[0];
	fromSidebar.doInit();
	from.value="";
	fromSidebar.description.value = "";
	for(var i=0; i<fromTag.length; i++) {
	    fromTag[i].selected=false;
	}
	/*
	  Validate
	 */
	TimeSafe.validate(from.idStr);
	TimeSafe.showSum(from.idStr.split('_')[2]);
    },
    
    /**
       Show sidebar with specified id
    */
    sidebarShow: function(id) {
	var newSidebar = $('#'+id)[0];
	if (!newSidebar) { 
	    return;
	}
	
	if (TimeSafe.currentSidebar != null) {
	    TimeSafe.currentSidebar.style.display='none';
	}
	
	TimeSafe.currentSidebar = newSidebar;
	newSidebar.doInit();
	TimeSafe.currentSidebar.style.display="block";
    },
    
    /**
       Hide current sidebar, if any
    */
    sidebarHide: function() {
	if (TimeSafe.currentSidebar != null) {
	    TimeSafe.currentSidebar.style.display='none';
	    TimeSafe.currentSidebar = null;
	}
    },

    /**
       Handle arrow keys and excepe key if pressed in one of the cells by moving around.
       Moving up and down is way harder than it should be. Maybe we could use table row stuff to find previous index?
    */
    slotKeypressEventHandler: function(evt) {
	evt = (evt) ? evt : ((window.event) ? event : null);
	if (evt) {
	    input_id = evt.target.id;
	    var el=null;
	    var id_data_str = $('#'+input_id)[0].id.split('_');
	    var id_data=[parseInt(id_data_str[1]), parseInt(id_data_str[2]), parseInt(id_data_str[3]) ];
	    
	    switch (evt.keyCode) {
	    case 37:
		// Move left
		el = $('#time_' + id_data[0] + "_" + id_data[1] + "_" + (id_data[2]-1))[0];
		break;    
	    case 38:
		// Move up
		el = $('#time_' + id_data[0] + "_" + (id_data[1]-1) + "_" + id_data[2])[0];
		if (!el) {
		    el = $('#time_' + (id_data[0]-1) + "_0_" + id_data[2])[0];
		    
		    if(el) {
			var i=1;
			while(true) {
			    var el2 = $('#time_' + (id_data[0]-1) + "_" + i + "_" + id_data[2])[0];
			    if(!el2) {
				break;
			    }
			    el=el2;
			    i++;
			}
		    }
		}
		break;    
	    case 39:
		// Move right
		el = $('#time_' + id_data[0] + "_" + id_data[1] + "_" + (id_data[2]+1))[0];
		break;    
	    case 40:
		// Move down
		el = $('#time_' + id_data[0] + "_" + (id_data[1]+1) + "_" + id_data[2])[0];
		if (!el) {
		    el = $('#time_' + (id_data[0]+1) + "_0_" + id_data[2])[0];
		}
		break;
	    case 27:
		TimeSafe.sidebarHide();
		break;
	    }
	    
	    if (el) {
		/*
		  If we found a hidden node, recursively call ourselves
		 */
		var row = el;
		while(row && row.nodeName.toLowerCase() != 'tr') {
		    row=row.parentNode;
		}
		if(row && row.style.display=='none') {
		    TimeSafe.slotKeypressEventHandler({'target':el, 'keyCode':evt.keyCode});
		}
		else {
		    el.focus();
		}
	    }
	}
    },
    
    /**
       Clear notification area in specified sidebar
    */
    notifyClear: function(id)
    {
	var notification = $('#notification_'+id)[0];
	notification.innerHTML ="";
    },

    /**
       Add notification in specified sidebar
    */
    notify:function(id, msg)
    {
	var notification = $('#notification_'+id)[0];
	notification.innerHTML += "<div class='error'>" + msg + "</div>";
    },

    /**
       Validate that all tags are filled out correctly
    */
    validateTags: function(id) {
	var sel = $('#tag_'+id)[0];
	var project = TimeSafeData.projects[id.split('_')[0]];
	var selCount = {};
	for (var i=0; i<project.tags.length; i++) {
	    var tag = project.tags[i];
	    
	    if(tag.group_id != null) {
		if(selCount[tag.group_id] == null)
		    selCount[tag.group_id] = 0;
		if(sel.options[i].selected) 
		    selCount[tag.group_id]++;
	    }
	    
	    if(tag.recommended && !sel.options[i].selected) {
		TimeSafe.notify(id, 'The "' + tag.name+ '" tag should usually be selected.');
		TimeSafe.warning[id] = 1;
	    }
	}

	$.each(selCount, function (gid, count) {
		var tg = TimeSafeData.tagGroups[gid];
		if(count > 1) {
		    TimeSafe.notify(id, 'At most one tag from group "' + tg.name+ '" may be chosen.');
		    TimeSafe.error[id] = 1;
		}
		if(count == 0 && tg.required) {
		    TimeSafe.notify(id, 'You must pick one tag from group "' + tg.name+ '".');
		    TimeSafe.error[id] = 1;
		}
	    });
    },

    /**
       Validate specified time entry
    */
    validate: function(id)
    {
	TimeSafe.warning[id] = 0;
	TimeSafe.error[id] = 0;
	TimeSafe.notifyClear(id);

	var time = $('#time_'+id)[0];
	var project_idx = id.split('_')[0];
	var slot = id.split('_')[1];
	var day = id.split('_')[2];
	var dayWork = TimeSafe.calcSum(day);
	var empty = false;

	if (dayWork > 60*24) {
	    TimeSafe.notify(id, 'Impossible to work more than 24 hours in a single day');
	    TimeSafe.error[id] = 1;
	}
	
	if (time.value!='') {
	    TimeSafe.validateTags(id);
	    
	    var time_val = time.value.parseTimeNazi();
	    var description = $('#description_'+id)[0];
	    var project_id = $('#project_id_'+project_idx)[0];
	    var isExternal=TimeSafeData.projects[project_idx].external;
	    if (isNaN(time_val)) {
		TimeSafe.error[id] = 1;
		TimeSafe.notify(id, "Time spent is not a number");
	    } else { 
		/*
		  This check is way too FreeCode specific. We should
		  add a feature for inserting custom cehcks through
		  the backend and use that. 2.0+...
		*/
		if (isExternal && (time_val % 30 != 0)){
		    TimeSafe.warning[id] = 1;
		    TimeSafe.notify(id, 'Time spent is not an even half hour');
		}
	    }

	    if (description) {
		var description_value = description.value;//tinyMCE.get('description').getContent();	    
		if(description_value.stripHTML().trim()=='') {
		    TimeSafe.error[id] = 1;
		    TimeSafe.notify(id, 'No description of work given');
		}
	    }
	} else {
	    empty = true;
	}

	/*
	  Count number of errors and warnings
	 */
	var errCount = 0;
	$.each(TimeSafe.error,function(idx,el){errCount += el;});
	var warnCount = 0;
	$.each(TimeSafe.warning,function(idx,el){warnCount += el;});

	/*
	  Set the td modification class
	*/
	/*
	  First strip any old modification information
	 */
	$('#td_'+id).removeClass("modified error warning");
	if(empty) {
	    /*
	      If the cell is empty, mark it as modified only if it was originally not empty.
	     */
	    project = TimeSafeData.projects[project_idx];
	    if(project.slot[slot] && project.slot[slot][day] && project.slot[slot][day].id != null) {
		$('#td_'+id).addClass("modified");
	    }
	} else {
	    /*
	      A non-empty cell gets the error tag if it contains an error, etc.
	     */
	    $('#td_'+id).addClass((TimeSafe.error[id]==1)?"error":((TimeSafe.warning[id]==1)?"warning":"modified"));
	}

	/*
	  Disable save button and notify user of why if we have errors. War user if we have warnings.
	*/
	$('#save')[0].disabled = errCount > 0;
	$('#notification_global')[0].innerHTML = (errCount>0)?'There are errors in your hour registration. Correct them before proceeding':((warnCount>0)?'There are warnings in your hour registration. Make sure that they are ok before proceeding.':'');
    },

    /**
       Format a number of minutes into something suitable for human reading
    */
    formatTime : function(tm)
    {
	switch(tm%60) {
	case 0:
	return tm / 60;
	case 30:
	return ""+ Math.floor(tm/60)+",5";
	default:
	return ""+ Math.floor(tm / 60) + ':' + (tm%60);
	}
    },
    
    /**
       Returns a DOM node suitable for using as a text status field.
    */
    makeText: function(id, content) {
	var res = document.createElement("span");
	if(id) {
	    res.id=id + "_" + TimeSafe.projectLines;
	}
	if (content) {
	    res.innerHTML = content;
	}
	return res;
    },

    /**
       Make a select box with tags for the specified project
    */
    makeTagSelect: function(line, selected) {
	var tags = document.createElement('select');
	tags.multiple=true;
	
	tags.addOptions = function(opts, selected) {
	    for(var i=0; i<opts.length; i++) {
		var opt = opts[i];
		var isSelected = false;
		var opt_id = opt.id;
		$.each(selected,function(idx,el){isSelected |= el==opt_id;});
		this.add(new Option(opt.name, opt.id, false, isSelected), null);
	    }
	};
	tags.addOptions(line.tags, selected);
	return tags;
    },

    /**
       Update the sum cell for the specified day
    */
    showSum: function(day) {
	$('#hour_sum_' + day)[0].innerHTML = TimeSafe.formatTime(TimeSafe.calcSum(day));
    },

    /**
       Calculate the number of hours worked for the specified day
    */
    calcSum: function(day) {
	var tm = 0.0;
	for( var line = 0; line < TimeSafe.projectLines; line++) {
	    for (var slot = 0;; slot++) {
		var idStr = "" + line + "_" + slot + "_" + day;
		/* This is called so often that it becomes performance critical - avoid jQuery... */
		var tmInput = document.getElementById('time_' + idStr);
		if (tmInput == null) {
		    break;
		}
		var dt = tmInput.value.parseTimeNazi();
		if(!isNaN(dt)) {
		    tm += dt;
		}
	    }
	}
	return tm;
    },

    /**
       Event handler that simply stops propagation of event
    */
    eventStopper: function(event){
	event.stopPropagation();
    },

    slotChangeEventHandler: function(event){
	TimeSafe.validate(event.target.idStr);
	day = event.target.idStr.split('_')[2];
	TimeSafe.showSum(day);
    },

    slotFocusEventHandler: function(event){
	if(!TimeSafe.dragStart)
	    TimeSafe.sidebarShow('sidebar_'+event.target.idStr);
    },
    /**
       Make an input cell with the fancy popup dialog and everything else
     */
    makeTimeInput: function(project, slot, day) {
	//return document.createElement('span');

	if (TimeSafe.inputTemplate == null) {
	    
	    TimeSafe.inputTemplate = document.createElement('span');
	    
	    var inp = document.createElement('input');
	    inp.className = "time";
	    inp.setAttribute('autocomplete','off');
	    
	    TimeSafe.inputTemplate.appendChild(inp);
	    
	    var anch = document.createElement('div');
	    anch.className = 'anchor';
	    
	    var sidebar = document.createElement('div');
	    sidebar.className = 'sidebar';

	    anch.appendChild(sidebar);
	    
	    TimeSafe.inputTemplate.appendChild(anch);
	    
	}
	
	var res = TimeSafe.inputTemplate.cloneNode(true);
	var currentLine = project.line;
	var idStr = "" + currentLine + "_" + slot + "_" + day;
	var sidebarId = 'sidebar_' + idStr;
	res.onclick=TimeSafe.eventStopper;

	var input = res.childNodes[0];
	input.onchange=TimeSafe.slotChangeEventHandler;
	input.onfocus=TimeSafe.slotFocusEventHandler;
	input.onkeypress=TimeSafe.slotKeypressEventHandler;
	/*
	input.onmousedown=function(e){
	    e.target.focus(); 
	    
	    return false;
	}
	*/
	input.id="time_" + idStr;
	input.name="time_" + idStr;
	input.idStr = idStr;
	
	if(project.slot[slot] && project.slot[slot][day]) {
	    input.value=TimeSafe.formatTime(project.slot[slot][day].minutes);
	    TimeSafe.entryIdLookup[project.slot[slot][day].id] = idStr;
	}
	
	var anchor = res.childNodes[1];
	var sidebar = anchor.childNodes[0];
	sidebar.id=sidebarId;
	/*
	  We delay as much of the table creation as possible, because
	  it is already painfully slow. With a bit of work, we could
	  reduce creation further by not creating the div nodes here
	  either, but sidebarShow would have to be rewritten in that
	  case.
	*/
	sidebar.doInit=function(){
	    
	    var tags = (project.slot[slot] && project.slot[slot][day]) ? project.slot[slot][day]._tags : [];
	    var sidebarContent = document.createElement('div');
	    sidebar.appendChild(sidebarContent);

	    var tagSelect = TimeSafe.makeTagSelect(project, tags);
	    tagSelect.onchange=function(){TimeSafe.validate(idStr);};
	    tagSelect.name = "tag_" + idStr + "[]";
	    tagSelect.id = "tag_" + idStr;
	    sidebarContent.appendChild(tagSelect);
	    
	    var description = document.createElement('textarea');
	    description.name = "description_" + idStr;
	    description.id = description.name;
	    description.onchange=function(){TimeSafe.validate(idStr);};
	    description.className= "description"
	    description.rows = 10;
	    sidebarContent.appendChild(description);
	    
	    var notification = document.createElement('span');
	    notification.id = "notification_" + idStr;
	    sidebarContent.appendChild(notification);
	    
	    if(project.slot[slot] && project.slot[slot][day]) {
		description.value=project.slot[slot][day].description;
		
		var entryId = document.createElement('input');
		entryId.type='hidden';
		entryId.name='entry_id_' + idStr;
		entryId.value=project.slot[slot][day].id;
		anchor.appendChild(entryId);
	    }	
	    sidebar.tag=tagSelect;
	    sidebar.description=description;
	    sidebar.doInit=function(){}
	}
	return res;
    },

    /**
       Find the row offset of the first row belonging to the specified project
    */
    findFirstRow: function(project) {
	var res = 0;
	var d = TimeSafeData.projects;
	for(var i = 0; i<d.length; i++) {
	    if(d[i]==project) {
		break;
	    }
	    res += d[i].slot.length;
	}
	return res;
    },
    
    /**
       Add the slot with the specified index from the specified
       project. If slot index is null, an empty new slot is created.
    */
    addProjectSlot : function (tbody, project, slotIdx)
    {
	var forceVisible=false;
	if (slotIdx == null) {
	    forceVisible = true;
	    slotIdx = project.slot.length;
	    project.slot[slotIdx]=[];
	}
	
	var row = tbody.insertRow(slotIdx==0?tbody.rows.length:TimeSafe.findFirstRow(project)+slotIdx);
	if(!forceVisible && project.slot[slotIdx].length == 0 && !project.is_resource) {
	    row.className = "default_invisible";
	    row.style.display='none';
	}
	else {
	    row.className = "default_visible";
	}
	
	row.addCell = function (content, className, id) {
	    var c = document.createElement('td');
	    if (className) {
		c.className = className;
	    }
	    if (id) {
		c.id = id;
	    }
	    c.appendChild(content);
	    this.appendChild(c);
	};

	row.addCell(TimeSafe.makeText('project_'+project.line,
				      slotIdx==0?project.project_name:""),
		    'project');
	row.id = "row_" + project.line + "_" + slotIdx;

	if(slotIdx ==0) {
	    var link = document.createElement('a');
	    link.innerHTML = '&nbsp;+&nbsp;';
	    link.className="add_line"
	    link.onclick = function(event) {
		TimeSafe.addProjectSlot(tbody, project);
	    };
	    row.addCell(link);
	}
	else {
	    row.addCell(document.createElement('span'));
	}
	
	for( var j=0; j<TimeSafeData.days; j++) {
	    var idStr = "" + project.line + "_" + slotIdx + "_" + j;
	    row.addCell(TimeSafe.makeTimeInput(project, slotIdx, j),
			(7+j-TimeSafeData.weekendOffset)%7<2?'end_week':null,
			'td_' + idStr);
	}
	
    },
    
    /**
       Add table rows for all the slots in the specified project
    */
    addProject : function (tbody, project)
    {
	project.line = TimeSafe.projectLines;
	
	if (project.slot.length == 0) {
	    project.slot[0]=[];
	}
	
	for(var i=0; i<project.slot.length; i++) {
	    TimeSafe.addProjectSlot(tbody, project, i);
	}
	
	var projectId = document.createElement('input');
	projectId.type='hidden';
	projectId.name="project_" + project.line;
	projectId.value = project.project_id;
	tbody.appendChild(projectId);
	TimeSafe.projectLines++;
    },
    
    /**
       Add table rows for all projects in TimeSafeData.projects
    */
    addProjectLines : function()
    {
	var doc = $('.content')[0];
	var tbody = document.createElement('tbody');
	tbody.id = 'time_body';
	var d = TimeSafeData.projects;
	for(var i = 0; i<d.length; i++) {
	    TimeSafe.addProject(tbody, d[i]);
	}
	$('#time')[0].appendChild(tbody);
	for(var i = 0; i<TimeSafeData.days; i++) {
	    TimeSafe.showSum(i);
	}
	$('#show_all')[0].checked = false;
	if(TimeSafeData.entry) {
	    var e = TimeSafeData.entry;
	    var e2 = $('#time_' + TimeSafe.entryIdLookup[e])[0];
	    if(e2) 
	    {
		e2.focus();
	    }
	    
	}
    },
    
    /**
       Toggle visiblity of unused projects
    */
    updateVisibility: function()
    {
	if($('#show_all')[0].checked) {
	    $('tr.default_invisible').show();
	} else {
	    $('tr.default_invisible').hide();
	}
    }
};

