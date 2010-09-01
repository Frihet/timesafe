<?php
  /**
   Form for editing tags and tag groups.

   MAking these simple forms is way more work than it should. More can be automated.
   */
class AdminTagGroupController
extends Controller
{


    function viewRun()
    {

        $title = "tag group administration";
        util::setTitle($title);
	$content = '';


        $content .= "<h2>Tag groups</h2>";        
        
        $form = "";
        $form .= "<table class='striped'>";
        
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th>Required</th>";
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;
        $hidden = array('controller'=>'adminTagGroup','task'=>'saveTagGroup');
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
                $t = new TagGroup(array('name'=>$name,'id'=>$id,'required'=>$required));
                $t->save();
            }
            $idx++;
        }
        
        $name_new = param('tag_group_name_new');
        $required_new = param('tag_group_required_new');
        if ($name_new) {
            $t = new TagGroup(array('name'=>$name_new,'required'=>$required_new));
            $t->save();
        }
        
        util::redirect(makeUrl(array('controller'=>'adminTagGroup','task'=>'view')));
        
    }
    
    
    function isAdmin() 
    {
        return true;
    }
    
}



?>