<?php

class util
{

	static $ci_html_title="";
	static $message_str="";


	function calcDayInterval($day1, $day2) 
	{
		$year1 = date('Y', $day1);
		$month1 = date('m', $day1);
		$day1 = date('d', $day1);
		$tm1 = mktime(12, 0, 0, $month1, $day1, $year1);

		$year2 = date('Y', $day2);
		$month2 = date('m', $day2);
		$day2 = date('d', $day2);
		$tm2 = mktime(12, 0, 0, $month2, $day2, $year2);
		
		return round(($tm2-$tm1)/(24*3600));
		
	}

	function formatTime($tm)
	{
		if($tm % 60 == 0) 
		{
			return $tm / 60;
		}
		else if ($tm % 30 == 0) 
		{
                    return number_format((double)$tm/60, 1, ',','');
		}
		else
		{
			return floor($tm / 60). ':'. ($tm%60);
		}
		
	}
	
        function unformatTime($time) 
        {
            if(strstr($time, ':')) {
                $arr = explode(':',$time, 2);
                $minutes = $arr[1] + 60*$arr[0];
            }
            else {
                $time = str_replace(',','.',$time);
                $minutes = 60.0 * $time;
            }
            return $minutes;
        }
        
            
            
	
}

function htmlEncode($str) 
{
    return htmlEntities($str, ENT_QUOTES, 'UTF-8');
    
}

function setTitle($str)
{
	util::$ci_html_title = $str;
}

function getTitle()
{
	return util::$ci_html_title;
}


function param($name, $default=null) 
{
    if(array_key_exists($name, $_REQUEST)) {
        return $_REQUEST[$name];
    }
    return $default;
}

function stripslashesDeep($value)
{
    $value = is_array($value) ?
                array_map('stripslashesDeep', $value) :
                stripslashes($value);

    return $value;
}

function checkMagicQuotes() 
{
    if (get_magic_quotes_gpc()) {
        
        $_REQUEST = stripslashesDeep($_REQUEST);
        $_GET = stripslashesDeep($_GET);
        $_POST = stripslashesDeep($_POST);
    }
    
}

function error($str, $log=true) 
{
    if ($log)
        logMessage("Error: $str");
    
    $fmt = "<div class='error'>Error: $str</div>";
    util::$message_str .= $fmt;
}

function message($str, $log=true) 
{
    if ($log)
        logMessage($str);
    
    $fmt = "<div class='message'>$str</div>";
    util::$message_str .= $fmt;
}

function messageGet()
{
    if (array_key_exists('message_str', $_REQUEST)) {
	    return $_REQUEST['message_str'] . util::$message_str;
    }
    return util::$message_str;
}

function redirect($page=null) 
{
    if (!$page) {
        $page = "?";
        
    }
    unset($_REQUEST['message_str']);
    if (messageGet()) {
        $page .= strchr($page, '?')!==false?'&':'?';
        $page .= "message_str=" . urlEncode(messageGet()) ;
    }
    
    header("Location: $page");
    exit(0);
}

function makeUrl($v1=null, $v2=null) 
{
    if(is_array($v1)) {
        $arr = $v1;
        
    }
    else {
        if($v1===null) {
            $arr = array();
        }
        else {
            $arr = array($v1=>$v2);
        }
    }

    $res = array();
    foreach($arr as $key => $value) 
    {
        if ($value !== null) {
            $res[] = urlEncode($key) . "=" . urlEncode($value);
        }
    }
    
	$filter = array( 'message_str'=>true, 'filter_column'=>true, 'filter_column_value'=>true);
	
    foreach($_GET as $key => $value) 
    {
			if (array_key_exists($key, $filter)) {
            continue;
        }
        
        if (!array_key_exists($key, $arr) ) {
            $res[] = urlEncode($key) . "=" . urlEncode($value);
        }
    }

    if (count($res) == 0) 
        return "index.php";
        

    return "?" . implode("&", $res);
}

function makeForm($content, $hidden=array(),$method='post')
{
        $form = "<form accept-charset='utf-8' method='$method' action='index.php'>\n";
        foreach($hidden as $name => $value) {
            $form .= "<input type='hidden' name='".htmlEncode($name)."' value='".htmlEncode($value)."'>\n";
        }
        
        $form .= $content;
        $form .= "</form>\n";
        return $form;
}


function makeLink($arr, $txt, $class=null, $mouseover=null) 
{
    $mouseover_str = "";
    
    if ($mouseover) {
        $class .= " mouseoverowner";
        $mouseover_str = "<div class='onmouseover'>\n$mouseover\n</div>";
        
    }
    
    $class_str = $class?"class='$class'":"";

    if (is_array($arr)) {
        $arr = makeUrl($arr);
    }
    
    return "<a $class_str href='$arr'>$mouseover_str" . htmlEncode($txt) . "</a>\n";
}


function makePopup($title, $label, $content, $class= null, $onmouseover=null, $id=null) 
{
    if( $id == null ) {
        global $popup_id;
        $popup_id++;
        $id = "popup_$popup_id";
    }
    
    return makeLink("javascript:popupShow(\"$id\");", $label, $class, $onmouseover) ."
    <div class='anchor'>
    <div class='popup' id='$id'>
    <div class='popup_title'>
    $title
    <a href='javascript:popupHide(\"$id\")'>x</a>
    </div>
    <div class='popup_content'>
$content
    </div>
    </div>
    </div>
";
     
}

function makePager($page_var, $msg_count) 
{
    $current_page = getParam($page_var, 1);
    $log_count = PAGER_PAGES;
   
    $pages = floor(($msg_count-1)/$log_count)+1;

    if ($pages > 1) {

        if($current_page != '1') {
            $pager .= "<a href='".makeUrl($page_var, null)."'>&#x226a;</a>&nbsp;&nbsp;";
            $pager .= "<a href='".makeUrl(array($page_var=>$current_page-1))."'>&lt;</a>&nbsp;&nbsp;";
        }
        else {
            $pager .= "&#x226a;&nbsp;&nbsp;&lt;&nbsp;&nbsp;";
        }
        
        
        for( $i=1; $i <= $pages; $i++) {
            if($i == $current_page) {
                $pager .= "$i&nbsp;&nbsp;";
            }
            else {
                $pager .= "<a href='".makeUrl(array($page_var=>$i))."'>$i</a>&nbsp;&nbsp;";
            }
            
        }

        if($current_page != $pages) {
            $pager .= "<a href='".makeUrl(array($page_var=>$current_page+1))."'>&gt;</a>&nbsp;&nbsp;";
            $pager .= "<a href='".makeUrl(array($page_var=>$pages))."'>&#x226b;</a>&nbsp;&nbsp;";
        }
        else {
            $pager .= "&gt;&nbsp;&nbsp;&#x226b;&nbsp;&nbsp;";
        }
    }
    return $pager;
}

function logMessage()
{
    
}

function printGzippedPage() 
{
    $accepted_encodings= $_SERVER['HTTP_ACCEPT_ENCODING'];
    if( headers_sent() ){
        $encoding = false;
    } else if( strpos($accepted_encodings, 'x-gzip') !== false ) {
        $encoding = 'x-gzip';
    } else if( strpos($accepted_encodings,'gzip') !== false ) {
        $encoding = 'gzip';
    } else{
        $encoding = false;
    }

    if( $encoding ) {
        $contents = ob_get_contents();
        ob_end_clean();
        header('Content-Encoding: '.$encoding);
        print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
        $size = strlen($contents);
        $contents = gzcompress($contents, 1);
        $contents = substr($contents, 0, $size);
        print($contents);
        exit();
    } else {
        ob_end_flush();
        exit();
    }
}

function array_to_set($arr)
{
	$res = array();
	foreach($arr as $val) 
	{
		$res[$val] = true;
	}
	return $res;
}
function date_format($date) 
{
	if( !$date)
		return "";
	
	return date('Y-m-d', $date) . "&nbsp;" . date('H:i',$date);
}

function sprint_r($var)
{
    ob_start();
    print_r($var);
    $res = ob_get_contents();
    ob_end_clean();
    return $res;
}

?>