<?php
/******************************************************************************
 *
 * Copyright © 2010
 *
 * FreeCode Norway AS
 * Nydalsveien 30A, NO-0484 Oslo, Norway
 * Norway
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 ******************************************************************************/


require_once('common/index.php');
require_once("model.php");

require_once('ldap.php');

class MyApp 
extends Application
{

    function __construct()
    {
        $this->addScript('static/TimeSafe.js');
        //$this->addScript('static/jquery-ui.js');
        //$this->addScript('static/jquery.flot.js');
        $this->addStyle('static/TimeSafe.css');
        //$this->addStyle('static/jquery-ui.css');
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
	echo makeLink(makeUrl(array("controller"=>null)), "Time registration", $is_tr?'selected':null);
        echo "</li>\n";

        echo "<li>";
	echo makeLink(makeUrl(array("controller"=>"admin")), "Administration", $is_admin?'selected':null);
        echo "</li>\n";
        
        echo "<li>";
	echo makeLink(makeUrl(array("controller"=>"help")), "Help", $is_help?'selected':null);
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
if(defined('FC_URL_PATH')) {
    util::$path= FC_URL_PATH;
}

$app = new MyApp();
$app->main();

?>