<?php
  /**
   Form for editing tags and tag groups.

   MAking these simple forms is way more work than it should. More can be automated.
   */
class AdminTagController
extends Controller
{


    function viewRun()
    {

        $title = "Tag administration";
        util::setTitle($title);
	$content = '';

        $content .= "<h2>Tags</h2>";

        $form = "";
        
        $form .= "<table class='striped'>";
        
        $form .= "<tr>";
        $form .= "<th class='name'>Name</th>";
        $form .= "<th>Color</th>";
        $form .= "<th>Project</th>";
        $form .= "<th>Project class</th>";
        $form .= "<th>Tag Group</th>";
        $form .= "<th>Recommended</th>";
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;

        $project_values =       array(null => 'None')+ Project::getProjectNames();
        $project_class_values = array(null=>'None')+ Project::getProjectClassNames();
        $group_values =         array(null => 'None')+TagGroup::getTagGroups();

        $hidden = array('controller'=>'adminTag','task'=>'saveTag');
        
        foreach(array_merge(Tag::fetch(),array(new Tag())) as $tag) {
            
            $form .= "<tr>";
            if($tag->id !== null)
                $hidden["tag[$idx][id]"] = $tag->id;

            $form .= "<td class='name'>".form::makeText("tag[$idx][name]",$tag->name)."</td>";
            $form .= "<td>".form::makeColorSelector("tag[$idx][color]",$tag->color, null)."</td>";
            $form .= "<td>".form::makeSelect("tag[$idx][project_id]",$project_values, $tag->project_id)."</td>";
            $form .= "<td>".form::makeSelect("tag[$idx][project_class_id]",$project_class_values, $tag->project_class_id)."</td>";
            $form .= "<td>".form::makeSelect("tag[$idx][group_id]",$group_values, $tag->group_id)."</td>";
            $form .= "<td>".form::makeCheckbox("tag[$idx][recommended]", $tag->recommended,'Recommended')."</td>";
            $remove_name = htmlEncode("tag[$idx][remove]");
            $form .= "<td><button type='submit' name='$remove_name' value='1'>Remove</button></td>";
            $form .= "</tr>";
            $idx++;
        }
        $form .= "</table>";
        $form .= "<div class='edit_buttons'>";
        $form .= "<button type='submit' id='save'>Save</button>";
        $form .= "</div>";
        
        $content .= form::makeForm($form, $hidden);
        
        $this->show(null, $content);

    }

    function saveTagRun()
    {
        foreach(param('tag') as $tag){

            foreach(array('group_id','project_id','project_class_id') as $el){
                if ($tag[$el] == '') {
                    $tag[$el] = null;
                }
            }
            
            if ($tag['remove']==1) {
                Tag::delete($tag['id']);
            }
            else {
                if($tag['name']) {
                    $t = new Tag();
                    //message("Input is " . sprint_r($tag));
                    $t->initFromArray($tag);
                    //message("Tag is " . sprint_r($t));
                    $t->save();
                }
            }
        }
        util::redirect(makeUrl(array('controller'=>'adminTag','task'=>'view')));
    }
    
    function isAdmin() 
    {
        return true;
    }
    
}



?>