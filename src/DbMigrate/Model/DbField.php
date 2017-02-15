<?php
namespace DbMigrate\Model;

class DbField {
	public $name;
	public $type;
	public $nullable;
	public $key;
	public $default = null;
	public $extra;
	
	function __construct($name, $type) {
		$this->name = $name;
		$this->type = $type;
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
}
