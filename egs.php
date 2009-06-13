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
        
        //select p.name, u.username, u.name from project p join resource r on r.projectid=p.id join useroverview u on r.personid=u.id where u.username='nooslilaxe' and p.archived='f';

    }

    function updateUser($name)
    {
        $query = "
select p.id, p.name 
from project p
join resource r
on r.projectid=p.id
join useroverview u
on u.id=r.personid
where p.archived = 'f'
    and u.username = :u
order by name
";
	$param = array(':u'=>$name);
	$new_res = egsDb::fetchList($query, 
				    $param);
	$query="
select p.id, p.egs_id
from tr_project p 
join tr_project_user pu
on pu.project_id = p.id
join tr_user u 
on pu.user_id = u.id
where u.name = :u";
	
	$old_res = db::fetchList($query, $param);
	$kill = array();
	foreach($old_res as $res) 
	{
	    $kill[$res['egs_id']] = $res;
	}
	
	foreach( $new_res as $res) 
	{
	    if( array_key_exists($res['id'],$kill) ) 
	    {
		$kill[$res['id']] = null;
	    }
	    else 
	    {
		db::query('insert into tr_project_user (project_id, user_id) select id, :u from tr_project where egs_id=:p',
			  array(':u'=>User::$user->id,
				':p'=>$res['id']));
	    }
	}
	foreach($kill as $row) 
	{
	    if($row !== null) 
	    {
		db::query('delete from tr_project_user where project_id=:p and user_id=:u',
			  array(':u'=>User::$user->id,
				':p'=>$row['id']));
	    }
	}
	
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
	
	$this->updateUser(User::$user->name);
    }

}

$egs = new Egs(EGS_DSN);
$egs->main();

?>