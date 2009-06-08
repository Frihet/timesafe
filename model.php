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
        if ($this->id != null) {
            db::query('update tr_entry set description = :description, minutes=:minutes where id=:id',
                      array(':description'=>$this->description,':minutes'=>$this->minutes, ':id'=>$this->id));
        }
        else {
            db::query('insert into tr_entry (description, minutes, project_id, user_id, perform_date) values (:description, :minutes, :project_id, :user_id, :perform_date)',
                      array(':description'=>$this->description,':minutes'=>$this->minutes, ':project_id'=>$this->project_id, ':user_id'=>$this->user_id,
                            ':perform_date'=>$this->perform_date));
            $this->id = db::lastInsertId('tr_entry_id_seq');
        }
        
        db::query('delete from tr_tag_map where entry_id=:id',
                  array(':id'=>$this->id));
        if ($this->_tags) {
            foreach($this->_tags as $tag_id) {
                db::query('insert into tr_tag_map (entry_id, tag_id) values (:id, :tag_id)',
                          array(':id'=>$this->id, ':tag_id'=>$tag_id));
            }
        }
    }

    function delete()
    {
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
join tr_user u
    on e.user_id = u.id
where u.name = :username 
    and perform_date <= :date_end 
    and perform_date >= :date_begin 
order by perform_date", array(':username'=>User::$me->name,
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
{
    static $_items;
        
    function getProjects() 
    {
        Project::_load();
        return Project::$_items;
    }
    
    function _load()
    {
        if (Project::$_items) {
            return;
        }
        foreach(db::fetchList("select id, name from tr_project where open=true") as $row) {
            Project::$_items[$row['id']] = $row['name'];
        }
        
    }
    
    function getName($id) 
    {
        Project::_load();
        return Project::$_items[$id];
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
    
    static $_tag_cache;

    function update() 
    {
                $query = "
update tr_tag
set name=:name, project_id = :project_id, visibility=:visibility, group_id=:group_id
where id= :id
";
                db::query($query, array(':name'=>$this->name,':id'=>$this->id,':project_id'=>$this->project_id, ':visibility'=>$this->visibility, ':group_id'=>$this->group_id));
    }
    
    function create()
    {
        db::query("
insert into tr_tag
(name, project_id, visibility, group_id)
values
(:name, :project_id, :visibility, :group_id)", 
                  array(':name'=>$this->name, 
                        ':project_id'=>$this->project_id,
                        ':visibility'=>$this->visibility,
                        ':group_id'=>$this->group_id)
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
        foreach($tag_list as $tag) {
            if ($tag->project_id == null || $tag->project_id == $project_id) {
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
    
    function init()
    {
        User::$me->id=1;
        User::$me->name='nooslilaxe';
        User::$me->fullname='Axel Liljencrantz';
        User::$user = User::$me;
        
        
    }
    
}

