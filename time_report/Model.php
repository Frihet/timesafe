<?php
/*
 * Copyright © 2007 FreeCode AS
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * @file Model.php
 * @author Claes Nästén <claes.nasten@freecode.no>
 *
 * $Id: Model.php 4245 2009-07-16 08:58:26Z nooslilaxe $
 *
 * This file contains various functions for communicating with the
 * database of the egs system.
 */

require_once('DB.php');

/**
 * Callback for quoting fields.
 */
function EGS_quote ($str) {
  return "'$str'";
}

class Model
{

  public $db;

  public $cache_taskpath; /**< projectid:tagids => path */
  public $cache_projects; /**< id => name, tasks */
  public $cache_users=null; /**< username => username, name */

  public $PROJECT_FIELDS = '';
  public $TASK_FIELDS = '';
  public $HOUR_FIELDS = "
company.name as customer, 
projecthours.typeid, 
projecthourtypes.name as typename, 
projecthours.id,
projecthours.projectid, 
projecthours.tagids,
TO_CHAR(projecthours.entered, 'YYYY-MM-DD') as entered, 
projecthours.hours, 
projecthours.overtime,
projecthours.billable,
projecthours.description, 
projecthours.username,
projecttask.name AS taskname, 
useroverview.name AS fullname, 
EXTRACT(epoch FROM projecthours.hours) AS seconds,
person.jobtitle as jobtitle ";

  public $HOUR_FIELDS_GROUP = "
projecthours.typeid, 
projecthourtypes.name as typename, 
projecthours.overtime,
projecthours.billable, 
projecthours.username,
useroverview.name AS fullname, 
sum(EXTRACT(epoch FROM projecthours.hours)) AS seconds,
person.jobtitle as jobtitle, 
extract(month from entered) as month, 
extract(year from entered) as year";

  public $billing=null;

  public $timesafe_tag = array();
  public $timesafe_billable_id = null;
  public $timesafe_work_leave_id = null;
  public $timesafe_40_overtime_id = null;
  public $timesafe_100_overtime_id = null;
  public $timesafe_vacation_id = null;
  public $timesafe_illness_id = array();
  public $timesafe_paying_nonwork_id = array();
  public $timesafe_unpaid_nonwork_id = array();
  
  

  /**
   * Model constructor.
   *
   * @param db Database.
   */
  function Model( $db = null, $db=null )
    {
      if (!$db) {
          $db =& DB::connect (TIMESAFE_DSN);
          if (PEAR::isError ($db)) {
              die ("Unable to connect: " . $db->getMessage ());
          }
      }
      $this->db = $db;
      
      $this->cache_projects = array ();
      $this->cache_taskpath = array ();

      $this->configure_timesafe();
    }

  /** 
   * Return array of available hour types. The array maps from type id
   * to type description.
   */
  function get_hour_types() 
  {
     $query = "
select t.id, t.name  
from tr_tag t 
left join tr_tag_group tg 
    on t.group_id = tg.id
where tg.name = 'Hour type'
";
      
      $res =& $this->db->query ($query);
      
      if (PEAR::isError ($res)) {
          print "<pre>$query</pre>";
          die ("Unable to get tags with groups:" . $res->getMessage ());
      }
      $types = array();
      while ($res->fetchInto ($row, DB_FETCHMODE_ASSOC)) {
          $types[$row['id']] = $row['name'];
      }
      return $types;
  }
  

        
  function user_wildcard()
  {
return "'%admin%'";
/*
      $u = $_SERVER['PHP_AUTH_USER'];
      $sub = substr($u, 0, 3);
      if ($sub != 'int') {
          $sub = substr($u, 0, 4);
      }
      
      $res = "'" . $this->db->escapeSimple($sub) . "%'";
      return $res;
*/      
  }

  /**
   * Returns array of available projects.
   *
   * @return Array id => name of projects.
   */
  function get_projects() {
    // Build query
    $query = "SELECT p.id, p.name FROM tr_project as p WHERE p.open";
    $query .= " ORDER BY name";

    // Query database
    $res =& $this->db->query($query);
    if (PEAR::isError($res)) {
      print "<pre>$query</pre>";
      die ("Unable to get projects: " . $res->getMessage());
    }

    // Fill users array
    $projects = array();
    while ($res->fetchInto ($row, DB_FETCHMODE_ASSOC)) {
      $projects[$row['id']] = $row['name'];
    }

    return $projects;
  }

  function has_user($name)
  {
      $u = $this->get_users();
      return isset($u[$name]);
  }
  

  /**
   * Returns array of available users.
   *
   * @return Array name
   */
  function get_users() {
      if($this->cache_users) {
          return $this->cache_users;
      }
      
      // Build query
      $query = "
       SELECT
	u.name, u.fullname
       FROM
	tr_user as u
       WHERE not u.deleted
      ";
      
      // Add order to query
      $query .= " ORDER BY u.name";
      
      // Query database
      $res =& $this->db->query($query);
      if (PEAR::isError($res)) {
          die ("Unable to get users: " . $res->getMessage());
      }
      
      // Fill users array
      $users = array();
      while ($res->fetchInto ($row, DB_FETCHMODE_ASSOC)) {
          $users[$row['name']] = $row['fullname'];
      }
      
      $this->cache_users = $users;
      
      return $users;
  }
  
  function get_billing_rate($username, $projectid)
  {
      if (!$this->billing) {
          
          $arr = array();
          $this->billing = $arr;
          
      }
      
      if (!isset($this->billing[$username][$projectid]) || $this->billing[$username][$projectid] < 500 )
          {
              return 890;
          }
      
      return $this->billing[$username][$projectid];
      
  }
  
  /**
   Do lookup of various timesafe info to help us convert timesafe data to something recognizable to time report...
   */
  function configure_timesafe()
  {      

      $query = "
select t.*, tg.name as group_name, tg.required  
from tr_tag t 
left join tr_tag_group tg 
    on t.group_id = tg.id";
      
      $res =& $this->db->query ($query);
      
      if (PEAR::isError ($res)) {
          print "<pre>$query</pre>";
          die ("Unable to get tags with groups:" . $res->getMessage ());
      }
      
      // Fill hours array
      while ($res->fetchInto ($row, DB_FETCHMODE_ASSOC)) {
          if(strtolower($row['name'])=='billable') {
              $this->timesafe_billable_id = $row['id'];
          }
          else if(preg_match('/^40 *% *overtime/',strtolower($row['name']))) {
              $this->timesafe_40_overtime_id = $row['id'];
          }
          else if(preg_match('/^100 *% *overtime/',strtolower($row['name']))) {
              $this->timesafe_100_overtime_id = $row['id'];
          }
          else if(preg_match('/^(work *leave|avspasering)$/',strtolower($row['name']))) {
              $this->timesafe_work_leave_id = $row['id'];
          }
          else if(preg_match('/^vacation$/',strtolower($row['name']))) {
              $this->timesafe_vacation_id = $row['id'];
          }
          else if(preg_match('/^(illness|sick.*)$/',strtolower($row['group_name']))) {
              $this->timesafe_illness_id[] = $row['id'];
          }
          else if(preg_match('/^(paying|paid) non[- ]?work$/',strtolower($row['group_name']))) {
              $this->timesafe_paying_nonwork_id[] = $row['id'];
          }
          else if(preg_match('/^(unpaid|non[ -]?paying) non[- ]?work$/',strtolower($row['group_name']))) {
              $this->timesafe_unpaid_nonwork_id[] = $row['id'];
          }
      }
      
      $query = "select id, name from tr_tag";
      $res =& $this->db->query ($query);

      if (PEAR::isError ($res)) {
          print "<pre>$query</pre>";
          die ("Unable to get tags: " . $res->getMessage ());
      }
      
      // Fill hours array
      while ($res->fetchInto ($row, DB_FETCHMODE_ASSOC)) {
          $this->timesafe_tag[$row['id']] = $row['name'];
          
      }
      
      $query = "
select p.id, 'NOCOMPANY'
from tr_project p 
";
      $res =& $this->db->query ($query);

      if (PEAR::isError ($res)) {
          print "<pre>$query</pre>";
          die ("Unable to get project company: " . $res->getMessage ());
      }
      
      // Fill hours array
      while ($res->fetchInto ($row, DB_FETCHMODE_ASSOC)) {
          $this->timesafe_company[$row['id']] = $row['name'];
          
      }
      
      $query = "
select u.name
from tr_user u
";
      $res =& $this->db->query ($query);
      
      if (PEAR::isError ($res)) {
          print "<pre>$query</pre>";
          die ("Unable to get user names: " . $res->getMessage ());
      }
      
      // Fill hours array
      while ($res->fetchInto ($row, DB_FETCHMODE_ASSOC)) {
          $this->timesafe_title[$row['username']] = 'Worker'; //$row['jobtitle'];
          
      }
      //print_r($this->timesafe_title);
      
  }

  function get_customer_name($project_id) 
  {
      return $this->timesafe_company[$project_id];
  }
 
  function get_user_title($username)
  {
      return $this->timesafe_title[$username];      
  }

  /**
   Return project hours from TimeSafe in a mostly time report compatible format.
   
   The biggegst difference is that tagids is an array of tags instead
   of a single task. This means a bunch of code will have to check if
   is_array($tagids) and do something differently then...
  */
  function get_hours_timesafe($from, $to, $projectids, $users, $group=false) 
  {
      /* We ignore the group flag. It is not required, it's just a hint thet makes the VSD page
       significantly quicker, but Gustavo says that is not currently a
       priority, it's better to focus on getting a good new report
       tool up and running.
       */
      $query = "
select array(select m.tag_id from tr_tag_map m where m.entry_id=e.id) as tags,
'timesafe:' || e.id as id,
p.id as projectid,
to_char(e.perform_date, 'YYYY-MM-DD') as entered,
e.description,
u.name as username,
u.fullname as fullname,
60 * minutes as seconds,
'cool person' as jobtitle
from tr_entry e
join tr_project p 
    on e.project_id = p.id
join tr_user u
    on e.user_id = u.id";
      $where = array();
      $param = array();
      
      if ($users) {
          $u = array();
          $idx = 0;
          
          foreach($users as $user) {
              $u[]= "?";
              $param[] = $user;
              $idx++;
          }
          
          $where[] = "u.name in (" . implode(", ", $u) . ")";
      }

      if ($projectids) {
          $u = array();
          $idx = 0;
          
          foreach($projectids as $pid) {
              $u[]= "?";
              $param[] = $pid;
              $idx++;
          }
          
          // $where[] = "e.project_id in (" . implode(", ", $u) . ")";
          $where[] = "p.egs_id in (" . implode(", ", $u) . ")";
      }

    // Date
  
      if ($from != null && $to != null) {
          $where[] ="(e.perform_date, e.perform_date) overlaps (TO_TIMESTAMP(?, 'YYYY-MM-DD'), TO_TIMESTAMP(?, 'YYYY-MM-DD')+ interval '1 day')";
          $param[] = $from;
          $param[] = $to;
      }
  
      /*      
    // Group together hours if specified
    if($group) {
        $query .= "\nGROUP BY projecthours.username, billable, typeid, typename, overtime, fullname, jobtitle, extract(month from entered), extract(year from entered)
ORDER BY year, month, username, typeid";
    }
    else {
        $query = $query. " ORDER BY projecthours.entered";
    }
      */

      if(count($where)) {
          $query .= "\nwhere " . implode(" and ", $where);
      }
      
      $res =& $this->db->query ($query, $param);

    if (PEAR::isError ($res)) {
      print "<pre>$query</pre>";
      die ("Unable to get hours: " . $res->getMessage ());
    }

    // Fill hours array
    $hours = array();
    while ($res->fetchInto ($row, DB_FETCHMODE_ASSOC)) {
        $row['timesafe'] = true;

        if(strlen ($row['tags'])) {
            $row['tags'] = explode(',',substr($row['tags'],1,-1));
        }
        else {
            $row['tags'] = array();
        }
        
        $row['billable'] = in_array($this->timesafe_billable_id, $row['tags'])?'t':'f';
        $row['tagids'] = $row['tags'][0];
        
        $ht = $this->get_hour_types();
        $ht_idx = 1;
        
        if(in_array($this->timesafe_40_overtime_id, $row['tags']))
            $ht_idx = 2;
        if(in_array($this->timesafe_100_overtime_id, $row['tags']))
            $ht_idx = 3;
        
        $row['typename'] = $ht[$ht_idx];
        $row['typeid'] = $ht_idx;
        
        $row['tagids'] = $row['tags'];
	$row['tagnames'] = array();
	
        $row['customer'] = $this->get_customer_name($row['projectid']);
        $row['jobtitle'] = $this->get_user_title($row['username']);
        
        /*
        echo $this->timesafe_100_overtime_id;
        echo "<br>";
        print_r($row);
        echo "<br>";
        */

        $hours[] = $row;
    }

    //echo "Number of results : " . count($hours) ." \n+n";
    
    //    var_dump($hours);

    /*
    foreach( $hours as $k => $v )
      {
        echo "$k<br>";
        foreach( $v as $k2 => $v2 )
          echo "&nbsp;&nbsp;$k2 => $v2<br>";
      }
    */
    //    print_r($hours);
    
    return $hours;

  }

  function get_tasks($row)
  {      
      /*
      echo "TASKID: ";
      print_r($row['tagids']);
      echo "<br>";
      */
      if(is_array($row['tagids'])) {
	  $res=array();
	  foreach( $row['tagids'] as $tagid) {	    
	      $res[]= $this->timesafe_tag[$tagid];
	  }
	  return $res;
      }
      else 
      {
	  return explode('/', rtrim($row['fptt'],'/'));
      }
  }
  
      

  /**
   * Returns array of hours.
   *
   * @param from Date begin.
   * @param to Date end.
   * @param projectids  Array of project ids.
   * @param users Array of usernames.
   */
  function get_hours ($from, $to, $projectids, $users, $group=false) {
    return $this->get_hours_timesafe($from, $to, $projectids, $users, $group);
  }

  /**
   * Returns link to project.
   *
   * @param projectid ID of project to build link for.
   * @return String with URL.
   */
  function link_project ($projectid) {
      return EGS_BASE . "?module=projects&amp;action=view&amp;id=$projectid";
  }

  /**
   * Return full html link to project including project name.
   *
   * @param projectid ID of project to build link for.
   * @return <a href ... link.
   */
  function html_link_project ($projectid) {
      return "<a href='" . $this->link_project ($projectid) . "'>"
      . $this->cache_get_project ($projectid) . "</a>";
  }

  /**
   * Returns link to task.
   *
   * @param tagids ID of task to build link for.
   * @return String with URL.
   */
  function link_task ($projectid, $tagids) {
      return "http://freecode.no";
//      return EGS_BASE . "?module=projects&amp;action=viewtask&amp;projectid=$projectid&amp;tagids=$tagids";
  }

  /**
   * Return full html link to task including task name.
   *
   * @param projectid ID of project to build link for.
   * @param tagids ID of task to build link for.
   * @return <a href ... link.
   */
  function html_link_task ($projectid, $tagids) {
      $tasks = $this->cache_get_tasks ($projectid);
      return "<a href='" . $this->link_task ($projectid, $tagids) . "'>"
      . $tasks[$tagids]['name'] . "</a>";
  }

  /**
   * Returns link to hour.
   *
   * @param hourid ID of hour to build link for.
   * @return String with URL.
   */
  function link_hour ($projectid, $hourid) {
      return $this->timesafe_url($hourid);
  }

  function timesafe_url($entry=null, $user=null) 
  {
      
      $suffix = "";
      if($entry) {
          $entry2 = explode(':', $entry);
          if(count($entry2) == 2) {
              $entry=$entry2[1];
          }
          
          $suffix = "?entry=$entry";
      }
      else if($user) {
          $suffix = "?user=$user";          
      }
      
      
      return TIMESAFE_BASE . $suffix;
  }
  

  /**
   * Create task path.
   *
   * @param projectid ID of project to create path for.
   * @param tagids ID of task to create path for.
   * @return Complete <a href... with links to individual elements in path.
   */
  function create_task_path ($projectid, $tagids)
  {
      if(!is_array($tagids))
          die("tagids $tagids is not an array");
  
      $path = $this->cache_get_project ($projectid);
      $base_url = htmlEntities($this->timesafe_url());

      $url = "<a href='$base_url'>".htmlEntities($path)."</a>";

      if(count($tagids)) {
	  $url = "<a href='$base_url'>".($path)."</a>";
	  $txt = array();

	  foreach($tagids as $tag) {
	      $txt[]= $this->timesafe_tag[$tag];
	  }
	  $txt = " [" . implode(', ',$txt). "]";

	  $path .= $txt;
	  $url .= $txt;

      }

      return array($url, $path);
    }

  /**
   * Get name of project caching the result.
   *
   * @param projectid ID of project to get name for.
   * @return String name.
   */
  function cache_get_project ($projectid) {
    // Get from DB if not in cache
    if (! array_key_exists ($projectid, $this->cache_projects)) {
      // Build query
      $query = "SELECT id, name FROM tr_project WHERE id=$projectid";

      // Query database
      $res =& $this->db->query($query);
      if (PEAR::isError ($res)) {
        print "<pre>$query</pre>";
        die ("Unable to get projects: " . $res->getMessage ());
      }

      $res->fetchInto ($row, DB_FETCHMODE_ASSOC);
      $this->cache_projects[$projectid] = array ('name' => $row['name']);
    }

    return $this->cache_projects[$projectid]['name'];
  }

  /**
   * Get tasks for project caching result.
   *
   * @param projectid ID of project to get tasks for.
   * @return array(tagids => array(name => name, parenttagids => parenttagids))
   */
  function cache_get_tasks ($projectid) {
/*
    // Get from DB if not in cache
    if (! array_key_exists ($projectid, $this->cache_projects)) {
      $this->cache_get_project ($projectid);
    }
    if (! array_key_exists ('tasks', $this->cache_projects[$projectid])) {
      // Build query
      $query = "SELECT id, name, parenttagids FROM projecttask WHERE projectid=$projectid";

      // Query database
      $res =& $this->db->query($query);
      if (PEAR::isError ($res)) {
        print "<pre>$query</pre>";
        die ("Unable to get tasks: " . $res->getMessage ());
      }


      while ($res->fetchInto ($row, DB_FETCHMODE_ASSOC)) {
        $this->cache_projects[$projectid]['tasks'][$row['id']] = array('name' => $row['name'], 'parenttagids' => $row['parenttagids']);
      }
    }

    if (isset( $this->cache_projects[$projectid]['tasks']))
      return $this->cache_projects[$projectid]['tasks'];
    else
      return null;
*/
return array('name' => "SOMETASK", 'parenttagids' => 0);
  }
  
  function is_illness($entry) 
  {
      $tagids = $entry['tagids'];
      if(!is_array($tagids))
          $tagids = array($tagids);

      return count(array_intersect($this->timesafe_illness_id,$tagids))>0;
  }

  function is_vacation($entry) 
  {
      $tagids = $entry['tagids'];
      if(!is_array($tagids))
          $tagids = array($tagids);

      return count(array_intersect($this->timesafe_vacation_id,$tagids))>0;
  }

  /**
   Unpaid work leave, avspassering, unpaid paternety leave, etc..
   */
  function is_work_leave($tagids)
  {
      $tagids = $entry['tagids'];
      if(!is_array($tagids))
          $tagids = array($tagids);

      return count(array_intersect($this->timesafe_work_leave_id,$tagids))>0;      
  }
  
  /**
   Red days, sick, sick children and personal leave/welfare leave, etc.
   */
  function is_paying_nonwork($entry)
  {
      if($this->is_illness($entry)) 
      {
	  return true;
      }
      $tagids = $entry['tagids'];
      if(!is_array($tagids))
          $tagids = array($tagids);

      return count(array_intersect($this->timesafe_paying_nonwork_id,$tagids))>0;      
  }
  
  /**
   Red days, sick, sick children and personal leave/welfare leave, etc.
   */
  function is_unpaid_nonwork($entry)
  {
      $tagids = $entry['tagids'];
      if(!is_array($tagids))
          $tagids = array($tagids);

      return count(array_intersect($this->timesafe_unpaid_nonwork_id,$tagids))>0;      
  }
  
};

?>
