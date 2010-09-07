<?php
/*
 * Copyright © 2007 FreeCode AS
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * @file FCToolkit.php
 * @author Gustavo Zaera <gustavo.zaera@freecode.no>
 *
 * $Id: FCToolkit.php 4201 2009-06-13 12:23:48Z nooslilaxe $
 *
 * FreeCode PHP toolkit includes functionality to handle input parsing,
 * mail sending and other useful utilities.
 */

/* Uggly bug-workaround in stupid PHP */
date_default_timezone_set(date_default_timezone_get());

require_once('FCConfig.php');
require_once('smarty/libs/Smarty.class.php');
if (is_dir('fctoolkit-dev')) {
    require_once('fctoolkit-dev/php/FCInput.php');
 }
 else {
    require_once('FCInput.php');
 }

class FCToolkit
{
  var $log_info;
  var $log_error;

  var $smarty;
  var $smarty_css;
  var $smarty_js;

  function PrintArray( $a, $level = 0 )
    {
      $res = "";
      if( is_array( $a ) )
        {
          foreach( $a as $k => $v )
            {
              for( $i = 0; $i < $level; $i++ )
                {
                  $res .= "&nbsp;&nbsp;";
                }
              $res .= "{$k}:";
              if( is_array( $v ) )
                {
                  $res .="<br>\n";
                  $res .= FCToolkit::PrintArray( $v, $level+1 );
                }
              else
                {
                  $res .="“{$v}”<br>\n";
                }
            }
        }
      else
        {
          for( $i = 0; $i < $level; $i++ )
            {
              $res .= "&nbsp;&nbsp;";
            }
          $res .= "“{$a}”<br>\n";

        }
      return $res;
    }

  // Constructor
  function FCToolkit()
    {
      $this->log_info = array();
      $this->log_error = array();

      $this->smarty = new Smarty();

      $this->smarty_css = array('fctoolkit/static/css/freecode.css' => 'screen,projection,print',
                                'fctoolkit/static/css/freecode_screen.css' => 'screen,projection',
                                'fctoolkit/static/css/freecode_print.css' => 'print',
                                'fctoolkit/static/css/calendar-blue.css' => 'screen,projection');

      $this->smarty_js = array('fctoolkit/static/js/prototype.js',
                               'fctoolkit/static/js/calendar.js',
                               'fctoolkit/static/js/calendar-en.js',
                               'fctoolkit/static/js/calendar-setup.js');

      $this->smarty->template_dir = './smarty/templates';
      $this->smarty->compile_dir  = './smarty/templates_c';
      $this->smarty->cache_dir    = './smarty/cache';
      $this->smarty->config_dir   = './smarty/configs';

      // Setup template dir for shared templates
      $this->smarty->assign('TEMPLATE_DIR', dirname(__FILE__) . '/templates');
    }

  /**
   * Add css to css list.
   *
   * @param css URL to css file.
   */
  function addSmartyCSS($css, $media) {
    $this->smarty_css[$css] = $media;
  }

  /**
   * Add javascript to js list.
   *
   * @param js URL to js file.
   */
  function addSmartyJS($js) {
    array_push($this->smarty_js, $js);
  }

  /**
   * Setup time data.
   */
  function initTime() {
      // Build hours and minutes array
      $hours = array();
      for ($i = 0; $i < 24; $i++) {
          array_push($hours, $i);
      }
      $this->smarty->assign('POSSIBLE_HOURS', $hours);
      $minutes = array();
      for ($i = 0; $i < 60; $i++) {
          array_push($minutes, $i);
      }
      $this->smarty->assign('POSSIBLE_MINUTES', $minutes);

      $this->smarty->assign('CURRENT_DATE', strftime('%Y-%m-%d'));
      $this->smarty->assign('CURRENT_HOUR', strftime('%H'));
      $this->smarty->assign('CURRENT_MINUTE', strftime('%M'));

      $this->smarty->assign('CURRENT_DATETIME_Ymd', strftime('%Y-%m-%d'));
      $this->smarty->assign('CURRENT_DATETIME_HM', strftime('%H:%M'));
      $this->smarty->assign('CURRENT_DATETIME_HMS', strftime('%H:%M:S'));

      $this->smarty->assign('CURRENT_DATETIME_YmdHM', strftime('%Y-%m-%d %H:%M'));
      $this->smarty->assign('CURRENT_DATETIME_YmdHMS', strftime('%Y-%m-%d %H:%M:S'));
  }

  /**
   * Display smarty and it's template.
   *
   * @param strTemplate Name of template.
   * @param strTitle Name of page, can be omitted.
   */
  function smartyDisplay($strTemplate, $strTitle=null) {
    // Assign non-public info
    $this->smarty->assign('LOG_INFO', $this->log_info);
    $this->smarty->assign('LOG_ERROR', $this->log_error);
    $this->smarty->assign('INCLUDE_CSS', $this->smarty_css);
    $this->smarty->assign('INCLUDE_JS', $this->smarty_js);

    // Display template
    if ($strTitle != null) {
      $this->smarty->assign('TITLE', $strTitle);
    }
    $this->smarty->display($strTemplate);
  }

  /**
   * Assign value to smarty template.
   *
   * @param strName Name of variable.
   * @param strValue Value of variable.
   */
  function smartyAssign($strName, $strValue) {
    $this->smarty->assign($strName, $strValue);
  }

  function close()
    {
    }

  /**
   * Return username of the authenticated user, exit if not logged in.
   *
   * @return Username of the authenticated user.
   */
  function getAuthUser() {
    if( !isset($_SERVER['PHP_AUTH_USER'])) {
        header('HTTP/1.0 401 Unauthorized');
        header('www-authenticate: basic realm="FreeCode"');
        FCToolkit::PrintError('ERROR: Please login before using this site!');
        exit(1);
    }

    return $_SERVER['PHP_AUTH_USER'];
  }

  /**
   * Return user information for logged in user, username is the only
   * field that will always be returned.
   *
   * @return array(attr=>value) with user information.
   */
  function getAuthUserInfo() {
    // Get authenticated user from web server, use that information to
    // search the LDAP database for information.
    $uid = $this->getAuthUser();
    $userinfo = array('username' => $uid);

    return $userinfo;
  }

  // function for printing a debug message line with position info
  function Debug( $strMsg )
    {
      if( !DEBUG )
        return;

      $bt = debug_backtrace();
      $bt = array_shift( $bt );

      $strFile = basename($bt['file']);
      $nLineNum = $bt['line'];

      print( "<b>$strFile:$nLineNum:</b> $strMsg<br/>\n" );
    }

  // function for formating an error
  function Error( $strMsg )
    {
      $bt = debug_backtrace();
      $bt = array_shift( $bt );

      $strFile = basename($bt['file']);
      $nLineNum = $bt['line'];

      return "<b>$strFile:$nLineNum: <font color='red'>$strMsg</font></b><br/>\n";
    }

  // function for printing a debug message line with position info
  function PrintError( $strMsg ) {

    /*
     * Don't use TCToolkit::Error internally here since the backtrace
     * doesn't always work then.
     */

      $bt = debug_backtrace();
      $bt = array_shift( $bt );

      $strFile = basename($bt['file']);
      $nLineNum = $bt['line'];

      print "<b>$strFile:$nLineNum: <font color='red'>$strMsg</font></b><br/>\n";
  }

  // function for printing a debug message line with position info
  function PrintMessage( $strMsg ) {
    print FCToolkit::Message( $strMsg );
  }

  // function for formating a message line
  function Message( $strMsg ) {
    return "<b>$strMsg</b><br/>\n";
  }

  /**
   * Add log info for display.
   *
   * @param $strTitle Title of info message.
   * @param $strMessage Message of info, default is null.
   */
  function logInfo( $strTitle, $strMessage=null ) {
    if ($strMessage) {
      array_push($this->log_info, array('title' => $strTitle,
                                        'message' => $strMessage));
    } else {
      array_push($this->log_info, array('title' => $strTitle));
    }
  }

  /**
   * Add log error for display.
   *
   * @param $strTitle Title of error message.
   * @param $strMessage Message of error, default is null.
   */
  function logError( $strTitle, $strMessage=null ) {
    if ($strMessage) {
      array_push($this->log_error, array('title' => $strTitle,
                                        'message' => $strMessage));
    } else {
      array_push($this->log_error, array('title' => $strTitle));
    }
  }

  /**
   * Parse a list of email addresses on the form
   *
   * Anders Andersson <anders.andersson@example.com>, John Doe <john.doe@example.com>
   *
   * @param text The list of addresses to parse
   * @return Associative array of email_address => name
   */
  function parseEmailAddresses ($text) {
    $values = array();
    if (preg_match_all('/([^<,]*)\s*<([^>,]+)>/', $text, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $values[trim($match[2])] = trim($match[1]);
      }
    }
    return $values;
  }

  /**
   * Send mail to one target.
   *
   * @param from Email mail is sent from.
   * @param subject Subject of mail.
   * @param body Body of mail, txt formatted.
   * @param file File to attach, null if none.
   * @param to array email => name with recipients.
   * @param cc array email => name with cc recipients.
   * @param bcc array email => name with bcc recipients.
   * @return Number of mails sent, -1 on error.
   */
  function SendOneMail($from, $subject, $body, $file, $to, $cc=null, $bcc=null, $headers=null) {
    // Make sure mail is include
    require_once('Mail.php');
    require_once('Mail/mime.php');

    // Parameters
    $text = $body;
    $html = $this->_FormatHtmlMail($text);
    $crlf = "\n";
    $recipients = array();
    $hdrs = array('From' => $from, 'Subject' => $subject);

    $recipient_types = array('To' => $to);
    if ($cc != null) {
      $recipient_types['Cc'] = $cc;
    }
    if ($bcc != null) {
      $recipient_types['Bcc'] = $bcc;
    }

    // Add recipients headers
    foreach ($recipient_types as $header => $values) {
      $hdr = array();
      foreach (array_keys($values) as $name) {
        if (is_int($name)) {
          $name = $email = $values[$name];
        } else {
          $email = $values[$name];
        }
        array_push($hdr, "$name <$email>");
        array_push($recipients, "$name <$email>");
      }
      $hdrs[$header] = implode(', ', $hdr);
    }

    // Add custom headers
    if ($headers != null) {
      foreach ($headers as $header => $value) {
        $hdrs[$header] = $value;
      }
    }

    // Build mail message
    $mime = new Mail_mime($crlf);

    $mime->setTXTBody($text);
    $mime->setHTMLBody($html);
    if ($file) {
      $mime->addAttachment($file, mime_content_type($file));
    }

    $body = $mime->get(array('head_charset' => 'UTF-8',
                             'text_charset' => 'UTF-8',
                             'html_charset' => 'UTF-8'));
    $hdrs = $mime->headers($hdrs);

    $mail =& Mail::factory('mail');
    // NOTE: Not setting recipients here as it ends up as double To
    // headers in the e-mail.
    $res = $mail->send($to, $hdrs, $body);

    return $res;
  }

  /**
   * Send multiple mails.
   *
   * @param from Email mail is sent from.
   * @param subject Subject of mail.
   * @param body Body of mail, txt formatted.
   * @param file File to attach, null if none.
   * @param target array email => name with recipients.
   * @return Number of mails sent, -1 on error.
   */
  function SendMail($from, $subject, $body, $file, $target, $headers=null) {
    foreach ($target as $email => $name)
    if (($res = $this->SendOneMail($from, $subject, $body, $file, array($email => $name), null, null, $headers)) < 0) {
	if ($file)
	  unlink ($file);
        return $res;
      }

    if ($file)
      unlink ($file);

    return count($target);
  }

  /**
   * Formats HTML part of e-mail change \n to <br>\n and escaping chars.
   *
   * @param msg Body of message to handle.
   * @return Handleded version of message.
   */
  function _FormatHtmlMail ($msg) {
    // Escape special characters.
    $msg = htmlspecialchars ($msg);

    // Replace \n with <br />\n
    $msg = str_replace ("\n", "<br />\n", $msg);

    // Wrap message in html and body.
    $msg = "<html><body>$msg</body></html>";

    return $msg;
  }

  /**
Like var_dump, but returns value as string instead of printing it
  */
  function VarDescribe ($arg)
    {
      ob_start();
      var_dump($arg);
      $my_string = ob_get_contents();
      ob_end_clean();

      return $my_string;
    }

  /**
Returns a html-formated stack trace
  */
  function GetStackTrace ($slice = 0)
    {
      $st = debug_backtrace ();
      $res = "<table>
<tr><th>Function</th><th>File</th><th>Line</th></tr>\n";

      $st = array_slice ($st, $slice+1);

      foreach ($st as $i)
        {
          $arg_str = array ();
          if (isset ($i['args']))
            {
              foreach ($i['args'] as $arg)
                {
                  if (is_numeric ($arg))
                    {
                      $arg_str[] = "$arg";
                    }
                  else if (is_string ($arg))
                    {
                      $msg = $arg;
                      if (strlen($arg) > 20)
                        {
                          $msg = substr ($msg, 0, 15). "...";
                        }
                      $arg_str[] = "'".htmlentities($msg)."'";
                }
                  else if (is_null ($arg))
                    {
                      $arg_str[] = "&lt;null&gt;";
                    }
                  else if (is_array($arg))
                    {
                      $arg_str [] = "&lt;array&gt;[" . count ($arg) . "]";
                    }
                  else if (is_object ($arg))
                    {
                      $arg_str [] = "&lt;object&gt;";
                    }
                  else
                    {
                      $arg_str [] = "&lt;unknown&gt;";
                    }
                }
            }
          $string = "<tr><td><b>";
          if (isset ($i['class']))
            $string .= $i['class']. "::";

          if (isset ($i['function']))
            $string .= $i['function'];

          $string .=" (". implode (", ", $arg_str). ")</b>: ";
          $string .= "</td><td>\n";


          if (isset ($i['file']))
            {
              $file = $i['file'];
              if (strlen ($file) > 30)
                {
                  $file = "..." . substr ($file, strlen ($file)-27);
                }
              $string .= $file;
            }
          $string .= "</td><td style='text-align: right;'>\n";

          if (isset ($i['line']))
            {
              $string .= $i['line'];
            }

          $string .= "</td></tr>\n";
          $res  .= $string;
        }

      $res .= "</table>\n";

      return $res;
    }

}
function FCToolkitErrorHandler ($errno, $errstr, $errfile, $errline)
{
    global $ignore_errno;
    
    if(in_array($errno, $ignore_errno) && !DEBUG ) {
        return true;
        }
    switch ($errno)
            {
            case E_USER_ERROR:
                echo "<b>Application error:</b> [$errno] $errstr<br />\n";
                echo "  Fatal error on line $errline in file $errfile";
                echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                echo "Stack trace:";
                echo FCToolkit::GetStackTrace (0);
                echo "Aborting...<br />\n";
                exit(1);
                break;
                
            case E_USER_WARNING:
                echo "<b>Application warning:</b> [$errno] $errstr<br />\n";
                echo "Stack trace:";
                echo FCToolkit::GetStackTrace (0);
                break;
                
            case E_USER_NOTICE:
                echo "<b>Application notice:</b> [$errno] $errstr<br />\n";
                echo "Stack trace:";
                echo FCToolkit::GetStackTrace (0);
                break;
                
            default:
                echo "Unknown error type: [$errno] $errstr<br />\n";
                echo "Stack trace:";
                echo FCToolkit::GetStackTrace (0);
                break;
            }

    /* Don't execute PHP internal error handler */
    return true;

    }
set_error_handler("FCToolkitErrorHandler");

/**
        Returns a textual representation of an arbitrary object as a
        text string. The result is identical to that of var_dump,
        except it's returned, not printed.
*/
function var_str($var)
{
    ob_start();
    var_dump($var);
    $res = ob_get_contents();
    ob_end_clean();
    return $res;
}

/*
        Undo the evil of magic quotes.
*/

function stripslashes_deep($value)
{
    $value = is_array($value) ?
                array_map('stripslashes_deep', $value) :
                stripslashes($value);

    return $value;
}

function check_magic_quotes() 
{
    if (get_magic_quotes_gpc()) {
        
        $_REQUEST = stripslashes_deep($_REQUEST);
        $_GET = stripslashes_deep($_GET);
        $_POST = stripslashes_deep($_POST);
    }
    
}

check_magic_quotes();

/*
vim:expandtab:tabstop=4:shiftwidth=4
*/?>
