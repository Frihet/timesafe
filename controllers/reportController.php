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
	
	$params = array_merge($_GET);
	$params['reports'] = $params['reports'] + 1;
	$form .= "<a href='" . makeUrl($params) . "' />Add another report item</a><br>";

	$all_users = User::getAllUsers();
	$all_tags = Tag::fetch();
	$all_projects = Project::getProjects();

	for ($report = 0; $report < $reports; $report++) {
	    $report_data = isset($_GET['report']) && isset($_GET['report'][$report]) ? $_GET['report'][$report] : array();
	    $report_data = array_merge(array(
	      'title' => '',
	      'cls' => '',
	      'type' => 'graph',
	      'order' => 'perform_date,user_fullname,project,tag_names',
	      'users' => array(),
	      'tags' => array(),
	      'projects' => array(),
	    ), $report_data);
	    
	    $hidden["report[{$report}][order]"] = $report_data['order'];
	    $report_data['order'] = explode(',', $report_data['order']);

            $form .= "<div class='report_form_part'>";
            $form .= " <div class='report_form_part_header'>";

    	    /* Shift left */
	    if ($report > 0) {
		$params = array_merge($_GET);
		if (!isset($params['report'])) $params['report'] = array();
		if (!isset($params['report'][$report])) $params['report'][$report] = array();
		if (!isset($params['report'][$report+1])) $params['report'][$report+1] = array();

		$temp = $params['report'][$report];
		$params['report'][$report] = $params['report'][$report-1];
		$params['report'][$report-1] = $temp;

		$params['report'] = isset($params['report']) ? array_values($params['report']) : array();
		$form .= "<a href='" . makeUrl($params) . "' />&lt;&lt;</a> ";
	    }

	    /* Remove-link */
	    $params = array_merge($_GET);
	    unset($params['report'][$report]);
	    $params['report'] = isset($params['report']) ? array_values($params['report']) : array();
	    $params['reports'] = $params['reports'] - 1;
	    $form .= "<a href='" . makeUrl($params) . "' />X</a> ";

	    /* Shift right */
	    if ($report < $reports - 1) {
		$params = array_merge($_GET);
		if (!isset($params['report'])) $params['report'] = array();
		if (!isset($params['report'][$report])) $params['report'][$report] = array();
		if (!isset($params['report'][$report+1])) $params['report'][$report+1] = array();

		$temp = $params['report'][$report];
		$params['report'][$report] = $params['report'][$report+1];
		$params['report'][$report+1] = $temp;

		$params['report'] = isset($params['report']) ? array_values($params['report']) : array();
		$form .= "<a href='" . makeUrl($params) . "' />&gt;&gt;</a>";
	    }
	    $form .= " </div>";
	    
	    $form .= "Title: " . form::makeText("report[{$report}][title]", $report_data['title'], null, null, array('onchange'=>'submit();'));
	    $form .= "Class: " . form::makeText("report[{$report}][cls]", $report_data['cls'], null, null, array('onchange'=>'submit();'));
	    $form .= "<table>
		       <tr><th>Users</th><th>Tags</th><th>Projects</th><th>Sort order</th></tr>
		       <tr>";

	    $form .= "<td>" . form::makeSelect("report[{$report}][users]", form::makeSelectList($all_users, 'name', 'fullname'), $report_data['users'], null, array('onchange'=>'submit();')) . "</td>";
	    $form .= "<td>" . form::makeSelect("report[{$report}][tags]", form::makeSelectList($all_tags, 'name', 'name'), $report_data['tags'], null, array('onchange'=>'submit();')) . "</td>";
	    $form .= "<td>" . form::makeSelect("report[{$report}][projects]", form::makeSelectList($all_projects, 'name', 'name'), $report_data['projects'], null, array('onchange'=>'submit();')) . "</td>";

	    $form .= '<td>';
	    foreach ($report_data['order'] as $item) {
		$new_order = array_merge(array($item), array_diff($report_data['order'], array($item)));
		$params = array_merge($_GET);
		$params['report'][$report]['order'] = implode(',',$new_order);
		$form .= "<a href='" . makeUrl($params) . "' />{$hour_list_columns[$item]}</a><br>";
	    }
	    $form .= "</td>";

	    $form .= "</tr></table>";

	    $form .= "Show as " . form::makeSelect("report[{$report}][type]", array('graph' => 'Graph', 'list' => 'Hour list', 'sum' => 'Hour summary'), $report_data['type'], null, array('onchange'=>'submit();'));

	    $form .= "</div>";
	}
        $content .= form::makeForm($form, $hidden, 'get');
	$content .= "<div class='report_form_end'></div>";


	$date_end = date('Y-m-d',Entry::getBaseDate());
	$date_begin = date('Y-m-d',Entry::getBaseDate()-(Entry::getDateCount()-1)*3600*24);

	for ($report = 0; $report < $reports; $report++) {
	    $report_data = isset($_GET['report']) && isset($_GET['report'][$report]) ? $_GET['report'][$report] : array();
	    $report_data = array_merge(array(
	      'title' => '',
	      'cls' => '',
	      'type' => 'graph',
	      'order' => 'perform_date,user_fullname,project,tag_names',
	      'users' => array(),
	      'tags' => array(),
	      'projects' => array(),
	    ), $report_data);
	    $report_data['order'] = explode(',', $report_data['order']);

	    $all = User::getAllUsers();
	    $user_ids = array();
	    foreach ($report_data['users'] as $usr) {
		$user_ids[] = $all[$usr]->id;
	    }

	    $filter = array(
	     'date_begin' => $date_begin,
	     'date_end' => $date_end,
	     'projects' => $report_data['projects'],
	     'tags' => $report_data['tags'],
	     'users' => $user_ids
	    );
	    $colors = Entry::colors($filter);
	    $hours_by_date = Entry::coloredEntries($filter, $report_data['order']);

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
	    $col1 = $report_data['order'][0];
	    $col2 = $report_data['order'][1];
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

	    $content .= "<div class='{$report_data['cls']}'>";
	    if ($report_data['title'] != "") {
	        $content .= "<h1>{$report_data['title']}</h1>";
	    }

	    if ($report_data['type'] == 'graph') {
	        $params = array_merge($report_data, array('controller'=>'graph', 'width' => '1024', 'height' => '480', 'date' => param('date')));
	        $params['order'] = implode(',', $params['order']);
		$content .= "<img src='" . makeUrl($params) . "' />";
	    }

	    if ($report_data['type'] == 'list') {
	        $columns = array_merge($hour_list_columns);
                $ordered_columns = array();
		foreach ($report_data['order'] as $col) {
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

	    if ($report_data['type'] == 'sum') {
	        $col1 = $report_data['order'][0];
	        $title1 = $hour_list_columns[$col1];
	        $col2 = $report_data['order'][1];
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