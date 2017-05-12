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
    public $foreignKeys;

	public function __construct($db, $name, $username) {
		$this->db = $db;
		$this->name = $name;
		$this->username = $username;
		$this->tables = $this->loadTableStructure($db, $name);
		$this->views = $this->loadViewStructure($db, $name);
        $this->triggers = $this->loadTriggers($db);
        $this->foreignKeys = $this->loadForeignKeys($db);
	}

	public function listTables() {
	    $remainingTables = array();
	    foreach ($this->tables as $table) {
	        $remainingTables[$table->getName()] = $table;
        }

	    $orderedTables = array();

	    while (!empty($remainingTables)) {
	        $count = count($orderedTables);
            foreach ($this->tables as $table) {
                if (isset($orderedTables[$table->getName()])) {
                    // Already added
                    continue;
                }
                $parents = $table->getParents();
                if (empty($parents)) {
                    // New root table
                    $orderedTables[$table->getName()] = $table;
                    unset ($remainingTables[$table->getName()]);
                    continue;
                }
                $add = true;
                foreach ($parents as $parent) {
                    if (!isset($orderedTables[$parent])) {
                        // At least 1 parent has not been added yet...
                        $add = false;
                        break;
                    }
                }
                if ($add) {
                    // New root table
                    $orderedTables[$table->getName()] = $table;
                    unset ($remainingTables[$table->getName()]);
                }
            }
            if ($count == count($orderedTables)) {
                // No table added, circular references
                break;
            }
        }
        foreach ($remainingTables as $table) {
            $orderedTables[$table->getName()] = $table;
        }

	    return $orderedTables;
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

    public function loadForeignKeys($db) {
	    $foreignKeys = array();

	    $name = $this->name;

	    $result = $db->query("SELECT * FROM information_schema.`KEY_COLUMN_USAGE` WHERE TABLE_SCHEMA = '$name' AND REFERENCED_TABLE_NAME IS NOT NULL");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $key = new DbForeignKey($row['CONSTRAINT_NAME'], $row['TABLE_NAME'], $row['COLUMN_NAME'], $row['REFERENCED_TABLE_NAME'], $row['REFERENCED_COLUMN_NAME']);
                $foreignKeys[$key->getId()] = $key;
                if (isset($this->tables[$key->getChildTable()])) {
                    $this->tables[$key->getChildTable()]->addParent($key->getParentTable());
                }
            }
            $result->free();
        }

        return $foreignKeys;
    }
}
