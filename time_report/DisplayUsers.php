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

class DisplayUsers extends Display 
{

    /**
     * Render users.
     */

    function render() 
    {
      $sortby = $this->params->sortby;
      $sortdirection = $this->params->sortdirection;

      $grouped_hours = $this->group_hours ($this->hours, $sortby, $sortdirection);

      $person=$this->person_info ($grouped_hours[0]);

      $in = $grouped_hours[0];
      $bad=array();
      $has_bad = false;

      $total = null;
            
      $type = $this->egs->get_hour_types();

      $prev = array();

      $work_leave_ok = array();
      $work_leave_done = array();
      
      
      foreach ($in as $key => $outer) {
          $bad_inner = array();
          
          foreach ($outer as $hour) {
              $is_timesafe = $hour['timesafe'];
              $total = $this->mergeHours( $total, $hour );

	      $task_name_list = explode('/', rtrim($hour['fptt'],'/'));
	      array_shift( $task_name_list);
	      $billable = $hour['billable'] != 'f';
	      if ($billable && !$is_timesafe) {
                  $has_division = false;
		  
		  if (!$has_division) 
		  {
		      $hour['badness'] = "Billable work must be filed under a Div4X or Div5 task";
		      $bad_inner[] = $hour;
		      
		  }
		  
	      }
	      

              
              if ( !$hour['description'] ) {
                  $hour['badness'] = "No description given";
                  $bad_inner[] = $hour;
              }

              if (!$hour['tagids'] && !$is_timesafe) {
                  $hour['badness'] = "No task given";
                  $bad_inner[] = $hour;
              }

              if ($hour['seconds'] > 3600 * 24) {
                  $hour['badness'] = "Minutes written in hour field?";
                  $bad_inner[] = $hour;
              }

              if ($hour['billable'] != 'f') {
                  if ($hour['seconds'] % 1800 != 0) {
                      $hour['badness'] = "Type set to billable, but time not a multiple of 30 minutes";
                      $bad_inner[] = $hour;
                  }
              }

              if ($hour['billable'] == 'f') {
                  $bill = "Nonbillable";
              } else {
                  $bill = "Billable";
              }
              
              $typeid = $hour["typeid"];
              $fullname = $hour['fullname'];
              
              if ($this->egs->is_work_leave($hour['tagids'] )) {
                  $work_leave_done[$fullname] += $hour['seconds'];
              }

              $work_leave_ok[$fullname] += 1 * $hour['seconds']; // config_get_work_leave_ratio( $typeid )
              
              $date = $hour["date"];
              $proj = $hour['fptt'];
              
              /*
              if (isSet($prev[$proj][$bill][$typeid][$date])) {
                  $hour['badness'] = "Same type of hours with same status reported twice in one day";
                  $bad_inner[] = $hour;
              }*/

              $prev[$proj][$bill][$typeid][$date] = true;
                            
          }              

          $has_bad |= count($bad_inner)>0;
          
          
          $bad[$key] = $bad_inner;
          
      }

      /*      var_dump($work_leave_ok);
      var_dump($work_leave_done);
      */

      $work_leave_ok_tot = 0;
      $work_leave_done_tot = 0;
      
      foreach($person as $name => $val ) {

          $work_leave_ok_tot += $work_leave_ok[$name];
          if (isSet($work_leave_done[$name]))
              $work_leave_done_tot += $work_leave_done[$name];
      }
      
      foreach($work_leave_done as $p => $done ) {

          if ($done > $work_leave_ok[$p]) {

              $item = array();
              $item["fullname_html"] = $p;
              $item["billable"] = 'f';
              $item["typename"] = 'Regular';
              $item["date_html"] = 'N/A';
              $item["fptt_html"] = 'N/A';
              $item["hours_html"] = Util::seconds_to_string($done-$work_leave_ok[$p]);
              $item["description_html"] = 'N/A';
              $item["badness"] = sprintf('Too many hours registered as work leave, %s hours registered, but only %s hours have been earned during current period.', Util::seconds_to_string($done), Util::seconds_to_string($work_leave_ok[$p]));
              
              $bad_inner = array($item);
              $bad[] = $bad_inner;
              
          }
      }
      
      
      $from = explode('-',$this->date_from);
      $to = explode('-',$this->date_to);
        
      $regular_hours = 0; //config_get_hours($from[0],$from[1],$from[2], $to[0],$to[1],$to[2]);
        
/*      
      echo "<pre>";
      var_dump(array_values($person));
      echo "</pre>";
*/    
      $total = $this->calcTotal($total);
      
      $this->fc->smartyAssign ('work_leave_ok_tot',  Util::seconds_to_string($work_leave_ok_tot ));
      $this->fc->smartyAssign ('work_leave_done_tot',  Util::seconds_to_string($work_leave_done_tot ));
      $rem = $work_leave_ok_tot-$work_leave_done_tot;
      $rem_str = Util::seconds_to_string($rem);
      if ($rem<0) {
          $rem_str = "<span class='error'>$rem_str</span>";
      }
      
      $this->fc->smartyAssign ('work_leave_remaining_tot', $rem_str);

      $this->fc->smartyAssign ('hour_type', $type );
      $this->fc->smartyAssign ('regular_hours', $regular_hours * count($this->users) );
      $this->fc->smartyAssign ('person', array_values($person));

      $this->fc->smartyAssign ('histogram_url', Util::create_url ("histogram", 
								$this->date_from,
								$this->date_to,
								$this->params->users,
								$this->params->projectids ) );
      $this->fc->smartyAssign ('page_width', '800');
      $this->fc->smartyAssign ('hours', $grouped_hours[0]);

      $this->fc->smartyAssign ('hours_bad', $bad);
      $this->fc->smartyAssign ('has_bad', $has_bad);

      $this->fc->smartyAssign ('total', $total);

      $displayfields = array ('User' => array('sortby' => 'username'),
			      'Date' => array('width' => '5em', 'sortby' => 'entered'),
			      'Task' => array('sortby' => 'fptt'),
			      'Hours' => array('width' => '4em', 'sortby' => null),
			      'Description' => array('sortby' => 'description'));
      $displayfields_bad = $displayfields;
      $displayfields_bad['Issue'] = array('sortby'=>null);

      $this->fc->smartyAssign ('TITLE', 'Work hours');
      $this->fc->smartyAssign ('displayfields', $displayfields);
      $this->fc->smartyAssign ('displayfields_bad', $displayfields_bad);


      $this->fc->smartyAssign ('sortby', $sortby);
      $this->fc->smartyAssign ('sortdirection',
			     ($sortdirection == 'asc') ? 'desc' : 'asc');


      $this->fc->smartyDisplay ('index.tpl');
    }


};

?>
