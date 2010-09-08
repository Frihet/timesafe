<?php

  /** The php backend for the main editing stuff. Mostly just creates a bunch of json data. All
   the exciting stuff is JavaScript. Which is scary.
   */
class EditorController
extends Controller
{
    

    function generateDates() 
    {
        
        $mid_day_time = Entry::getBaseDate();
        
        $res = array();
        
        for($i=Entry::getDateCount()-1; $i >= 0; $i--) {
            $tm = $mid_day_time - 3600*24*$i;
            $foo=new stdClass();
            $foo->month = (int)date('m', $tm);
            $foo->year = (int)date('Y', $tm);
            $foo->day = (int)date('d', $tm);
            $foo->timestamp = $tm;
            $wd = (int)date('w', $tm);
            $foo->workday = ($wd>0) && ($wd <6);
            $res[] = $foo;
            
        }
        return $res;
    }
    
    function nextBaseDateStr()
    {
        $tm = Entry::getBaseDate();
        return date('Y-m-d', $tm + 7* 3600*24);
    }

    function prevBaseDateStr()
    {
        $tm = Entry::getBaseDate();
        return date('Y-m-d', $tm - 7* 3600*24);
    }

    function nowBaseDateStr()
    {
        return date('Y-m-d');
    }

    function populateTimeSlots()
    {
        $res = array();
        foreach( Project::getProjects() as $project_id => $orig) {
            $project = new StdClass();
            $project->project_id = $project_id;
            $project->project_name = $orig->name;
            $project->slot = array();
            $project->external = in_array(2, $orig->getProjectClass());
            $project->is_resource = $orig->is_resource;
            
            $res[$project_id] = $project;
        }
        
        foreach(Entry::fetch() as $entry) {
            $proj = $res[$entry->project_id];
            $idx = -1;
            $offset = "".$entry->getDateOffset();
            for ($i=0; $i<count($proj->slot); $i++) {
                $slot = $proj->slot[$i];

                if ($slot && !array_key_exists($offset,$slot)) {
                    $idx = $i;
                    break;
                }
            }
            
            if ($idx == -1) {
                $idx = count($proj->slot);
                $proj->slot[]=array();
            }
            
            $proj->slot[$idx][$offset] = $entry;
            
        }
        
        /*
         Make sure there is always at least one slot for each project
        */
        foreach($res as $project) {
            if (!count($project->slot)) {
                //$project->slot[] = array();
            }
        }

        return array_values($res);
    }

    /**
     Create the json data. and the user selection and forward/backward buttons.
     */
    function viewRun()
    {
        $content = "";
        $content .= "<div id='debug' style='position:absolute;right: 100px;'></div>";
        $content .= "<div id='dnd_text' style='position:absolute;left: 0px;top: 0px; display:none;'></div>";
        $form = "";
        
        if (param('entry')!==null) {
	    /** It has been specified that we want to view a specific
	     entry - we must figure out what user this entry belongs
	     to, and set that to the current user.
	     */
	    /**
	     TODO: Also chenge date interval
	     */
            $e = param('entry');
	    $row = db::fetchRow('
select u.name as name, e.perform_date as perform_date 
from tr_entry e 
join tr_user u 
on e.user_id=u.id 
where e.id=:id', array(':id'=>$e));
	    $name = $row['name'];
            
	    if($name) 
	    {
		$a = User::getAllUsers();
		User::$user = $a[$name];
	    }
            $date = $row['perform_date'];
            $_REQUEST['date'] = $date;
        }
	$username = User::$user->name;
        util::setTitle("Editing hours for " . User::$user->fullname);
	
        $next = self::nextBaseDateStr();
        $prev = self::prevBaseDateStr();
        $now = self::nowBaseDateStr();
	$user = form::makeSelect('user', form::makeSelectList(User::getAllUsers(),'name', 'fullname'),$username, null, array('onchange'=>'submit();'));
        
	$user_form = form::makeForm($user, param('date')?array('date'=>param('date')):array(), 'get');

        $prev_link = makeUrl(array('date'=>$prev));
        $next_link = makeUrl(array('date'=>$next));
        $now_link = makeUrl(array('date'=>$now));
        $content .= "<p><a href='$prev_link'>«earlier</a> <a href='$now_link'>today</a>  <a href='$next_link'>later»</a></p>";
        $content .= $user_form;
	
        $content .= "<p><input type='checkbox' id='show_all' onchange='TimeSafe.updateVisibility();'/><label for='show_all'>Show all projects</label></p>";
        
	$form .= "<table id='time' class='time'><thead><tr>";
        $dates = $this->generateDates();
        
        $form .= "<th></th>";
        $form .= "<th></th>";
        $date_idx=0;
        
        foreach($dates as $date) {
            $class = $date->workday ? "weekday" : "weekend";
            
            $form .= "<th class='$class'>".date("D<\\b\\r>M<\\b\\r>j",$date->timestamp)."</th>";
            //$form .= "<th class='$class'>".date("n/m",$date->timestamp)."</th>";

            $date = $date->year."-".$date->month."-".$date->day;
            $form .= "<input type='hidden' name='date_$date_idx' value='$date'/>";
            
            $date_idx++;
        }
        
        $time_slots = $this->populateTimeSlots();
        
        $project_idx = 0;
        $form .= "</tr></thead>\n<tbodyy id='time_bodyy'></tbodyy>\n";

        $form .= "<tfoot>\n<tr>\n<th>Sum</th><td>";
        
        foreach(range(0, count($dates)-1) as $idx) {
            $form .= "<td id='hour_sum_$idx' class='hour_sum'></td>";
        }
        
        $form .= "</tr>\n</tfoot>\n";
        $form .= "</table>";
        $form .= "<div class='edit_buttons'><button type='submit' id='save'>Save</button></div><span id='notification_global'></span>";

        $date_count = Entry::getDateCount();

        $offset = (12-date('N',Entry::getBaseDate()))%7;
        
        $form .= "\n<script>\nvar TimeSafeData = {
        weekendOffset: $offset,
        days: $date_count,
        projects: ";
        
        $tag_lookup = array();
        foreach( $time_slots as $project_data) {
            $project_data->tags = tag::getTags($project_data->project_id);
        }
        $form .= json_encode($time_slots);
        
        $form .= ",\n\ttagGroups: " . json_encode(TagGroup::fetch());
        $entry = param('entry');
        if( $entry) {
            $form .= ",\n\tentry: " . $entry;
        }
        
        $form .= "\n};\n</script>\n\n";

        $from=date('Y-m-d',Entry::getBaseDate()-3600*24*(Entry::getDateCount()-1));
        $to=date('Y-m-d',Entry::getBaseDate()+3600*24);
        
        $content .= form::makeForm($form,array('controller'=>'editor', 'task'=>'save','user'=>$username));
	$content .= "<div class='figure'><img src='" . makeUrl(array('controller'=>'graph')) . "' /><em class='caption'>Figure 1: Work performed. Warning! This is the number of hours stored on the server when this page was generated. The graph does not reflect any unsaved edits.</em></div>";
        

	//$content .= $this->entryListRun();
        //$content .= "<button type='button' onclick='TimeSafe.addProjectLines();'>Add</button>";
        
        $this->show(null, $content);

    }
    
    /**
     Create a list of all entries in the database. Currently unused.
     */
    function entryListRun()
    {

        $content = "";

        $entry_list = Entry::fetch();
        if (!count($entry_list)) {
            return "";
        }        
        
        $content .= "
<table class='striped'>
<tr>
<th>Project</th>
<th>Tags</th>
<th>Date</th>
<th>Time</th>
<th>Description</th>
</tr>
";
        foreach ($entry_list as $entry) {
            $project = htmlEncode(Project::getName($entry->project_id));
            $tags = array();
            
            if ($entry->_tags) {
                foreach($entry->_tags as $tag_id) {
                    
                    $tags[] = htmlEncode(Tag::getName($tag_id));
                }
            }
            $tags = implode(", ", $tags);
            
            $date = date('Y-m-d',$entry->perform_date);
            $time = util::formatTime($entry->minutes);
            $description = $entry->description;
            
            $content .= "<tr>
<td>$project</td>
<td>$tags</td>
<td>$date</td>
<td>$time</td>
<td>$description</td>
</tr>";
            
        }

        $content .= "
</table>
";
        return $content;
    
    }
    
    /** Save all updates. This is currently kind of slow because is
     actually saves _all_ entries, not just the saved ones. A future
     version should use a naming hierarchy that php can unserialize
     automatically into a double-array, so that the saving code could
     be simplified a bit.
     */
    function saveRun()
    {
        /*
	 echo "<pre>";
	 print_r($_REQUEST);
	 exit(1);
        */
        for($project_idx=0; ($project_id = param("project_$project_idx"))!== null; $project_idx++) {
            
            for ($slot_idx = 0;; $slot_idx++) {
                if (param("time_{$project_idx}_{$slot_idx}_0") === null) {
                    break;
                }
                
                for ($day_idx=0;; $day_idx++) {
                    $base_id = "{$project_idx}_{$slot_idx}_{$day_idx}";
                    
                    $time = param("time_$base_id");
                    $description = param("description_$base_id");
                    $tag = param("tag_$base_id");
                    $entry_id = param("entry_id_$base_id",-1);
                    
                    $perform_date = param("date_$day_idx");
                    
                    if ($time === null) {
                        break;
                    }

                    $minutes = util::unformatTime($time);

                    if ($minutes == 0) {
                        if($entry_id >= 0) {
                            $e = new Entry();
                            $e->id = $entry_id;
                            $e->delete();
                        }
                        continue;
                    }
                    /** If there is no description, then we have no
                     complete entry. This is likely because the
                     sidebar was never created for this entry. Move
                     on.
                     */
                    if ($description === null) {
                        continue;
                    }
                    
		    $e = new Entry();
                    $e->initFromArray(array('id'=>$entry_id>=0?$entry_id:null,
                                            'description'=>$description,
                                            'minutes'=>$minutes, 
                                            'project_id'=>$project_id, 
                                            'user_id'=>User::$user->id,
                                            'perform_date'=>$perform_date));
                    
                    $e->setTags($tag);
                    $e->save();
                    
                }
            }
        }
        message("Hours have been saved");
        util::redirect(makeUrl(User::$me!=User::$user?array('user'=>param('user')):array('user'=>null)));        
    }
      
}


?>