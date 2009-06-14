<?php

require_once('common/index.php');
require_once("model.php");

require_once('ldap.php');

class MyApp 
extends Application
{

    function __construct()
    {
        $this->addScript('static/TimeSafe.js');
        //$this->addScript('static/jquery.flot.js');
        $this->addStyle('static/TimeSafe.css');
	require_once('egs.php');

    }
    
    
    /**
     Write out the top menu.
    */    
    function writeMenu($editor)
    {
        
        $is_admin = $editor->isAdmin();
        $is_help = $editor->isHelp();
        $is_tr = !$is_admin && !$is_help;
	    
        echo "<div class='main_menu'>\n";
        echo "<div class='main_menu_inner'>";
        echo "<div class='logo'><a href='?'>TimeSafe</a></div>";

        echo "<ul>\n";
 
        echo "<li>";
	echo makeLink("?controller=editor", "Time registration", $is_tr?'selected':null);
        echo "</li>\n";

        echo "<li>";
	echo makeLink("?controller=admin", "Administration", $is_admin?'selected':null);
        echo "</li>\n";
        
        echo "<li>";
	echo makeLink("?controller=help", "Help", $is_help?'selected':null);
        echo "<li>";
        
        echo "</ul></div></div>\n";
    }

    function getDefaultController()
    {
        return "editor";
    }
    
    function getApplicationName()
    {
        return "TimeSafe";
    }
    
}

$app = new MyApp();
$app->main();

?>