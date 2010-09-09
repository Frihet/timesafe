<?php

require_once 'time_report/Graph.php';

class GraphController
extends Controller
{
    function viewRun()
    {

        $date_end = date('Y-m-d',Entry::getBaseDate());
        $date_begin = date('Y-m-d',Entry::getBaseDate()-(Entry::getDateCount()-1)*3600*24);

	$all = User::getAllUsers();
	$user_ids = array();
	foreach (param('users',array(User::$user->name)) as $usr) {
	    $user_ids[] = $all[$usr]->id;
	}
	
        $colors = Entry::colors($date_begin, $date_end, $user_ids);
	$hours_by_date = Entry::groupByColor($date_begin, $date_end, $user_ids);
	
	$color_to_idx = array();
	$idx_to_color = array();
	$idx_to_tag_names = array();
	$idx = 0;
	foreach ($colors as $color) {
	    $idx_to_color[$idx] = array($color['color_r'], $color['color_g'], $color['color_b']);
	    $idx_to_tag_names[$idx] = $color['tag_names'];
	    $color_to_idx[$color['color_r'] * 256 * 256 + $color['color_g'] * 256 + $color['color_b']] = $idx;
	    $idx++;
	}

        $h = new Graph('png');

        $h->setXAxisType(GRAPH_DATE, 'd-m');
	$h->setParams(array("color_map"=>$idx_to_color));

	foreach ($hours_by_date as $date => $hours) {
	    $hour_lengths = array_fill(0, count($idx_to_color), false);
	    foreach ($hours as $hour) {
	        $idx = $color_to_idx[$hour['color_r'] * 256 * 256 + $hour['color_g'] * 256 + $hour['color_b']];
	        $hour_lengths[$idx] = $hour['minutes'] / 60.0;
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
	
	$h->setParams(array('width' => 640,
                            'height' => 240));

        $h->setLegend($idx_to_tag_names);


//        $h->addPlot(array(8,9,10));

        $h->write();
	exit(0);
    }
}


?>