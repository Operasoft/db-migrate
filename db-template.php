<?php
require_once __DIR__.'/vendor/autoload.php';

use DbMigrate\Config\Template\TemplateConfigurationManager;
use DbMigrate\Renderer\PhpTemplateRenderer;
use DbMigrate\Loader\DbTableLoader;
use DbMigrate\Model\DbStructure;
use DbMigrate\Renderer\PhpUnitTemplateRenderer;

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

// Prepare the template renderers
$phpRenderer = new PhpTemplateRenderer(new Twig_Loader_Filesystem(__DIR__.'/templates/php'), $config->getOutput().'/src');
$phpUnitRenderer = new PhpUnitTemplateRenderer(new Twig_Loader_Filesystem(__DIR__.'/templates/phpunit'), $config->getOutput().'/tests');

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
                $phpRenderer->renderModel($templateConfig, $table);
            }
            if (isset($templateConfig->getNamespace()['repository'])) {
                $phpRenderer->renderRepositoryInterface($templateConfig, $table);
            }
            if (isset($templateConfig->getNamespace()['repository_doctrine'])) {
                $phpRenderer->renderDoctrineRepository($templateConfig, $table);
                $phpUnitRenderer->renderDoctrineRepositoryTest($templateConfig, $table);

            }
        } else {
            DbTableLoader::loadConstants($mysqli, $table);
            if (isset($templateConfig->getNamespace()['model'])) {
                $phpRenderer->renderModelConstants($templateConfig, $table);
            }
        }
    }

    // Release the DB connections
    $mysqli->close();
}

echo "DONE".PHP_EOL;
