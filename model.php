<?php
  /**
   All database access ges through this file.

   */

define('TR_VISIBILITY_PROJECT',0);
define('TR_VISIBILITY_INTERNAL',1);
define('TR_VISIBILITY_EXTERNAL',2);
define('TR_VISIBILITY_ALL',3);



class Entry
extends DbItem
{

    var $id;
    var $description;
    var $user_id;
    var $project_id;
    var $minutes;
    var $perform_date;
    var $_tags = array();
    static $_items;
    
    /**
     Save this time entry to db. 
     */
    function save()
    {
	db::begin();
	$ok = true;

	if ($this->description == '') 
	{
	    $ok =false;
	}
	
	
        if ($this->id != null) {
            $ok &= db::query('update tr_entry set description = :description, minutes=:minutes where id=:id',
			     array(':description'=>$this->description,':minutes'=>$this->minutes, ':id'=>$this->id));
        }
        else {
            $ok &= db::query('
insert into tr_entry 
(
	description, minutes, project_id, user_id, perform_date
) 
values 
(
	:description, :minutes, :project_id, :user_id, :perform_date
)',
			     array(':description'=>$this->description,':minutes'=>$this->minutes, ':project_id'=>$this->project_id, ':user_id'=>$this->user_id,
				   ':perform_date'=>$this->perform_date));
            $this->id = db::lastInsertId('tr_entry_id_seq');
        }
	
        $ok &= db::query('delete from tr_tag_map where entry_id=:id',
			 array(':id'=>$this->id));
        if ($this->_tags) {
            foreach($this->_tags as $tag_id) {
                $ok &= db::query('insert into tr_tag_map (entry_id, tag_id) values (:id, :tag_id)',
				 array(':id'=>$this->id, ':tag_id'=>$tag_id));
            }
        }
	if($ok) 
	{
	    db::commit();
	}
	else 
	{
	    db::rollback();
	    error('Did not save entry ' . $this->id);
	}
    }

    function delete()
    {
        db::query('delete from tr_tag_map where entry_id=:id',
                  array(':id'=>$this->id));
        
        db::query('delete from tr_entry where id=:id',
                  array(':id'=>$this->id));
            
    }
    
    
    /**
     Set the list of tags for this entry. Should be an array of tag ids.
     */
    function setTags($tag_list) 
    {
        $this->_tags = $tag_list;
    }
    
    function getBaseDate()
    {
        $date = param('date');
        if( $date) {
            list($year, $month, $day) = explode('-',$date);
        }
        else {
            $now = time();
            $year = date('Y', $now);
            $month = date('m', $now);
            $day = date('d', $now);
        }
        
        return mktime(12, 0, 0, $month, $day, $year);
        
    }
    
    function getDateCount()
    {
        return 14;	
    }
    
    function getDateOffset()
    {
        $then = $this->perform_date;
        return self::getDateCount() - 1 - util::calcDayInterval($then, Entry::getBaseDate());
    }
    
    function fetch()
    {
        if (Entry::$_items) {
            return Entry::$_items;
        }     

        $date_end = date('Y-m-d',self::getBaseDate());
        $date_begin = date('Y-m-d',self::getBaseDate()-(self::getDateCount()-1)*3600*24);

        $data = db::fetchList("
select e.id, project_id, user_id, minutes, 
    extract(epoch from perform_date) as perform_date, 
    description
from tr_entry e
where e.user_id = :user_id 
    and perform_date <= :date_end 
    and perform_date >= :date_begin 
order by perform_date", array(':user_id'=>User::$user->id,
                              ':date_end'=>$date_end,
                              ':date_begin'=>$date_begin));
        
        $out = array();
        
        $in=array();
        
        foreach($data as $entry) {
            $it = new Entry();
            $it->initFromArray($entry);
            $out[$it->id] = $it;
            $in[] = $entry['id'];
        }
        
        if (count($in)) {
            list($in_str, $param) = db::in_list($in);
            $query2 = "select * from tr_tag_map where entry_id in ($in_str)";
            foreach(db::fetchList($query2, $param) as $tag) {
                $out[$tag['entry_id']]->_tags[] = $tag['tag_id'];
                
            }
        }
        
        Entry::$_items =$out;
        return $out;
        
    }
}


class Project
extends dbItem
{
    var $id;
    var $egs_id;
    var $start_date;
    var $external;
    var $name;
    var $is_resource;
    
    static $_items;
        
    function getProjects() 
    {
        Project::_load();
        return Project::$_items;
    }
    
    function getProjectNames() 
    {
        Project::_load();
        $res = array();
        
        foreach(Project::$_items as $project) {
            $res[$project->id] = $project->name;
        }
        return $res;
    }
    
    function _load()
    {
        if (Project::$_items) {
            return;
        }
        foreach(db::fetchList("
select p.id, p.name, p.egs_id, 
    extract(epoch from p.start_date) as start_date, 
    case when p.external='t' then 1 else 0 end as external,
    (select id from tr_project_user pu where pu.user_id=:user_id and pu.project_id = p.id) is not null as is_resource
from tr_project p
where p.open=true 
order by p.name",
			      array(':user_id'=>User::$user->id)) as $row) {
	    //message($row['tralala']);
	    
            $p = new Project();
            $p->initFromArray($row);
            Project::$_items[$p->id] = $p;
        }        
    }
    
    function getEgsMapping()
    {
        $mapping = array();
        foreach(db::fetchList("select id, egs_id from tr_project where open=true") as $row) {
            $mapping[$row['egs_id']] = $row['id'];
        }
        return $mapping;
    }
    
    function getName($id) 
    {
        Project::_load();
        return Project::$_items[$id]->name;
    }
    
    function getProject($id) 
    {
        Project::_load();
        return Project::$_items[$id];
    }
    
    function add($name, $egs_id, $start_date, $external) 
    {
        db::query('insert into tr_project (name, egs_id, start_date, external) values (:name, :value, :d, :ext)',
                  array(':name'=>$name, ':value'=>$egs_id,
                        ':d'=>$start_date,
                        ':ext'=>$external));
    }
  
    function update($id, $name, $egs_id, $start_date, $external) 
    {
        db::query('update tr_project set name=:name, egs_id=:value, start_date=:d, external=:ext where id=:id',
                  array(':name'=>$name, 
                        ':value'=>$egs_id,
                        ':d'=>$start_date,
                        ':id'=>$id,
                        ':ext'=>$external));        
    }    
  
}

class Tag
extends DbItem
{

    var $id;
    var $name;
    var $visibility;
    var $project_id;
    var $group_id;
    var $recommended;
    
    static $_tag_cache;

    function update() 
    {
                $query = "
update tr_tag
set name=:name, project_id = :project_id, visibility=:visibility, group_id=:group_id, recommended=:r
where id= :id
";
                db::query($query, array(':name'=>$this->name,
                                        ':id'=>$this->id,
                                        ':project_id'=>$this->project_id, 
                                        ':visibility'=>$this->visibility,
                                        ':group_id'=>$this->group_id, 
                                        ':r'=>$this->recommended?'t':'f'));
    }
    
    function create()
    {
        db::query("
insert into tr_tag
(name, project_id, visibility, group_id, recommended)
values
(:name, :project_id, :visibility, :group_id, :r)", 
                  array(':name'=>$this->name, 
                        ':project_id'=>$this->project_id,
                        ':visibility'=>$this->visibility,
                        ':group_id'=>$this->group_id,
                        ':r'=>$this->recommended?'t':'f')
                  );
    }
    
    function delete($id) 
    {
        db::query("update tr_tag set deleted = true where id=:id", 
                  array(':id'=>$id));
    }
    
    function getTags($project_id) 
    {
        $tag_list = self::fetch();
        
        $out = array();
        $project_external = Project::getProject($project_id)->external;
        
        foreach($tag_list as $tag) {
            $vis = $tag->visibility;

            $show = ($project_external && ($vis == TR_VISIBILITY_EXTERNAL)) ||
            (!$project_external && ($vis == TR_VISIBILITY_INTERNAL)) ||
            ($vis == TR_VISIBILITY_ALL) ||
            ($vis == TR_VISIBILITY_PROJECT && $tag->project_id == $project_id);
            
            if ($show) {
                $out[] = $tag;//array($tag->id, $tag->name);
            }
        }
        
        return $out;
    }

    function getName($tag_id) 
    {
        $tag_list = self::fetch();
        return $tag_list[$tag_id]->name;
        

    }
    

    function fetch() 
    {
        if (self::$_tag_cache !== null) {
            return self::$_tag_cache;
        }
        

        $data = db::fetchList("
select * 
from tr_tag
where deleted=false
order by name");
        
        $out = array();
        
        foreach($data as $entry) {
            $it = new Tag();
            $it->initFromArray($entry);
            $out[$it->id] = $it;
        }
        //var_dump($out);
        self::$_tag_cache = $out;
        
        return $out;
        
    }
    
    
}

class TagGroup
extends DbItem
{

    var $id;
    var $name;
    var $required;
    
    static $_tag_group_cache;

    function update() 
    {
        $query = "
update tr_tag_group
set name=:name, required = :required
where id= :id
";
        db::query($query, array(':name'=>$this->name,':id'=>$this->id,':required'=>$this->required?'t':'f'));
    }
    
    function create()
    {
        db::query("
insert into tr_tag_group
(name, required)
values
(:name, :required)", array(':name'=>$this->name, ':required'=>$this->required?'t':'f'));
    }
    
    function delete($id) 
    {
        db::query("update tr_tag set group_id=null where group_id=:id", 
                  array(':id'=>$id));
        db::query("delete from tr_tag_group where id=:id", 
                  array(':id'=>$id));
    }
    
    function getTagGroups() 
    {
        $tag_list = self::fetch();
        
        $out = array();
        foreach($tag_list as $tag) {
            $out[$tag->id] = $tag->name;
        }
        return $out;
    }

    function getName($id) 
    {
        $tag_list = self::fetch();
        return $tag_list[$id]->name;
    }
    

    function fetch() 
    {
        if (self::$_tag_group_cache !== null) {
            return self::$_tag_group_cache;
        }
        

        $data = db::fetchList("
select * 
from tr_tag_group
order by name");
        
        $out = array();
        
        foreach($data as $entry) {
            $it = new TagGroup();
            $it->initFromArray($entry);
            $out[$it->id] = $it;
        }

        self::$_tag_group_cache = $out;
        
        return $out;
        
    }
    
    
}


class User
extends DbItem
{
    static $me;
    static $user;
    static $_all=null;
    
    public $id;
    public $name;
    public $fullname;

    function __construct($id, $name, $fullname) 
    {
	$this->id = $id;
	$this->name = $name;
	$this->fullname = $fullname;
        
        User::$_all[$this->name] = $this;
    }
    
    function getAllUsers()
    {
	
	if( self::$_all)
	{
	    return self::$_all;
	}
	self::$_all = array();
	foreach(db::query('select * from tr_user where deleted=false order by fullname') as $row) 
	{
	    new User($row['id'], $row['name'], $row['fullname']);
	}
	return self::$_all;
    }

    function save() 
    {
	//echo "Save {$this->id} {$this->name}<br>";
	
	if($this->id !== null) {
	    db::query('update tr_user set name=:n, fullname=:f where id=:id',
		      array(':id'=>$this->id,
			    ':f'=>$this->fullname,
			    ':n'=>$this->name));
	    
	} else {
	    db::query('insert into tr_user (name, fullname) values(:n, :f)',
		      array(':f'=>$this->fullname,
			    ':n'=>$this->name));
	    $this->id = db::lastInsertId('tr_user_id_seq');
	    
	}
    }
    
    function delete()
    {
        db::query('update tr_user set deleted=true where id=:id',
                  array(':id'=>$this->id));	    
	if( self::$_all)
	{
            unset(self::$_all[$this->name]);
        }
        
    }
    
}

