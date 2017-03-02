<?php
namespace DbMigrate\Config;

/**
 * Class ConfigurationManager
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class ConfigurationManager
{
    /** @var DbConnection */
    private $sourceDb;

    /** @var DbConnection */
    private $targetDb;

    /** @var string */
    private $output = './';

    /** @var DbConfig[] */
    private $dbConfigs;

    public function load($file) {
        $string = $this->loadConfigFile($file);

        $json = json_decode($string, true);

        if (!isset($json['source'])) {
            throw new \Exception('Missing source configuration parameter');
        }
        $this->sourceDb = new DbConnection($json['source']['name'], $json['source']['host'], $json['source']['username'], $json['source']['password']);

        if (!isset($json['target'])) {
            throw new \Exception('Missing target configuration parameter');
        }
        $this->targetDb = new DbConnection($json['target']['name'], $json['target']['host'], $json['target']['username'], $json['target']['password']);

        if (!isset($json['databases'])) {
            throw new \Exception('Missing databases configuration parameter');
        }

        $this->dbConfigs = array();
        foreach($json['databases'] as $db) {
            $this->dbConfigs[] = new DbConfig($db['source'], $db['target'], $db['structure'], $db['content']);
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
     * @return DbConnection
     */
    public function getTargetDb()
    {
        return $this->targetDb;
    }

    /**
     * @return DbConfig[]
     */
    public function getDbConfigs()
    {
        return $this->dbConfigs;
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