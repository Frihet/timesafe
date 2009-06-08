<?php

  /** Minimal db abstraction. We use a static class like a namespace
   in order to have a single gglobal database connection with minimal
   namespace pollution.

   An added benefit is that it _might_ be possible to port this api to
   a different database abstraction than PDO if need be.

   Should be notet that there are definitely a few postgresisms in the
   database code as of today, it will not work cleanly without some
   modification on e.g. MySQL, though it should definitely be doable.

   Among the features added by this layer is a faked nested
   transaction support. Since PDO does not support nested
   transactions, we implement a weak workaround. Specifically, one can
   nest transaction any which way, but it any single rollback is
   performed, the whole transaction, and not just that part, will be
   rolled back. Hardly ideal, since there are uses for partial
   rollbacks, but better than nothing.

  */

class dbMaker
{
    
    function makeDb($name) 
    {
        eval("class " . $name . '
{
    static $debug=false;
	
    static $db;
    static $last_res;
    static $last_count=null;
    static $query_count=0;
    static $query_time = 0.0;
	
    static $error = null;

    static $statement_cache=array();
    static $transaction_count=0;
    static $transaction_fail=false;

    /**
     Try to initialize the database. Returns true if the connection could be set up, false otherwise.
     */
    function init($dsn)
    {
        try {
            // Work around retarded mysql connection bugs. 
            if(substr($dsn,0,5)=="mysql") {
                list($dsn,$user, $pass) = explode(",", $dsn);
                $user=trim($user);
                $pass=trim($pass);
                if(strstr($dsn, "port") !== false && strstr($dsn, "localhost") !== false) {
                    $dsn = str_replace("localhost", "127.0.0.1", $dsn);
                }
                self::$db = new PDO($dsn, $user, $pass, array(PDO::ATTR_PERSISTENT => true));
            } else {
                self::$db = new PDO($dsn, null, null, array(PDO::ATTR_PERSISTENT => true));
            }
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);            
            self::$db->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);     
        } catch (PDOException $e) {
            self::$error = $e->getMessage();
            error($e->getMessage());
            return false;
        }
        return true;

    }

    /**
     Returns the latest error message, if any.
    */
    function getError()
    {
        return self::$error;
    }        

    function in_list($arr) 
    {
        static $counter=0;
        $out1 = array();
        $out2 = array();
        foreach($arr as $it) {
            $out1[] = ":list_item_" . $counter;
            $out2[":list_item_" . $counter] = $it;
            $counter++;
        }
        return array(implode(", ",$out1), $out2);
    }
    
    /**
     Returns the id output of the last insert query.
     */
    function lastInsertId($param) 
    {
        return self::$db->lastInsertId($param);
    }
	
    /**
     Execute the specified query.
    */
    function query($q, $param=array())
    {
        $t1 = microtime(true);
        self::$query_count += 1;
        try {
            if (array_key_exists($q, self::$statement_cache)) {
                $res = self::$statement_cache[$q];
            } else {
                self::$statement_cache[$q] = $res = self::$db->prepare($q);
            }
            
            $ok = $res->execute($param);
        }
        catch (PDOException $e) {
            self::$error = $e->getMessage();
            error("Query «" .$q . "» " . (count($param)?" with parameters ".sprint_r($param):"") . ": ".$e->getMessage());
        }
	
        $t2 = microtime(true);
        self::$query_time += ($t2-$t1);
			
        if (self::$debug) {
            $msg = sprintf("SQL query took %.3f seconds: %s", $t2-$t1, $q);
            if (count($param)) {
                $msg .= "\n".sprint_r($param);
            }
            
            message($msg);
        }
        
        self::$last_res = $res;
        self::$last_count=null;
        return $ok?$res:false;
    }

    /**
     Fetch the output of the specified query as a list off hashes
     */
    function fetchList($q, $param=array()) 
    {
        $res = self::query($q, $param);
        $out = array();
        if (!$res) {
            return array();
        }
        while($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $out[] = $row;
        }

        self::$last_count = $res->rowCount();
        $res->closeCursor();        
        return $out;
    }

    /**
     Fetch a single row of output from the specified query as a hash
     */
    function fetchRow($q, $param=array()) 
    {
        $res = self::query($q, $param);
        $row = $res->fetch(PDO::FETCH_ASSOC);
        self::$last_count = $res->rowCount();
        $res->closeCursor();
        return $row;
    }

    /**
     Fetch a single value from the specified query
     */
    function fetchItem($q, $param=array()) 
    {
        $res = self::query($q, $param);
        if (!$res) {
            return null;
        }
        $row = $res->fetch(PDO::FETCH_ASSOC);
        self::$last_count = $res->rowCount();
        $res->closeCursor();
        return $row?$row[0]:null;
    }

    /**
     Count the number of results from the last query
     */
    function count()
    {
        if (self::$last_count !== null) {
            return self::$last_count;
        }
        return self::$last_res->rowCount();
    }

    /**
     Begin transaction
    */
    function begin()
    {
        if (!self::$transaction_count) {
            self::$transaction_fail=false;
            self::$db->beginTransaction();		
        }        
        self::$transaction_count++;
    }
    
    /** 
     Commit transaction if all nested transactions succeded,
     rollback otherwise.
    */
    function commit()
    {
        self::$transaction_count--;
        if (!self::$transaction_count) {
            if (!self::$transaction_fail) {
                self::$db->commit();		                
            } else {
                self::$db->rollback();		
            }
        }
    }
    
    /**
     Rollback transaction
    */
    function rollback()
    {
        self::$transaction_fail=true;
        self::commit();
    }
  
}
');
    }
}


dbMaker::makeDb("db");


?>