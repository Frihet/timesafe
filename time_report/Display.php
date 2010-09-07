<?
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

/**
   The base class for all time_report modules
*/
class Display 
{

    var $fc;
    var $egs;
    var $hours;
    var $users;

    var $date_from;
    var $date_to;

    var $date_min;
    var $date_max;

    var $link_prev = null;
    var $link_next = null;

    var $mode;

    var $user_data;
    

    function Display ($fc, $egs, &$hours, $users, $params) 
    {
        $this->fc = $fc;
        $this->egs = $egs;
        $this->hours =& $hours;

        //$this->user_data = new UserEdit();
        
        $this->users = $users;
        $this->date_from = $params->from;
        $this->date_to = $params->to;
        $this->mode = $params->type;

        $this->params = $params;
        

        if ($this->date_from && $this->date_to) 
            {
                $this->date_min = strtotime ($this->date_from);
                $this->date_max = strtotime ($this->date_to);
            
                $this->fc->smartyAssign ('curr_url', Util::create_curr_url ());
	                                 
                $link_prev = Util::create_step_url ($this->mode,
                                                    $this->date_from,
                                                    $this->date_to,
                                                    false);
                $link_next = Util::create_step_url ($this->mode, 
                                                    $this->date_from,
                                                    $this->date_to,
                                                    true);

                $this->fc->smartyAssign ('link_prev', $link_prev);
                $this->fc->smartyAssign ('link_next', $link_next);
            }
        $this->fc->smartyAssign ('date_from',
                                 strftime('%Y-%m-%d', strtotime ($this->date_from)));
        $this->fc->smartyAssign ('date_to',
                                 strftime('%Y-%m-%d', strtotime ($this->date_to)));
      
                
        $this->fc->smartyAssign( 'page_type', $this->mode );

        $this->fc->smartyAssign( 'version', TIME_REPORT_VERSION );
        $this->fc->smartyAssign( 'author', TIME_REPORT_AUTHOR );

        $this->fc->smartyAssign('selected_users', $params->users?$params->users:array());
        $this->fc->smartyAssign('selected_projects', $params->projectids?$params->projectids:array());

        $this->fc->smartyAssign('all_users', $this->egs->get_users ());
        $this->fc->smartyAssign('all_projectids', $this->egs->get_projects ());
        $this->fc->smartyAssign('page_width', '550');
        
        $this->fc->smartyAssign('page_type_list', array('dc'=>'DC weekly report',
                                                        "illness"=>"Illness",
                                                        "invoice"=>"Invoice",
                                                        "salary"=>"Salary",
                                                        "vsd"=>"VSD",
                                                        "user"=>"Work hours"));
    }

    /**
     Detect if the specified work entry is a double overtime entry
    */
    function is_double_overtime( $data )
    {
        if (substr($data['description'], 0, 3) == '100') {
            return true;
        }
        
        $date = $data['date'];
        $parts = explode('-',$date);
        $tm = mktime(0, 0, 0, $parts[1], $parts[2], $parts[0] );
        $day_of_week = date('w', $tm );
      
        if( $day_of_week == 0 || $day_of_week == 6 ) {
            return true;
        }
      
        /*      
      echo "<pre>";
      var_dump($data);
      echo "</pre>";
        */
        return false;
    }
  
    function person_info (&$hours)
    {
        $person = array();
        $types = $this->egs->get_hour_types();
      
        foreach ($hours as $day_key => $day) {
          
            foreach ($day as $el_key => $el) {
              
                $name = $el['fullname'];
              
                if (!isset( $person[$name]->seconds )) {
              
                    $person[$name]->name = $name;
                    $person[$name]->seconds = array();
                  
                    foreach( $types as $key => $val ) {
                        $person[$name]->seconds["Billable"][$key] = 0;
                        $person[$name]->seconds["Nonbillable"][$key] = 0;
                        $person[$name]->seconds["Vacation"][$key] = 0;
                        //  $person[$name]->seconds["Work leave"][$key] = 0;
                    }
                }
              
                $hr = $el['seconds'];

                if( $this->egs->is_vacation($el)) {
                    $person[$name]->seconds["Vacation"][$el['typeid']]-= $el['seconds'];
                }              
                else if ($this->egs->is_work_leave($el['tagids'] ))
 {
                    //$person[$name]->seconds["Work leave"][$el['typeid']]-= $el['seconds'];
                }

	      
                if ($el['billable'] == 'f') {
                    $person[$name]->seconds["Nonbillable"][$el['typeid']] += $el['seconds'];
                } else {
                    $person[$name]->seconds["Billable"][$el['typeid']] += $el['seconds'];
                }              
            }
        }

//        var_dump($person);
        return $person;
    }

    function createMatrix()
    {
        $types = $this->egs->get_hour_types();
        foreach(array("Billable","Nonbillable","Vacation") as $bill2) {
            foreach($types as $id => $name) {
                $matrix[$bill2][$id]->val = 0;
                $matrix[$bill2][$id]->str = "0.0";
                $matrix[$bill2][$id]->class = "number";
            }
        }
        return $matrix;
    }
    

    function mergeHours($matrix, $el)
    {

        if( !$matrix ) {
            $matrix = $this->createMatrix();            
        }
        
        if ($el['billable'] == 'f') {
            $bill = "Nonbillable";
        } else {
            $bill = "Billable";
        }

        $type = $el["typeid"];
	
        if ($this->egs->is_vacation($el)) {
            $matrix["Vacation"][$type]->val -= $el['seconds'];
            $tmp = Util::seconds_to_string($matrix["Vacation"][$type]->val);
            $matrix["Vacation"][$type]->str = $tmp;
        }
        
        if (!$this->egs->is_work_leave($el['tagids'] )) {
	    $matrix[$bill][$type]->val += $el['seconds'];
            $tmp = Util::seconds_to_string ($matrix[$bill][$type]->val);
            $matrix[$bill][$type]->str = $tmp;
        }
        
        return $matrix;
      
    }

    function type2idx( $billid, $typeid, $tagids ) 
    {
        $types = $this->egs->get_hour_types();        
        
        if ($this->egs->is_work_leave($tagids)) {
            return (count($types)*2);
        }
        return ((count($types)*$billid) + $typeid)-1;
    }

    function calcTotal( $data )
    {
        $tot1 = array();
        $data2 = array();

        if( !$data ) {
            $data = $this->createMatrix();            
        }

        foreach ($data as $key => $value)
        {
            $has_data = false;
            
            foreach ($value as $key2 => $value2) {
                if( $value2->val != 0 ) {
                    $has_data = true;
                    break;
                }
            }
            if( $has_data ) {
                $data2[$key] = $value;
            }
        }
        
        $data = $data2;
                
        foreach ($data as $key => $value)
        {
            $row = 0;

            foreach ($value as $key2 => $value2) {
                $row += $value2->val;
            }

            $d = null;
            $d->val = $row;
            $d->str = Util::seconds_to_string ( $row );
            $d->class = "number right_sum";
            $data[$key][] = $d;
        }
        
        $types = $this->egs->get_hour_types();
        $types[] = "Total";
        
        foreach($types as $id => $name) {
            $tot1[$id]->val = 0;
            $tot1[$id]->str = "0.0";
            $tot1[$id]->class="number bottom_sum";
        }
        
        foreach ($data as $key => $value)
        {
            foreach ($value as $key2 => $value2) {
                $tot1[$key2]->val += $value2->val;
                $tot1[$key2]->str = Util::seconds_to_string ( $tot1[$key2]->val );
            }
        }
        
        $data["Total"] = $tot1;
        
        return $data;
    }
  
    /**
     * Converts the hours into the following structures:
     *
     * array (sortby => array('fullname', 'date', 'fptt', 'hours', 'description', 'project_name')))
     */
  
    function group_hours (&$hours, $sortby, $sortdirection) 
    {
        $hours_billable = 0;
        $hours_non_billable_overtime = 0;
        $hours_non_billable = 0;
        $hours_billable_overtime = 0;
        $hours_total = 0;
        $grouped_hours = array ();

        foreach ($hours as $hour => $hi) 
	{
            // Add fptt to info before selecting sort as it is a valid sort.
            $project_name = null;
            
            if (!is_null($hi['projectid'])) {
                $fptt_info = $this->egs->create_task_path($hi['projectid'], $hi['tagids']);
                $hi['fptt'] = $fptt_info[1];

                $project_name = $this->egs->cache_get_project ($hi['projectid']);
                if( strstr( $project_name, ":" ) ) {
                    //                    $project_name = substr( strstr( $project_name, ":" ), 1 );
                }
            }
            
            $sort = $hi[$sortby];
            $fullname = '<a href="' . Util::create_url('user', $GLOBALS['params']->from, $GLOBALS['params']->to, array($hi['username']), null) . '">' . str_replace(" ", "&nbsp;", $hi['fullname']) . "</a>";
            $date = '<a href="' . Util::create_url($this->mode, $hi['entered'], $hi['entered'], null, null) . '">' . $hi['entered'] . "</a>";

            $fullname_nolink = $hi['fullname'];
            $date_nolink = $hi['entered'];
            $hours_nolink = Util::seconds_to_string ($hi['seconds']);

            
            $seconds_nolink = $hi['seconds'];
            $tm = Util::seconds_to_string ($hi['seconds']);
            if( $hi['seconds'] > 3600*20 ) {
                $tm = "<blink style='font-weight: bold; color: #f00;'>$tm</b>";
            }
            

            $hours_link = '<a href="' . $this->egs->link_hour ($hi['projectid'],
                                                          $hi['id'])
	    . '">' . $tm . '</a>';
	   
            // Validate description
            if (empty ($hi['description'])) {
                $desc = '<blink style="font-weight: bold; color: #f00;">***&nbsp;MISSING&nbsp;DESCRIPTION&nbsp;***</blink>';
            } else {
                $desc = str_replace("  ", "&nbsp;&nbsp;", $hi['description']);
                $desc = str_replace("\n", "<br />\n", $hi['description']);
            }

            // Make sure entries exist in array
            if (! array_key_exists ($sort, $grouped_hours)) 
                {
                    $grouped_hours[($sort)] = array();
                 
                }
            
            // Add entry
            array_push ($grouped_hours[$sort],
                        array ('fullname_html' => $fullname,
                               'fullname' => $fullname_nolink,
                               'date_html' => $date,
                               'date' => $date_nolink,
                               'fptt' => $fptt_info[1],
                               'fptt_html' => $fptt_info[0],
                               'hours' => $hours_nolink,
                               'hours_html' => $hours_link,
                               'seconds' => $seconds_nolink,
                               'billable' => $hi['billable'],
                               'overtime' => $hi['overtime'],
                               'description' => $hi['description'],
                               'description_html' => $desc,
                               'project_name' => $project_name,
                               'jobtitle' => $hi['jobtitle'],
                               'projectid' => $hi['projectid'],
                               'tagids' => $hi['tagids'],
                               'typeid' => $hi['typeid'],
                               'month' => $hi['month'],
                               'year' => $hi['year'],
                               'timesafe' => $hi['timesafe'],
                               'customer' => $hi['customer'],
                               'typename' => $hi['typename'],
                               'username' => $hi['username']));
	  
            $hours_total += $hi['seconds'];
            if ($hi['billable'] == 't') 
                {
                    $hours_billable += $hi['seconds'];
                    if ($hi['overtime'] == 't') 
                        {
                            $hours_billable_overtime += $hi['seconds'];
                        }
                } 
            else 
                {
                    $hours_non_billable += $hi['seconds'];
                    if ($hi['overtime'] == 't') 
                        {
                            $hours_non_billable_overtime += $hi['seconds'];
                        }
                }
	}

        // Format hours total
        $hours_total = Util::seconds_to_string ($hours_total);
        $hours_billable = Util::seconds_to_string ($hours_billable);
        $hours_billable_overtime = Util::seconds_to_string ($hours_billable_overtime);
        $hours_non_billable = Util::seconds_to_string ($hours_non_billable);
        $hours_non_billable_overtime = Util::seconds_to_string ($hours_non_billable_overtime);

        if ($sortdirection == 'asc') 
            {
                krsort ($grouped_hours);
            }
        else 
            {
                ksort ($grouped_hours);
            }

        return array($grouped_hours, $hours_total,
                     $hours_billable, $hours_billable_overtime,
                     $hours_non_billable, $hours_non_billable_overtime);
    }

    /**
     * Build and return title string.
     */
    function get_title() {
        $title = 'Time Report';
        if ($this->mode != 'form') {
            $title .= ' - Hours of ';
  
            // Add persons to title
            if (count($this->users) == 1) {
                $users = array_keys($this->users);
                $title .= $this->users[$users[0]];
            } else {
                $title .= 'multiple users';
            }
  
            // Add date to title
            if (! empty($this->date_from) && ! empty($this->date_to)) {
                $title .= ' ' . $this->date_from . ' - ' . $this->date_to;
            }
        }

        return $title;
    }

    function formatDescription( $d )
    {
      /*
        Remove leading empty lines
      */
      $pat = "^( |\n)*";
      $d = preg_replace( $pat, "", $d );
      
      /*
        Format * - based lists nicely
      */
      $pat = "^".' *\* *';
      $d = preg_replace( $pat, "&nbsp;-&nbsp;", $d );
      $pat = "\n".' *\* *';
      $d = preg_replace( $pat, "<br>&nbsp;-&nbsp;", $d );

      /*
        Preserve newlines
      */
      $pat = "\n";
      $d = preg_replace( $pat, "<br>", $d );

      /*
        Turn multiple newlines into a paragraph break
      */

      $pat = "<br>(\r| |\n)*<br>";
      $d = preg_replace( $pat, "</p><p>", $d );
      return "$d";
    }
  

}

?>