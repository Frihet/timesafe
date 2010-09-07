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

class DisplayInvoice extends Display 
{

  /**
   * Render users.
   */
  function render () 
    {
      $grouped_hours = $this->group_hours ($this->hours, "entered", "desc");
      $type = $this->egs->get_hour_types();

      $project=array();
      
      foreach ($grouped_hours[0] as $date => $hours)
      {

	  foreach ($hours as $el_key => $el) 
          {
              
              if ($this->egs->is_work_leave( $el['tagids'] )) {
                  continue;
              }

	      $name = $el['fullname'];
	      $hr = $el['seconds'];
              $proj = $el['project_name'];
              $cust = $el['customer'];

              $idx = $cust . ":" . $proj;
              
              if( isset($project[$idx]) ) {
                  $p = $project[$idx];                  
              } else {
                  $p=null;
                  $p->project = $proj;
                  $p->customer = $cust;
                  
                  $p->total=null;
                  $p->person=array();
                  $p->details=array();

                  $p->details["Billable"]->total_val = 0;
                  $p->details["Billable"]->total_str = '0.0';
                  $p->details["Billable"]->arr = array();

                  $p->details["Nonbillable"]->total_val = 0;
                  $p->details["Nonbillable"]->total_str = '0.0';
                  $p->details["Nonbillable"]->arr = array();
              }

              if( !isset($p->person[$name]) ) {
                  $p->person[$name]=null;
              }
              
              $p->total = $this->mergeHours( $p->total, $el );
              $p->person[$name]= $this->mergeHours( $p->person[$name], $el );

	      $el['description'] = $this->formatDescription( $el['description'] );
              if ($el['billable'] == 't') {
                  $bill = "Billable";                  
              }
              else {
                  $bill = "Nonbillable";
              }

              $p->details[$bill]->arr[] = $el;
              $p->details[$bill]->total_val += $el['seconds'];
              $p->details[$bill]->total_str = Util::seconds_to_string($p->details[$bill]->total_val);
              
              
              $project[$idx] = $p;
              
          }
      }

      $project2 = array();
      
      $total = array();
      foreach($type as $t=>$i) 
      {
          $total[$t] = 0;
      }
      
      foreach ($project as $name => $data) {
          $project[$name]->total = $this->calcTotal( $data->total );

          if( !isset($project[$name]->total["Billable"]))
              continue;
          

          $grand = end( $project[$name]->total["Billable"]);
                    
          if( $grand->val == 0)
              continue;
          

          foreach ($data->person as $name2 => $data2 ) {
              $project[$name]->person[$name2] = $this->calcTotal( $data2 );
          }

          ksort($project[$name]->person);
          
          $project[$name]->person_names=implode(", ", array_keys($project[$name]->person));


          // Calculate hours for project summary

          $s = array();
          foreach( $data->total["Billable"] as $key => $val) {
          
              // Ignore columns not known, i.e. the totals column.
              
              if( isSet($total[$key])) {
                  $s[$key] = Util::seconds_to_string( $val->val );
                  $total[$key] += $val->val;
              }

          }
          
          $project[$name]->summary = $s;
          $project2[$name] = $project[$name];
          
      }
      $project = $project2;
      
      ksort($project);

      foreach( $total as $key => $value ) {
          $total[$key] = Util::seconds_to_string($value);
      }
      

      //echo "<pre>";
      //var_dump($project);
      
      

      $this->fc->smartyAssign ('project', $project );
      $this->fc->smartyAssign ('type', $type );
      $this->fc->smartyAssign ('total', $total );

      //      $this->fc->smartyAssign ('person_names', implode(", ", array_keys($person)));

      /*      
      $this->fc->smartyAssign ('date_from',
			     strftime('%a %d %b, %Y', strtotime ($this->date_from)));
      $this->fc->smartyAssign ('date_to',
			     strftime('%a %d %b, %Y', strtotime ($this->date_to)));
      */
      $this->fc->smartyAssign ('TITLE', 'Invoice specification summary');
      $this->fc->smartyDisplay ('index.tpl');

    }


};

?>
