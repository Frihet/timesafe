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

class Util {
    /**
     * Formats seconds hours.hour part
     *
     * @param seconds Seconds to format.
     * @return String formatted seconds.
     */
     function seconds_to_string($seconds) {
	 return sprintf("%.1f", (float) $seconds / 3600);
     }

     /**
      * Create url for refining.
      *
      * @param type Type parameter in URL.
      * @param date_start Current start date.
      * @param date_end Current stop date.
      * @param users Users to refine to.
      * @param projects Projects to refine to.
      */
      function create_url($type, $date_start, $date_end, $users, $projects, $extra=array()) {
	
         $url = "?type=$type";

	 // Add date range
	 if ($date_start) {
	     $url .= "&from=$date_start";
	 }
	 if ($date_end) {
	     $url .= "&to=$date_end";
	 }

	 // Add users
         if ($users) {
             foreach ($users as $user) {
                 $url .= "&users[]=$user";
             }
         }
	 // Add projects
         if ($projects) {
             foreach ($projects as $projectid) {
                 $url .= "&projectids[]=$projectid";
             }
         }

	 foreach ($extra as $key => $value)
	   {
	     $url .= "&$key=$value";
	   }

	 return $url;
      }

     /**
      * Create current url.
      */
     function create_curr_url()
     {
       global $params;
       $from = isset($params->from) ? $params->from : null;
       $to = isset($params->to) ? $params->to : null;
       $users = isset($params->users) ? $params->users : null;
       $projects = isset($params->projectids) ? $params->projectids : null;
       
       return Util::create_url($params->type,
				$from, $to, $users, $projects);

     }

     /**
      * Create url for paging.
      *
      * @param type Type parameter in URL.
      * @param date_start Current start date.
      * @param date_end Current stop date.
      * @param interval +/- interval in days.
      */
     function create_step_url($type, $date_start, $date_end, $is_next) 
     {

       global $params;

       $date_min = strtotime($date_start);
       $date_max = strtotime($date_end);

       $this->date_days = ($date_max - $date_min) / 86400 + 1;

       $min_month = strftime("%m", $date_min);
       $min_day = strftime("%d", $date_min);
       $min_year = strftime("%Y", $date_min);
       
       $max_month = strftime("%m", $date_max + 26*3600);
       $max_day = strftime("%d", $date_max + 26*3600);
       $max_year = strftime("%Y", $date_max + 26*3600);

       $interval = ($date_max - $date_min) / 86400 + 1;
       
       if ($min_day == 1 && $max_day == 1 && (($min_month+1)%12 == $max_month%12)) {
           if ($is_next) {
               $from = mktime(0, 0, 0, $max_month, 1, $max_year);
               $to = mktime(0, 0, 0, $max_month+1, 0, $max_year);
           } else {
               $from = mktime(0, 0, 0, $min_month-1, 1, $min_year);
               $to = mktime(0, 0, 0, $min_month, 0, $min_year);
           }
       }
       else if (!$is_next) {
	   $from = strtotime($date_start) - 86400 * abs($interval);
	   $to = strtotime($date_start) - 86400;
       } else {
	   $from = strtotime($date_end) + 86400;
	   $to = strtotime($date_end) + 86400 * $interval;
       }

       $from_str = strftime('%Y-%m-%d', $from);
       $to_str = strftime('%Y-%m-%d', $to);
       
       $users = isset($params->users) ? $params->users : null;
       $projects = isset($params->projectids) ? $params->projectids : null;
       
       return Util::create_url($type, $from_str, $to_str, $users, $projects);
     }
};

?>
