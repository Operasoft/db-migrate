<?php
namespace DbMigrate\Config\Template;

use DbMigrate\Config\DbConnection;

/**
 * Class TemplateConfigurationManager
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class TemplateConfigurationManager
{
    /** @var DbConnection */
    private $sourceDb;

    /** @var string */
    private $output = './';

    /** @var TemplateConfig[] */
    private $templateConfigs;

    public function load($file) {
        $string = $this->loadConfigFile($file);

        $json = json_decode($string, true);

        if (!isset($json['source'])) {
            throw new \Exception('Missing source configuration parameter');
        }
        $this->sourceDb = new DbConnection($json['source']['name'], $json['source']['host'], $json['source']['username'], $json['source']['password']);

        if (!isset($json['databases'])) {
            throw new \Exception('Missing databases configuration parameter');
        }

        $this->templateConfigs = array();
        foreach($json['databases'] as $db) {
            $this->templateConfigs[] = new TemplateConfig($db['name'], $db['namespace'], $db['excludes'], $db['constants']);
        }

        if (isset($json['output'])) {
            $this->output = $json['output'];
        }
    }

    /**
     * @return DbConnection
     */
    public function getSourceDb()
    {
        return $this->sourceDb;
    }

    /**
     * @return TemplateConfig[]
     */
    public function getTemplateConfigs()
    {
        return $this->templateConfigs;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    private function loadConfigFile($file)
    {
        $result = "";
        $string = file_get_contents($file);
        // JSON files do not support comments, while our configuration file do if a line starts with //
        // Strip out those lines.
        $lines = explode("\n", $string);
        foreach ($lines as $line) {
            if (substr(trim($line), 0, 2 ) !== "//") {
                $result .= $line."\n";
            }
        }
        return $result;
    }


}