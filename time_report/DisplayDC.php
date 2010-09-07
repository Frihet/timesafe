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

class DisplayDC extends Display 
{

    function calculate()
    {
        $tmp = new StdClass();
        $tmp->external_billable=0;
        $tmp->external_nonbillable=0;
        $tmp->internal_nonbillable=0;
        $tmp->vsd=0;
	$total = clone($tmp);
	
        $res = array('Div4A'=>clone($tmp),
                     'Div4B'=>clone($tmp),
                     'Div4C'=>clone($tmp),
                     'Div4D'=>clone($tmp),
                     'Div4E'=>clone($tmp),
                     'Div4F'=>clone($tmp),
                     'Div4G'=>clone($tmp),
                     'Div5'=>clone($tmp));
        
        $grouped_hours = $this->group_hours ($this->hours, $sortby, $sortdirection);
        $in = $grouped_hours[0];
        foreach ($in as $key => $outer) {
            foreach ($outer as $hour) {
                /*
                 echo('<pre>');
                 var_dump($hour);
                 exit(12);
                */
                $project_name = $hour['project_name'];
                //$task_name_list = explode('/', rtrim($hour['fptt'],'/'));
                $task_name_list = $this->egs->get_tasks($hour);
		/*
		echo "TASK LIST:";
		print_r($task_name_list);
		echo "<br>";
		*/
                array_shift( $task_name_list);
                $seconds = $hour['seconds'];
                $billable = $hour['billable'] != 'f';
		$added = false;
                		
                if (eregi('^div4[a-g]', $project_name)) {
                    $subdivision = strtoupper(substr($project_name, 3,2));
                    
                    $res["Div".$subdivision]->internal_nonbillable += $seconds;
                    $added = true;
                }
		else if (eregi('^div5', $project_name)) {
                    $res['Div5']->internal_nonbillable += $seconds;
                    $added = true;
		}
                else {
                    $field1 = eregi('^div[0-9]', $project_name)?'internal':'external';
                    //echo $field1;
                    //echo $project_name;
                    
                    $subdivision = false;
                    foreach($task_name_list as $task) {
                        $div = false;
                        
			if (eregi('^div4[a-g]', $task)) {
                            $subdivision = strtoupper(substr($task, 3,2));
			    $div = true;
			}
			else if (eregi('^div5', $task)) {
                            $subdivision = '5';
			    $div = true;
			}
			
			if($div) {
                            $field = $field1 . '_'.($billable?'billable':'nonbillable');
                            $res["Div".$subdivision]->$field += $seconds;
                            $added = true;
                            
                            if ($billable) {
                                $res["Div".$subdivision]->vsd += $seconds*$this->egs->get_billing_rate($hour['username'],$hour['projectid'])/3600;
                            }
                            break;
                        }
                    }
                    if ($billable && !$added) {
                        //var_dump($hour);
                    }
                                        
                }
                
            }
        }
        /*
        echo('<pre>');
        var_dump($res);
        exit(12);
        /**/
        foreach($res as $div) {
            $total->internal_nonbillable += $div->internal_nonbillable;
            $total->external_billable += $div->external_billable;
            $total->external_nonbillable += $div->external_nonbillable;
            $total->vsd += $div->vsd;
        }
	$res['Total'] = $total;
        
        return $res;
        
    }


    /**
     * Render users.
     */

    function render() 
    {
        $data = $this->calculate();

        foreach($data as $division => $tm) {
            $data[$division]->internal_nonbillable = Util::seconds_to_string($data[$division]->internal_nonbillable);
            $data[$division]->external_billable = Util::seconds_to_string($data[$division]->external_billable);
            $data[$division]->external_nonbillable = Util::seconds_to_string($data[$division]->external_nonbillable);
            $data[$division]->vsd = number_format($data[$division]->vsd, 0, ',', ' ');
        }
        
      
        $this->fc->smartyAssign ('data', $data);
        $this->fc->smartyDisplay ('index.tpl');
    }

};

?>
