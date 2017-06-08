<?php
namespace DbMigrate\Model;

/**
 * Class DbKey
 * @package DbMigrate\Model
 * @author Christian Labonté
 */
class DbKey
{
    /** @var string */
    private $name;

    /** @var string */
    private $definition;

    /**
     * DbKey constructor.
     * @param string $name
     * @param string $definition
     */
    public function __construct($name, $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param string $definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    public function isPrimary()
    {
        return (strpos($this->definition, "PRIMARY KEY") !== FALSE);
    }
}