<?php
namespace DbMigrate\Config\Template;

/**
 * Class TemplateConfig
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class TemplateConfig
{
    /** @var string The name of the database on the source host */
    private $source;

    /** @var array The list of namespaces defined */
    private $namespace;

    /** @var array The list of tables that should be excluded */
    private $excludeList;

    /** @var array The list of tables that only store constants*/
    private $constantsList;

    /**
     * DbConfig constructor.
     * @param string $source
     * @param array $namespace
     * @param array $content
     */
    public function __construct($source, $namespace, array $excludeList = null, array $constantsList = null)
    {
        $this->source = $source;
        $this->namespace = $namespace;
        $this->excludeList = $excludeList;
        $this->constantsList = $constantsList;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return array
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param array $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * Checks if a table must be excluded
     * @param $table
     *
     * @return bool
     */
    public function isExcluded($table) {
        if (empty($this->excludeList)) {
            return false;
        }
        return in_array($table, $this->excludeList);
    }

    /**
     * Checks if a table only contains constants
     * @param $table
     *
     * @return bool
     */
    public function isConstant($table) {
        if (empty($this->constantsList)) {
            return false;
        }
        return in_array($table, $this->constantsList);
    }

}