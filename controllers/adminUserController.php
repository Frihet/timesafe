<?php
  /**
   Form for editing tags and tag groups.

   MAking these simple forms is way more work than it should. More can be automated.
   */
class AdminUserController
extends Controller
{


    function viewRun()
    {

        $title = "User administration";
        util::setTitle($title);
	$content = '';


        $content .= "<h2>Users</h2>";

        $hidden = array('controller'=>'adminUser','task'=>'saveUsers');
	$project_values = User::getProjectNames();
        
        $form = "";
        $form .= "<table class='striped'>";
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th>Fullname</th>";
        $form .= "<th>Password</th>";
	foreach ($project_values as $project_id => $project_name) {
	 $form .= "<th>{$project_name}</td>";
	}
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;

        foreach(array_merge(User::getAllUsers(),array(new User(null, '', '', ''))) as $usr) {
            $form .= "<tr>";
            if($usr->id !== null)
                $hidden["usr[$idx][id]"] = $usr->id;
            $form .= "<td>".form::makeText("usr[$idx][name]",$usr->name)."</td>";
            $form .= "<td>".form::makeText("usr[$idx][fullname]",$usr->fullname)."</td>";
            $form .= "<td>".form::makeText("usr[$idx][password]",'')."</td>";

	    foreach ($project_values as $project_id => $project_name) {
	     $form .= "<td>" . form::makeListCheckbox("usr[$idx][_projects]", $project_id, in_array($project_id, $usr->getProjects()), "", null) . "</td>";
	    }
            $remove_name = htmlEncode("usr[$idx][remove]");
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

    function saveUsersRun()
    {
        foreach(param('usr') as $usr){
            if (isset($usr['remove'])) {
                $usr_obj = new User($usr['id'], '', '', '');
		$usr_obj->delete();
            }
            else {
                if($usr['name']) {
	            if ($usr['password'] == '')
		     unset($usr['password']);
		    else
		     $usr['password'] = md5($usr['password']);
		    if (!isset($usr['_projects'])) $usr['_projects'] = array();
                    $pc = new User($usr['id']);
                    //message("Input is " . sprint_r($project));
                    $pc->initFromArray($usr);
                    //message("User is " . sprint_r($pc));
                    $pc->save();
                }
            }
        }
        util::redirect(makeUrl(array('controller'=>'adminUser','task'=>'view')));
    }
    
    function isAdmin() 
    {
        return true;
    }
    
}



?>