<?php
declare(strict_types=1);

// Using namespaces aliasing
use \Util\Util as Util;
use \Database\Database as Database;

// Set environment
require_once('../../../common/php/environment.php');

// Get arguments
$args = Util::getArgs();

// Connect to database
$db = new Database();

$result = "ok";

// Close connection
$db = null;

// Set response
Util::setResponse($result);