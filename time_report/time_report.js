/*
 * Fallback XmlHttp version for IE browsers
 */
if( !window.XMLHttpRequest ) XMLHttpRequest = function()
{
  try { return new ActiveXObject("MSXML3.XMLHTTP");     } catch(e) {}
  try { return new ActiveXObject("MSXML2.XMLHTTP.3.0"); } catch(e) {}
  try { return new ActiveXObject("Msxml2.XMLHTTP");     } catch(e) {}
  try { return new ActiveXObject("Microsoft.XMLHTTP");  } catch(e) {}

  throw new Error("Could not find an XMLHttpRequest alternative.");
};

var textLoaded = true;

function getQueryString(formId) {
    var queryString="";
    var frm = document.getElementById(formId);
    var numberElements = frm.elements.length;
    for(var i = 0; i < numberElements; i++) {

	if (frm.elements[i].options != null) {
	    for (var j=0; j<frm.elements[i].options.length; j++) {
		if (frm.elements[i].options[j].selected) {
                    if(queryString != "")
            		queryString += "&";
		
	            queryString += frm.elements[i].name+"=";
        	    queryString += encodeURIComponent(frm.elements[i].options[j].value);
		}
	    }
	}
	if (frm.elements[i].value != "") {
            if(queryString != "")
                queryString += "&";

            queryString += frm.elements[i].name+"=";
            queryString += encodeURIComponent(frm.elements[i].value);
	}


    }
    return queryString;
} 


/*
 * This function will use AJAX to repeatedly replace the current slide
 * with the next one.
 */
function timeReportSubmit() {

    
    /*
     * This index parameter tells the server what slide to render. The
     * rendered slide will be this number modulo the number of slides.
     */
    var vars1=getQueryString("report_form");
    var vars2=vars1 + "&dom=partial";

    var request = new XMLHttpRequest();

    request.open("POST", "index.php", 1);
    request.setRequestHeader("Content-Type",
                             "application/x-www-form-urlencoded"); 

    request.onreadystatechange = function() 
    {
        if (request.readyState == 4 && request.status == 200) {
	    d = document.getElementById('content');
            textLoaded = true;
            if (d!=null && request.responseText!=null) {
		d.innerHTML = request.responseText;
	    }
	}
    };
    
    request.send(vars2);
    textLoaded = false;

    var url = new String(document.location);
    url = url.split("?")[0];
    url = url + "?" + vars1;
    document.getElementById("url_direct").innerHTML = "<a href='"+url+"'>URL for this exact report</a>";
    startSpinner();
}

function getFirstDay(d) {
	return new Date(d.getFullYear(), d.getMonth(), 1);
}

function getLastDay(d) {
    var year = d.getFullYear();
    var month = d.getMonth();
    if (month == 11) {
	year++;
	month=0;
    } else {
	month++;
    }
    var d = new Date( year, month, 1);
    var d2 = new Date(d.valueOf()-1);
    return new Date(d2.getFullYear(), d2.getMonth(), d2.getDate());
}

Date.parseISO = function (str) {
    str = new String(str);
    var arr = str.split("-");
    return new Date(arr[0], arr[1]-1, arr[2]);
}

function getISO (dt) {
    var m = new String(1+dt.getMonth());
    while (m.length<2) {
	m = "0" + m;
    } 

    var d = new String(dt.getDate());
    while (d.length<2) {
	d = "0" + d;
    } 

    return "" + dt.getFullYear() + "-" + m + "-" + d;
}

function isMonth() {
    var from = Date.parseISO(document.getElementById("from").value);
    var to = Date.parseISO(document.getElementById("to").value);

    if (from.valueOf() == getFirstDay(from).valueOf() && to.valueOf() == getLastDay(from).valueOf()) {
	return true;	
    }
    return false;
}

function nextPeriod() {
	
    var from = Date.parseISO(document.getElementById("from").value);
    var to = Date.parseISO(document.getElementById("to").value);

    if (isMonth()) {
	var m = new Date(to.valueOf() + 1000*3600*26);
	document.getElementById("from").value = getISO(getFirstDay(m));
	document.getElementById("to").value = getISO(getLastDay(m));
    } else {
        var span = to.valueOf() - from.valueOf();
        var from2 = new Date(to.valueOf() + 1000*3600*26);
	var to2 = new Date(from2.valueOf()+span);
	document.getElementById("from").value = getISO(from2);
	document.getElementById("to").value = getISO(to2);
    }

    timeReportSubmit();
}

function prevPeriod()
{
    var from = Date.parseISO(document.getElementById("from").value);
    var to = Date.parseISO(document.getElementById("to").value);

    if (isMonth()) {
	var m = new Date(from.valueOf() - 1000*3600*26);
	document.getElementById("from").value = getISO(getFirstDay(m));
	document.getElementById("to").value = getISO(getLastDay(m));
    } else {
        var span = to.valueOf() - from.valueOf();
        var to2 = new Date(from.valueOf() - 1000*3600*2);
	var from2 = new Date(to2.valueOf()-span);
	document.getElementById("from").value = getISO(from2);
	document.getElementById("to").value = getISO(to2);
    }

    timeReportSubmit();
}

function isLoading()
{
    var imageArray = document.getElementsByTagName("img");
    var imageCount = imageArray.length;

    if (!textLoaded) {
        return true;
    }

    for (var i = 0; i < imageCount; i++) {
	if (!imageArray[i].complete) {
	    return true;
	}
    }
    return false;
}

/**
 * Show the spinner and call spinnerInternal function to check if we're still loading.
 */
function startSpinner()
{
    document.getElementById("spinner").style.display="inline";
    document.getElementById("spinner").innerHTML = "<img src='static/img/spinner.gif'>&nbsp;&nbsp;Loading...";
    spinnerInternal();
}

/** 
 * Check if we're still loading. If yes, check again in a little while, if no, stop showing the spinner.
 */
function spinnerInternal() 
{    
    if (isLoading()) {
        setTimeout("spinnerInternal()", 300);
    }
    else {
	document.getElementById("spinner").style.display="none";
        document.getElementById("spinner").innerHTML = "";
    }
}

