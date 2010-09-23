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
session_start();

if (isset($_SESSION['user'])) {
    $all = User::getAllUsers();
    $my_name = $_SESSION['user'];
    User::$me = $all[$my_name];
    User::$user = $all[param('user',$my_name)];
} else if (!in_array(param('controller'), array('login', 'help'))) {
    if (!isset($_SESSION['user'])) {
	util::redirect(makeUrl(array('controller'=>'login','task'=>'view')));
    }
}

class MyApp 
extends Application
{
    static $copyright = "© 2010 Freecode AS";
    static $copyright_link = "http://www.freecode.no";

    static $license = "GPL3";
    static $license_link = "http://www.gnu.org/licenses/gpl-3.0.html";

    function __construct()
    {
        $this->addScript('static/TimeSafe.js');
        //$this->addScript('static/jquery-ui.js');
        //$this->addScript('static/jquery.flot.js');
        $this->addStyle('static/TimeSafe.css', 'screen,projection,print');
        //$this->addStyle('static/jquery-ui.css');
	//require_once('egs.php');

    }
    
    
    /**
     Write out the top menu.
    */    
    function writeMenu($editor)
    {

        $is_admin = $editor->isAdmin();
        $is_help = $editor->isHelp();
        $is_tr = !$is_admin && !$is_help;

	echo "<ul class='main_menu'>\n";

        if (isset(User::$me)) {
	    echo "<li>";
	    echo makeLink(makeUrl(array("controller"=>"editor")), "Time registration", $is_tr?'selected':null);
	    echo "</li>\n";

	    echo "<li>";
	    echo makeLink(makeUrl(array("controller"=>"report")), "Reporting", $is_tr?'selected':null);
	    echo "</li>\n";

	    echo "<li>";
	    echo makeLink(makeUrl(array("controller"=>"adminUser")), "Users", $is_admin?'selected':null);
	    echo "</li>\n";

	    echo "<li>";
	    echo makeLink(makeUrl(array("controller"=>"adminProjectClass")), "Project classes", $is_admin?'selected':null);
	    echo "</li>\n";

	    echo "<li>";
	    echo makeLink(makeUrl(array("controller"=>"adminProject")), "Projects", $is_admin?'selected':null);
	    echo "</li>\n";

	    echo "<li>";
	    echo makeLink(makeUrl(array("controller"=>"adminTag")), "Tags", $is_admin?'selected':null);
	    echo "</li>\n";

	    echo "<li>";
	    echo makeLink(makeUrl(array("controller"=>"adminTagGroup")), "Tag groups", $is_admin?'selected':null);
	    echo "</li>\n";
	}

	echo "<li>";
	echo makeLink(makeUrl(array("controller"=>"help")), "Help", $is_help?'selected':null);
	echo "</li>";

	if (isset(User::$me)) {
	    echo "<li>";
	    echo makeLink(makeUrl(array("controller"=>"logout")), "Log out", null);
	    echo "</li>";
        } else {
	    echo "<li>";
	    echo makeLink(makeUrl(array("controller"=>"login")), "Log in", null);
	    echo "</li>";
	}
        echo "</ul>\n";
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