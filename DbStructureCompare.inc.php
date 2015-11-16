<?php
require_once(__DIR__.'/DbStructure.inc.php');

class DbStructureCompare {
	private $srcDb;
	private $targetDb;
	
	public function __construct($srcDb, $targetDb) {
		$this->srcDb = $srcDb;
		$this->targetDb = $targetDb;
	}
	
	/**
	 * Compare the source and target DbStructure objects to create the migration 
	 * SQL script to upgrade the target DB to match the source DB structure
	 */
	public function compareStructure() {
		$scripts = array();
		
		// 1. Compare the list of tables
		$error = false;
		$names = array();
		echo "Comparing table list...";
		foreach ($this->srcDb->tables as $name => $table) {
			if (!isset($this->targetDb->tables[$name])) {
				echo "\n\tTable $name not found in ". $this->targetDb->name;
				$error = true;
				$scripts[] = $this->addCreateTableScript($this->srcDb->db, $name);
			} else {
				$names[] = $name;
			}
		}
		
		if ($error == false) {
			echo "OK\n\n";
		} else {
			echo "\nSource and target DB table lists do not match\n\n";
		}
		
		// Comparing table structure
		echo "Comparing table structure... ";
		foreach ($names as $name) {
			$table1 = $this->srcDb->tables[$name];
			$table2 = $this->targetDb->tables[$name];
			$msgs = $this->compareTables($table1, $table2, $this->srcDb->name, $this->targetDb->name, $scripts);
			if (count($msgs) > 0) {
				echo "Table $name has some issues:\n";
				foreach ($msgs as $msg) {
					echo "\t$msg\n";
				}
			}
		}

		// 2. Compare the list of views
		$error = false;
		$names = array();
		echo "Comparing view list...";
		foreach ($this->srcDb->views as $name => $view) {
			if (!isset($this->targetDb->views[$name])) {
				echo "\n\tView $name not found in ". $this->targetDb->name;
				$error = true;
				$scripts[] = $this->addCreateViewScript($db1, $name, $this->srcDb->username, $this->target->username);
			} else {
				$names[] = $name;
			}
		}
		if ($error == false) {
			echo "OK\n\n";
		} else {
			echo "\nSource and target DB view lists do not match\n\n";
		}
		
		// Comparing view structure
		echo "Comparing view structure... ";
		foreach ($names as $name) {
			$view1 = $this->srcDb->views[$name];
			$view2 = $this->targetDb->views[$name];
			$msgs = $this->compareViews($db1, $view1, $view2, $this->srcDb->name, $this->targetDb->name, $this->srcDb->username, $this->target->username, $scripts);
			if (count($msgs) > 0) {
				echo "View $name has some issues:\n";
				foreach ($msgs as $msg) {
					echo "\t$msg\n";
				}
			}
		}
		
		return $scripts;		
	}
	
	/**
	 * Creates the SQL script to create a new table on the target database
	 */
	private function addCreateTableScript($srcDb, $table) {
		$script = "";
		
		$result = $srcDb->query("SHOW CREATE TABLE $table");
		if ($result) {
			$row = $result->fetch_assoc();
			if (!empty($row['Create Table'])) {
				// This is a real table
				$script .= "-- NEW TABLE: $table".PHP_EOL;
				$script .= $row['Create Table'].';'.PHP_EOL.PHP_EOL;
			} else {
				echo "ERROR: CREATE TABLE script not found for '$table'".PHP_EOL;
			}
			$result->free();
		} else {
			echo "ERROR: Failed to add CREATE TABLE script for '$table'".PHP_EOL;
		}
		
		return $script;
	}
	
	private function compareTables($table1, $table2, $db1_name, $db2_name, &$scripts) {
		$msg = array();
		$prevField = null;
		
		foreach ($table1->fields as $name => $field) {
			if (!isset($table2->fields[$name])) {
				$msg[] = "Field $name not found in ". $db2_name;
				$scripts[] = $this->addAddFieldScript($table1->name, $field, $prevField);
			} else {
				$field2 = $table2->fields[$name];
				if ($field->type != $field2->type) {
					$reason = "ERROR: Field $name has a different type: $db1_name.". $field->type . " : $db2_name.". $field2->type;
					$msg[] = $reason;
					$scripts[] = $this->addAlterFieldScript($table1->name, $field, $reason);
				}
				if ($field->nullable != $field2->nullable) {
					$reason = "WARNING: Field $name has a different nullable value: $db1_name.". $field->nullable . " : $db2_name.". $field2->nullable;
					$msg[] = $reason;
					$scripts[] = $this->addAlterFieldScript($table1->name, $field, $reason);
				}
				if ($field->key != $field2->key) {
					$msg[] = "ERROR: Field $name has a different key value: $db1_name.". $field->key . " : $db2_name.". $field2->key;
					if ($field->key) {
						$scripts[] = $this->addKeyScript($table1->name, $field);
					}
				}
				if ($field->default != $field2->default) {
					$msg[] = "WARNING: Field $name has a different default value: $db1_name.". $field->default . " : $db2_name.". $field2->default;
				}
				if ($field->extra != $field2->extra) {
					$msg[] = "WARNING: Field $name has a different extra value: $db1_name.". $field->extra . " : $db2_name.". $field2->extra;
				}
			}
			
			$prevField = $field->name;
		}

		foreach ($table2->fields as $name => $field) {
			if (!isset($table1->fields[$name])) {
				$msg[] = "Field $name not found in ". $db1_name;
			}
		}	
		
		return $msg;
	}
	
	/**
	 * Creates the SQL script to add a new field to an existing table
	 */
	private function addAddFieldScript($table, $field, $after) {
		$script = "";
		$options = "";
		if ($field->nullable) {
			$options = "NULL";
		} else {
			$options = "NOT NULL";
		}
		if ($field->default != null) {
			if ($field->isString()) {
				$options .= " DEFAULT '{$field->default}'";
			} else {
				$options .= " DEFAULT ".$field->default;
			}
		} else if ($field->nullable) {
			$options .= " DEFAULT NULL";
		}
		
		$key = "";
		if ($field->key == "PRI") {
			$key = ", ADD PRIMARY KEY (`{$field->name}`)";	
		} else if ($field->key == 'MUL') {
			$key = ", ADD KEY `{$field->name}` (`{$field->name}`)";
		} else if ($field->key == 'UNI') {
			$key = ", ADD UNIQUE (`{$field->name}`)";	
		}
		
		if (empty($after)) {
			$SQL = "ALTER TABLE `$table` ADD `{$field->name}` {$field->type} $options $key;";
		} else {
			$SQL = "ALTER TABLE `$table` ADD `{$field->name}` {$field->type} $options AFTER `$after` $key;";
		}
		
		$script .= "-- ADD FIELD {$field->name} to table $table".PHP_EOL;
		$script .= $SQL.PHP_EOL;
		
		return $script;
	}
	
	/**
	 * Creates the SQL script to modify an existing field in an existing table
	 */
	private function addAlterFieldScript ($table, $field, $reason) {
		$script = "";
		
		$options = "";
		if ($field->nullable) {
			$options = "NULL";
		} else {
			$options = "NOT NULL";
		}
		if ($field->default != null) {
			if ($field->isString()) {
				$options .= " DEFAULT '{$field->default}'";
			} else {
				$options .= " DEFAULT ".$field->default;
			}
		} else if ($field->nullable) {
			$options .= " DEFAULT NULL";
		}
		
		$key = "";
		if ($field->key == "PRI") {
			$key = ", PRIMARY KEY (`{$field->name}`)";	
		} else if ($field->key == 'MUL') {
			$key = ", KEY `{$field->name}` (`{$field->name}`)";
		} else if ($field->key == 'UNI') {
			$key = ", UNIQUE (`{$field->name}`)";	
		}
		
		$SQL = "ALTER TABLE `$table` CHANGE `{$field->name}` `{$field->name}` {$field->type} $options $key;";
		
		$script .= "-- MODIFY FIELD {$field->name} in table $table".PHP_EOL."-- Reason: $reason".PHP_EOL;
		$script .= $SQL.PHP_EOL;
		
		return $script;
	}

	/**
	 * Creates the SQL script to add a new key to an existing table
	 */
	private function addKeyScript($table, $field) {
		$script = "";
		
		$SQL = null;
		if ($field->key == "PRI") {
			$SQL = "ALTER TABLE `$table` ADD PRIMARY KEY (`{$field->name}`);";	
		} else if ($field->key == 'MUL') {
			$SQL = "ALTER TABLE `$table` ADD KEY `{$field->name}` (`{$field->name}`);";
		} else if ($field->key == 'UNI') {
			$SQL = "ALTER TABLE `$table` ADD UNIQUE (`{$field->name}`);";	
		}
		
		if (!empty($SQL)) {
			$script .= "-- ADD KEY {$field->name} to table $table".PHP_EOL;
			$script .= $SQL.PHP_EOL;
		} else {
			echo "ERROR: Don't know how to handle key";
		}
		
		return $script;
	}
	
	/**
	 * Creates the SQL script to create a new view
	 */
	private function addCreateViewScript($db, $view, $src_username, $target_username) {
		$script = "";
		
		$result = $db->query("SHOW CREATE VIEW $view");
		if ($result) {
			$row = $result->fetch_assoc();
			if (!empty($row['Create View'])) {
				$script .= "-- NEW VIEW: $view".PHP_EOL;
				$script .= str_replace($src_username, $target_username, $row['Create View']).';'.PHP_EOL.PHP_EOL;
			} else {
				echo "ERROR: CREATE VIEW script not found for '$view'".PHP_EOL;
			}
			$result->free();
		} else {
			echo "ERROR: Failed to add CREATE VIEW script".PHP_EOL;
		}
		
		return $script;
	}
	
	/**
	 * Compare the same view between the source and target DB and creates the migration SQL script
	 * to play on the target DB.
	 */
	private function compareViews($db, $table1, $table2, $db1_name, $db2_name, $src_username, $target_username, &$scripts) {
		$msg = array();
		
		$same = true;
		foreach ($table1->fields as $name => $field) {
			if (!isset($table2->fields[$name])) {
				$msg[] = "Field $name not found in ". $db2_name;
				$same = false;
			} else {
				$field2 = $table2->fields[$name];
				if ($field->type != $field2->type) {
					$reason = "ERROR: Field $name has a different type: $db1_name.". $field->type . " : $db2_name.". $field2->type;
					$msg[] = $reason;
					$same = false;
				}
				if ($field->key != $field2->key) {
					$msg[] = "ERROR: Field $name has a different key value: $db1_name.". $field->key . " : $db2_name.". $field2->key;
					if ($field->key) {
						$same = false;
					}
				}
				if ($field->extra != $field2->extra) {
					$msg[] = "WARNING: Field $name has a different extra value: $db1_name.". $field->extra . " : $db2_name.". $field2->extra;
				}
			}
		}

		foreach ($table2->fields as $name => $field) {
			if (!isset($table1->fields[$name])) {
				$msg[] = "Field $name not found in ". $db1_name;
			}
		}	

		if (!$same) {
			$scripts[] = $this->addAlterViewScript($db, $table1->name, $msg, $src_username, $target_username);		
		}
		
		return $msg;
	}
	
	/**
	 * Creates the SQL script to migrate the view on the target DB to match the one on the source DB
	 */
	private function addAlterViewScript($db, $view, $msgs, $src_username, $target_username) {
		$script = "";
		
		$result = $db->query("SHOW CREATE VIEW $view");
		if ($result) {
			$row = $result->fetch_assoc();
			if (!empty($row['Create View'])) {
				$script .= "-- ALTER VIEW: $view".PHP_EOL;
				foreach ($msgs as $msg) {
					$script .= "\t-- $msg".PHP_EOL;
				}
				// replace the view owner
				$sql = str_replace($src_username, $target_username, $row['Create View']);
				// Replace the CREATE command by an ALTER command
				$sql = str_replace('CREATE ALGORITHM', 'ALTER ALGORITHM', $sql);
				$script .= $sql.';'.PHP_EOL;
			} else {
				echo "ERROR: CREATE VIEW script not found for '$view'".PHP_EOL;
			}
			$result->free();
		} else {
			echo "ERROR: Failed to add ALTER VIEW script".PHP_EOL;
		}
		
		return $script;
	}	
}