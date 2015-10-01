<?php
require_once(__DIR__.'/DbStructure.inc.php');

/**
 * This class is used to compare the content of a table between two databases and generates the 
 * SQL migration script to synchronize the target DB with the source DB.
 */
class DbContentCompare {
	private $dbStructure;
	private $srcDb;
	private $targetDb;
	
	public function __construct($dbStructure, $srcDb, $targetDb) {
		$this->dbStructure = $dbStructure;
		$this->srcDb = $srcDb;
		$this->targetDb = $targetDb;
	}

	public function compareTable($table, $mode, $key) {
		$script = "";
		
		if ($mode == "newOnly") {
			$script .= $this->findNewEntries($table, $key);
		} else if ($mode == "update") {
			$script .= $this->findUpdatedEntries($table, $key);			
		} else {
			echo "ERROR: Unknowm content comparison mode: $mode";
		}
		return $script;
	}

	/**
	 * Extract all the entries from a table and return them in a associative array organized by the $key field
	 */
	private function listEntries($db, $table, $key) {
		$entries = array();
		
		$result = $db->query("SELECT * FROM $table");
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				$entries[$row[$key]] = $row;
			}
			$result->free();
		}

		return $entries;		
	}
	
	/**
	 * Checks if a given field's value must be surrounded with quotes (') in the SQL script
	 */
	private function isQuoteRequired($table, $field) {
		$dbField = $this->dbStructure->tables[$table]->fields[$field];
		if ($dbField->isString() || $dbField->isDateOrTime()) {
			return true;
		}
		
		return false;
	}
	
	private function addInsertScript($table, $entry) {
		$first = true;
		$script = "INSERT INTO `$table` (";
		foreach ($entry as $name => $value) {
			if (!$first) {
				$script .= ', ';
			} else {
				$first = false;														
			}
			$script .= "`$name`";
		}
		$script .= ") VALUES ".PHP_EOL;
		
		return $script;
	}
	
	private function addReplaceScript($table, $entry) {
		$first = true;
		$script = "REPLACE INTO `$table` (";
		foreach ($entry as $name => $value) {
			if (!$first) {
				$script .= ', ';
			} else {
				$first = false;														
			}
			$script .= "`$name`";
		}
		$script .= ") VALUES ".PHP_EOL;
		
		return $script;
	}

	private function addEntryScript($table, $entry) {
		$script = "(";
		
		$firstValue = true;
		foreach ($entry as $name => $value) {
			if (!$firstValue) {
				$script .= ', ';
			} else {
				$firstValue	= false;						
			}
			if ($this->isQuoteRequired($table, $name)) {
				$script .= "'".str_replace("'", "''", $value)."'";
			} else {
				if (empty($value)) {
					$script .= "NULL";
				} else {
					$script .= "$value";					
				}
			}
		}				
		$script .= ")";

		return $script;
	}
	
	/**
	 * Creates the SQL script to migrate new entries from the source DB
	 * to the target DB for a given table
	 */
	private function findNewEntries($table, $key) {
		$script = "";
		
		// Retrieve the list of entries in target DB
		echo "Retrieving $table entries from target DB... ";
		$targetEntries = $this->listEntries($this->targetDb, $table, $key);
		echo count($targetEntries)." entries found".PHP_EOL;

		// Retrieve the list of entries in source DB
		echo "Retrieving $table entries from source DB... ";
		$srcEntries = $this->listEntries($this->srcDb, $table, $key);
		echo count($srcEntries)." entries found".PHP_EOL;
		
		// Find out the list of entries that are missing in the target databases
		$first = true;
		$count = 0;
		foreach ($srcEntries as $key => $entry) {
			if (!isset($targetEntries[$key])) {
				// This entry does not exist in the target DB
				if ($first) {
					$script = "-- Adding new entries in table $table".PHP_EOL;
					$script .= $this->addInsertScript($table, $entry);
					$first = false;
				}
				if ($count != 0) {
					$script .= ','.PHP_EOL;
				}
				$script .= $this->addEntryScript($table, $entry);
				$count++;
			}
		}
		if ($count > 0) {
			$script .= ';'.PHP_EOL;			
		}
		
		$script .= PHP_EOL;
		return $script;		
	}

	/**
	 * Creates the SQL script to migrate new and updated entries from the source DB
	 * to the target DB for a given table
	 */
	private function findUpdatedEntries($table, $key) {
		$script = "";

		// Retrieve the list of entries in target DB
		echo "Retrieving $table entries from target DB... ";
		$targetEntries = $this->listEntries($this->targetDb, $table, $key);
		echo count($targetEntries)." entries found".PHP_EOL;

		// Retrieve the list of entries in source DB
		echo "Retrieving $table entries from source DB... ";
		$srcEntries = $this->listEntries($this->srcDb, $table, $key);
		echo count($srcEntries)." entries found".PHP_EOL;
		
		// Find out the list of entries that are missing in the target databases
		$first = true;
		$count = 0;
		foreach ($srcEntries as $key => $entry) {
			$proceed = false;
			if (!isset($targetEntries[$key])) {
				// This is a new entry, we need to migrate it
				$proceed = true;
			} else if (strtotime($entry['modified']) > strtotime($targetEntries[$key]['modified'])) {
				// The entry on the source DB is more recent than the one on the target DB
				echo "$key - Source date: ".$entry['modified']. " target date: ".$targetEntries[$key]['modified'];
				$proceed = true;
			}
			
			if ($proceed) {
				// This entry does not exist in the target DB
				if ($first) {
					$script = "-- Adding new and updated entries in table $table".PHP_EOL;
					$script .= $this->addReplaceScript($table, $entry);
					$first = false;
				}
				if ($count != 0) {
					$script .= ','.PHP_EOL;
				}
				$script .= $this->addEntryScript($table, $entry);
				$count++;				
			}
		}
		if ($count > 0) {
			$script .= ';'.PHP_EOL;			
		}

		$script .= PHP_EOL;
		return $script;		
	}
}
