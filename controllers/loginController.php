<?php
  /**
    Log in form
   */
class LoginController
extends Controller
{


    function viewRun()
    {
    //$my_name = $_SERVER['PHP_AUTH_USER'];


        util::setTitle("Log in");

	$hidden = array('controller'=>'login','task'=>'login');
        $form = "";
        $form .= "<table class='striped'>";
        $form .= "<tr><th>User name</th><td>".form::makeText("username","")."</td></tr>";
        $form .= "<tr><th>Password</th><td>".form::makePassword("password","")."</td></tr>";
        $form .= "</table>";
	$form .= "<button type='submit'>Log in</button>";

        $content = form::makeForm($form, $hidden);

        $this->show(null, $content);

    }

    function loginRun()
    {
        $users = User::getAllUsers();

	if ($users[param('username')]->password == md5(param('password'))) {
	    $_SESSION['user'] = param('username');
	    util::redirect(makeUrl(array('controller'=>'editor','task'=>'view')));
	}
	return $this->viewRun();
    }
}



?>