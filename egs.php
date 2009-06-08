<?php



class Egs
{

    function __construct() 
    {
        
    }

    function accessString($table = 'project',$field='owner') 
    {
        return " AND {$table}.{$field} like " . $this->userWildcard();
    }
  
    function userWildcard()
    {
        $u = User::$user->name;
        $sub = substr($u, 0, 3);
        if ($sub != 'int') {
            $sub = substr($u, 0, 4);
        }
        
        $res = "'" . $this->db->escapeSimple($sub) . "%'";
        return $res;
        
    }




    function getProjects() {
        // Build query
        $query = "SELECT project.id, project.name FROM project, projectaccess WHERE project.archived='f' AND project.id=projectaccess.projectid";
        
        // Limit users to the same class of users as currently logged in
        $query .= $this->access_string();
        
        $query .= " ORDER BY name";
        
        // Query database
        $res =& $this->db->query($query);
        if (PEAR::isError($res)) {
            print "<pre>$query</pre>";
            die ($res->getMessage());
        }
        // Fill users array
        $projects = array();
        while ($res->fetchInto ($row, DB_FETCHMODE_ASSOC)) {
            $projects[$row['id']] = $row['name'];
        }
        
        return $projects;
  }

    

}


?>