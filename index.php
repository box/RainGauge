<?php
/**
 * This is the main loader and controller init script for the Rain Gauge project
 * It just loads the config, creates a controller and invokes it.  See
 * lib/RainGauge.php for the main project code.
 * @author Gavin Towey <gavin@box.com>, Geoffrey Anderson <ganderson@box.com>
 * @created 2012-05-15
 * @license Apache 2.0 license.  See LICENSE document for more info
 **/

set_include_path( get_include_path() . PATH_SEPARATOR . "./lib");
require "Helpers.php";
require "RainGauge.php";

error_reporting(E_ERROR);
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

$conf = array();
include "conf/config.inc.php";
if (empty($conf))
{
	$action = 'noconfig';
}

$controller = new RainGauge($conf);
if (is_callable(array($controller, $action )))
{
	$controller->$action();
}
else
{
	print "Invalid action ($action)";
}

?>
