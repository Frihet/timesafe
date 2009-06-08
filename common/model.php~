<?php

class dbItem
{

    /**
     * Returns an array of all public properties of this object
     * type. By convention, this is exactly the same as the list of
     * fields in the database, and also the same thing as all fields
     * whose name does not begin with an underscore.
     */
    function getPublicProperties() {
        static $cache = null;
        if (is_null( $cache )) {
            $cache = array();
            foreach (get_class_vars( get_class( $this ) ) as $key=>$val) {
                if (substr( $key, 0, 1 ) != '_') {
                    $cache[] = $key;
                }
            }
        }
        return $cache;
    }

    function initFromArray($arr)
    {
        $count = 0;
        if ($arr) {
            foreach ($this->getPublicProperties() as $key) {
                if (array_key_exists($key, $arr)) {
                    $this->$key = $arr[$key];
                    $count ++;
                }
            }
        }
        
        return $count;
        
    }
    
    function find($col_name, $col_value, $class_name, $table_name) 
    {
        $res = new $class_name();
        $data = db::fetchRow("select * from $table_name where $col_name=:value",
                             array(':value'=>$col_value));
                
        if (!$data) {
            return null;
        }
        $res->initFromArray($data);
        
        return $res;
    }
        
}

?>