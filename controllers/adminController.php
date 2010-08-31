<?php
  /**
   Form for editing tags and tag groups.

   MAking these simple forms is way more work than it should. More can be automated.
   */
class AdminController
extends Controller
{


    function viewRun()
    {

        $title = "Administration";
        util::setTitle($title);
	$content = '';


        $content .= "<h2>Users</h2>";
        $form = "";
        $form .= "<table class='striped'>";
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th>Fullname</th>";
        $form .= "<th>Password</th>";
        $form .= "<th>Projects</th>";
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;

        $hidden = array('controller'=>'admin','task'=>'saveUsers');
	$project_values = array(null => 'None')+ User::getProjectNames();
        
        foreach(array_merge(User::getAllUsers(),array(new User(null, '', '', ''))) as $user) {
            $form .= "<tr>";
            if($user->id !== null)
                $hidden["user[$idx][id]"] = $user->id;
            $form .= "<td>".form::makeText("user[$idx][name]",$user->name)."</td>";
            $form .= "<td>".form::makeText("user[$idx][fullname]",$user->fullname)."</td>";
            $form .= "<td>".form::makeText("user[$idx][password]",'')."</td>";
            $form .= "<td>".form::makeSelect("user[$idx][_projects]", $project_values, $user->getProjects()). "</td>";
            $remove_name = htmlEncode("user[$idx][remove]");
            $form .= "<td><button type='submit' name='$remove_name' value='1'>Remove</button></td>";
            $form .= "</tr>";
            $idx++;
        }
        $form .= "</table>";
        $form .= "<div class='edit_buttons'>";
        $form .= "<button type='submit' id='save'>Save</button>";
        $form .= "</div>";
        
        $content .= form::makeForm($form, $hidden);



        $content .= "<h2>Project classes</h2>";
        $form = "";
        $form .= "<table class='striped'>";
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;

        $hidden = array('controller'=>'admin','task'=>'saveProjectClass');
        
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



        $content .= "<h2>Projects</h2>";
        $form = "";
        $form .= "<table class='striped'>";
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th>Start date</th>";
        $form .= "<th>Classes</th>";
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;

        $hidden = array('controller'=>'admin','task'=>'saveProject');
        
        $class_values = array(null => 'None')+ Project::getProjectClassNames();

        foreach(array_merge(Project::getProjects(),array(new Project(array()))) as $project) {            
            $form .= "<tr>";
            if($project->id !== null)
                $hidden["project[$idx][id]"] = $project->id;
            $form .= "<td>".form::makeText("project[$idx][name]",$project->name)."</td>";
            $form .= "<td>".form::makeText("project[$idx][start_date]", date("r", $project->start_date))."</td>";
            $form .= "<td>".form::makeSelect("project[$idx][_project_class]",$class_values, $project->getProjectClass())."</td>";
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



        $content .= "<h2>Tags</h2>";

        $form = "";
        
        $form .= "<table class='striped'>";
        
        $form .= "<tr>";
        $form .= "<th>Name</th>";
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

        $hidden = array('controller'=>'admin','task'=>'saveTag');
        
        foreach(array_merge(Tag::fetch(),array(new Tag())) as $tag) {
            
            $form .= "<tr>";
            if($tag->id !== null)
                $hidden["tag[$idx][id]"] = $tag->id;

            $form .= "<td>".form::makeText("tag[$idx][name]",$tag->name)."</td>";
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
        
        $content .= "<h2>Tag groups</h2>";        
        
        $form = "";
        $form .= "<table class='striped'>";
        
        $form .= "<tr>";
        $form .= "<th>Name</th>";
        $form .= "<th>Required</th>";
        $form .= "<th></th>";
        $form .= "</tr>";
        $idx = 0;
        $hidden = array('controller'=>'admin','task'=>'saveTagGroup');
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

    function saveUsersRun()
    {
        foreach(param('user') as $usr){
            if (isset($usr['remove'])) {
                $usr_obj = new User($usr['id'], '', '', '');
		$usr_obj->delete();
            }
            else {
                if($usr['name']) {
		    if (!isset($usr['_projects'])) $usr['_projects'] = array();
                    $pc = new User(null, '', '', '');
                    //message("Input is " . sprint_r($project));
                    $pc->initFromArray($usr);
                    //message("Project is " . sprint_r($pc));
                    $pc->save();
                }
            }
        }
        //util::redirect(makeUrl(array('controller'=>'admin','task'=>'view')));
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
        util::redirect(makeUrl(array('controller'=>'admin','task'=>'view')));
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
        util::redirect(makeUrl(array('controller'=>'admin','task'=>'view')));
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
        util::redirect(makeUrl(array('controller'=>'admin','task'=>'view')));
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
        
        util::redirect(makeUrl(array('controller'=>'admin','task'=>'view')));
        
    }
    
    
    function isAdmin() 
    {
        return true;
    }
    
}



?>