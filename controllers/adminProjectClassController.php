<?php
  /**
   Form for editing tags and tag groups.

   MAking these simple forms is way more work than it should. More can be automated.
   */
class AdminProjectClassController
extends Controller
{


    function viewRun()
    {

        $title = "Project class administration";
        util::setTitle($title);
	$content = '';

        $content .= "<h2>Project classes</h2>";
        $form = "";
        $form .= "<table class='striped'>";
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;

        $hidden = array('controller'=>'adminProjectClass','task'=>'saveProjectClass');
        
        foreach(array_merge(ProjectClass::getProjectClasses(),array(new ProjectClass(array()))) as $project_class) {
            $form .= "<tr>";
            if($project_class->id !== null)
                $hidden["project_class[$idx][id]"] = $project_class->id;
            $form .= "<td>".form::makeText("project_class[$idx][name]",$project_class->name)."</td>";
            $remove_name = htmlEncode("project_class[$idx][remove]");
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

    function saveProjectClassRun()
    {
        foreach(param('project_class') as $project_class){
            if (isset($project_class['remove'])) {
                ProjectClass::delete($project_class['id']);
            }
            else {
                if($project_class['name']) {
                    $pc = new ProjectClass(array());
                    //message("Input is " . sprint_r($project));
                    $pc->initFromArray($project_class);
                    //message("Project is " . sprint_r($pc));
                    $pc->save();
                }
            }
        }
        util::redirect(makeUrl(array('controller'=>'adminProjectClass','task'=>'view')));
    }
    
    function isAdmin() 
    {
        return true;
    }
    
}



?>