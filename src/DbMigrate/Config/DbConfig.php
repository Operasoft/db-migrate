<?php
namespace DbMigrate\Config;

/**
 * Class DbConfig
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class DbConfig
{
    /** @var string The name of the database on the source host */
    private $source;

    /** @var string The name of the database on the target host */
    private $target;

    /** @var boolean Whether we want to migrate the structure of this DB */
    private $structure;

    /** @var array The list of tables that we want to migrate content */
    private $content;

    /**
     * DbConfig constructor.
     * @param string $source
     * @param string $target
     * @param bool $structure
     * @param array $content
     */
    public function __construct($source, $target, $structure = true, array $content = null)
    {
        $this->source = $source;
        $this->target = $target;
        $this->structure = $structure;
        $this->content = $content;
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
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param string $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return boolean
     */
    public function isStructure()
    {
        return $this->structure;
    }

    /**
     * @param boolean $structure
     */
    public function setStructure($structure)
    {
        $this->structure = $structure;
    }

    /**
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param array $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

}