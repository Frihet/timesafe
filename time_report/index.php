<?php
/******************************************************************************
 *
 * Copyright © 2007
 *
 * FreeCode Norway AS
 * Slemdalsveien 70, NO-0370 Oslo, Norway
 * 0555 Oslo-N
 * Norway
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
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

require_once("../config.php");
/*
require_once("../common/index.php");
require_once("../model.php");
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
*/
  
/*
 If we are on the dev server, include development versions of the toolkit.
*/

$t1 = microtime(true);

require_once ('fctoolkit/FCToolkit.php');
require_once ('Model.php');

require_once("config.php");


define ('TIME_REPORT_VERSION', '$Id: index.php 1131 2007-10-24 10:10:20Z nooslilaxe $');
define ('TIME_REPORT_AUTHOR', '$Author: nooslilaxe $');

// Up the memory limit a bit, some of these queries can be pretty big.
// If 20 megabyte still isn't enough, we probably need to redo some
// parts of the algorithm...
ini_set('memory_limit','20M');

if (!isset($_REQUEST["type"])) 
{
    
    $_REQUEST["type"]="user";
    $now = time();
    $y = date("Y", $now);
    $m = date("m", $now);

    $_REQUEST["from"]=date("Y-m-d", mktime(0,0,0,$m, 1, $y));
    $_REQUEST["to"]=date("Y-m-d", mktime(0,0,0,$m+1, 0, $y));
    $_REQUEST["users"]=array('admin'); //$_SESSION['user']->user->name);
}

// Parse and validate input.
$input = new FCInput();

if (isset ($_REQUEST['type']) && ($_REQUEST['type'] =='vsd_graph'))
{
  $input->add( "x", "string", array( 'time','person'), 'time' );
  $input->add( "y", "string", array( 'vsd','billable','time'), 'vsd' );
}

$input->add( "from", "date" );
$input->add( "to", "date" );
$input->add( "type", "string", array( 'project', 'user', 'period', 'invoice', 'histogram','vsd','vsd_graph','salary','illness','project','project_graph','dc') );
$input->add( "sortby", "string", array ('username', 'entered', 'fptt', 'description','entered'), "entered" );
$input->add( "sortdirection", "string", array( "asc", "desc"), 'desc' );
$input->add( "printable", "string", array( "true", "false" ), "false" );
$input->add( "users", array( "array", "alpha" ) );
$input->add( "projectids", array( "array", "numeric" ) );
$input->add( "options", array( "array", "alpha" ) );
$input->add( "dom", "string", array( "full", "partial" ), "full" );

global $params;

if (!$params = $input->parse ($_REQUEST)) 
{
    var_dump($_REQUEST);
    
  FCToolkit::PrintError( $input->getError() );
}

/*
if ($params->dom == 'partial') 
{
    echo "<pre>";
    var_dump($_REQUEST);
    
    echo "</pre>";
}
*/

/**
 * Provide further validation of input parameters.
 *
 * @param params Parameters to validate.
 */

function validate_information (&$params) 
{
  $err = 0;
  
  if (isset ($params->type)) 
    {
      if ($params->type != 'invoice') 
	{
	  if (! isset ($params->from) || ! isset ($params->to)) 
	    {
	      $err = "Date parameters are missing";
	    }
	}
    }

  return $err;
}

if ($err = validate_information ($params)) 
{
  FCToolkit::PrintError ($input->getError ());
  $params = null;
}

$egs = new Model ();
$fc = new FCToolkit ();

$fc->addSmartyCSS('time_report.css.php', 'screen,projection');
$fc->addSmartyJS('time_report.js');

// Display
$display = null;


if (isset ($params->type)) {
    $do_group = in_array($params->type,array('vsd','vsd_graph'));
    
  $hours = $egs->get_hours ($params->from,
			    $params->to,
			    $params->projectids,
			    $params->users,
                            $do_group);
  
  // Fill in user information used as a base for grouped hours
  $users = array();
  if ($params->users) {
    $egs_users = $egs->get_users ();
    foreach ($params->users as $user) {
      if (array_key_exists ($user, $egs_users)) {
        $users[$user] = $egs_users[$user];
      }
    }
  }

  switch ($params->type) 
    {
    case 'user':
      require_once ('DisplayUsers.php');
      $display = new DisplayUsers ($fc,
				   $egs,
				   $hours,
                                   $users,
				   $params);
      
      break;

    case 'histogram':
      require_once ('DisplayHistogram.php');
      $display = new DisplayHistogram ($fc, 
				       $egs, 
				       $hours, $users,
				       $params);
      break;

    case 'period':
      require_once ('DisplayPeriod.php');
      $display = new DisplayPeriod ($fc,
				    $egs,
				    $hours, $users,
				    $params);

      break;

    case 'invoice':
      require_once ('DisplayInvoice.php');
      $display = new DisplayInvoice ($fc,
				     $egs,
				     $hours, $users,
				     $params);

      break;

    case 'salary':
      require_once ('DisplaySalary.php');
      $display = new DisplaySalary ($fc,
                                    $egs,
                                    $hours, $users,
                                    $params);

      break;

    case 'vsd':
      require_once ('DisplayVSD.php');
      $display = new DisplayVSD ($fc,
				 $egs,
				 $hours, 
				 $users,
				 $params);      
      break;

    case 'vsd_graph':
      require_once ('DisplayVSDGraph.php');
      $display = new DisplayVSDGraph ($fc,
				      $egs,
				      $hours, 
				      $users,
				      $params);

      break;

    case 'project':
      require_once ('DisplayProject.php');
      $display = new DisplayProject ($fc,
                                     $egs,
                                     $hours, 
                                     $users,
                                     $params);      
      break;

    case 'project_graph':
      require_once ('DisplayProjectGraph.php');
      $display = new DisplayProjectGraph ($fc,
                                          $egs,
                                          $hours, 
                                          $users,
                                          $params);

      break;

    case 'illness':
        require_once ('DisplayIllness.php');
        $display = new DisplayIllness ($fc,
                                       $egs,
                                       $hours, 
                                       $users,
                                       $params);
        
        break;

    case 'dc':
        require_once ('DisplayDC.php');
        $display = new DisplayDC ($fc,
                                  $egs,
                                  $hours, 
                                  $users,
                                  $params);
      
        break;

    default:
        echo "Unkown module \"{$params->type}\"";
        break;

    }
    
}
else 
{
    echo "Internal error: No page type";    
}

$t2 = microtime(true);
$fc->smartyAssign('render_time', sprintf("%.2f",$t2-$t1));
$fc->smartyAssign('dom', $params->dom);
$fc->smartyAssign('mode', $display->mode);

// Render output
if ($display) 
{
  $display->render ();
}

?>
