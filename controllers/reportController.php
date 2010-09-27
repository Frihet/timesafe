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

	$hour_list_columns = array('perform_date' => 'Date', 'minutes' => 'Minutes', 'user_fullname' => 'User', 'project' => 'Project', 'tag_names' => 'Tags', 'description' => 'Description');

        $form = "";
	$reports = isset($_GET['reports']) ? intval($_GET['reports']) : 1;
	$hidden = array('controller' => 'report', 'reports' => $reports);
	if (param('date')) $hidden['date']  = param('date');

        $form .= "<p><a href='$prev_link'>«earlier</a> <a href='$now_link'>today</a>  <a href='$next_link'>later»</a></p>";
	$form .= '<div>Number of reports: ' . form::makeText('reports', $reports, null, null, array('onchange'=>'submit();')) . "</div>";

	$all_users = User::getAllUsers();
	$all_tags = Tag::fetch();
	$all_projects = Project::getProjects();

	for ($report = 0; $report < $reports; $report++) {
	    $title = isset($_GET['title_'.$report]) ? $_GET['title_'.$report] : "";
	    $cls = isset($_GET['cls_'.$report]) ? $_GET['cls_'.$report] : "";

	    $type = isset($_GET['type_'.$report]) ? $_GET['type_'.$report] : 'graph';

	    $hour_list_order = isset($_GET['hour_list_order_'.$report]) ? explode(',', $_GET['hour_list_order_'.$report]) : array('perform_date','user_fullname','project','tag_names');
	    $hidden['hour_list_order_'.$report] = implode(',', $hour_list_order);

	    $users = isset($_GET['users_'.$report]) ? $_GET['users_'.$report] : array();
	    $tags = isset($_GET['tags_'.$report]) ? $_GET['tags_'.$report] : array();
	    $projects = isset($_GET['projects_'.$report]) ? $_GET['projects_'.$report] : array();


            $form .= "<div class='report_form_part'>";
	    $form .= "Title: " . form::makeText('title_'.$report, $title, null, null, array('onchange'=>'submit();'));
	    $form .= "Class: " . form::makeText('cls_'.$report, $cls, null, null, array('onchange'=>'submit();'));
	    $form .= "<table>
		       <tr><th>Users</th><th>Tags</th><th>Projects</th><th>Sort order</th></tr>
		       <tr>";

	    $form .= "<td>" . form::makeSelect('users_'.$report, form::makeSelectList($all_users, 'name', 'fullname'), $users, null, array('onchange'=>'submit();')) . "</td>";
	    $form .= "<td>" . form::makeSelect('tags_'.$report, form::makeSelectList($all_tags, 'name', 'name'), $tags, null, array('onchange'=>'submit();')) . "</td>";
	    $form .= "<td>" . form::makeSelect('projects_'.$report, form::makeSelectList($all_projects, 'name', 'name'), $projects, null, array('onchange'=>'submit();')) . "</td>";

	    $form .= '<td>';
	    foreach ($hour_list_order as $item) {
		$new_order = array_merge(array($item), array_diff($hour_list_order, array($item)));
		$params = array_merge($_GET, array('hour_list_order_'.$report => implode(',',$new_order)));
		$form .= "<a href='" . makeUrl($params) . "' />{$hour_list_columns[$item]}</a><br>";
	    }
	    $form .= "</td>";

	    $form .= "</tr></table>";

	    $form .= "Show as " . form::makeSelect('type_'.$report, array('graph' => 'Graph', 'list' => 'Hour list', 'sum' => 'Hour summary'), $type, null, array('onchange'=>'submit();'));

	    $form .= "</div>";
	}
        $content .= form::makeForm($form, $hidden, 'get');
	$content .= "<div class='report_form_end'></div>";

	for ($report = 0; $report < $reports; $report++) {
	    $title = isset($_GET['title_'.$report]) ? $_GET['title_'.$report] : "";
	    $cls = isset($_GET['cls_'.$report]) ? $_GET['cls_'.$report] : "";

	    $users = isset($_GET['users_'.$report]) ? $_GET['users_'.$report] : array();
	    $tags = isset($_GET['tags_'.$report]) ? $_GET['tags_'.$report] : array();
	    $projects = isset($_GET['projects_'.$report]) ? $_GET['projects_'.$report] : array();
	    $type = isset($_GET['type_'.$report]) ? $_GET['type_'.$report] : 'graph';

	    $hour_list_order = isset($_GET['hour_list_order_'.$report]) ? explode(',', $_GET['hour_list_order_'.$report]) : array('perform_date','user_fullname','project','tag_names');

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
	     'projects' => isset($_GET['projects_'.$report]) ? $_GET['projects_'.$report] : array(),
	     'tags' => isset($_GET['tags_'.$report]) ? $_GET['tags_'.$report] : array(),
	     'users' => $user_ids
	    );
	    $colors = Entry::colors($filter);
	    $hours_by_date = Entry::coloredEntries($filter, $hour_list_order);

	    $color_to_idx = array();
	    $idx_to_color = array();
	    $idx_to_tag_names = array();
	    $idx = 0;
	    foreach ($colors as $color) {
		$idx_to_color[$idx] = array($color['color_r'], $color['color_g'], $color['color_b']);
		$idx_to_tag_names[$idx] = $color['tag_names'];
		$tag_names_to_idx[$color['tag_names']] = $idx;
		$colorname = util::colorToHex($color['color_r'], $color['color_g'], $color['color_b']);
		$color_to_idx[$colorname] = $idx;
		$idx++;
	    }

	    /* Sum stuff up */
	    $col1 = $hour_list_order[0];
	    $col2 = $hour_list_order[1];
	    $col1values = array();
	    $col2values = array();
	    $sums = array('total' => array('total' => 0));
	    foreach ($hours_by_date as $hours) {
		foreach ($hours as $hour) {
		    $col1value = $hour[$col1];
		    $col2value = $hour[$col2];
		    if (!in_array($col1value, $col1values)) $col1values[] = $col1value;
		    if (!in_array($col2value, $col2values)) $col2values[] = $col2value;
		    if (!isset($sums[$col1value])) $sums[$col1value] = array('total' => 0);
		    if (!isset($sums[$col1value][$col2value])) $sums[$col1value][$col2value] = 0;
		    if (!isset($sums['total'][$col2value])) $sums['total'][$col2value] = 0;
		    $sums[$col1value][$col2value] += $hour['minutes'];
		    $sums[$col1value]['total'] += $hour['minutes'];
		    $sums['total'][$col2value] += $hour['minutes'];
		    $sums['total']['total'] += $hour['minutes'];
		}
	    }

	    $content .= "<div class='{$cls}'>";
	    if ($title != "") {
	        $content .= "<h1>{$title}</h1>";
	    }

	    if ($type == 'graph') {
	        $params = array('controller'=>'graph', 'width' => '1024', 'height' => '480', 'date' => param('date'));
		foreach ($_GET as $name => $value) {
		    if (util::ends_with($name, "_{$report}")) {
		        $name = substr($name, 0, strlen($name)-strlen("_{$report}"));
		        $params[$name] = $value;
		    }
		}
		$content .= "<img src='" . makeUrl($params) . "' />";
	    }

	    if ($type == 'list') {
	        $columns = array_merge($hour_list_columns);
                $ordered_columns = array();
		foreach ($hour_list_order as $col) {
		    $ordered_columns[$col] = $columns[$col];
		    unset($columns[$col]);
		}
		$columns = array_merge($ordered_columns, $columns);

		$content .= "<table class='report_timetable'>";
		$content .= " <tr>";
		foreach($columns as $col => $col_desc) {
		    if ($col == 'tag_names') {
		        $content .= "<th></th>";
 		    }
		    $content .= "<th>{$col_desc}</th>";
	        }
		$content .= " </tr>";

		foreach ($hours_by_date as $hours) {
		    foreach ($hours as $hour) {
			$content .= " <tr>";
			$first = true;
			foreach($columns as $col => $col_desc) {
			    $tag = $first ? 'th' : 'td';
			    $value = $hour[$col];
			    if ($col == 'perform_date')
			        $value = date('Y-m-d', $value);
			    if ($col == 'tag_names') {
			        $color = util::colorToHex($hour['color_r'], $hour['color_g'], $hour['color_b']);
			        $content .= "<{$tag} style='background: {$color}; color: {$color}'>#</{$tag}>";
			    }
			    $content .= "<{$tag}>{$value}</{$tag}>";
			    $first = false;
			}
			$content .= " </tr>";
		    }
		}
		$content .= "</table>";
	    }

	    if ($type == 'sum') {
	        $col1 = $hour_list_order[0];
	        $title1 = $hour_list_columns[$col1];
	        $col2 = $hour_list_order[1];
	        $title2 = $hour_list_columns[$col2];

		$content .= "<table class='report_timetable'>";
		$content .= " <tr>";
		$content .= "  <th>{$title1}</th>";
		if ($col1 == 'tag_names') {
		    $content .= "  <th></th>";
		}
		foreach ($col2values as $col2value) {
		    if ($col2 == 'perform_date') {
			$col2value = date('Y-m-d', $col2value);
		    }
		    $content .= "<th>{$col2value}</th>";
		}
		$content .= "  <th>Total</th>";
		$content .= " </tr>";
		if ($col2 == 'tag_names') {
		    $content .= " <tr>";
		    $content .= "  <th></th>";
		    foreach ($col2values as $col2value) {
		        $color = $idx_to_color[$tag_names_to_idx[$col2value]];
			$color = util::colorToHex($color[0], $color[1], $color[2]);
			$content .= "<td style='background: {$color}; color: {$color}'>#</td>";
		    }
		    $content .= "  <td></td>";
		    $content .= " </tr>";
		}

		foreach ($sums as $col1value => $col2_sums) {
		    if ($col1value != 'total') {
		        if ($col1 == 'perform_date') {
			    $col1value = date('Y-m-d', $col1value);
			}
			$content .= "<tr><th>{$col1value}</th>";
			if ($col1 == 'tag_names') {
			    $color = $idx_to_color[$tag_names_to_idx[$col1value]];
			    $color = util::colorToHex($color[0], $color[1], $color[2]);
			    $content .= "<td style='background: {$color}; color: {$color}'>#</td>";
			}
			foreach ($col2values as $col2value) {
			    if ($col2value != 'total') {
				if (isset($col2_sums[$col2value])) {
				    $content .= "<td>{$col2_sums[$col2value]}</td>";
				} else {
				    $content .= "<td></td>";
				}
			    }
			}
			$content .= "<td>{$col2_sums['total']}</td>";
			$content .= " </tr>";
		    }
		}
		$content .= "</table>";
	    }
	    $content .= "</div>";
	}

        $this->show(null, $content);

    }
    
    
      
}


?>