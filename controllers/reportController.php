<?php

  /** The php backend for the main editing stuff. Mostly just creates a bunch of json data. All
   the exciting stuff is JavaScript. Which is scary.
   */

require_once("util.php");

class ReportController
extends Controller
{
    private $hour_list_columns = array('perform_date' => 'Date', 'hours' => 'Hours', 'user_fullname' => 'User', 'project' => 'Project', 'tag_names' => 'Marks', 'description' => 'Description');

    function makeReportData ($date_begin, $date_end, $nrreports, $reports) {
        $res = array();
	for ($report = 0; $report < $nrreports; $report++) {
	    $report_data = isset($reports) && isset($reports[$report]) ? $reports[$report] : array();
	    $report_data = array_merge(array(
	      'title' => '',
	      'cls' => '',
	      'type' => 'graph',
	      'order' => 'perform_date,user_fullname,project,tag_names',
	      'users' => array(),
	      'tags' => array(),
	      'projects' => array(),
	      'mark_types' => 'both'
	    ), $report_data);
	    $report_data['order'] = explode(',', $report_data['order']);

	    $all = User::getAllUsers();
	    $user_ids = array();
	    foreach ($report_data['users'] as $usr) {
		$user_ids[] = $all[$usr]->id;
	    }

	    $filter = array(
	     'date_begin' => formatDate($date_begin),
	     'date_end' => formatDate($date_end),
	     'projects' => $report_data['projects'],
	     'tags' => $report_data['tags'],
	     'users' => $user_ids
	    );
	    $hours_by_date = Entry::coloredEntries($filter, $report_data['order'], $report_data['mark_types']);

	    foreach ($hours_by_date as &$hours) {
		foreach ($hours as &$hour) {
                    $hour['perform_date'] = date('Y-m-d', $hour['perform_date']);
                }
            }

	    $col1 = $report_data['order'][0];
	    $title1 = $this->hour_list_columns[$col1];
	    $col2 = $report_data['order'][1];
	    $title2 = $this->hour_list_columns[$col2];

            $tag_names_to_idx = array();
            $color_to_idx = array();
            $idx_to_color = array();
            $idx_to_tag_names = array();

	    if ($col1 == 'tag_names' || $col2 == 'tag_names') {
		$order = $report_data['order'];
	        if ($col1 == 'tag_names') {
		    $tmp = $order[0];
		    $order[0] = $order[1];
		    $order[1] = $tmp;
		}
		$colors = Entry::colors($filter, $order, $report_data['mark_types']);
		$idx = 0;
		foreach ($colors as $color) {
		    $idx_to_color[$idx] = array($color['color_r'], $color['color_g'], $color['color_b']);
		    $idx_to_tag_names[$idx] = $color['tag_names'];
		    $tag_names_to_idx[$color['tag_names']] = $idx;
		    $colorname = util::colorToHex($color['color_r'], $color['color_g'], $color['color_b']);
		    $color_to_idx[$colorname] = $idx;
		    $idx++;
		}
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
		    $sums[$col1value][$col2value] += $hour['hours'];
		    $sums[$col1value]['total'] += $hour['hours'];
		    $sums['total'][$col2value] += $hour['hours'];
		    $sums['total']['total'] += $hour['hours'];
		}
	    }

            $res[] = array(
                'report_data' => $report_data,
                'idx_to_color' => $idx_to_color,
                'tag_names_to_idx' => $tag_names_to_idx,
                'hours_by_date' => $hours_by_date,
                'sums' => $sums,
                'col1' => $col1,
                'col2' => $col2, 
                'title1' => $title1,
                'title2' => $title2,
                'col1values' => $col1values,
                'col2values' => $col2values,
                );
        }
        return $res;
    }


    function viewRun()
    {
        if (empty($_GET['start'])) {
            $date_begin = today() - 14*24*3600;
	    $date_end = today();
        } else {
            $date_begin = parseDate($_GET['start']);
	    $date_end = parseDate($_GET['end']);
        }

	$reports = isset($_GET['reports']) ? intval($_GET['reports']) : 1;

        $report_datas = self::makeReportData($date_begin, $date_end, $reports, $_GET['report']);

        if (!empty($_GET['format']) && $_GET['format'] == 'json') {
          header('Content-type: text/plain');
          echo json_encode($report_datas);
          exit(0);
        }


        $content = "";
        $content .= "<div id='debug' style='position:absolute;right: 100px;'></div>";
        $content .= "<div id='dnd_text' style='position:absolute;left: 0px;top: 0px; display:none;'></div>";

        util::setTitle("Reporting");

        $content .= "<p><a href='". makeUrl(array('format' => 'json')) . "'>Download as JSON</a></p>";

	/* Manage saved reports */
	$hidden = array('controller'=>'report','task'=>'saveReport', 'current_query' => makeUrl($_GET));
	$form = "";
	$form .= "<div class='report_manager'>";
        $form .= " <table>";
        $form .= "  <tr><th>Saved reports</th><th></th></tr>";
        $idx = 0;
        foreach(Report::fetch() as $report_manager) {
            $form .= " <tr>";
            if($report_manager->id !== null)
                $hidden["report_manager[$idx][id]"] = $report_manager->id;
            $form .= "  <td><a href='{$report_manager->query}'>{$report_manager->name}</td>";
            $form .= "  <td><button type='submit' name='report_manager[{$idx}][remove]' value='1'>Remove</button></td>";
            $form .= " </tr>";
            $idx++;
        }
	$form .= "  <tr>";
	$hidden["report_manager[$idx][query]"] = makeUrl($_GET);
	$form .= "   <td>".form::makeText("report_manager[{$idx}][name]", "", null, null)."</td>";
	$form .= "   <td><button type='submit' name='report_manager[{$idx}][add]' value='1'>Save</button></td>";
	$form .= "  </tr>";
        $form .= " </table>";
	$form .= "</div>";
	$content .= form::makeForm($form, $hidden);


	/* Manage this report */
        $form = "";
	$hidden = array('controller' => 'report', 'reports' => $reports);

	$form .= "<p>";
        $form .= " Start: " . makeDateSelector("start", formatDate($date_begin), null, null, array('onchange'=>'submit();'));
        $form .= " End: " . makeDateSelector("end", formatDate($date_end), null, null, array('onchange'=>'submit();'));
	$form .= "</p>";

	$params = array_merge($_GET);
	$params['reports'] = $reports + 1;
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
	      'mark_types' => 'both'
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
		if (!isset($params['report'][$report-1])) $params['report'][$report-1] = array();

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
	    $params['reports'] = $reports - 1;
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
		$form .= "<a href='" . makeUrl($params) . "' />{$this->hour_list_columns[$item]}</a><br>";
	    }
	    $form .= "</td>";

	    $form .= "</tr></table>";

	    $form .= "Show as " . form::makeSelect("report[{$report}][type]", array('graph' => 'Graph', 'list' => 'Hour list', 'sum' => 'Hour summary'), $report_data['type'], null, array('onchange'=>'submit();'));

	    $form .= " Mark types: " . form::makeSelect("report[{$report}][mark_types]", array('both' => 'Both', 'tags' => 'Tags', 'classes' => 'Project classes'), $report_data['mark_types'], null, array('onchange'=>'submit();'));

	    $form .= "</div>";
	}
        $content .= form::makeForm($form, $hidden, 'get');
	$content .= "<div class='report_form_end'></div>";

        foreach($report_datas as $report) {

	    $content .= "<div class='{$report['report_data']['cls']} report_item'>";
	    if ($report_data['title'] != "") {
	        $content .= "<h1>{$report['report_data']['title']}</h1>";
	    }

	    if ($report['report_data']['type'] == 'graph') {
	        $params = array_merge($report['report_data'], array('controller'=>'graph', 'width' => '1024', 'height' => '480', 'start' => param('start'), 'end' => param('end')));
	        $params['order'] = implode(',', $params['order']);
		$content .= "<img src='" . makeUrl($params) . "' />";
	    }

	    if ($report['report_data']['type'] == 'list') {
	        $columns = array_merge($this->hour_list_columns);
                $ordered_columns = array();
		foreach ($report['report_data']['order'] as $col) {
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

		foreach ($report['hours_by_date'] as $hours) {
		    foreach ($hours as $hour) {
			$content .= " <tr>";
			$first = true;
			foreach($columns as $col => $col_desc) {
			    $tag = $first ? 'th' : 'td';
			    $value = $hour[$col];
			    if ($col == 'tag_names') {
			        $color = util::colorToHex($hour['color_r'], $hour['color_g'], $hour['color_b']);
			        $content .= "<{$tag} style='background: {$color}; color: {$color}'>#</{$tag}>";
			    }
			    $content .= "<{$tag} class='column_{$col}'>{$value}</{$tag}>";
			    $first = false;
			}
			$content .= " </tr>";
		    }
		}
		$content .= "</table>";
	    }

	    if ($report['report_data']['type'] == 'sum') {
		$content .= "<table class='report_timetable'>";
		$content .= " <tr>";
		$content .= "  <th>{$report['title1']}</th>";
		if ($report['col1'] == 'tag_names') {
		    $content .= "  <th></th>";
		}
		foreach ($report['col2values'] as $col2value) {
		    $content .= "<th>{$col2value}</th>";
		}
		$content .= "  <th>Total</th>";
		$content .= " </tr>";
		if ($report['col2'] == 'tag_names') {
		    $content .= " <tr>";
		    $content .= "  <th></th>";
		    foreach ($report['col2values'] as $col2value) {
		        $color = $report['idx_to_color'][$report['tag_names_to_idx'][$col2value]];
			$color = util::colorToHex($color[0], $color[1], $color[2]);
			$content .= "<td style='background: {$color}; color: {$color}'>#</td>";
		    }
		    $content .= "  <td></td>";
		    $content .= " </tr>";
		}

		foreach ($report['sums'] as $col1value => $col2_sums) {
		    if ($col1value != 'total') {
			$content .= "<tr><th class='column_{$report['col1']}'>{$col1value}</th>";
			if ($report['col1'] == 'tag_names') {
			    $color = $report['idx_to_color'][$report['tag_names_to_idx'][$col1value]];
			    $color = util::colorToHex($color[0], $color[1], $color[2]);
			    $content .= "<td style='background: {$color}; color: {$color}'>#</td>";
			}
			foreach ($report['col2values'] as $col2value) {
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
    

    function saveReportRun()
    {
        foreach(param('report_manager') as $report_manager){
            if (isset($report_manager['remove']) && $report_manager['remove']==1) {
                Report::delete($report_manager['id']);
            } else if(isset($report_manager['add'])) {
		unset($report_manager['add']);
		$r = new Report();
		//message("Input is " . sprint_r($report_manager));
		$r->initFromArray($report_manager);
		// message("Report_Manager is " . sprint_r($r));
		$r->save();
            }
        }
        util::redirect($_POST['current_query']);
    }    
}


?>