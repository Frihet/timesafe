<?php
  /******************************************************************************
   *
   * Copyright © 2007
   *
   * FreeCode Norway AS
   * Slemdalsveien 70, NO-0370 Oslo, Norway
   * 0555 Oslo-N
   * Norway
   *
   * This program is free software; you can redistribute it and/or modify it
   * under the terms of the GNU General Public License as published by the Free
   * Software Foundation; either version 2 of the License, or (at your option)
   * any later version.
   *
   * This program is distributed in the hope that it will be useful, but WITHOUT
   * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
   * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
   * more details.
   *
   * You should have received a copy of the GNU General Public License along with
   * this program; if not, write to the Free Software Foundation, Inc., 51
   * Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
   *
   ******************************************************************************/

require_once 'Util.php';
require_once 'Display.php';
require_once 'Graph.php';
require_once 'config.php';

function noop ($errno, $errstr, $errfile, $errline)
{
    return true;
}

set_error_handler("noop");


function my_div($a, $b)
{
    $res = array();
    for ($i=0; $i<count($a); $i++) {
        if (isSet($b[$i]) && $b[$i])
            $res[] = $a[$i]/$b[$i];            
        else 
            $res[] = 0;
        
    }
    return $res;
}

function array_mul($a, $b)
{
    $res = array();
    if (is_array($b)) {
        for ($i=0; $i<count($a); $i++) {
            if (isSet($b[$i]))
                $res[] = $a[$i]*$b[$i];            
            else 
                $res[] = 0;
        
        }
    } else {
        for ($i=0; $i<count($a); $i++) {
            $res[] = $a[$i]*$b;
        }
    }
    
    return $res;    
}


class DisplayVSDGraph extends Display 
{

    var $x_value;
    var $y_value;
    

    /**
     * Display class displaying invoice.
     *
     * @param egs Egs.
     */
    function DisplayVSDGraph ($fc, $egs, &$hours, $users, $params) 
    {
        $this->Display ($fc, $egs, $hours, $users, $params);
        $this->x_value = $params->x;
        $this->y_value = $params->y;        
    }


    /**
     * Convert from seconds to hours. (Really complex math, here...
     */

    function ParseHours($h) 
    {
        return (float)$h/3600;
    }

    function makeAcronym($str)
    {
        $res = $str[0];
        $prev_space = false;
        $prev_uc = false;
        
        for ($i=0; $i<strlen($str); $i++) {
            if ($str[$i]==' ') {
                $prev_space = true;
            } else {
                $uc = ord($str[$i]) >= 128;
                
                if ($prev_space || ($prev_uc && $uc)) {
                    $res .= $str[$i];
                    //                    echo ord($str[$i]). "<br>";
                    $prev_uc = $uc;
                    
                } else {
                    $prev_uc = false;
                }
                $prev_space = false;
            }
        }
        
        return $res;
    }
    

    /**
     * Render users.
     */
    function render() 
    {
        global $params;


        $sortby = $params->sortby;
        $sortdirection = $params->sortdirection;
        $grouped_hours = $this->group_hours($this->hours, "fullname", "desc");


        $person = array ();
        
        foreach ($grouped_hours[0] as $k => $i) {
            
            foreach ($i as $j) {
                
                if ($j['billable'] != 'f') {
                    if (!isset($person[$j['fullname']])) {
                        $person[$j['fullname']] = count($person);
                    }
                }
            }
        }
        
        if (!count($person))
            return;


        $g = new Graph('png');

        if ($this->y_value == 'vsd') {
            
            $this->render_vsd_time($g, $grouped_hours, $person);
            
        } else if ($this->y_value == 'time') {
            
            $this->render_time_person($g, $grouped_hours, $person);
                
        } else if ($this->y_value == 'billable') {

            $this->render_billable_time($g, $grouped_hours, $person, isSet($params->options) && in_array ("collapse", $params->options) );

        }

        $g->setParams(array('width' => 700, 
                            'height' => 400));
        
        $g->write();

    }

    function render_vsd_time (&$g, &$grouped_hours, $person)
    {
        
        global $config_color_billable;
        global $config_color_billable_overtime;
        global $config_color_nonbillable;
        global $config_color_nonbillable_overtime;        
        global $config_default_billing_rate;

        //        var_dump($grouped_hours);
        
        
        /*
         This array is used to keep track of who has been working every month
        */
        $month_person = array();
                

        for ($i=0; $i<12; $i++) {
            $month[$i] = array_fill(0, count($person), 0);
        }
                
        foreach ($grouped_hours[0] as $k => $i) {
                
            foreach ($i as $j) {
                    
                if ($j['billable'] != 'f') {
                    
                    $hr = $j['hours'];
                    $m = $j['month']-1;
                    
                    $pid = $person[$j['fullname']];
                                        
                    $pers = $month[$m];
                    $pers[$pid] += $hr *$this->egs->get_billing_rate($j['username'],$j['projectid']);
                    $month[$m] = $pers;



                    $target = $this->user_data->get_target_billable($j['username'])/100;
                    $month_person[$m][$person[$j['fullname']]]=$target;
                    

                }
            }
        }
         
        $year = $j['year'];
        
        $data_target=array();
        
        for ($i=0; $i<12; $i++) {
            $count = count($person);
            $data_taget[$i] = array();
            for ($j=0; $j<$count; $j++) {
                
                $out_idx = 0;
                
                if (isSet($month_person[$i][$j])) {
                    
                    if (isSet($data_target[$out_idx][$i]))
                        $data_target[$out_idx][$i] += config_get_hours( $year, $i+1, 1, $year, $i+2, 0, false) * $month_person[$i][$j];
                    else
                        $data_target[$out_idx][$i] = config_get_hours( $year, $i+1, 1, $year, $i+2, 0, false) * $month_person[$i][$j];
                } else {
                    $data_target[$j][$i] = 0;
                }
            }
            
        }
        
        $val = array_mul($data_target[0], $config_default_billing_rate);
        $x=array();
        $y=array();
        $pos = 0;
        
        foreach( $val as $v ) {
            $x[] = $pos;
            $pos++;
            $x[] = $pos;
            $y[] = $v;
            $y[] = $v;
        }
        $g->addPlot( $x, $y );
        
        $month_name = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
        
        for ($i=0; $i<12; $i++) {
            $g->addHistogramPoint($month_name[$i], $month[$i]);
        }

        $legend = array_flip($person);        
        array_unshift($legend, "80 % billing");
        
        $g->setLegend($legend);
        
        $g->setYUnit('kr');
                
    }


    function render_time_person( &$g, &$grouped_hours, $person)
    {
        
        global $config_color_billable;
        global $config_color_billable_overtime;
        global $config_color_nonbillable;
        global $config_color_nonbillable_overtime;        

        $group_arr = $person;
        $group_label = 'fullname';
                
        $types = $this->egs->get_hour_types();

        $used_types=array_fill(0, 2*count($types), false);
        
        foreach ($grouped_hours[0] as $k => $i) {
            foreach ($i as $j) {
                $billid = ($j['billable']=='f'?1:0);
                $id = $j['typeid'];
                $used_types[$this->type2idx($billid,$id,$j['tagids'])] = true;
            }
        }

        

        $lookup=array();
        $lookup_pos=0;
        
        foreach ($used_types as $key => $value) {
            if ($value) {
                $lookup[$key] = $lookup_pos++;
            }
        }
        
        foreach(array("Billable","Nonbillable") as $billid =>$bill2) {
            foreach($types as $id => $name) {
                $idx = $this->type2idx($billid,$id,-1);
                if (!isSet($lookup[$idx]))
                    continue;
                $idx = $lookup[$idx];
                
                $col[$idx] = config_get_color( $billid, $id );
                $legend[$idx] = $bill2 . " " . $name;
            }
        }
        /*        
        $wl_idx = $this->type2idx(0, 0, config_get_work_leave_tagids());
        
        if (isSet($lookup[$wl_idx])) {
            $wl_idx = $lookup[$wl_idx];
            $col[$wl_idx] = array(63,63,63);
            $legend[$wl_idx] = "Work leave";
        }
        */


        $legend[] = 'Target billable hours';
        $legend[] = 'Target hours';
        
        $col[] = array(192,96,32);
        $col[] = array(0,128,64);
                
        $g->setParams(array("color_map" => $col) );
        
        for ($i=0; $i<count($group_arr); $i++) {
            $data[$i] = array_fill(0, count($col)-2, 0);
        }
        
        $target=array();
        
        
        foreach ($grouped_hours[0] as $k => $i) {
	    
            foreach ($i as $j) {
                
                if (isSet($group_arr[$j['fullname']])) {
                    
                    
                    if($this->egs->is_work_leave($j['tagids'])) {
			continue;
                    }
                    

                    $billid = ($j['billable']=='f'?1:0);

                    $idx = $lookup[$this->type2idx($billid, $j['typeid'], $j['tagids'])];
                                        
                    $hr = $j['hours'];
                    
                    $pid = $group_arr[$j['fullname']];
                    
                    $pers = $data[$pid];
                    $pers[$idx] += $hr;
                    $data[$pid] = $pers;
                    
                    $target[$group_arr[$j['fullname']]] = $this->user_data->get_target_billable($j['username'])/100;

                }
                    
            }
	}
            
	$group_arr_flip =array_flip($group_arr);
                
	for ($i=0; $i<count($data); $i++) {
	    $g->addHistogramPoint($this->makeAcronym($group_arr_flip[$i]), $data[$i]);     
	}

        $from = explode('-',$this->date_from);
        $to = explode('-',$this->date_to);
                        
        $regular_hours = config_get_hours($from[0],$from[1],$from[2], $to[0],$to[1],$to[2]);

        $g->addPlot(array(-0.5, count($target)-0.5), array($regular_hours,$regular_hours ));
        
        $x=array();
        $y=array();
        
        
        for ($i=0; $i < count($target); $i++) {
            
            $x[] = $i-0.5;
            $x[] = $i+0.5;
            
            $y[] = $target[$i]*$regular_hours;
            $y[] = $target[$i]*$regular_hours;
        }
        $g->addPlot($x, $y);
            
        $g->setLegend($legend);
        
        $g->setYUnit('h');
                
    }
    

    function render_billable_time ( &$g, &$grouped_hours, $person, $collapse)
    {

        if ($collapse) {
            $data_size = 1;
        } else {
            $data_size = count($person);
        }

        for ($i=0; $i<$data_size; $i++) {
            $data[$i] = array_fill(0, 12, 0);
        }

        /*
         This array is used to keep track of who has been working every month
        */
        $month_person = array();
                
        foreach ($grouped_hours[0] as $k => $i) {
                
            foreach ($i as $j) {
                    
                if ($j['billable'] != 'f') {
                        
                    $hr = $j['hours'];
                    $m = $j['month']-1;
                    
                    if ($collapse ) {
                        $pid = 0;
                    }
                    else {
                        $pid = $person[$j['fullname']];
                    }
                    
                    $month = $data[$pid];
                    $month[$m] += $hr;
                    $data[$pid] = $month;
        
                    $target = $this->user_data->get_target_billable($j['username'])/100;

                    $month_person[$m][$person[$j['fullname']]]=$target;
                    
                }
            }
        }

        $year = $j['year'];
        
        $data_target=array();
        
        for ($i=0; $i<12; $i++) {
            $count = count($person);
            $data_taget[$i] = array();
            for ($j=0; $j<$count; $j++) {
                
                $out_idx = $collapse?0:$j;
                
                if (isSet($month_person[$i][$j])) {
                    
                    if (isSet($data_target[$out_idx][$i]))
                        $data_target[$out_idx][$i] += config_get_hours( $year, $i+1, 1, $year, $i+2, 0, false) * $month_person[$i][$j];
                    else
                        $data_target[$out_idx][$i] = config_get_hours( $year, $i+1, 1, $year, $i+2, 0, false) * $month_person[$i][$j];
                } else {
                    $data_target[$j][$i] = 0;
                }
            }
            
        }
        
        $month_name = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

        /*        echo "<pre>";
        
        var_dump($data);
        var_dump($data_target);
        echo "</pre>";
        */
        
        $g->setXTicks(range(0, 11), $month_name);
        for ($i=0; $i<count($data); $i++) {
            /*            for ($i=0; $i<12; $i++) {
                
            }
            */
            $g->addPlot( range(0, 11), array_mul(my_div($data[$i],$data_target[$i]),100*0.8), array('cross'=>true));
        }

        $g->addPlot( array(0,11), array(80,80));

                
    if ($collapse) {
        $legend = array('Actual billing');
    } else {
        $legend = array_flip($person);
    }

    $legend[] = "Target";
    
        
        $g->setLegend($legend);
        $g->setYUnit('%');

    }
    
};

?>
