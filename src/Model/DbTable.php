<?php
namespace DbMigrate\Model;

class DbTable {
    /** @var string */
	public $name;
	/** @var DbField[] */
	public $fields;
    /** @var int */
    public $rows;
    /** @var array  */
    private $data;
    /** @var string[] */
    private $parents;
    /** @var DbKey[] */
    private $keys;

	function __construct($name) {
		$this->name = $name;
		$this->rows = -1;
		$this->fields = array();
        $this->data = array();
        $this->parents = array();
        $this->keys = array();
	}
	
	public function isEmpty() {
	    return $this->rows == 0;
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

    public function getPrimaryField()
    {
        // Look for the primary ID field;
        foreach ($this->fields as $field) {
            if ($field->isPrimaryKey()) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return DbField[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param DbField[] $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return string[]
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * @param string[] $parent
     */
    public function setParents(array $parents)
    {
        $this->parents = $parents;
    }

    /**
     * @param string $parent
     */
    public function addParent($parent)
    {
        $this->parents[] = $parent;
    }

    /**
     * @return DbKey[]
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param DbKey[] $keys
     */
    public function setKeys($keys)
    {
        $this->keys = $keys;
    }

    public function addKey(DbKey $key)
    {
        $this->keys[$key->getName()] = $key;
    }

    public function containKey($name)
    {
        return isset($this->keys[$name]);
    }

    public function getKey($name)
    {
        return $this->keys[$name];
    }
}
