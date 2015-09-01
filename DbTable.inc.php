<?php

class DbTable {
	public $name;
	public $rows;
	public $fields;
	
	function __construct($name) {
		$this->name = $name;
		$this->rows = -1;
		$this->fields = array();
	}
	
	public function isEmpty() {
		return $this->rows == 0;
	}
}
