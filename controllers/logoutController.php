<?php
  /**
    Log out form
   */
class LogoutController
extends Controller
{
    function viewRun()
    {
    	unset($_SESSION['user']);
	util::redirect(makeUrl(array('controller'=>'login','task'=>'view')));
    }
}



?>