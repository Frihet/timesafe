<?php

class TRAdminEditor
extends Editor
{

    function viewWrite()
    {
        $title = "Administration";
	
        setTitle($title);
        $content .= "<h1>$title</h1>";

        $form = "";
        
        $form .= "<table class='striped'>";
        
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th>Project</th>";
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;
        
        foreach(Tag::fetch() as $tag) {
            
            $form .= "<tr>";
            $form .= form::makeHidden('tag_id_'.$idx,$tag->id);
            
            $form .= "<td>".form::makeText('tag_name_'.$idx,$tag->name)."</td>";
            $form .= "<td>".form::makeSelect('project_id_'.$idx,array(-1 => 'All')+Project::getProjects(), $tag->project_id===null?-1:$tag->project_id)."</td>";
            $remove_name = "remove_$idx";
            $form .= "<td><button type='submit' name='$remove_name' value='1'>Remove</button></td>";
            
            $form .= "</tr>";
            $idx++;
        }
        $form .= "<tr>";
        $form .= "<td>".form::makeText('tag_name_new',"")."</td>";
        $form .= "<td>".form::makeSelect('project_id_new',array(-1 => 'All')+Project::getProjects())."</td>";
        $form .= "<td><button type='submit'>Add</button></td>";
        $form .= "</tr>";
        
        $form .= "</table>";
        $form .= "<div class='edit_buttons'>";
        $form .= "<button type='submit' id='save'>Save</button>";
        $form .= "</div>";
        
        $content .= makeForm($form, array('action'=>'admin','task'=>'save'));
        
        $this->show($content);
    }

    function saveWrite()
    {
        $idx = 0;
        
        while(true) {
            $tag_id_key = "tag_id_$idx";
            
            if (!array_key_exists($tag_id_key, $_REQUEST)) {
                break;
            }
            $tag_id = param($tag_id_key);
            $name = param("tag_name_$idx");
            $project_id = param("project_id_$idx");

            if (param("remove_$idx")) {
                Tag::delete($tag_id);
                

            }
            else {
                $t = new Tag();
                $t->initFromArray(array('name'=>$name,'id'=>$tag_id,'project_id'=>$project_id==-1?null:$project_id));
                $t->update();
                
            }
            
            $idx++;
        }
        $name_new = param('tag_name_new');
        $project_id_new = param('project_id_new');
        if ($name_new) {
            $t = new Tag();
            $t->initFromArray(array('name'=>$name_new,'project_id'=>$project_id_new==-1?null:$project_id_new));
            $t->create();
        }

        redirect(makeUrl(array('action'=>'admin','task'=>'view')));
        
    }
    
    
    function isAdmin() 
    {
        return true;
    }
    
}



?>