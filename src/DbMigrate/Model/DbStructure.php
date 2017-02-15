<?php
namespace DbMigrate\Model;

class DbStructure {
	public $db;
	public $username;
	public $name;
	public $tables;
	public $views;

	public function __construct($db, $name, $username) {
		$this->db = $db;
		$this->name = $name;
		$this->username = $username;
		$this->tables = $this->loadTableStructure($db, $name);
		$this->views = $this->loadViewStructure($db, $name);
	}
	
	/**
	 * Loads the table structure associated with a given database
	 * @args $db The mysqli connection to use 
	 * @args $name The database name to load table structure for
	 */
	public function loadTableStructure($db, $name) {
		$structure = array();

		// List the tables
		$result = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				$table = new DbTable($row["Tables_in_$name"]);
				$structure[$table->name] = $table;
			}
			$result->free();
		}
		
		// Gather information about each table
		foreach ($structure as $name => $table) {
			// Extract the number of rows it contains
			$result = $db->query("SELECT COUNT(*) AS count FROM $name");
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					$table->rows = $row['count'];
				}
				$result->free();
			}
			
			// Extract each field
			$result = $db->query("DESCRIBE $name");
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					$field = new DbField($row['Field'], $row['Type']);
					if ($row['Null'] == 'YES') {
						$field->nullable = true;
					} else {
						$field->nullable = false;
					}
					$field->key = $row['Key'];
					$field->default = $row['Default'];
					$field->extra = $row['Extra'];
					$table->fields[$field->name] = $field;
				}
				$result->free();
			}
		}
		
		return $structure;		
	}
	
	public function loadViewStructure($db, $name) {
		$structure = array();

		// List the tables
		$result = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				$table = new DbTable($row["Tables_in_$name"]);
				$structure[$table->name] = $table;
			}
			$result->free();
		}
		
		// Gather information about each table
		foreach ($structure as $name => $table) {
			// Extract the number of rows it contains
			$result = $db->query("SELECT COUNT(*) AS count FROM $name");
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					$table->rows = $row['count'];
				}
				$result->free();
			}
			
			// Extract each field
			$result = $db->query("DESCRIBE $name");
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					$field = new DbField($row['Field'], $row['Type']);
					if ($row['Null'] == 'YES') {
						$field->nullable = true;
					} else {
						$field->nullable = false;
					}
					$field->key = $row['Key'];
					$field->default = $row['Default'];
					$field->extra = $row['Extra'];
					$table->fields[$field->name] = $field;
				}
				$result->free();
			}
		}
		
		return $structure;
	}
	
}
