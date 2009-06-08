function popupShow(id) {
	box = document.getElementById(id);
	box.style.display="block";
}

function popupHide(id) {
	box = document.getElementById(id);
	box.style.display="none";
}



// this function is needed to work around 
// a bug in IE related to element attributes
function hasClass(obj) {
	var result = false;
	if (obj.getAttributeNode("class") != null) {
		result = obj.getAttributeNode("class").value;
	}
	return result;
}   
/**
   This is a lot more complex than the ususal examples you see of
   javascript striped tables, because it needs to handle a few extra
   cases, like non-striped tables nested within striped tables.

   It's possible that jQuery could be made to handle those things,
   though.
 */
function stripe(id) {
		
	// the flag we'll use to keep track of 
	// whether the current row is odd or even
	var even = false;
		
	// obtain a reference to the desired tables
	var table_list = (id != null) ? $("#" + id) : $('.striped');
    
	for(var tab=0; tab<table_list.length;tab++) {
		
		var table = table_list[tab];

		// by definition, tables can have more than one tbody
		// element, so we'll have to get the list of child
		// &lt;tbody&gt;s 
		var tbodies = table.getElementsByTagName("tbody");
		    
		// and iterate through them...
		for (var h = 0; h < tbodies.length; h++) {
			
			// find all the tr elements... 
			var trs = tbodies[h].getElementsByTagName("tr");
			
			// ... and iterate through them
			for (var i = 0; i < trs.length; i++) {

				var parent = trs[i].parentNode;
				while(parent && parent.nodeName.toLowerCase() != 'tbody') 
				{
					parent = parent.parentNode;
				}
	
				/*
				  Check that we're not seeing a tr of a table nested inside a striped table.
				 */
				if( parent != tbodies[h] ) 
				{
					continue;
				}
				
		    
				// avoid rows that have a class attribute
				// or backgroundColor style
				if (! hasClass(trs[i]) &&
				    ! trs[i].style.backgroundColor) {
 		  
					// get all the cells in this row...
					var tds = trs[i].getElementsByTagName("td");
					var ths = trs[i].getElementsByTagName("th");
		    
					// and iterate through them...
					for (var j = 0; j < tds.length; j++) {
			
						var mytd = tds[j];
			
						mytd.className = even?'even':'odd';
					}
					for (var j = 0; j < ths.length; j++) {
			
						var myth = ths[j];
			
						myth.className = even?'even':'odd';
					}
				}
				// flip from odd to even, or vice-versa
				even =  ! even;
			}
		}
	}
}

function installCheckFields() {
    var ok = true;
    fields = ['admin_username','admin_password','dsn'];
    for(var i =0; i<fields.length; i++) {
	if ($("#"+fields[i])[0].value == '') {
	    $("#"+fields[i] + '_error')[0].innerHTML = "No value specified";
	    ok = false;
	}
	else {
	    $("#"+fields[i] + '_error')[0].innerHTML = "";
	}
    }

    if ($('#admin_password')[0].value != $('#admin_password2')[0].value) {
	$('#admin_password2_error')[0].innerHTML = "Passwords don't match";
	ok = false;
    }
    else {
	$('#admin_password2_error')[0].innerHTML = "";
    }
    return ok;
}


function installDbCheck() {
    var url = 'index.php?action=db_check&dsn=' + encodeURIComponent($('#dsn')[0].value);
    $.get(url, null, function(response) {
	    $('#db_notification')[0].innerHTML = response;
	}
	,'text');
}



/*************************************************************
 *    DYNIFS - Dynamic IFrame Auto Size v1.0.0
 *
 *    Copyright (C) 2006, Markus (phpMiX)
 *    This script is released under GPL License.
 *    Feel free to use this script (or part of it) wherever you need
 *    it ...but please, give credit to original author. Thank you. :-)
 *    We will also appreciate any links you could give us.
 *    http://www.phpmix.org
 *
 *    Enjoy! ;-)
 *************************************************************/

var dynamicIFrame = {
	// Storage for known IFrames.
  iframes: {},
  // Here we save any previously installed onresize handler.
  oldresize: null,
  // Flag that tell us if we have already installed our onresize handler.
  ready: false,
  // The document dimensions last time onresize was executed.
  dim: [-1,-1],
  // Timer ID used to defer the actual resize action.
  timerID: 0,
  // Obtain the dimensions (width,height) of the given document.
  getDim: function(d) {
		var w=200, h=200, scr_h, off_h;
		if( d.height ) { return [d.width,d.height]; }
		with( d.body ) {
			if( scrollHeight ) { h=scr_h=scrollHeight; w=scrollWidth; }
			if( offsetHeight ) { h=off_h=offsetHeight; w=offsetWidth; }
			if( scr_h && off_h ) h=Math.max(scr_h, off_h);
		}
		return [w,h];
	},
  // This is our window.onresize handler.
  onresize: function() {
		// Invoke any previously installed onresize handler.
		if( typeof this.oldresize == 'function' ) { this.oldresize(); }
		// Check if the document dimensions really changed.
		var dim = this.getDim(document);
		if( this.dim[0] == dim[0] && this.dim[1] == dim[1] ) return;
		// Defer the resize action to prevent endless loop in quirksmode.
		if( this.timerID ) return;
		this.timerID = setTimeout('dynamicIFrame.deferred_resize();', 10);
	},
  // This is where the actual IFrame resize is invoked.
  deferred_resize: function() {
		// Walk the list of known IFrames to see if they need to be resized.
		for( var id in this.iframes ) this.resize(id);
		// Store resulting document dimensions.
		this.dim = this.getDim(document);
		// Clear the timer flag.
		this.timerID = 0;
	},
  // This is invoked when the IFrame is loaded or when the main window is resized.
  resize: function(id) {
		// Browser compatibility check.
		if( !window.frames || !window.frames[id] || !document.getElementById || !document.body )
			return;
		// Get references to the IFrame window and layer.
		var iframe = window.frames[id];
		var div = document.getElementById(id);
		if( !div ) return;
		// Save the IFrame id for later use in our onresize handler.
		if( !this.iframes[id] ) {
			this.iframes[id] = true;
		}
		// Should we inject our onresize event handler?
		if( !this.ready ) {
			this.ready = true;
			this.oldresize = window.onresize;
			window.onresize = new Function('dynamicIFrame.onresize();');
		}
		// This appears to be necessary in MSIE to compute the height
		// when the IFrame'd document is in quirksmode.
		// OTOH, it doesn't seem to break anything in standards mode, so...
		if( document.all ) div.style.height = '0px';
		// Resize the IFrame container.
		var dim = this.getDim(iframe.document);
		div.style.height = (dim[1]+30) + 'px';
	}
};

function FreeCMDBInit()
{
    Date.format='yyyy-mm-dd';
}

FreeCMDBInit();
