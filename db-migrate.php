<?php
require_once(__DIR__.'/DbStructure.inc.php');
require_once(__DIR__.'/DbStructureCompare.inc.php');
require_once(__DIR__.'/DbContentCompare.inc.php');

//-----------------------------------------------------------------------------
// Make sure the config file defines the proper variables
//-----------------------------------------------------------------------------
function validateConfig($config) {
	$result = true;
	
	if (empty($config['db_src'])) {
		echo "ERROR: 'db_src' variable missing from config file".PHP_EOL;
		$result = false;
	}
	
	if (empty($config['db_target'])) {
		echo "ERROR: 'db_target' variable missing from config file".PHP_EOL;
		$result = false;
	}

	if (!isset($config['db_tables'])) {
		echo "ERROR: 'db_tables' variable missing from config file".PHP_EOL;
		$result = false;
	}
	
	return $result;
}

//-----------------------------------------------------------------------------
// MAIN LOOP
//-----------------------------------------------------------------------------
if (empty($argv[1])) {
	die("ERROR: missing config file");
}
echo "Using config file: ".$argv[1].PHP_EOL;
$config = include($argv[1]);

if (!validateConfig($config)) {
	die("ERROR: Invalid config file provided");
}
echo "Config file validated".PHP_EOL;

$compareStructure = true;
$compareContent = true;

if (!empty($argv[2])) {
	if ($argv[2] == "-contentOnly") {
		$compareStructure = false;
	} else if ($argv[2] == "-structureOnly") {
		$compareContent = false;		
	} else {
		die("Unknown parameter: ".$argv[2]);
	}
}

// Setup the DB connections
$srcDb = mysqli_init();
$srcDb->real_connect($config['db_src']['host'], $config['db_src']['username'], $config['db_src']['password'], $config['db_src']['database']);
if ($srcDb->connect_errno) {
	die("Failed to connect to source database: (" . $srcDb->connect_errno . ") " . $srcDb->connect_error);
}
echo "Connected to source database ".$config['db_src']['host']." - ".$config['db_src']['database'].PHP_EOL;

$targetDb = mysqli_init();
$targetDb->real_connect($config['db_target']['host'], $config['db_target']['username'], $config['db_target']['password'], $config['db_target']['database']);
if ($targetDb->connect_errno) {
	$srcDb->close();
	die("Failed to connect to target database: (" . $targetDb->connect_errno . ") " . $targetDb->connect_error);
}
echo "Connected to target database ".$config['db_target']['host']." - ".$config['db_target']['database'].PHP_EOL;

$date = date('Y-m-d_His');

// Load the source database structure
$srcDb_structure = new DbStructure($srcDb, $config['db_src']['database'], $config['db_src']['username']);

if ($compareStructure) {
	echo "Comparing DB structures...".PHP_EOL;
	
	// Load the target database structure
	$targetDb_structure = new DbStructure($targetDb, $config['db_target']['database'], $config['db_target']['username']);

	//-----------------------------------------------------------------------------
	// Compare both DB structures and generate the migration script
	//-----------------------------------------------------------------------------
	$tool = new DbStructureCompare($srcDb_structure, $targetDb_structure);
	$scripts = $tool->compareStructure();
	
	// Store the migration script to file
	$script = implode(PHP_EOL, $scripts);
	$name = "migrate-structure-".$config['db_src']['database']."-".$config['db_target']['database']."-".$date.".sql";
	$fp = fopen(__DIR__."/$name", "w");
	fwrite($fp, $script);
	fclose($fp);

	echo "Structure migration script stored in file $name".PHP_EOL;	
}

if ($compareContent) {
	//-----------------------------------------------------------------------------
	// Compare table contents and generate the migration script
	//-----------------------------------------------------------------------------
	$tool = new DbContentCompare($srcDb_structure, $srcDb, $targetDb);
	$scripts = array();
	foreach ($config['db_tables'] as $name => $values) {
		$scripts[] = $tool->compareTable($name, $values['mode'], $values['key']);	
	}
	
	// Store the migration script to file
	$script = implode(PHP_EOL, $scripts);
	$name = "migrate-content-".$config['db_src']['database']."-".$config['db_target']['database']."-".$date.".sql";
	$fp = fopen(__DIR__."/$name", "w");
	fwrite($fp, $script);
	fclose($fp);

	echo "Content migration script stored in file $name".PHP_EOL;	
}

// Release the DB connections
$srcDb->close();
$targetDb->close();
echo "DONE".PHP_EOL;