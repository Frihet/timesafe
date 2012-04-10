<?php
  /**
   All database access ges through this file.

   */
  /*
define('TR_VISIBILITY_PROJECT',0);
define('TR_VISIBILITY_INTERNAL',1);
define('TR_VISIBILITY_EXTERNAL',2);
define('TR_VISIBILITY_ALL',3);
  */
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

    /* reporting functions of various kinds */

    function sqlColoredTags() {
        return array(
         "select
	   et.entry_id,
	   t.color_r,
	   t.color_g,
	   t.color_b,
	   t.name
	  from
	   tr_tag_map et
	   join tr_tag t on
	    et.tag_id = t.id
	    and t.color_r is not null
	    and t.color_g is not null
	    and t.color_b is not null",
	 array());
    }

    function sqlColoredClasses() {
        return array(
         "select
	   e.id as entry_id,
	   c.color_r,
	   c.color_g,
	   c.color_b,
	   c.name
	  from
	   tr_entry e
	   join tr_project_project_class ec on
	    e.project_id = ec.project_id
	   join tr_project_class c on
	    ec.project_class_id = c.id
	    and c.color_r is not null
	    and c.color_g is not null
	    and c.color_b is not null",
	 array());
    }

    function sqlColoredMarks($mark_types = 'both') {
        if ($mark_types == 'tags')
	    return self::sqlColoredTags();
        else if ($mark_types == 'classes')
	    return self::sqlColoredClasses();

        $sql1 = self::sqlColoredTags();
        $sql2 = self::sqlColoredClasses();
        return array("{$sql1[0]} union {$sql2[0]}", array_merge($sql1[1], $sql2[1]));
    }

    function sqlColoredEntries($filter, $order, $mark_types = 'both') {
        $sql = self::sqlColoredMarks($mark_types);
	$user_sql = util::arrayToSqlIn("e.user_id", isset($filter['users']) ? $filter['users'] : array());
	$project_sql = util::arrayToSqlIn("p.name", isset($filter['projects']) ? $filter['projects'] : array());

	$tag_sql = util::arrayToSqlIn("t.name", isset($filter['tags']) ? $filter['tags'] : array());
	if ($tag_sql[0] != "true") {
	    $tag_sql[0] = "e.id in (select et.entry_id from tr_tag_map et join tr_tag t on et.tag_id = t.id where {$tag_sql[0]})";
	}

	$order = implode(',',  $order);

        return array(
         "select
	   e.id,
	   u.id as user_id,
	   u.name as user,
	   u.fullname as user_fullname,
	   p.id as project_id,
	   p.name as project,
	   e.description,
	   extract(epoch from e.perform_date) as perform_date,
	   trunc(e.minutes / 60.0, 1) as hours,
	   avg(t.color_r) :: integer as color_r, avg(t.color_g) :: integer as color_g, avg(t.color_b) :: integer as color_b,
	   array_to_string(array_agg(coalesce(t.name, 'Other')), ', ') as tag_names
	  from
	   tr_entry e
	   join tr_user u on
            e.user_id = u.id
	   join tr_project p on
            e.project_id = p.id
	   left outer join (select * from ({$sql[0]}) t order by t.entry_id, t.name) t on -- See note on ordering and array_agg at http://www.postgresql.org/docs/8.4/static/functions-aggregate.html 
	    e.id = t.entry_id
	  where
	   perform_date <= :date_end 
	   and perform_date >= :date_begin 
           and {$user_sql[0]}
           and {$project_sql[0]}
	   and {$tag_sql[0]}
	  group by
	   e.id, u.id, u.name, u.fullname, p.id, p.name, e.description, e.perform_date, e.minutes
	  order by
           {$order}",
	 array_merge($sql[1], $user_sql[1], $project_sql[1], $tag_sql[1],
	  array(':date_end'=>$filter['date_end'],
	        ':date_begin'=>$filter['date_begin'])));
    }

    function sqlCol2($filter = array(), $order, $mark_types = 'both') {
        $sql = self::sqlColoredEntries($filter, array('perform_date'), $mark_types);

	$col2 = $order[1];

	if ($col2 == 'tag_names') {
	  $col2 .= ', color_r, color_g, color_b';
	}

        return array(
         "select
           {$col2}
	  from
	   ({$sql[0]}) as s
          group by
	   {$col2}
	  order by
	   {$col2};",
	 $sql[1]);
    }

    function sqlGroupByTwoCols($filter, $order, $mark_types = 'both') {
        $sql = self::sqlColoredEntries($filter, $order, $mark_types);
	$col1 = $order[0];
	$col2 = $order[1];

	if ($col2 == 'tag_names') {
	  $col2 .= ', color_r, color_g, color_b';
	}
	
        return array(
         "select
	   {$col1},
           {$col2},
	   sum(hours) as hours
	  from
	   ({$sql[0]}) as s
          group by
	   {$col1},
	   {$col2}
	  order by
	   {$col1},
	   {$col2};",
	 $sql[1]);
    }

    function groupByColumn($items, $col) {
	$items_by_col = array();
        $last_col_value = null;
	$last_items = null;
	foreach ($items as $item) {
            if ($last_col_value != $item[$col]) {
	        if ($last_col_value != null)
		    $items_by_col[$last_col_value] = $last_items;
		$last_col_value = $item[$col];
	        $last_items = array();
            }
	    $last_items[] = $item;
        }
	if ($last_col_value != null)
	    $items_by_col[$last_col_value] = $last_items;
	return $items_by_col;
    }

    function colors($filter, $order = array('perform_date', 'tag_names'), $mark_types = 'both') {
        $sql = self::sqlCol2($filter, $order, $mark_types);
        $rows = db::fetchList($sql[0], $sql[1]);
	if ($order[1] != 'tag_names') {
	  /* We have no colors, so invent some; we spread the colors assigned evenly over the three dimensioned color space {r,g,b}. */
          $row_nr = count($rows);
	  $rows_per_dimension = pow($row_nr, 1/3);
	  $color_incr_per_row = max(array(1, 255 / $rows_per_dimension));
	  $color = array('color_r' => 0, 'color_g' => 0, 'color_b' => 0);
	  $colored_rows = array();
	  foreach ($rows as $row) {
	    $colored_rows[] = array_merge($row, $color);
	    $color['color_r'] += $color_incr_per_row;
	    if ($color['color_r'] > 255) {
	      $color['color_r'] = 0; $color['color_g'] += $color_incr_per_row;
	    }
	    if ($color['color_g'] > 255) {
	      $color['color_g'] = 0; $color['color_b'] += $color_incr_per_row;
	    }
	  }
	  $rows = $colored_rows;
	}
	$colors = array();
	foreach ($rows as $row) {
	  $colors[$row[$order[1]]] = $row;
	}
	return $colors;
    }

    function coloredEntries($filter, $order = array('perform_date', 'tag_names'), $mark_types = 'both') {
        $sql = self::sqlColoredEntries($filter, $order, $mark_types);
        return self::groupByColumn(db::fetchList($sql[0], $sql[1]), $order[0]);
    }

    function groupByColor($filter, $order = array('perform_date', 'tag_names'), $mark_types = 'both') {
    	$sql = self::sqlGroupByTwoCols($filter, $order, $mark_types);
        $rows = self::groupByColumn(db::fetchList($sql[0], $sql[1]), $order[0]);
	if ($order[1] != 'tag_names') {
	  $colors = self::colors($filter, $order);
	  foreach ($rows as $col1value => $rows_for_col1) {
	    $colored_rows = array();
	    foreach ($rows_for_col1 as $row) {
	      $colored_rows[] = array_merge($row, $colors[$row[$order[1]]]);
	    }
	    $rows[$col1value] = $colored_rows;
	  }
	}
	return $rows;
    }
    
}


class ProjectClass
extends DbItem
{

    var $id;
    var $name;
    var $color;
    var $color_r;
    var $color_g;
    var $color_b;
    var $deleted;
    
    static $_project_class_cache;

    function __construct($param=null) 
    {
        $this->table = 'tr_project_class';
	$this->deleted = false;
        if($param) {
            if ((int)$param == $param) {
                $this->load($param);
            }
            else if (is_array($param)) {
                $this->initFromArray($param);
            }
        }
    }

    function getPublicProperties() {
        static $cache = null;
        if (is_null( $cache )) {
            $cache = array();
            foreach (get_class_vars( get_class( $this ) ) as $key=>$val) {
                if (substr( $key, 0, 1 ) != '_' && $key != "color") {
                    $cache[] = $key;
                }
            }
        }
        return $cache;
    }

    function initFromArray($arr) {
    	parent::initFromArray($arr);
	if ($this->color_r !== null && $this->color_g !== null && $this->color_b !== null) {
	   $this->color = util::colorToHex($this->color_r, $this->color_g, $this->color_b);
        }
    }

    function delete($id) 
    {
        db::query("update tr_project_class set deleted = true where id=:id", 
                  array(':id'=>$id));
    }
    
    function getProjectClasses() 
    {
        return self::fetch();
    }

    function getName($project_class_id) 
    {
        $project_class_list = self::fetch();
        return $project_class_list[$project_class_id]->name;
    }

    function fetch() 
    {
        if (self::$_project_class_cache !== null) {
            return self::$_project_class_cache;
        }
        

        $data = db::fetchList("
select id, name, color_r, color_g, color_b, deleted
from tr_project_class
where deleted=false
order by name");
        
        $out = array();
        
        foreach($data as $entry) {
            $it = new ProjectClass();
            $it->initFromArray($entry);
            $out[$it->id] = $it;
        }
        //var_dump($out);
        self::$_project_class_cache = $out;
        
        return $out;   
    }

    function save()
    {
        $this->color_r = null;
        $this->color_g = null;
        $this->color_b = null;
	if (preg_match('/^#[0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F]$/', $this->color) > 0) {
            $this->color_r = hexdec(substr($this->color, 1, 2));
            $this->color_g = hexdec(substr($this->color, 3, 2));
            $this->color_b = hexdec(substr($this->color, 5, 2));
	}
        return $this->saveInternal();
    }
    
}


class Project
extends dbItem
{
    var $id;
    var $egs_id;
    var $start_date;
    var $name;
    var $is_resource;
    var $open;

    var $_project_class=null;
    
    static $_items = array();

    function __construct($arr, $is_item=true) 
    {
        $this->table = "tr_project";
	$this->open = true;
        $this->initFromArray($arr);
        if($is_item)
            Project::$_items[$this->id] = $this;
    }
    
        
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

    function getProjectClassNames()
    {
        $res = array();
        foreach(db::query("select id, name from tr_project_class where deleted = false") as $row){
            $res[$row['id']] = $row['name'];
        }
        return $res;
        
    }
        
    function getProjectClass()
    {
        if($this->_project_class !== null){
            return $this->_project_class;
        }
        $this->_project_class=array();

        foreach(db::fetchList("select project_class_id from tr_project_project_class where project_id=:id", array(":id"=>$this->id)) as $row) {
            $this->_project_class[]= $row['project_class_id'];
        }
        return $this->_project_class;
    }
    
    function _load()
    {
        if (Project::$_items) {
            return;
        }
        foreach(db::fetchList("
select p.id, p.name, p.egs_id, 
    extract(epoch from p.start_date) as start_date,
    (select id from tr_project_user pu where pu.user_id=:user_id and pu.project_id = p.id) is not null as is_resource,
    p.open as open
from tr_project p
where p.open=true 
order by p.name",
			      array(':user_id'=>User::$user->id)) as $row) {
	    //message($row['tralala']);
            $p = new Project($row);
        }        
        
        foreach(db::fetchList("select project_id, project_class_id from tr_project_project_class") as $row) {
            if(array_key_exists($row['project_id'],Project::$_items))
                Project::$_items[$row['project_id']]->_project_class[]= $row['project_class_id'];
        }
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
    
    function saveInternal($key='id') 
    {
        db::begin();

        $ok = parent::saveInternal($key);

        $ok = $ok && db::query('delete from tr_project_project_class where project_id=:id', array(':id'=>$this->id));
        foreach($this->_project_class as $cl) {
            $ok = $ok && db::query('insert into tr_project_project_class (project_id, project_class_id) values (:pid, :cid)',
                      array(':pid'=>$this->id, 
                            ':cid'=>$cl));
        }
        if($ok) {
            db::commit();
        }
        else {
            db::rollback();
        }
    }

    function undelete()
    {
        db::query('update tr_project set open=true where id=:id',
                  array(':id'=>$this->id));
        if (Project::$_items) {
            Project::$_items[$this->id] = $this;
        }
    }
    
    function delete()
    {
        db::query('update tr_project set open=false where id=:id',
                  array(':id'=>$this->id));
        if (Project::$_items) {
            unset(Project::$_items[$this->id]);
        }
    }
      
}

class Tag
extends DbItem
{

    var $id;
    var $name;
    var $project_class_id;
    var $project_id;
    var $group_id;
    var $recommended;
    var $color;
    var $color_r;
    var $color_g;
    var $color_b;
    
    static $_tag_cache;

    function __construct($param=null) 
    {
        $this->table = 'tr_tag';
        if($param) {
            if ((int)$param == $param) {
                $this->load($param);
            }
            else if (is_array($param)) {
                $this->initFromArray($param);
            }
        }
    }

    function getPublicProperties() {
        static $cache = null;
        if (is_null( $cache )) {
            $cache = array();
            foreach (get_class_vars( get_class( $this ) ) as $key=>$val) {
                if (substr( $key, 0, 1 ) != '_' && $key != "color") {
                    $cache[] = $key;
                }
            }
        }
        return $cache;
    }

    function initFromArray($arr) {
    	parent::initFromArray($arr);
	if ($this->color_r !== null && $this->color_g !== null && $this->color_b !== null) {
	   $this->color = util::colorToHex($this->color_r, $this->color_g, $this->color_b);
        }
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
        //$project_external = Project::getProject($project_id)->external;
        
        foreach($tag_list as $tag) {           
            $show = false;
            
            if($tag->project_class_id === null) {
                if($tag->project_id !== null) {
                    $show = $tag->project_id == $project_id;
                } else {
                    $show = true;
                }
            } else {
                $show = (in_array($tag->project_class_id, Project::getProject($project_id)->getProjectClass()));
            }
                        
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

    function save()
    {
        $this->color_r = null;
        $this->color_g = null;
        $this->color_b = null;
	if (preg_match('/^#[0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F]$/', $this->color) > 0) {
            $this->color_r = hexdec(substr($this->color, 1, 2));
            $this->color_g = hexdec(substr($this->color, 3, 2));
            $this->color_b = hexdec(substr($this->color, 5, 2));
	}
        return $this->saveInternal();
    }
}

class TagGroup
extends DbItem
{

    var $id;
    var $name;
    var $required;
    
    static $_tag_group_cache;

    function __construct($param=null) 
    {
        $this->table = 'tr_tag_group';
        if($param) {
            if ((int)$param == $param) {
                $this->load($param);
            }
            else if (is_array($param)) {
                $this->initFromArray($param);
            }
        }
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
    
    function save()
    {
        return $this->saveInternal();
    }
     
    
}


class User
extends DbItem
{
    static $me;
    static $user;
    static $_all=null;
    static $_all_by_id=null;
    
    public $id;
    public $name;
    public $fullname;
    public $password;
    var $_projects = null; 

    function __construct($param) 
    {
        $this->table = 'tr_user';
	parent::__construct($param);
     }
    
    function load($key_value)
    {
	parent::load($key_value);
        User::$_all[$this->name] = $this;
        User::$_all_by_id[$this->id] = $this;
	$this->getProjects();
    }
  
    function getPublicProperties() {
        static $cache = null;
        if (is_null( $cache )) {
            $cache = array();
            foreach (get_class_vars( get_class( $this ) ) as $key=>$val) {
                if (substr( $key, 0, 1 ) != '_' && !in_array($key, array('me', 'user'))) {
                    $cache[] = $key;
                }
            }
        }
        return $cache;
    }

    function getAllUsers()
    {
	
	if( self::$_all)
	{
	    return self::$_all;
	}
	self::$_all = array();
	self::$_all_by_id = array();
	foreach(db::query('select id from tr_user where deleted=false order by fullname') as $row) 
	{
	    new User((int)$row['id']);
	}
	return self::$_all;
    }


    function getProjectNames()
    {
        $res = array();
        foreach(db::query("select id, name from tr_project where open = true") as $row){
            $res[$row['id']] = $row['name'];
        }
        return $res;
        
    }
        
    function getProjects()
    {
        if($this->_projects !== null){
            return $this->_projects;
        }
        $this->_projects=array();

        foreach(db::fetchList("select project_id from tr_project_user where user_id=:id", array(":id"=>$this->id)) as $row) {
            $this->_projects[]= $row['project_id'];
        }
        return $this->_projects;
    }    

    function saveInternal($key='id') 
    {
        db::begin();

        $ok = parent::saveInternal($key);

        $ok = $ok && db::query('delete from tr_project_user where user_id=:id', array(':id'=>$this->id));
        foreach($this->_projects as $p) {
            $ok = $ok && db::query('insert into tr_project_user (project_id, user_id) values (:pid, :uid)',
                      array(':pid'=>$p,
                            ':uid'=>$this->id));
        }
        User::$_all[$this->name] = $this;
        User::$_all_by_id[$this->id] = $this;

        if($ok) {
            db::commit();
        }
        else {
            db::rollback();
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
	if( self::$_all_by_id)
	{
            unset(self::$_all_by_id[$this->id]);
        }
        
    }
    
}


class Report
extends DbItem
{

    var $id;
    var $name;
    var $query;

    static $_report_cache;
    
    function __construct($param=null) 
    {
        $this->table = 'tr_report';
        if($param) {
            if ((int)$param == $param) {
                $this->load($param);
            }
            else if (is_array($param)) {
                $this->initFromArray($param);
            }
        }
    }

    function delete($id) 
    {
        db::query("delete from tr_report where id=:id", 
                  array(':id'=>$id));
    }    

    function fetch() 
    {
        if (self::$_report_cache !== null) {
            return self::$_report_cache;
        }
        

        $data = db::fetchList("
select * 
from tr_report
order by name");
        
        $out = array();
        
        foreach($data as $entry) {
            $it = new Report();
            $it->initFromArray($entry);
            $out[$it->id] = $it;
        }

        self::$_report_cache = $out;
        
        return $out;
        
    }
    
    function save()
    {
        return $this->saveInternal();
    }
     
    
}
