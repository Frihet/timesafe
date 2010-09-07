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
 * @file FCConfig.php
 * @author Gustavo Zaera <gustavo.zaera@freecode.no>
 *
 * $Id: FCConfig.php 818 2007-09-18 11:42:28Z noosbrychr $
 *
 * Define constants and global vars for all FreeCode php scripts.
 */

// constants
define('DEBUG', 0);

// Home of weird egs settings. The ones we need should probably be documented somewhere.
//require_once('../egs/conf/config.php');

// DSN required to connect to database
//define ('DB_DSN', 'pgsql://egs:Aa9d_ol1@db.freecode.no/egs_s2009');
//define ('FREECODE_DB_DSN', 'pgsql://freecode:jki872k.I@db.freecode.no/freecode');

// URL path to egs
//define ('EGS_BASE', '/egs/');

//$ignore_errno = array(8, 2048);
$ignore_errno = array(2, 8, 2048);

define('TIMESAFE_DSN', "pgsql://timesafe:timesafe@localhost/timesafe");
define('TIMESAFE_BASE', '../');

/*
vim:expandtab:tabstop=2:shiftwidth=2
*/?>
