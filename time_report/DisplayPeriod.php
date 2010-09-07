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

function display_users_sort_cmp ($a, $b)
{
  $a = preg_replace('/<a href[^>]*>/', '',  $a);
  $b = preg_replace('/<a href[^>]*>/', '',  $b);
  return strcasecmp ($a, $b);
}

class DisplayPeriod extends Display
{
  var $seconds_total;

  /**
   * Display class displaying invoice.
   *
   * @param egs Egs.
   */
  function DisplayPeriod ($fc, $egs, $hours, $users, $date_from, $date_to) {
    $this->Display( $fc, $egs, $hours, $users, $date_from, $date_to, "period" );
    $this->seconds_total = 0;
  }

  /**
   * Render users.
   */
  function render () {
    $grouped_hours = $this->group_hours ($this->hours);
    $date_array = $this->create_date_array ();
    $this->fill_hour_info ($grouped_hours, $date_array);

    $this->fc->smartyAssign ('page_width', '800');
    $this->fc->smartyAssign ('hours', $grouped_hours);
    $this->fc->smartyAssign ('dates', $date_array);
    $this->fc->smartyAssign ('date_from', $this->date_from);
    $this->fc->smartyAssign ('date_to', $this->date_to);

    $this->fc->smartyDisplay ('period.tpl', $this->get_title());
  }


  /**
   * Converts the hours into the following structures:
   *
   * array ('username' => array ('name',
   *                             'tasks' => array('task' => array('date' => hours))))
   */

  function group_hours ($hours) {
    $grouped_hours = array ();

    foreach ($hours as $hour => $hi) {
      $name = $hi['username'];
      $date = $hi['entered'];

      // Build task link
      $task_info = $this->egs->create_task_path ($hi['projectid'],
						 $hi['tagids']);
      $task = $task_info[0];


      // Make sure entries exist in array
      if (! array_key_exists ($name, $grouped_hours)) {
	$grouped_hours[$name] = array('fullname' => $hi['fullname'],
				      'tasks' => array ());
      }
      if (! array_key_exists ($task, $grouped_hours[$name]['tasks'])) {
	$grouped_hours[$name]['tasks'][$task] = array();
      }
      if (! array_key_exists ($date, $grouped_hours[$name]['tasks'][$task])) {
	$grouped_hours[$name]['tasks'][$task][$date] = 0;
      }

      // Add seconds
      $grouped_hours[$name]['tasks'][$task][$date] += $hi['seconds'];
      $this->seconds_total += $hi['seconds'];
    }

    return $grouped_hours;
  }

  /**
   * Generate array of dates.
   */
  function create_date_array ()
    {
      $date_array = array();

      for ($i = 0; $i < $this->date_days; $i++) {
	$date = strftime ('%Y-%m-%d', $this->date_min + $i * 86400);
	$day_of_week = strftime ('%a', $this->date_min + $i * 86400);

	$date_array[$date] = $day_of_week;
      }

      return $date_array;
    }

  /**
   * Fill hour info.
   */
  function fill_hour_info (&$grouped_hours, $date_array)
    {
      foreach ($grouped_hours as $username => $user) {
	foreach ($user['tasks'] as $task => $dates) {
	  foreach ($date_array as $date_check => $weekday) {
	    if (! array_key_exists ($date_check, $dates)) {
	      $grouped_hours[$username]['tasks'][$task][$date_check] = '&nbsp;';
	    } else {
	      $grouped_hours[$username]['tasks'][$task][$date_check] = 
		Util::seconds_to_string ($grouped_hours[$username]['tasks'][$task][$date_check]);
	    }
	  }

	  uksort ($grouped_hours[$username]['tasks'], 'display_users_sort_cmp');
	  ksort ($grouped_hours[$username]['tasks'][$task]);
	}
      }
      ksort ($grouped_hours);
    }
};

?>
