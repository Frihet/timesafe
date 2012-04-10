<?php

require_once 'time_report/Graph.php';

class GraphController
extends Controller
{
    function formatDate($tm)
    {
        return date('Y-m-d', $tm);
    }

    function today()
    {
        $now = time();
        $year = date('Y', $now);
        $month = date('m', $now);
        $day = date('d', $now);
        return mktime(12, 0, 0, $month, $day, $year);
    }

    function parseDate($date)
    {
        list($year, $month, $day) = explode('-',$date);
        return mktime(12, 0, 0, $month, $day, $year);
    }

    function viewRun()
    {
        if (empty($_GET['start'])) {
            $date_begin = self::today() - 14*24*3600;
	    $date_end = self::today();
        } else {
            $date_begin = self::parseDate($_GET['start']);
	    $date_end = self::parseDate($_GET['end']);
        }

	$all = User::getAllUsers();
	$user_ids = array();
	foreach (param('users',array()) as $usr) {
	    $user_ids[] = $all[$usr]->id;
	}

    	$hour_list_order = isset($_GET['order']) ? explode(',', $_GET['order']) : array('perform_date','user_fullname','project','tag_names');
	
	$filter = array(
	 'date_begin' => self::formatDate($date_begin),
 	 'date_end' => self::formatDate($date_end),
	 'projects' => isset($_GET['projects']) ? $_GET['projects'] : array(),
	 'tags' => isset($_GET['tags']) ? $_GET['tags'] : array(),
	 'users' => $user_ids
	);
	$mark_types = isset($_GET['mark_types']) ? $_GET['mark_types'] : 'both';
        $colors = Entry::colors($filter, $hour_list_order, $mark_types);
	$hours_by_date = Entry::groupByColor($filter, $hour_list_order, $mark_types);

	$color_to_idx = array();
	$idx_to_color = array();
	$idx_to_col2 = array();
	$idx = 0;
	foreach ($colors as $color) {
	    $idx_to_color[$idx] = array($color['color_r'], $color['color_g'], $color['color_b']);
	    $idx_to_col2[$idx] = $color[$hour_list_order[1]];
	    $color_to_idx[util::colorToHex($color['color_r'], $color['color_g'], $color['color_b'])] = $idx;
	    $idx++;
	}

        $h = new Graph('png');

	if ($hour_list_order[0] == 'perform_date') {
          $h->setXAxisType(GRAPH_DATE, 'd-m');
        } else {
	  $h->setXAxisType(GRAPH_REGULAR, 'd-m');
        }
	$h->setParams(array("color_map"=>$idx_to_color));

	foreach ($hours_by_date as $date => $hours) {
	    $hour_lengths = array_fill(0, count($idx_to_color), false);
	    foreach ($hours as $hour) {
	        $idx = $color_to_idx[util::colorToHex($hour['color_r'], $hour['color_g'], $hour['color_b'])];
	        $hour_lengths[$idx] = $hour['hours'];
	    }
	    $h->addHistogramPoint($date, $hour_lengths);
	}

/*
	$datas = array("Hej", "Hopp");
	$x = 0.5/count($datas);
	$dx= 1.0/count($datas);
	foreach($datas as $data) {
	    $h->addText(array('x'=>$x, 'y'=>1.0), $data, array('valign'=>'top'));
	    $x += $dx;
	}
*/
	
	$h->setParams(array('width' => (int) param('width', 640),
                            'height' => (int) param('height', 240)));

        $h->setLegend($idx_to_col2);


//        $h->addPlot(array(8,9,10));

        $h->write();
	exit(0);
    }
}


?>