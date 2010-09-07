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



class DisplayHistogram extends Display
{

    /**
     * Display class displaying invoice.
     *
     * @param egs Egs.
     */
  function DisplayHistogram ($fc, $egs, $hours, $users, $params) 
    {
      $this->Display ($fc, $egs, $hours, $users, $params);
    }

    /**
     * Convert from seconds to hours. (Really complex math, here...
     */

    function ParseHours($h)
    {
        return (float)$h/3600;
    }

    /**
     * Render users.
     */
    function render()
    {
        global $params;
        global $config_color_billable;
        global $config_color_billable_overtime;
        global $config_color_nonbillable;
        global $config_color_nonbillable_overtime;

        $sortby = $params->sortby;
        $sortdirection = $params->sortdirection;
        $grouped_hours = $this->group_hours($this->hours, $sortby, $sortdirection);

        $h = new Graph('png');

        $h->setXAxisType(GRAPH_DATE, 'd-m');
        
        $types = $this->egs->get_hour_types();

        $used_types=array_fill(0, 2*count($types)+1, false);

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

        $col=array();
        $legend = array();

        foreach(array("Billable","Nonbillable") as $billid =>$bill2) {
	    $typeidx = 0;
            foreach($types as $id => $name) {
                $idx = $this->type2idx($billid,$id,-1);
                if (!isSet($lookup[$idx]))
                    continue;
                $idx = $lookup[$idx];
                $col[$idx] =  array($billid * (255 - ($typeidx * 2)),(1-$billid) * 255 - ($typeidx * 2),0); //config_get_color( $billid, $id );
                $legend[$idx] = $bill2 . " " . $name;
		$typeidx += 1;
            }
        }
        $wl_idx = $this->type2idx(0, 0, $this->egs->timesafe_work_leave_id);
        
        if (isSet($lookup[$wl_idx])) {
            $wl_idx = $lookup[$wl_idx];
            $col[$wl_idx] = array(63,63,63);
            $legend[$wl_idx] = "Work leave";
        }
        
        $col [] = array(127,127,127);

        /*
        echo "<pre>";

        var_dump($col);
        var_dump($legend);
        /**/
        $h->setParams(array("color_map"=>$col));

        $prev_date = false;

        $days=0;

        $tot_data = array();

        foreach ($grouped_hours[0] as $k => $i) {

            $bill = 0;
            $nonbill = 0;
            $bill_over = 0;
            $nonbill_over = 0;

            $date->month = substr($k, 5, 2);
            $date->day = substr($k, 8);
            $date->year = substr($k, 0, 4);
            $date->timestamp = mktime(0, 0, 0, $date->month, $date->day, $date->year);
            
            if ($prev_date) {
                while (true) {
                    $prev_date->day++;
                    $prev_date->timestamp = mktime(0, 0, 0, $prev_date->month, $prev_date->day, $prev_date->year);
                    if ($prev_date->timestamp < $date->timestamp) {
                        $h->addHistogramPoint($prev_date->timestamp, array(0));
                        $days++;
                        $tot_data[] = null;
                    } else {
                        break;
                    }
                }
            }

            $bars=array_fill(0, count($lookup), 0);

            foreach ($i as $j) {
                $hr = $this->parseHours($j['seconds']);
                $billid = ($j['billable']=='f'?1:0);
                $bars[$lookup[$this->type2idx($billid, $j['typeid'], $j['tagids'])]] += $hr;
            }

            /*
             * $k is the date in YYYY-MM-DD format, so 'substr ($k, 5)' is
             * the month and day. Hopefylly the year is redundant.
             */
            $h->addHistogramPoint($date->timestamp, $bars);
            $tot_data[] = sprintf("%.1f",array_sum($bars));
            
            $days++;
            $prev_date = (PHP_VERSION < 5) ? $date : clone($date);
        }

        if( $days > 0 ) {
            /*
             * Try to make histogram fit on 1024 display, but if need
             * be, make it arbitrarily long.
             */
            $max_width = 950;
            $width = 220 + 32*$days;
            if( $width > $max_width ) {
                $width = max($max_width, 420+16*$days);
            }

            $height = 256;
        } else {
            $width = 1;
            $height = 1;
        }
        
        if (count($tot_data)!=0) {
            
            $x = 0.5/count($tot_data);        
            $dx= 1.0/count($tot_data);

            foreach( $tot_data as $t ) {
                if ($t !== null) {
                    $h->addText(array('x'=>$x, 'y'=>1.0), $t, array('valign'=>'top'));
                }
                
                $x += $dx;
            }
        
        }
        

        $h->setParams(array('width' => $width,
                            'height' => $height));

        $h->setLegend($legend);


        $h->addPlot(array(8*count($this->users),8*count($this->users)));

        
        $h->write();

    }

};

?>
