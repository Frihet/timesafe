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

	
	$form .= "<h2>Include</h2>";
	
	$show_graph = isset($_GET['show_graph']) ? $_GET['show_graph'] == 't' : true;
	$show_hour_list = isset($_GET['show_hour_list']) ? $_GET['show_hour_list'] == 't' : true;
	$show_hour_summary_per_user = isset($_GET['show_hour_summary_per_user']) ? $_GET['show_hour_summary_per_user'] == 't' : true;
	$show_hour_summary = isset($_GET['show_hour_summary']) ? $_GET['show_hour_summary'] == 't' : true;

	$form .= form::makeCheckbox('show_graph', $show_graph, "Graph", null, null, array('onchange'=>'submit();'));
	$form .= form::makeCheckbox('show_hour_list', $show_hour_list, "Hour list", null, null, array('onchange'=>'submit();'));
	$form .= form::makeCheckbox('show_hour_summary_per_user', $show_hour_summary_per_user, "Hour summary per user", null, null, array('onchange'=>'submit();'));
	$form .= form::makeCheckbox('show_hour_summary', $show_hour_summary, "Total hour summary", null, null, array('onchange'=>'submit();'));

	$content .= form::makeForm($form, $hidden, 'get');


	if ($show_graph) {
	    $content .= "<div class='figure'><img src='" . makeUrl(array_merge($_GET, array('controller'=>'graph', 'width' => '1024', 'height' => '480'))) . "' /></div>";
        }

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
	$colors = Entry::colors($filter);
	$hours_by_date = Entry::coloredEntries($filter);

	$color_to_idx = array();
	$idx_to_color = array();
	$idx_to_tag_names = array();
	$idx = 0;
	foreach ($colors as $color) {
	    $idx_to_color[$idx] = array($color['color_r'], $color['color_g'], $color['color_b']);
	    $idx_to_tag_names[$idx] = $color['tag_names'];
	    $colorname = util::colorToHex($color['color_r'], $color['color_g'], $color['color_b']);
	    $color_to_idx[$colorname] = $idx;
	    $idx++;
	}
        
	/* Sum stuff up */
	$sums = array('total' => array('total' => 0));
	foreach ($hours_by_date as $date => $hours) {
	    foreach ($hours as $hour) {
	        $usr = $hour['user_fullname'];
	        $color = util::colorToHex($hour['color_r'], $hour['color_g'], $hour['color_b']);
		if (!isset($sums[$usr])) $sums[$usr] = array('total' => 0);
		if (!isset($sums[$usr][$color])) $sums[$usr][$color] = 0;
		if (!isset($sums['total'][$color])) $sums['total'][$color] = 0;
		$sums[$usr][$color] += $hour['minutes'];
		$sums[$usr]['total'] += $hour['minutes'];
		$sums['total'][$color] += $hour['minutes'];
		$sums['total']['total'] += $hour['minutes'];
	    }
        }

	if ($show_hour_list) {
	    $content .= "<table class='report_timetable'><tr><th>Date</th><th></th><th>Minutes</th><th>User</th><th>Project</th><th>Tags</th><th>Description</th></tr>";
	    foreach ($hours_by_date as $date => $hours) {
		$date = date('Y-m-d', $date);
		foreach ($hours as $hour) {
		    $color = util::colorToHex($hour['color_r'], $hour['color_g'], $hour['color_b']);
		    $content .= "<tr><th>{$date}</th><td style='background: {$color}'>&nbsp;</td><td>{$hour['minutes']}</td><td>{$hour['user_fullname']}</td><td>{$hour['project']}</td><td>{$hour['tag_names']}</td><td>{$hour['description']}</td></tr>";
		    $date = '';
		}
	    }
	    $content .= "</table>";
	}

	if ($show_hour_summary_per_user) {
	    $content .= "<table class='report_timetable'>";
	    $content .= " <tr>";
	    $content .= "  <th>User</th>";
	    foreach ($colors as $color) {
		$content .= "<th>{$color['tag_names']}</th>";
	    }
	    $content .= "  <th>Total</th>";
	    $content .= " </tr>";
	    $content .= " <tr>";
	    $content .= "  <th></th>";
	    foreach ($colors as $color) {
		$color = util::colorToHex($color['color_r'], $color['color_g'], $color['color_b']);
		$content .= "<td style='background: {$color}'>&nbsp;</td>";
	    }
	    $content .= "  <td></td>";
	    $content .= " </tr>";

	    foreach ($sums as $usr => $color_sums) {
		if ($usr != 'total') {
		    $content .= "<tr><th>{$usr}</th>";
		    foreach ($colors as $color) {
			if ($color != 'total') {
			    $color = util::colorToHex($color['color_r'], $color['color_g'], $color['color_b']);
			    if (isset($color_sums[$color])) {
				$content .= "<td>{$color_sums[$color]}</td>";
			    } else {
				$content .= "<td></td>";
			    }
			}
		    }
		    $content .= "<td>{$color_sums['total']}</td>";
		    $content .= " </tr>";
		}
	    }
	}

	if ($show_hour_summary) {
	    $content .= "<table class='report_timetable'>";
	    $content .= "<tr><th>Tags</th><th></th><th>Minutes</th></tr>";
	    foreach ($sums['total'] as $color => $sum) {
		if ($color != 'total') {
		    $tags = $idx_to_tag_names[$color_to_idx[$color]];
		    $content .= "<tr><th>{$tags}</th><td style='background: {$color}'>&nbsp;</td><td>{$sum}</td>";
		}
	    }
	    $content .= "<tr><th>Total</th><td></td><td>{$sums['total']['total']}</td>";

	    $content .= "</table>";
	}

        $this->show(null, $content);

    }
    
    
      
}


?>