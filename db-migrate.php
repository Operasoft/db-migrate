<?php
spl_autoload_register(function ($class_name) {
    require 'src/'.$class_name . '.php';
});

use DbMigrate\Comparator\DbContentComparator;
use DbMigrate\Comparator\DbStructureComparator;
use DbMigrate\Config\ConfigurationManager;
use DbMigrate\Model\DbStructure;

//-----------------------------------------------------------------------------
// MAIN LOOP
//-----------------------------------------------------------------------------
date_default_timezone_set('UTC');

if (empty($argv[1])) {
	die("ERROR: missing config file");
}
echo "Using config file: ".$argv[1].PHP_EOL;

$config = new ConfigurationManager();
$config->load($argv[1]);

$source = $config->getSourceDb();
$target = $config->getTargetDb();

foreach ($config->getDbConfigs() as $dbConfig) {
    //
    // Step 1: Setup DB connections
    echo 'Connecting to source DB '.$dbConfig->getSource().'...'.PHP_EOL;
    $srcDb = $source->connect($dbConfig->getSource());

    echo 'Connecting to target DB '.$dbConfig->getTarget().'...'.PHP_EOL;
    $targetDb = $target->connect($dbConfig->getTarget());

    $date = date('Y-m-d');

    //
    // Step 2: Load the source database structure
    $srcDb_structure = new DbStructure($srcDb, $dbConfig->getSource(), $source->getUsername());

    if ($dbConfig->isStructure()) {
        echo "Comparing DB structures...".PHP_EOL;

        // Load the target database structure
        $targetDb_structure = new DbStructure($targetDb, $dbConfig->getTarget(), $target->getUsername());

        //-----------------------------------------------------------------------------
        // Compare both DB structures and generate the migration script
        //-----------------------------------------------------------------------------
        $tool = new DbStructureComparator($srcDb_structure, $targetDb_structure);
        $scripts = $tool->compareStructure();

        // Store the migration script to file
        $name = $config->getOutput().'/'.$source->getName().'-'.$target->getName().'-'.$dbConfig->getSource().'_schema'."-".$date.".sql";

        $script = implode(PHP_EOL, $scripts);
        if (!empty(trim($script))) {
            file_put_contents($name, $script);
            echo "Schema migration script stored in file $name".PHP_EOL;
        } else {
            echo "NO SCHEMA MIGRATION REQUIRED".PHP_EOL;
            if (file_exists($name)) {
                unlink($name);
            }
        }
    }

    if (!empty($dbConfig->getContent())) {
        //-----------------------------------------------------------------------------
        // Compare table contents and generate the migration script
        //-----------------------------------------------------------------------------
        $tool = new DbContentComparator($srcDb_structure, $srcDb, $targetDb);
        $scripts = array();
        foreach ($dbConfig->getContent() as $content) {
            $scripts[] = $tool->compareTable($content['table'], $content['mode'], $content['key']);
        }

        // Store the migration script to file
        $name = $config->getOutput() . '/' . $source->getName() . '-' . $target->getName() . '-' . $dbConfig->getSource() . '_content' . "-" . $date . ".sql";

        $script = implode(PHP_EOL, $scripts);
        if (!empty(trim($script))) {
            file_put_contents($name, $script);
            echo "Content migration script stored in file $name" . PHP_EOL;
        } else {
            echo "NO CONTENT MIGRATION REQUIRED".PHP_EOL;
            if (file_exists($name)) {
                unlink($name);
            }
        }

    }

    // Release the DB connections
    $srcDb->close();
    $targetDb->close();
}

echo "DONE".PHP_EOL;