<?php

  /**
   Project syncing with Egs. 
  */
class Egs
{

    function __construct($dsn) 
    {
        dbMaker::makeDb('egsDb');        
        if(!egsDb::init($dsn) ) {
            die("EGS database problem. Could not connect to the database.");
        }        
    }

    function userWildcard()
    {
        $u = User::$user->name;
        $sub = substr($u, 0, 3);
        if ($sub != 'int') {
            $sub = substr($u, 0, 4);
        }
        
        return $sub. "%";
    }
    
    function getProjects() {
        // Build query
        $query = "
select p.id, p.name, p.startdate as start_date, owner
from project p
where p.archived='f'
    and p.owner like :user_wildcard
order by name
";
        // Limit users to the same class of users as currently logged in
        $param = array(':user_wildcard' => $this->userWildcard());
        
        // Fill users array
        $projects = array();
        foreach(egsDb::fetchList($query, $param) as $row) {
            $projects[$row['id']] = $row;
        }
        return $projects;
    }

    function main()
    {
        $egs = $this->getProjects();
        $map = Project::getEgsMapping();
        
        foreach($egs as $egs_id => $egs) {
            $external = preg_match('/^Div[1-7][a-gA-G]? *[:-]/',$egs['name'])?'f':'t';

            if( array_key_exists($egs_id, $map) ) {
                if(param('reload_projects')==1) {
                    Project::update($map[$egs_id],
                                    $egs['name'],
                                    $egs_id, 
                                    $egs['start_date'],
                                    $external);
                }
                continue;
            }
            Project::add($egs['name'], 
                         $egs_id, 
                         $egs['start_date'],
                         $external);
        }
    }

}

$egs = new Egs(EGS_DSN);
$egs->main();

?>