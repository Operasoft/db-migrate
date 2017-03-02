<?php
namespace DbMigrate\Model;

class DbField {
    /** @var string */
    public $name;
	/** @var string */
    public $type;
	/** @var boolean */
    public $nullable;
    public $key;
    public $default = null;
    public $extra;
	/** @var string */
    private $comment = null;

	function __construct($name, $type) {
		$this->name = $name;
		$this->type = $type;
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return boolean
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @param boolean $nullable
     */
    public function setNullable($nullable)
    {
        $this->nullable = $nullable;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param null $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param mixed $extra
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comments
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function isUuid() {
        if (stripos($this->type, "CHAR(36)") !== FALSE) {
            return true;
        }

        return false;
    }

	public function isString() {
	
		if (stripos($this->type, "CHAR") !== FALSE) {
			return true;
		}
		if (stripos($this->type, "TEXT") !== FALSE) {
			return true;
		}
		return false;
	}
	
	public function isDateOrTime() {
		if (stripos($this->type, "DATE") !== FALSE) {
			return true;
		}
		if (stripos($this->type, "TIME") !== FALSE) {
			return true;
		}
		return false;		
	}

	public function isBoolean() {
	    if (stripos($this->type, "BOOL") !== FALSE) {
	        return true;
        }
        if (stripos($this->type, "TINYINT(1)") !== FALSE) {
            return true;
        }
        return false;
    }

    public function isInteger() {
        if (stripos($this->type, "INT") !== FALSE) {
            return true;
        }
        return false;
    }

    public function isFloat() {
        if (stripos($this->type, "FLOAT") !== FALSE) {
            return true;
        }
        if (stripos($this->type, "DECIMAL") !== FALSE) {
            return true;
        }
        if (stripos($this->type, "DOUBLE") !== FALSE) {
            return true;
        }
        return false;
    }

    public function isPrimaryKey() {
        return $this->key == "PRI";
    }

    public function isUniqueKey() {
        return $this->key == "UNI";
    }

    public function isIndexKey() {
        return $this->key == "MUL";
    }
}
