<?php

  /** The php backend for the main editing stuff. Mostly just creates a bunch of json data. All
   the exciting stuff is JavaScript. Which is scary.
   */

require_once("util.php");

function makePrefixedUrl($prefix, $arr)
{
    /* Convert "foo[bar][fie]" to array("foo", "bar", "fie) */
    if (strpos($prefix, "[") === false) {
        $prefix = array($prefix);
    } else {
        $prefix = explode("[", $prefix, 2);
        $base = $prefix[0];
        $prefix = array_merge(array($base), explode("][", substr($prefix[1], 0, -1)));
    }

    $toset = array_merge($_GET);
    $current = &$toset;
    for ($i = 0; $i < count($prefix) - 1; $i ++) {
        $item = $prefix[$i];
        if (!isset($current[$item])) {
            $current[$item] = array();
        }
        $current = &$current[$item];
    }
    $current[$prefix[count($prefix)-1]] = $arr;

    return makeUrl($toset);
}

class ReportController
extends Controller
{
    private $hour_list_columns = array('perform_date' => 'Date', 'hours' => 'Hours', 'user_fullname' => 'User', 'project' => 'Project', 'tag_names' => 'Marks', 'description' => 'Description');

    function makeReportDataElement($date_begin, $date_end, $report_definition) {
        //echo "<pre>"; print_r($report_definition); echo "</pre>";
        $report_data = array_merge(array(
          'title' => '',
          'cls' => '',
          'type' => 'graph',
          'order' => 'perform_date,user_fullname,project,tag_names',
          'user_fullname' => array(),
          'tag_names' => array(),
          'project' => array(),
          'mark_types' => 'both'
        ), $report_definition);
        $report_data['order'] = explode(',', $report_data['order']);

        $filter = array_merge($report_data);
        $filter['date_begin'] = formatDate($date_begin);
        $filter['date_end'] = formatDate($date_end);

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

        /* Handle groups */
        $groups = array();
        if ($report_data['type'] == 'group') {            
            foreach ($sums as $group => $sumdata) {
                if ($group == 'total') continue;

                if (!isset($report_data['items'])) $report_data['items'] = array('reports' => 1);
                if (!isset($report_data['items']['reports'])) $report_data['items']['reports'] = 1;
                $definitions = array('reports' => $report_data['items']['reports']);
                for ($index = 0; $index < $report_data['items']['reports']; $index++) {
                    $item = array_merge(isset($report_data['items'][$index]) ? $report_data['items'][$index] : array());
                    $item[$col1] = array($group);
                    $definitions[$index] = $item;
                }
                $groups[$group] = self::makeReportData($date_begin, $date_end, $definitions);
            }
        }

        return array(
            'report_data' => $report_data,
            'idx_to_color' => $idx_to_color,
            'tag_names_to_idx' => $tag_names_to_idx,
            'hours_by_date' => $hours_by_date,
            'sums' => $sums,
            'items' => $groups,
            'col1' => $col1,
            'col2' => $col2, 
            'title1' => $title1,
            'title2' => $title2,
            'col1values' => $col1values,
            'col2values' => $col2values,
            );

    }

    function makeReportData ($date_begin, $date_end, $report_definitions) {
        if (!isset($report_definitions)) $report_definitions = array();
	$report_definitions['reports'] = isset($report_definitions['reports']) ? intval($report_definitions['reports']) : 1;

        $res = array();
	for ($index = 0; $index < $report_definitions['reports']; $index++) {
            $report_definition = isset($report_definitions[$index]) ? $report_definitions[$index] : array();
            $res[] = self::makeReportDataElement($date_begin, $date_end, $report_definition);
        }
        return $res;
    }



    function reportViewerItem($report_data)
    {
        $content = '';
        $content .= "<div class='{$report_data['report_data']['cls']} report_item'>";
        if ($report_data['report_data']['title'] != "") {
            $content .= "<h1>{$report_data['report_data']['title']}</h1>";
        }

        if ($report_data['report_data']['type'] == 'graph') {
            $params = array_merge($report_data['report_data'], array('controller'=>'graph', 'width' => '1024', 'height' => '480', 'start' => param('start'), 'end' => param('end')));
            $params['order'] = implode(',', $params['order']);
            $content .= "<img src='" . makeUrl($params) . "' />";
        } else if ($report_data['report_data']['type'] == 'list') {
            $columns = array_merge($this->hour_list_columns);
            $ordered_columns = array();
            foreach ($report_data['report_data']['order'] as $col) {
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

            foreach ($report_data['hours_by_date'] as $hours) {
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
        } else if ($report_data['report_data']['type'] == 'sum') {
            $content .= "<table class='report_timetable'>";
            $content .= " <tr>";
            $content .= "  <th>{$report_data['title1']}</th>";
            if ($report_data['col1'] == 'tag_names') {
                $content .= "  <th></th>";
            }
            foreach ($report_data['col2values'] as $col2value) {
                $content .= "<th>{$col2value}</th>";
            }
            $content .= "  <th>Total</th>";
            $content .= " </tr>";
            if ($report_data['col2'] == 'tag_names') {
                $content .= " <tr>";
                $content .= "  <th></th>";
                foreach ($report_data['col2values'] as $col2value) {
                    $color = $report_data['idx_to_color'][$report_data['tag_names_to_idx'][$col2value]];
                    $color = util::colorToHex($color[0], $color[1], $color[2]);
                    $content .= "<td style='background: {$color}; color: {$color}'>#</td>";
                }
                $content .= "  <td></td>";
                $content .= " </tr>";
            }

            foreach ($report_data['sums'] as $col1value => $col2_sums) {
                if ($col1value != 'total') {
                    $content .= "<tr><th class='column_{$report_data['col1']}'>{$col1value}</th>";
                    if ($report_data['col1'] == 'tag_names') {
                        $color = $report_data['idx_to_color'][$report_data['tag_names_to_idx'][$col1value]];
                        $color = util::colorToHex($color[0], $color[1], $color[2]);
                        $content .= "<td style='background: {$color}; color: {$color}'>#</td>";
                    }
                    foreach ($report_data['col2values'] as $col2value) {
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
        } else if ($report_data['report_data']['type'] == 'sumtotal') {
            $content .= "<table class='report_timetable'>";
            $content .= " <tr>";
            $content .= "  <th>{$report_data['title1']}</th>";
            if ($report_data['col1'] == 'tag_names') {
                $content .= "  <th></th>";
            }
            $content .= "  <th>Total</th>";
            $content .= " </tr>";

            foreach ($report_data['sums'] as $col1value => $col2_sums) {
                if ($col1value != 'total') {
                    $content .= "<tr><th class='column_{$report_data['col1']}'>{$col1value}</th>";
                    if ($report_data['col1'] == 'tag_names') {
                        $color = $report_data['idx_to_color'][$report_data['tag_names_to_idx'][$col1value]];
                        $color = util::colorToHex($color[0], $color[1], $color[2]);
                        $content .= "<td style='background: {$color}; color: {$color}'>#</td>";
                    }
                    $content .= "<td>{$col2_sums['total']}</td>";
                    $content .= " </tr>";
                }
            }
            $content .= "</table>";
        } else if ($report_data['report_data']['type'] == 'group') {
            foreach($report_data['items'] as $title => $group) {
                $content .= "<div class='report_group'>";
                $content .= "<h1>{$title}</h1>";
                foreach ($group as $item) {
                    $content .= self::reportViewerItem($item);
                }
                $content .= "</div>";
            }
        }
        $content .= "</div>";
        return $content;
    }

    function reportViewer($report_datas)
    {
        $content = '';
        foreach($report_datas as $report_data) {
            $content .= self::reportViewerItem($report_data);
	}
        return $content;
    }

    function reportDesignerElement($prefix, $report_data, $data) {
        $report_data = array_merge(array(
          'title' => '',
          'cls' => '',
          'type' => 'graph',
          'order' => 'perform_date,user_fullname,project,tag_names',
          'user_fullname' => array(),
          'tag_names' => array(),
          'project' => array(),
          'mark_types' => 'both'
        ), $report_data);

        $header = "Show as " . form::makeSelect("{$prefix}[type]", array('graph' => 'Graph', 'list' => 'Hour list', 'sum' => 'Hour summary', 'sumtotal' => 'Total hour summary', 'group' => 'Group of reports'), $report_data['type'], null, array('onchange'=>'submit();'));

        $form = makeHidden("{$prefix}[order]", $report_data['order']);
        $report_data['order'] = explode(',', $report_data['order']);

        $form .= "Title: " . form::makeText("{$prefix}[title]", $report_data['title'], null, null, array('onchange'=>'submit();'));
        $form .= "Class: " . form::makeText("{$prefix}[cls]", $report_data['cls'], null, null, array('onchange'=>'submit();'));
        $form .= "<table>
                   <tr><th>Users</th><th>Tags</th><th>Projects</th><th>Sort order</th></tr>
                   <tr>";

        $form .= "<td>" . form::makeSelect("{$prefix}[user_fullname]", form::makeSelectList($data['all_users'], 'fullname', 'fullname'), $report_data['user_fullname'], null, array('onchange'=>'submit();')) . "</td>";
        $form .= "<td>" . form::makeSelect("{$prefix}[tag_names]", form::makeSelectList($data['all_tags'], 'name', 'name'), $report_data['tag_names'], null, array('onchange'=>'submit();')) . "</td>";
        $form .= "<td>" . form::makeSelect("{$prefix}[project]", form::makeSelectList($data['all_projects'], 'name', 'name'), $report_data['project'], null, array('onchange'=>'submit();')) . "</td>";

        $form .= '<td>';
        foreach ($report_data['order'] as $item) {
            $new_order = array_merge(array($item), array_diff($report_data['order'], array($item)));
            $params = array_merge($report_data);
            $params['order'] = implode(',',$new_order);
            $form .= "<a href='" . makePrefixedUrl($prefix, $params) . "' />{$this->hour_list_columns[$item]}</a><br>";
        }
        $form .= "</td>";

        $form .= "</tr></table>";

        $form .= "<div>Mark types: " . form::makeSelect("{$prefix}[mark_types]", array('both' => 'Both', 'tag_names' => 'Tags', 'classes' => 'Project classes'), $report_data['mark_types'], null, array('onchange'=>'submit();')) . "</div>";

        if ($report_data['type'] == 'group') {
            $items = isset($report_data['items']) ? $report_data['items'] : array();
            $form .= "<br>" . self::reportDesignerItems("{$prefix}[items]", $items, $data);
        }

        return array("header" => $header, "body" => $form);
    }

    function reportDesignerItem($prefix, $index, $report_datas, $data) {
        if (!isset($report_datas[$index])) $report_datas[$index] = array();

        $content = self::reportDesignerElement("{$prefix}[{$index}]", $report_datas[$index], $data);

        $form = "";
        $form .= "<div class='report_form_part'>";
        $form .= " <div class='report_form_part_header'>";
        $form .= "   <div class='report_form_part_header_left'>";
        $form .= $content['header'];
        $form .= "   </div>";
        $form .= "   <div class='report_form_part_header_right'>";

        /* Shift left */
        if ($index > 0) {
            if (!isset($report_datas[$index-1])) $report_datas[$index-1] = array();

            $params = array_merge($report_datas);

            $temp = $params[$index];
            $params[$index] = $params[$index-1];
            $params[$index-1] = $temp;

            $form .= "<a href='" . makePrefixedUrl($prefix, $params) . "' />&lt;&lt;</a> ";
        }

        /* Remove-link */
        $params = array_merge($report_datas);
        $reports = $params['reports'] - 1;
        unset($params[$index]);
        unset($params['reports']);
        $params = array_values($params);
        $params['reports'] = $reports;
        $form .= "<a href='" . makePrefixedUrl($prefix, $params) . "' />X</a> ";

        /* Shift right */
        if ($index < $params['reports'] - 1) {
            if (!isset($report_datas[$index+1])) $report_datas[$index+1] = array();

            $params = array_merge($report_datas);

            $temp = $params[$index];
            $params[$index] = $params[$index+1];
            $params[$index+1] = $temp;

            $form .= "<a href='" . makePrefixedUrl($prefix, $params) . "' />&gt;&gt;</a>";
        }
        $form .= "   </div>";
        $form .= " </div>";
        $form .= $content['body'];
        $form .= " </div>";

        return $form;
    }

    function reportDesignerItems($prefix, $report_datas, $data) {
        if (!isset($report_datas)) $report_datas = array();
	$report_datas['reports'] = isset($report_datas['reports']) ? intval($report_datas['reports']) : 1;

        $form = '';

        $form .= makeHidden("{$prefix}[reports]", $report_datas['reports']);

	$params = array_merge($report_datas);
	$params['reports'] = $params['reports'] + 1;
	$form .= "<a href='" . makePrefixedUrl($prefix, $params) . "' />Add another report item</a><br>";

	for ($index = 0; $index < $report_datas['reports']; $index++) {
            $form .= self::reportDesignerItem($prefix, $index, $report_datas, $data);
	}
        return $form;
    }

    function reportDesigner($date_begin, $date_end)
    {
	/* Manage this report */
        $form = "";
	$hidden = array('controller' => 'report');

	$form .= "<p>";
        $form .= " Start: " . makeDateSelector("start", formatDate($date_begin), null, null, array('onchange'=>'submit();'));
        $form .= " End: " . makeDateSelector("end", formatDate($date_end), null, null, array('onchange'=>'submit();'));
	$form .= "</p>";

        $data = array(
            'all_users' => User::getAllUsers(),
	    'all_tags' => Tag::fetch(),
	    'all_projects' => Project::getProjects());

        $report_datas = isset($_GET['report']) ? $_GET['report'] : array();

        $form .= self::reportDesignerItems('report', $report_datas, $data);

        return form::makeForm($form, $hidden, 'get') . "<div class='report_form_end'></div>";
    }

    function savedReportManager()
    {
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
	return form::makeForm($form, $hidden);
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


    function viewRun()
    {
        if (empty($_GET['start'])) {
            $date_begin = today() - 14*24*3600;
	    $date_end = today();
        } else {
            $date_begin = parseDate($_GET['start']);
	    $date_end = parseDate($_GET['end']);
        }

        $report_datas = self::makeReportData($date_begin, $date_end, isset($_GET['report']) ? $_GET['report'] : array());

        if (!empty($_GET['format']) && $_GET['format'] == 'json') {
          header('Content-type: text/plain');
          echo json_encode($report_datas);
          exit(0);
        }


        $content = "";
        $content .= "<div id='debug' style='position:absolute;right: 100px;'></div>";
        $content .= "<div id='dnd_text' style='position:absolute;left: 0px;top: 0px; display:none;'></div>";

        util::setTitle("Reporting");

	$content .= self::savedReportManager();

        $content .= "<p><a href='". makeUrl(array('format' => 'json')) . "'>Download as JSON</a></p>";

        $content .= self::reportDesigner($date_begin, $date_end, isset($_GET['report']) ? $_GET['report'] : array());

        $content .= self::reportViewer($report_datas);

        $this->show(null, $content);

    }
}


?>