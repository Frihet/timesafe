<?php

  /** The php backend. Mostly just creates a bunch of json data. All
   the exciting stuff is JavaScript. Which is scary.
   */
class TREditorController
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

    function populateTimeSlots()
    {
        $res = array();
        foreach( Project::getProjects() as $project_id => $orig) {
            $project = new StdClass();
            $project->project_id = $project_id;
            $project->project_name = $orig->name;
            $project->slot = array();
            $project->external = $orig->external;
            $project->is_new = $orig->start_date> (time()-3600*24*7*4);
            
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

    function viewRun()
    {
        $content = "";
        $form = "";
	$username = param('user',$_SERVER['PHP_AUTH_USER']);
	
        $next = self::nextBaseDateStr();
        $prev = self::prevBaseDateStr();
	$user = form::makeSelect('user', form::makeSelectList(User::getAllUsers(),'name', 'fullname'),$username, null, array('onchange'=>'submit();'));
	$user_form = form::makeForm($user, array(), 'get');

        $content .= "<p><a href='?date=$prev'>«earlier</a> <a href='?date=$next'>later»</a></p>";
        $content .= $user_form;
	
        $content .= "<p><input type='checkbox' id='show_all' onchange='TimeSafe.updateVisibility();'/><label for='show_all'>Show all projects</label></p>";
        
	$form .= "<table id='time' class='time'><thead><tr>";
        $dates = $this->generateDates();
        
        $form .= "<th></th>";
        $form .= "<th></th>";
        $date_idx=0;
        
        foreach($dates as $date) {
            $class = $date->workday ? "weekday" : "weekend";
            
            $form .= "<th class='$class'>{$date->day}/{$date->month}</th>";
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
        
        $form .= "\n};\nTimeSafe.addProjectLines();\n</script>\n\n";

        $from=date('Y-m-d',Entry::getBaseDate()-3600*24*(Entry::getDateCount()-1));
        $to=date('Y-m-d',Entry::getBaseDate());
        
        $content .= form::makeForm($form,array('action'=>'trEditor', 'task'=>'save','user'=>$username));
	$content .= "<div class='figure'><img src='../time_report/?type=histogram&from=$from&to=$to&users[]=$username' /><em class='caption'>Figure 1: Work performed. Warning! This is the number of hours currently stored on the server. This graph does not reflect any unsaved edits.</em></div>";
        

	//$content .= $this->entryListRun();
        //$content .= "<button type='button' onclick='TimeSafe.addProjectLines();'>Add</button>";
        
        $this->show(null, $content);

    }

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