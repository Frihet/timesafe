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

/*
 *
 * This file contains a general purpuse input parsing and validation class.
 *
 * Usage example:
 *
 * $in = new FCInput();
 * // action can be one of 'stop', 'walk' or 'run'. The default is 'walk'
 * $in->add( "action", "string", array("stop","walk","run"),"walk");
 * //  upload is an uploaded file
 * $in->add( "upload", "file" );
 * // dates is an array of dates
 * $in->add( "dates", array( "array", "date"));
 *
 * $params = $in->parse();
 *
 */


/**
 *
 * Helper class containing information about one specific parameter
 *
 */

class InputType
{
  var $name;
  var $types;
  var $param;
  var $default;

  function InputType( $n, $t, $p, $d )
    {
      if( !is_array( $t ) )
        $t = array( $t );

      $this->name = $n;
      $this->types = $t;
      $this->param = $p;
      $this->default = $d;
    }

  function validate( $evil, $pos = 0 )
    {
      $err = null;

      switch( $this->types[ $pos ] )
        {
        case "array":

            if( !is_array( $evil ) ) {
                $evil = array( $evil );
            }

            foreach( $evil as $key => $value ) {
                if( $err = $this->validate( $value, $pos+1 ) )
                    break;
            }

          break;

        case "email":
        case "date":
        case "date_YmdHM":
        case "alpha":
        case "numeric":
          $err = $this->validate_item( $evil, $this->types[ $pos ] );
          break;

        case "string":
          if( $this->param )
            {
              if( ! in_array( $evil, $this->param ) )
                {
                  $err = "Field '{$this->name}' has invalid value $evil";
                }
            }
          break;

        default:
          $err = "Field '{$this->name}' is of unknown input type ". $this->types[ $pos ];
          break;

        }

      return $err;
    }

  /**
   * Validate item making sure it is of specified type.
   *
   * @param str String to validate.
   * @param type Type str should have.
   * @return Error string or null if ok.
   */
  function validate_item ($str, $type) {
    switch ($type) {
    case 'alpha':
      if (preg_match ('/^[a-z]+$/i', $str) != 1) {
        return "Field '{$this->name}' should only contain letters!";
      }
      break;
    case 'email':
      if (preg_match ('/(.+@[a-z0-9.|.*<.+@[a-z0-9]+>)$/i', $str) != 1) {
        return "Field '{$this->name}' has value '$str', which is not a valid email adress!";
      }
      break;
    case 'numeric':
      if (preg_match ('/^[0-9]+$/', $str) != 1) {
        return "Field '{$this->name}' should only contain numbers!";
      }
      break;
    case 'date':
      if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $str) != 1) {
        return "Field '{$this->name}' is not in ISO date format YYYY-MM-DD ex 2006-01-18!";
      }
      break;
    case 'date_YmdHM':
      if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$/', $str) != 1) {
        return "Field '{$this->name}' is not in ISO date format with hours and minutes YYYY-MM-DD HH:MM ex 2006-01-18 13:23!";
      }
      break;
    default:
      return "Field {$this->name} is of unknown type!";
    }
    return null;
  }

}


/**
 *
 * The main class.
 *
 */

class FCInput {

  var $types;
  var $err;

  function Input()
    {
      $this->types = array();
      $this->err = null;
    }

  /**
   * Use this method to add parameters to the list
   */

  function add( $name, $types, $param=null, $default=null )
    {
      $this->types[$name] = new InputType( $name, $types, $param, $default );
    }

  /**
   * Method to parse input parameters.
   *
   * @return Returns associative array with parameters.
   */
  function parse ($input = null)
    {
      if (!$input)
	$input = $_REQUEST;

      $params = (object) null;

      foreach( $this->types as $name => $type )
        {
          $params->$name = null;

          if( isset( $input[$name] ) )
            {

              $value = $input[$name];
              
              $err = $type->validate( $value );

              if( $err )
                {
                  $this->err = $err;
                  $params = null;
                  break;
                }
              else
                {
                  $params->$name = $value;
                }
            }
          else if( $type->types[0] == "file"  )
            {
              if( isset( $_FILES[ $name ] ) )
                {
                  $file = 'files/' . $_FILES[ $name ]['name'];
                  if ( move_uploaded_file ($_FILES[ $name ]['tmp_name'], $file))
                    {
                      $params->$name = $file;
                    }
                }
            }
          else
            {
              $params->$name = $type->default;
            }
        }

      return $params;
  }

  function getError ()
    {
      return $this->err;
    }

};

?>
