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

require_once 'Util.php';
require_once 'Display.php';

class DisplayIllness extends Display 
{

  /**
   * Render users.
   */
  function render () 
    {

      $grouped_hours = $this->group_hours ($this->hours, "entered", "desc" );

      $person=array();
      
      foreach($grouped_hours[0] as $key => $outer) {
          
          foreach($outer as $hour) {

              if (!$this->egs->is_illness($hour)) {
                  continue;
              }
              

              $name = $hour['fullname'];
              $proj = $hour['fptt'];
              $desc = $hour['description'];
              

              if( isSet($person[$name]) ) {
                  $p = $person[$name];
                  
              } else {
                  $p=null;
                  $p->name = $name;
                  $p->type = array();
                  
              }
              
              if (!isSet($p->type[$proj])) {
                  $p->type[$proj]->dates = array();
                  $p->type[$proj]->total_val = 0;
              }
              
              $p->type[$proj]->dates[] = $hour;
              $p->type[$proj]->total_val += $hour['seconds'];
              $p->type[$proj]->total_str = Util::seconds_to_string($p->type[$proj]->total_val);
              
              $person[$name]=$p;
          }              

      }
      
      $this->fc->smartyAssign ('person', array_values($person));

      $this->fc->smartyAssign ('TITLE', 'Illness specification');

      $this->fc->addSmartyCSS('static/css/invoice.css', 'screen,print');
      $this->fc->addSmartyCSS('static/css/invoice_print.css', 'print');

      /*
      $this->fc->smartyAssign ('date_from',
			     strftime('%a %d %b, %Y', strtotime ($this->date_from)));
      $this->fc->smartyAssign ('date_to',
			     strftime('%a %d %b, %Y', strtotime ($this->date_to)));
      */
      $this->fc->smartyDisplay ('index.tpl');
    }


};

?>
