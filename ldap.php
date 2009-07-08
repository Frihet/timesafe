<?php
  /** Kludge in ldap users into TimeSafe. Do this by fetching all
   users from our own office on every page view, which is likely to be
   a bit resource intensive, but not prohibitivly so, and it makes
   sure the user list is always completely correct.

   This will need to be improved in the future, e.g. 1.1 or 2.0.
   */
require_once('FCToolkit.php');

class TsLdap
{
    
    function userWildcard()
    {
        $u = $_SERVER['PHP_AUTH_USER'];
        $sub = substr($u, 0, 3);
        if ($sub != 'int') {
            $sub = substr($u, 0, 4);
        }
        
        return $sub. "*";
    }
    

    function updateUsers() {
	
	$fc = new FCToolkit();
	$all = User::getAllUsers();
	
	$u = db::fetchList('select id, name, fullname from tr_user');
	$my_name = $_SERVER['PHP_AUTH_USER'];
	User::$me = $all[$my_name];
	User::$user = $all[param('user',$my_name)];
	
	//echo $my_name;
	
	// Get authenticated user from web server, use that information to
	// search the LDAP database for information.
/*	$uid = $this->getAuthUser();
	$userinfo = array('username' => $uid);
*/
	$ldapconn = $fc->getLDAPConnectionBound();
	if ($ldapconn) {
	    $base = LDAP_USER_BASE;
	    //$base = "ou=people,ou=auth,dc=freecodeint,dc=com";
	    //$base = "dc=com";
	    
	    $filter = sprintf('(&(objectClass=posixAccount)(uid=%s))', 
			      self::userWildcard());
		    
	    $entry = ldap_search($ldapconn, $base, $filter, array('cn','uid','userpassword'));
	    	    
	    if (ldap_count_entries($ldapconn, $entry) >= 1) {
		$info = ldap_get_entries($ldapconn, $entry);
		//message(sprint_r(User::$_all));
		//return;
		$is_in_ldap = array();
                
		foreach($info as $entry) {
		    $full = $entry['cn']['0'];
		    $name = $entry['uid']['0'];
		    $pass = $entry['userpassword']['0'];
		    /* Ignore disabled users. There is no disabled
                     flag, they simply don't have a password.
                     */
                    if($pass === null) {
			continue;
		    }
                    $is_in_ldap[$name] = true;
                    
		    if( array_key_exists($name, $all)) {
			$u = $all[$name];
			if($u->fullname != $full) {
			    $u->fullname = $full;
			    $u->save();
			}
                    } else {
			$u = new User(null, $name, $full);
			$u->save();
                    }
		    
		}
                foreach($all as $u) {
                    if(!array_key_exists($u->name, $is_in_ldap)){
                        //message( "User deleted: " . sprint_r($u));
                        $u->delete();
                    }
                }
	    } else {
		die("Failed to find users in LDAP.");
	    }
	}
	else 
	{
	    die("Failed to obtain LDAP connection");
	}
	
	return $userinfo;
    }

}

TsLdap::updateUsers();

?>