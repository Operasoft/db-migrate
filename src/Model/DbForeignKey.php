<?php
namespace DbMigrate\Model;

/**
 * Class DbForeignKey
 * @package DbMigrate\Model
 * @author Christian Labonté
 */
class DbForeignKey
{
    /** @var string */
    private $id;
    /** @var string */
    private $name;
    /** @var string */
    private $parentTable;
    /** @var string */
    private $parentField;
    /** @var string */
    private $childTable;
    /** @var string */
    private $childField;

    /**
     * DbForeignKey constructor.
     * @param string $parentTable
     * @param string $parentField
     * @param string $childTable
     * @param string $childField
     */
    public function __construct($name, $parentTable, $parentField, $childTable, $childField)
    {
        $this->id = $parentTable.'.'.$name;
        $this->name = $name;
        $this->parentTable = $parentTable;
        $this->parentField = $parentField;
        $this->childTable = $childTable;
        $this->childField = $childField;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
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
    public function getParentTable()
    {
        return $this->parentTable;
    }

    /**
     * @param string $parentTable
     */
    public function setParentTable($parentTable)
    {
        $this->parentTable = $parentTable;
    }

    /**
     * @return string
     */
    public function getParentField()
    {
        return $this->parentField;
    }

    /**
     * @param string $parentField
     */
    public function setParentField($parentField)
    {
        $this->parentField = $parentField;
    }

    /**
     * @return string
     */
    public function getChildTable()
    {
        return $this->childTable;
    }

    /**
     * @param string $childTable
     */
    public function setChildTable($childTable)
    {
        $this->childTable = $childTable;
    }

    /**
     * @return string
     */
    public function getChildField()
    {
        return $this->childField;
    }

    /**
     * @param string $childField
     */
    public function setChildField($childField)
    {
        $this->childField = $childField;
    }

    /**
     * Checks if 2 foreign keys are equal
     * @param DbForeignKey $key
     *
     * @return bool
     */
    public function equals(DbForeignKey $key) {
        if ($this->name != $key->getName()) {
            return false;
        }

        if ($this->parentTable != $key->getParentTable()) {
            return false;
        }

        if ($this->parentField != $key->getParentField()) {
            return false;
        }

        if ($this->childTable != $key->getChildTable()) {
            return false;
        }

        if ($this->childField != $key->getChildField()) {
            return false;
        }

        return true;
    }

}