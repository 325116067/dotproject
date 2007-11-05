<?php
/* $Id$ */

/** {{{ Copyright (c) 2003-2005 The dotProject Development Team <core-developers@dotproject.net>
 *
 *  This file is part of dotProject.

 *  dotProject is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  dotProject is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with dotProject; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @license		http://www.gnu.org/licenses/gpl.txt GNU Public License (GPL)
 * @copyright	2003-2005 The dotProject Development Team <core-developers@dotproject.net>
 * 
 * @package		dotProject
 * @version		CVS: $Id$
 * }}}
 */

ini_set('display_errors', 1); // Ensure errors get to the user.
error_reporting(E_ALL & ~E_NOTICE);

global $baseDir;
global $baseUrl;

// only rely on env variables if not using a apache handler
function safe_get_env($name) 
{
	if (isset($_SERVER[$name])) {
		return $_SERVER[$name];
	} elseif (strpos(php_sapi_name(), 'apache') === false) {
		getenv($name);
	} else {
		return '';
	}
}

// Necessary for CGI mode
if (isset($_SERVER['PATH_TRANSLATED'])) {
    $baseDir = str_replace('index.php', '', $_SERVER['PATH_TRANSLATED']);
    // If $_SERVER variables are set.
} elseif (isset($_SERVER['SCRIPT_FILENAME'])) {
    $baseDir = str_replace('index.php', '', $_SERVER['SCRIPT_FILENAME']);
} else {
    $baseDir = str_replace('index.php', '', __FILE__);
}

// Set the include path to include sub directories of lib.
// This ensures third party libraries can work unencumbered.
set_include_path($baseDir .'/lib:.:'  . get_include_path());

// automatically define the base url
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
$baseUrl .= safe_get_env('HTTP_HOST');
$pathInfo = safe_get_env('PATH_INFO');
if (!empty($pathInfo)) {
	$baseUrl .= str_replace('\\', '/', dirname($pathInfo));
} else {
	$baseUrl .= str_replace('\\', '/', dirname(safe_get_env('SCRIPT_NAME')));
}

// If we are at the top level we will have a trailing slash, which we need to remove, otherwise we get invalid URLs for some servers (like IIS)
$baseUrl = preg_replace(':/*$:', '', $baseUrl);

// To avoid the usual problems with registered globals and other hacks, use a define rather
// than a global.  These are also used as a sentinel to stop direct calling of pages that shouldn't be.

define('DP_BASE_DIR', $baseDir);
define('DP_BASE_URL', $baseUrl);

// required includes for start-up
global $dPconfig;
$dPconfig = array();
?>
