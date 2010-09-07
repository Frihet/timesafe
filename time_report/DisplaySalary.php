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

class DisplaySalary extends Display 
{

    /**
     * Update the array $arr by increasing column $idx by the amount
     * $inc. The columns $sum_col are updated as partial sums from the
     * previous sum up to the column before the sum. All columns in
     * $border have a left side border class.
     */
    function format_add($arr, $idx, $inc, $sum_col, $border)
    {
        if(!isSet($arr[$idx])) {

            $arr[$idx]->val=0;
            $arr[$idx]->str='0.0';
            $arr[$idx]->class='number';
            
            if (array_search($idx, $border)!== false) {
                $arr[$idx]->class.=' border_left';
            }

        }
        
        $arr[$idx]->val += $inc;
        $arr[$idx]->str = Util::seconds_to_string($arr[$idx]->val);
        
        $sum_start=0;
        
        foreach ($sum_col as $i) {
            $val = 0;
            for ($j = $sum_start; $j < $i; $j++) {
                $val += $arr[$j]->val;
            }
            
            $arr[$i]->val = $val;
            $arr[$i]->str = Util::seconds_to_string($arr[$i]->val);
            $arr[$i]->class='number right_sum';
            
            if (array_search($i, $border)!== false) {
                $arr[$i]->class.=' border_left';
            }
            
            $sum_start = $i;
        }

        ksort($arr);
        return $arr;
        
    }
    
    /**
     * Render users.
     */
    function render () 
    {
      $grouped_hours = $this->group_hours ($this->hours, "entered", "desc");

      $type = $this->egs->get_hour_types();
      
      $person=array();
      
      foreach ($grouped_hours[0] as $date => $hours)
      {

	  foreach ($hours as $el_key => $el) 
          {
              
	      $name = $el['fullname'];
	      $hr = $el['seconds'];
              $proj = $el['project_name'];
              $typeid = $el["typeid"];


              if( isset($person[$name]) ) {
                  $p = $person[$name];                  
              } else {
                  $p=null;
                  $p->name = $name;
                  $p->total=null;
                  $p->project=array();
                  $p->details=array();

                  $p->details["Billable"]->total_val = 0;
                  $p->details["Billable"]->total_str = '0.0';
                  $p->details["Billable"]->arr=array();

                  $p->details["Nonbillable"]->total_val = 0;
                  $p->details["Nonbillable"]->total_str = '0.0';
                  $p->details["Nonbillable"]->arr=array();
                  $p->work_leave_done = 0;
                  $p->work_leave_done_str = '0.0';
                  $p->work_leave_ok = 0;
                  $p->work_leave_ok_str = '0.0';
                  $p->work_leave_remaining = 0;
                  $p->work_leave_remaining_str = '0.0';
                  $p->non_work = 0;
                  $p->unpaid_non_work = 0;
                  $p->non_work_str = "0.0";
                  $p->unpaid_non_work_str = "0.0";
              }

              if ($this->egs->is_work_leave($el['tagids']) ) {
                  $p->work_leave_done += $el['seconds'];
                  $p->work_leave_done_str = Util::seconds_to_string($p->work_leave_done);
	      } else {
		  
                  if ($this->egs->is_paying_nonwork($el)) {
                      $p->non_work += $el['seconds'];
                      $p->non_work_str = Util::seconds_to_string($p->non_work);
                  }

                  if ($this->egs->is_unpaid_nonwork($el)) {
                      $p->unpaid_non_work += $el['seconds'];
                      $p->unpaid_non_work_str = Util::seconds_to_string($p->unpaid_non_work);
                  }

                  if( !isset($p->project[$proj]) ) {
                      $p->project[$proj]=null;
                  }
                  
                  $p->total = $this->mergeHours( $p->total, $el );
                  $p->project[$proj]= $this->mergeHours( $p->project[$proj], $el );
                  
                  $el['description'] = $this->formatDescription( $el['description'] );
                  if ($el['billable'] == 't') {
                      $bill = "Billable";                  
                  } else {
                      $bill = "Nonbillable";
                  }

                  $p->work_leave_ok += config_get_work_leave_ratio( $typeid ) * $el['seconds'];
                  $p->work_leave_ok_str = Util::seconds_to_string($p->work_leave_ok);
                  
                  $p->details[$bill]->arr[] = $el;
                  $p->details[$bill]->total_val += $el['seconds'];
                  $p->details[$bill]->total_str = Util::seconds_to_string($p->details[$bill]->total_val);
              }

              $p->work_leave_remaining = $p->work_leave_ok - $p->work_leave_done;
              $p->work_leave_remaining_str = Util::seconds_to_string($p->work_leave_remaining);
              
              
              $person[$name] = $p;
              
          }
      }
            
      $summary=array();
      $border=array(0, count($type), count($type)+1, count($type)+2, count($type)+3);
      $totidx = count($type)+2;
      $sumidx = array(4, 6);

      foreach ($person as $name => $data) {
          $person[$name]->total = $this->calcTotal( $data->total );

          foreach ($data->project as $name2 => $data2 ) {
              $person[$name]->project[$name2] = $this->calcTotal( $data2 );
          }

          $idx=0;
          $s = array();
          
          foreach($type as $typeid => $typename) {
              $inc = 0;
              foreach(array("Billable","Nonbillable") as $bill) {
		  
                  if( isSet($person[$name]->total[$bill][$typeid])) {
                      $inc += $person[$name]->total[$bill][$typeid]->val;
                  }
              }
              
              $s = $this->format_add($s, $idx, $inc, $sumidx, $border );
              $summary = $this->format_add($summary, $idx, $inc, $sumidx, $border );
              $idx++;
          }
	  
          $vacation = 0;
	  
          if (isSet($person[$name]->total["Vacation"][1])) {
              $vacation = $person[$name]->total["Vacation"][1]->val;
          }
	  $vacation += -$data->unpaid_non_work;
          
          $s = $this->format_add($s, $idx, $vacation, $sumidx, $border);
          $summary = $this->format_add($summary, $idx, $vacation, $sumidx, $border );
          $idx++;
          $idx++;

          $s = $this->format_add($s, $idx, -$data->non_work, $sumidx, $border);
          $summary = $this->format_add($summary, $idx, -$data->non_work, $sumidx, $border );
          $idx++;
          
          $person[$name]->summary = $s;

          ksort($person[$name]->project);
      }
      
      //      echo "<pre>".var_str($person)."</pre>";
      

      ksort($person);
      
      $this->fc->smartyAssign ('summary', $summary );
      $this->fc->smartyAssign ('person', $person );
      $this->fc->smartyAssign ('bill', array("Billable","Nonbillable"));
      $this->fc->smartyAssign ('type', $type );

      $this->fc->smartyAssign ('person_names', implode(", ", array_keys($person)));

      /*
      $this->fc->smartyAssign ('date_from',
			     strftime('%a %d %b, %Y', strtotime ($this->date_from)));
      $this->fc->smartyAssign ('date_to',
			     strftime('%a %d %b, %Y', strtotime ($this->date_to)));
      */

      $this->fc->smartyAssign ('TITLE', 'Salary specification');

      $this->fc->smartyDisplay ('index.tpl');

    }


};

?>
