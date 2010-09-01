<?php
  /**
   Form for editing tags and tag groups.

   MAking these simple forms is way more work than it should. More can be automated.
   */
class AdminProjectController
extends Controller
{


    function viewRun()
    {

        $title = "Project administration";
        util::setTitle($title);
	$content = '';

        $content .= "<h2>Projects</h2>";

        $hidden = array('controller'=>'adminProject','task'=>'saveProject');        
        $class_values = Project::getProjectClassNames();

        $form = "";
        $form .= "<table class='striped'>";
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th>Start date</th>";
	foreach ($class_values as $class_id => $class_name) {
	 $form .= "<th class='membership_col'><div>{$class_name}</div></td>";
	}
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;


        foreach(array_merge(Project::getProjects(),array(new Project(array()))) as $project) {            
            $form .= "<tr>";
            if($project->id !== null)
                $hidden["project[$idx][id]"] = $project->id;
            $form .= "<td>".form::makeText("project[$idx][name]",$project->name)."</td>";
            $form .= "<td>".form::makeText("project[$idx][start_date]", date("r", $project->start_date))."</td>";

	    foreach ($class_values as $class_id => $class_name) {
	     $form .= "<td>" . form::makeListCheckbox("project[$idx][_project_class]", $class_id, in_array($class_id, $project->getProjectClass()), "", null) . "</td>";
	    }
            $remove_name = htmlEncode("project[$idx][remove]");
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
    
    function saveProjectRun()
    {
        foreach(param('project') as $project){
            if ($project['remove']==1) {
                Project::delete($project['id']);
            }
            else {
                if($project['name']) {
                    $p = new Project(array());
                    //message("Input is " . sprint_r($project));
                    $p->initFromArray($project);
                    //message("Project is " . sprint_r($p));
                    $p->save();
                }
            }
        }
        util::redirect(makeUrl(array('controller'=>'adminProject','task'=>'view')));
    }
    
    function isAdmin() 
    {
        return true;
    }
    
}



?>