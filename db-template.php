<?php
require_once __DIR__.'/vendor/autoload.php';

use DbMigrate\Config\Template\TemplateConfigurationManager;
use DbMigrate\Generator\TemplateGenerator;
use DbMigrate\Loader\DbTableLoader;
use DbMigrate\Model\DbStructure;

//-----------------------------------------------------------------------------
// MAIN LOOP
//-----------------------------------------------------------------------------
date_default_timezone_set('UTC');

if (empty($argv[1])) {
    die("ERROR: missing config file");
}
echo "Using config file: ".$argv[1].PHP_EOL;

$config = new TemplateConfigurationManager();
$config->load($argv[1]);

// Prepare the template generator
$loader = new Twig_Loader_Filesystem(__DIR__.'/templates/php');

$generator = new TemplateGenerator($loader, $config->getOutput());
$source = $config->getSourceDb();

foreach ($config->getTemplateConfigs() as $templateConfig) {
    //
    // Step 1: Setup DB connections
    echo 'Connecting to source DB ' . $templateConfig->getName() . '...' . PHP_EOL;
    $mysqli = $source->connect($templateConfig->getName());

    $date = date('Y-m-d');

    //
    // Step 2: Load the source database structure
    $structure = new DbStructure($mysqli, $templateConfig->getName(), $source->getUsername());

    //
    // Step 3: Render the Model templates
    foreach ($structure->tables as $table) {
        if ($templateConfig->isExcluded($table->name)) {
            echo "Skipping table from ignore list {$table->name}".PHP_EOL;
            continue;
        }

        if (!$templateConfig->isConstant($table->name)) {
            if (isset($templateConfig->getNamespace()['model'])) {
                $generator->renderModel($templateConfig, $table);
            }
            if (isset($templateConfig->getNamespace()['repository'])) {
                $generator->renderRepositoryInterface($templateConfig, $table);
            }
        } else {
            DbTableLoader::loadConstants($mysqli, $table);
            if (isset($templateConfig->getNamespace()['model'])) {
                $generator->renderModelConstants($templateConfig, $table);
            }
        }
    }

    // Release the DB connections
    $mysqli->close();
}

echo "DONE".PHP_EOL;
