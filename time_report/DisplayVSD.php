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

/*


TODO:

skapa dropdown i user-rapporten før att ændra vilken person man visar rapport før



DONE:

8-timmars-streck i user-histogram

Fixa VSD-y-axel skall vara kronor

Fixa VSD-y-axel skall vara timmer igen

Ny vsd-graf vsd før denna månad

Alla vsd per peron skall separera billable/nonbillable samt øvertid

vsd-per-person skall visa initialer længs x-axeln

man skall kunna fylla i en lista på alla helgdagar i config-filen

Nya vsd-grafer faktureringsgrad

vsd-grafer skall visa mål och møjlig nivå

visa beløning i grønt! Algoritm: om viktat faktureringsgrad mot standard > (100- count(kunder)*5)

antal kunder ræknas bara de med mer æn t.ex. 16 timmar eller så...

*/

require_once 'Util.php';
require_once 'Display.php';
require_once 'config.php';

class DisplayVSD extends Display 
{
  
    /**
     * Render users.
     */
    function render () 
    {
        global $params;

        $grouped_hours = $this->group_hours ($this->hours, "fullname", "desc");

        $persons = array ();
        
        $from = explode('-',$this->date_from);
        $to = explode('-',$this->date_to);
        
        $regular_hours = config_get_hours($from[0],$from[1],$from[2], $to[0],$to[1],$to[2]);
        
        $time_total = 0;
        $billable_time_total = 0;
        $time_target_total=0;

        foreach ($grouped_hours[0] as $user) {
            $tmp = $user[0];
            $p['name'] = $tmp['fullname'];
            $p['jobtitle'] = $tmp['jobtitle'];
            $p['time'] = 0;
            $p['billable_time'] = 0;

            $project_count = array();
            
            $target = $this->user_data->get_target_billable($tmp['username'])/100;

            if ($target==0) {
                
                continue;
            }
            
            foreach ($user as $work) {
                $p['time'] += $work['seconds'];

                if ($work['billable'] != 'f') {
                    $p['billable_time'] += $work['seconds'];
                    
                    if(!isSet($project_count[$work['project_name']])){
                        $project_count[$work['project_name']]=0;
                    }
                    
                    $project_count[$work['project_name']]+=$work['seconds']/3600;

                }
            }
            
            $time_total += $p['time'];
            $billable_time_total += $p['billable_time'];
            
            $p['billable_percentage_total'] = round (100.0*$p['billable_time']/$p['time']);
            $p['billable_percentage_regular'] = round (100.0*$p['billable_time']/($regular_hours*3600));
            
            $time_target_total += $target *($regular_hours*3600);

            if ($target == 0)
                $p['billable_percentage_target'] = "-";
            else
                $p['billable_percentage_target'] = round (0.8*100.0*$p['billable_time']/($regular_hours*3600)/$target);
            

            $p_count = 0;
            foreach ($project_count as $proj) {
                if($proj > 20) {
                    $p_count++;
                }
            }

            $class = "";
            
            if ($p['billable_percentage_target'] > (100-5*$p_count)) {
                $class = "class='good'";
            } else if ($p['billable_percentage_target'] < 80) {
                $class = "class='bad'";
            }
            $p['billable_percentage_target'] = "<em $class title='Worked on $p_count projects'>{$p['billable_percentage_target']}</em>";
            
            $p['time'] = Util::seconds_to_string($p['time']);
            $p['billable_time'] = Util::seconds_to_string($p['billable_time']);
            
            $persons[] = $p;
            
	}
        /*        
        echo "<pre>";
        var_dump ($persons);
        echo "</pre>";
        */

        if ($time_total) {
            $billable_percentage_total_avg = round (100.0*$billable_time_total /  $time_total);
        } else {
            $billable_percentage_total_avg = 0;
        }

        if ($time_target_total) {
            $billable_percentage_target_avg = round (0.8*100.0*$billable_time_total / $time_target_total);
        } else {
            $billable_percentage_target_avg = "0.0";
        }

        $time_total = Util::seconds_to_string($time_total);
        $billable_time_total = Util::seconds_to_string($billable_time_total);

        $current = strtotime ($this->date_from);
        $current_year = date ("Y", $current);
        $current_month = date ("m", $current);
      
        $prev = Util::create_url ($this->mode, 
                                  date ('Y-m-d', mktime (0, 0, 0, $current_month-1, 1, $current_year)),
                                  date ('Y-m-d', mktime (0, 0, 0, $current_month, 0, $current_year)),
                                  $params->users,
                                  $params->projectids );
      
        $next = Util::create_url ($this->mode,
                                  date ('Y-m-d', mktime (0, 0, 0, $current_month+1, 1, $current_year)),
                                  date ('Y-m-d', mktime (0, 0, 0, $current_month+2, 0, $current_year)),
                                  $params->users,
                                  $params->projectids );
      
        $this->fc->smartyAssign ('TITLE', 'VSD report');
        $this->fc->smartyAssign ('persons', $persons);
        $this->fc->smartyAssign ('billable_percentage_total_avg', $billable_percentage_total_avg);
        $this->fc->smartyAssign ('billable_percentage_target_avg', $billable_percentage_target_avg);
        $this->fc->smartyAssign ('billable_time_total', $billable_time_total);
        $this->fc->smartyAssign ('time_total', $time_total);
      
        $this->fc->smartyAssign ('prev', $prev);
        $this->fc->smartyAssign ('next', $next);

        $year = substr($this->date_from,0,4);
      
        $url = Util::create_url("vsd_graph", 
                                $this->date_from,
                                $this->date_to,
                                $params->users,
                                $params->projectids,
                                array ('x'=>'person','y'=>'time'));

        $this->fc->smartyAssign('vsd_graph_1_url', $url);
        
        $url = Util::create_url("vsd_graph", 
                                "$year-01-01",
                                $this->end_date($year),
                                $params->users,
                                $params->projectids,
                                array ('x'=>'time','y'=>'vsd'));

        $this->fc->smartyAssign('vsd_graph_2_url', $url);
        
        $url = Util::create_url("vsd_graph", 
                                "$year-01-01",
                                $this->end_date($year),
                                $params->users,
                                $params->projectids,
                                array ('x'=>'person','y'=>'time'));

        $this->fc->smartyAssign('vsd_graph_3_url', $url);
        
        $url = Util::create_url("vsd_graph", 
                                "$year-01-01",
                                $this->end_date($year),
                                $params->users,
                                $params->projectids,
                                array ('x'=>'time','y'=>'billable'));

        $url = Util::create_url("vsd_graph", 
                                "$year-01-01",
                                $this->end_date($year),
                                $params->users,
                                $params->projectids,
                                array ('x'=>'time','y'=>'billable'));

        $this->fc->smartyAssign('vsd_graph_4_url', $url);
        
        $url = Util::create_url("vsd_graph", 
                                "$year-01-01",
                                $this->end_date($year),
                                $params->users,
                                $params->projectids,
                                array ('x'=>'time','y'=>'billable','options[]'=>'collapse'));

        $this->fc->smartyAssign('vsd_graph_5_url', $url);
        
        
        //      $this->fc->smartyAssign ('', $);

        $this->fc->smartyDisplay ('index.tpl');

    }


    function end_date($year) 
    {
        $tm = time();
        if ($year == strftime("%Y", $tm)) {
            return strftime("%Y-%m-%d", $tm);
        }
        
        return "$year-12-31";
    }
    

};

?>
