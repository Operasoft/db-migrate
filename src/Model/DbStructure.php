<?php
namespace DbMigrate\Model;

class DbStructure {
	public $db;
	public $username;
	public $name;
	/** @var DbTable[]  */
	public $tables;
	public $views;
    public $triggers;

	public function __construct($db, $name, $username) {
		$this->db = $db;
		$this->name = $name;
		$this->username = $username;
		$this->tables = $this->loadTableStructure($db, $name);
		$this->views = $this->loadViewStructure($db, $name);
        $this->triggers = $this->loadTriggers($db);
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
			$result = $db->query("SHOW FULL COLUMNS FROM $name");
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					$field = new DbField($row['Field'], $row['Type']);
					if ($row['Null'] == 'YES') {
						$field->setNullable(true);
					} else {
						$field->setNullable(false);
					}
					$field->setKey($row['Key']);
					$field->setDefault($row['Default']);
					$field->setExtra($row['Extra']);
                    $field->setComment($row['Comment']);
					$table->fields[$field->getName()] = $field;
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
						$field->setNullable(true);
					} else {
						$field->setNullable(false);
					}
					$field->setKey($row['Key']);
					$field->setDefault($row['Default']);
					$field->setExtra($row['Extra']);
					$table->fields[$field->getName()] = $field;
				}
				$result->free();
			}
		}
		
		return $structure;
	}

    /**
     * @param \mysqli $db
     *
     * @return DbTrigger[]
     */
	public function loadTriggers($db) {
        $triggers = array();

        // List the tables
        $result = $db->query("SHOW TRIGGERS");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $trigger = new DbTrigger($row['Trigger'], $row['Event'], $row['Table'], $row['Statement'], $row['Timing']);
                $triggers[$trigger->getName()] = $trigger;
            }
            $result->free();
        }

        return $triggers;
    }
}