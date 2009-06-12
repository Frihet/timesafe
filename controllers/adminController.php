<?php

class AdminController
extends Controller
{


    function viewRun()
    {

        $title = "Administration";
	
        util::setTitle($title);
        $content = "<h2>Tags</h2>";

        $form = "";
        
        $form .= "<table class='striped'>";
        
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th>Project</th>";
        $form .= "<th>Tag Group</th>";
        $form .= "<th>Recommended</th>";
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;

        $project_values = array('all' => 'All','internal'=>'All internal','external'=>'All external')+Project::getProjectNames();
        $group_values = array('none' => 'None')+TagGroup::getTagGroups();
        
        $hidden = array('action'=>'admin','task'=>'saveTag');
        
        foreach(Tag::fetch() as $tag) {
            
            $form .= "<tr>";
            $hidden['tag_id_'.$idx] = $tag->id;
            $project_id_or_visibility = $tag->project_id;
            if ($tag->visibility == TR_VISIBILITY_INTERNAL) {
                $project_id_or_visibility = 'internal';
            } else if ($tag->visibility == TR_VISIBILITY_EXTERNAL) {
                $project_id_or_visibility = 'external';
            } else if ($tag->visibility == TR_VISIBILITY_ALL) {
                $project_id_or_visibility = 'all';
            }
                

            $form .= "<td>".form::makeText('tag_name_'.$idx,$tag->name)."</td>";
            $form .= "<td>".form::makeSelect('project_id_'.$idx,$project_values, $project_id_or_visibility)."</td>";
            $form .= "<td>".form::makeSelect('tag_group_id_'.$idx,$group_values, $tag->group_id===null?'none':$tag->group_id)."</td>";
            $form .= "<td>".form::makeCheckbox('recommended_'.$idx, $tag->recommended,'Recommended')."</td>";
            $remove_name = "remove_$idx";
            $form .= "<td><button type='submit' name='$remove_name' value='1'>Remove</button></td>";
            
            $form .= "</tr>";
            $idx++;
        }
        $form .= "<tr>";
        $form .= "<td>".form::makeText('tag_name_new',"")."</td>";
        $form .= "<td>".form::makeSelect('project_id_new',$project_values)."</td>";
        $form .= "<td>".form::makeSelect('tag_group_id_new',$group_values)."</td>";
        $form .= "<td>".form::makeCheckbox('recommended_new', false, 'Recommended')."</td>";
        $form .= "<td><button type='submit'>Add</button></td>";
        $form .= "</tr>";
        
        $form .= "</table>";
        $form .= "<div class='edit_buttons'>";
        $form .= "<button type='submit' id='save'>Save</button>";
        $form .= "</div>";
        
        $content .= form::makeForm($form, $hidden);
        



        $content .= "<h2>Tag groups</h2>";        
        
        $form = "";
        $form .= "<table class='striped'>";
        
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th>Required</th>";
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;
        $hidden = array('action'=>'admin','task'=>'saveTagGroup');
        foreach(TagGroup::fetch() as $tag_group) {
            
            $form .= "<tr>";
            $hidden['tag_group_id_'.$idx]=$tag_group->id;
            $form .= "<td>".form::makeText('tag_group_name_'.$idx,$tag_group->name)."</td>";

            $checked_str = $tag_group->required?'checked':'';
            
            $form .= "<td>".form::makeCheckbox("tag_group_required_$idx",$tag_group->required, "Required")."</td>";
            $remove_name = "remove_$idx";
            $form .= "<td><button type='submit' name='$remove_name' value='1'>Remove</button></td>";
            
            $form .= "</tr>";
            $idx++;
        }
        $form .= "<tr>";
        $form .= "<td>".form::makeText('tag_group_name_new',"")."</td>";
        $form .= "<td>".form::makeCheckbox('tag_group_required_new', false, 'Required')."</td>";
        $form .= "<td><button type='submit'>Add</button></td>";
        $form .= "</tr>";
        
        $form .= "</table>";
        $form .= "<div class='edit_buttons'>";
        $form .= "<button type='submit' id='save'>Save</button>";
        $form .= "</div>";
        
        $content .= form::makeForm($form, $hidden);

        $this->show(null, $content);

    }

    function saveTagRun()
    {
        $idx = 0;
        
        while(true) {
            $tag_id_key = "tag_id_$idx";
            if (!array_key_exists($tag_id_key, $_REQUEST)) {
                break;
            }
            $tag_id = param($tag_id_key);
            $name = param("tag_name_$idx");
            $recommended = param("recommended_$idx");
            list($project_id, $visibility) = $this->parseProjectId(param("project_id_$idx"));

            $tag_group_id = param("tag_group_id_$idx");
            if ($tag_group_id == 'none') {
                $tag_group_id = null;
            }
            
            if (param("remove_$idx")) {
                Tag::delete($tag_id);
            }
            else {
                $t = new Tag();
                $t->initFromArray(array('name'=>$name,'id'=>$tag_id,'project_id'=>$project_id, 'visibility'=>$visibility, 'group_id' => $tag_group_id, 'recommended'=>$recommended));
                //message("TJOHO $recommended <br>");
                
                $t->update();
            }
            
            $idx++;
        }

        $name = param('tag_name_new');
        list($project_id, $visibility) = $this->parseProjectId(param("project_id_new"));
        $tag_group_id = param("tag_group_id_new");
        $recommended = param("recommended_new");
        if ($tag_group_id == 'none') {
            $tag_group_id = null;
        }
        
        if ($name) {
            $t = new Tag();
            $t->initFromArray(array('name'=>$name,'project_id'=>$project_id, 'visibility'=>$visibility, 'group_id' => $tag_group_id, 'recommended'=>$tag_recommended));
            
            $t->create();
        }

        util::redirect(makeUrl(array('action'=>'admin','task'=>'view')));
        
    }
    
    function parseProjectId($project_id) 
    {
        $visibility = TR_VISIBILITY_PROJECT;
        if ($project_id == 'all') {
                $visibility = TR_VISIBILITY_ALL;
                $project_id = null;
        }
        else if ($project_id == 'internal') {
            $visibility = TR_VISIBILITY_INTERNAL;
            $project_id = null;
        }
        else if ($project_id == 'external') {
            $visibility = TR_VISIBILITY_EXTERNAL;
            $project_id = null;
        }
        return array($project_id, $visibility);
    }
        
    function saveTagGroupRun()
    {
        $idx = 0;
        
        while(true) {
            $id_key = "tag_group_id_$idx";
            
            if (!array_key_exists($id_key, $_REQUEST)) {
                break;
            }
            $id = param($id_key);
            $name = param("tag_group_name_$idx");
            $required = param("tag_group_required_$idx");
            
            if (param("remove_$idx")) {
                TagGroup::delete($id);
            }
            else {
                $t = new TagGroup();
                $t->initFromArray(array('name'=>$name,'id'=>$id,'required'=>$required?true:false));
                $t->update();
            }
            $idx++;
        }
        
        $name_new = param('tag_group_name_new');
        $required_new = param('tag_group_required_new');
        if ($name_new) {
            $t = new TagGroup();
            $t->initFromArray(array('name'=>$name_new,'required'=>$required_new?true:false));
            $t->create();
        }
        
        util::redirect(makeUrl(array('action'=>'admin','task'=>'view')));
        
    }
    
    
    function isAdmin() 
    {
        return true;
    }
    
}



?>