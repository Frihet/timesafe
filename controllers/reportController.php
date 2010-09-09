<?php

  /** The php backend for the main editing stuff. Mostly just creates a bunch of json data. All
   the exciting stuff is JavaScript. Which is scary.
   */
class ReportController
extends Controller
{
    function baseDateStr()
    {
        $tm = Entry::getBaseDate();
        return date('Y-m-d', $tm);
    }

    function nextBaseDateStr()
    {
        $tm = Entry::getBaseDate();
        return date('Y-m-d', $tm + 7* 3600*24);
    }

    function prevBaseDateStr()
    {
        $tm = Entry::getBaseDate();
        return date('Y-m-d', $tm - 7* 3600*24);
    }

    function nowBaseDateStr()
    {
        return date('Y-m-d');
    }

    function viewRun()
    {
        $content = "";
        $content .= "<div id='debug' style='position:absolute;right: 100px;'></div>";
        $content .= "<div id='dnd_text' style='position:absolute;left: 0px;top: 0px; display:none;'></div>";

        util::setTitle("Reporting");

        $next = self::nextBaseDateStr();
        $prev = self::prevBaseDateStr();
        $now = self::nowBaseDateStr();
        $prev_link = makeUrl(array('date'=>$prev));
        $next_link = makeUrl(array('date'=>$next));
        $now_link = makeUrl(array('date'=>$now));
        $content .= "<p><a href='$prev_link'>«earlier</a> <a href='$now_link'>today</a>  <a href='$next_link'>later»</a></p>";


        $form = "";
	$hidden = array('controller' => 'report');
	if (param('date')) $hidden['date']  = param('date');

	$form .= "<table>
	           <tr><th>Users</th><th>Tags</th><th>Projects</th></tr>
                   <tr>";

	$users = isset($_GET['users']) ? $_GET['users'] : array();
	$form .= "<td>" . form::makeSelect('users', form::makeSelectList(User::getAllUsers(),'name', 'fullname'), $users, null, array('onchange'=>'submit();')) . "</td>";

	$tags = isset($_GET['tags']) ? $_GET['tags'] : array();
	$form .= "<td>" . form::makeSelect('tags', form::makeSelectList(Tag::fetch(), 'name', 'name'), $tags, null, array('onchange'=>'submit();')) . "</td>";

	$projects = isset($_GET['projects']) ? $_GET['projects'] : array();
	$form .= "<td>" . form::makeSelect('projects', form::makeSelectList(Project::getProjects(), 'name', 'name'), $projects, null, array('onchange'=>'submit();')) . "</td>";

	$form .= "</tr></table>";

	$content .= form::makeForm($form, $hidden, 'get');
	
	$content .= "<div class='figure'><img src='" . makeUrl(array_merge($_GET, array('controller'=>'graph', 'width' => '1024', 'height' => '480'))) . "' /></div>";
        



        $date_end = date('Y-m-d',Entry::getBaseDate());
        $date_begin = date('Y-m-d',Entry::getBaseDate()-(Entry::getDateCount()-1)*3600*24);

	$all = User::getAllUsers();
	$user_ids = array();
	foreach (param('users',array()) as $usr) {
	    $user_ids[] = $all[$usr]->id;
	}
	
	$filter = array(
	 'date_begin' => $date_begin,
 	 'date_end' => $date_end,
	 'projects' => isset($_GET['projects']) ? $_GET['projects'] : array(),
	 'tags' => isset($_GET['tags']) ? $_GET['tags'] : array(),
	 'users' => $user_ids
	);
	$hours_by_date = Entry::coloredEntries($filter);
        
	$content .= "<table class='report_timetable'><tr><th>Date</th><th></th><th>User</th><th>Project</th><th>Minutes</th><th>Tags</th><th>Description</th></tr>";
	foreach ($hours_by_date as $date => $hours) {
	    $date = date('Y-m-d', $date);
	    foreach ($hours as $hour) {
	        $color = '#' . str_pad(dechex($hour['color_r']), 2, "0", STR_PAD_LEFT) . str_pad(dechex($hour['color_g']), 2, "0", STR_PAD_LEFT) . str_pad(dechex($hour['color_b']), 2, "0", STR_PAD_LEFT);
	        $content .= "<tr><th>{$date}</th><td style='background: {$color}'>&nbsp;</td><td>{$hour['user_fullname']}</td><td>{$hour['project']}</td><td>{$hour['minutes']}</td><td>{$hour['tag_names']}</td><td>{$hour['description']}</td></tr>";
		$date = '';
	    }
        }
        $content .= "</table>";

        $this->show(null, $content);

    }
    
    
      
}


?>