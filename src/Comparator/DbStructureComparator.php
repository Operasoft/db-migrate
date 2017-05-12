<?php
namespace DbMigrate\Comparator;

use DbMigrate\Model\DbForeignKey;
use DbMigrate\Model\DbStructure;

class DbStructureComparator {
    /** @var  DbStructure */
	private $srcDb;

    /** @var  DbStructure */
	private $targetDb;

    /**
     * DbStructureComparator constructor.
     * @param DbStructure $srcDb
     * @param DbStructure $targetDb
     */
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
		$existingTables = array();
		echo "Comparing table list...";
		foreach ($this->srcDb->listTables() as $name => $table) {
			if (!isset($this->targetDb->tables[$name])) {
				echo "\n\tTable $name not found in ". $this->targetDb->name;
				$error = true;
				$scripts[] = $this->getCreateTableScript($this->srcDb->db, $name);
			} else {
				$existingTables[] = $name;
			}
		}

		// Check for tables that are only on the target DB
        foreach ($this->targetDb->listTables() as $name => $table) {
            if (!isset($this->srcDb->tables[$name])) {
                echo "\n\tTable $name not found in ". $this->srcDb->name;
                $error = true;
                $scripts[] = $this->getDropTableScript($name);
            }
        }
		
		if ($error == false) {
			echo "OK\n\n";
		} else {
			echo "\nSource and target DB table lists do not match\n\n";
		}
		
		// Comparing table structure
		echo "Comparing table structure... ";
		foreach ($existingTables as $name) {
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
		$existingViews = array();
		echo "Comparing view list...";
		foreach ($this->srcDb->views as $name => $view) {
			if (!isset($this->targetDb->views[$name])) {
				echo "\n\tView $name not found in ". $this->targetDb->name;
				$error = true;
				$scripts[] = $this->getCreateViewScript($db1, $name, $this->srcDb->username, $this->targetDb->username);
			} else {
                $existingViews[] = $name;
			}
		}
		if ($error == false) {
			echo "OK\n\n";
		} else {
			echo "\nSource and target DB view lists do not match\n\n";
		}
		
		// Comparing view structure
		echo "Comparing view structure... ";
		foreach ($existingViews as $name) {
			$view1 = $this->srcDb->views[$name];
			$view2 = $this->targetDb->views[$name];
			$msgs = $this->compareViews($db1, $view1, $view2, $this->srcDb->name, $this->targetDb->name, $this->srcDb->username, $this->targetDb->username, $scripts);
			if (count($msgs) > 0) {
				echo "View $name has some issues:\n";
				foreach ($msgs as $msg) {
					echo "\t$msg\n";
				}
			}
		}
		echo PHP_EOL;

		// Comparing triggers
        echo "Comparing triggers... ";
        foreach ($this->srcDb->triggers as $name => $trigger) {
            if (!isset($this->targetDb->triggers[$name])) {
                echo PHP_EOL."\tTrigger $name not found in ". $this->targetDb->name;
                $error = true;
                $scripts[] = $this->getCreateTriggerScript($this->srcDb->db, $name, $this->srcDb->username, $this->targetDb->username);
            } else if (!$trigger->equals($this->targetDb->triggers[$name])) {
                echo PHP_EOL."\tTrigger $name has changed";
                $scripts[] = $this->getAlterTriggerScript($this->srcDb->db, $name, $this->srcDb->username, $this->targetDb->username);
            }
        }

        // Check for triggers that are only on the target DB
        foreach ($this->targetDb->triggers as $name => $trigger) {
            if (!isset($this->srcDb->triggers[$name])) {
                echo PHP_EOL."\tTrigger $name not found in ". $this->srcDb->name;
                $error = true;
                $scripts[] = $this->getDropTriggerScript($name);
            }
        }
        echo PHP_EOL;

        // Comparing foreign keys
        echo "Comparing foreign keys... ";
        foreach ($this->srcDb->foreignKeys as $name => $key) {
            if (!isset($this->targetDb->foreignKeys[$name])) {
                if (isset($existingTables[$key->getChildTable()])) {
                    echo PHP_EOL."\tForeign key $name not found in ". $this->targetDb->name;
                    $error = true;
                    $scripts[] = $this->getCreateForeignKeyScript($this->srcDb->db, $key, $this->srcDb->username, $this->targetDb->username);
                }
            } else if (!$key->equals($this->targetDb->foreignKeys[$name])) {
                echo PHP_EOL."\tForeign key $name has changed";
                $scripts[] = $this->getAlterForeignKeyScript($this->srcDb->db, $key, $this->srcDb->username, $this->targetDb->username);
            }
        }

        // Check for triggers that are only on the target DB
        foreach ($this->targetDb->foreignKeys as $name => $key) {
            if (!isset($this->srcDb->foreignKeys[$name])) {
                echo PHP_EOL."\tForeign key $name not found in ". $this->srcDb->name;
                $error = true;
                $scripts[] = $this->getDropForeignKeyScript($key);
            }
        }

        if ($error == false) {
            echo "OK";
        } else {
            echo PHP_EOL."Source and target DB triggers do not match";
        }
        echo PHP_EOL.PHP_EOL;

		return $scripts;		
	}
	
	/**
	 * Creates the SQL script to create a new table on the target database
	 */
	private function getCreateTableScript($srcDb, $table) {
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

    /**
     * Creates the SQL script to create a new table on the target database
     */
    private function getDropTableScript($table) {
        $script = "-- OBSOLETE TABLE: $table".PHP_EOL;
        $script .= "-- **** IF THE TABLE HAS BEEN RENAMED, uncomment this script:****".PHP_EOL;
        $script .= "-- RENAME TABLE $table TO <new_name>;".PHP_EOL;
        $script .= "-- **** IF THE TABLE HAS BEEN DROPPED, uncomment this script:****".PHP_EOL;
        $script .= "-- DROP TABLE $table;".PHP_EOL;
        $script .= PHP_EOL;

        return $script;
    }

	private function compareTables($table1, $table2, $db1_name, $db2_name, &$scripts) {
		$msg = array();
		$prevField = null;
		
		foreach ($table1->fields as $name => $field) {
			if (!isset($table2->fields[$name])) {
				$msg[] = "Field $name not found in ". $db2_name;
				$scripts[] = $this->getAddFieldScript($table1->name, $field, $prevField);
			} else {
				$field2 = $table2->fields[$name];
				if ($field->type != $field2->type) {
					$reason = "ERROR: Field $name has a different type: $db1_name.". $field->type . " : $db2_name.". $field2->type;
					$msg[] = $reason;
					$scripts[] = $this->getAlterFieldScript($table1->name, $field, $reason, $field->key != $field2->key);
				}
				if ($field->nullable != $field2->nullable) {
					$reason = "WARNING: Field $name has a different nullable value: $db1_name.". $field->nullable . " : $db2_name.". $field2->nullable;
					$msg[] = $reason;
					$scripts[] = $this->getAlterFieldScript($table1->name, $field, $reason, $field->key != $field2->key);
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
                $scripts[] = $this->getDropFieldScript($table1->name, $field);
			}
		}	
		
		return $msg;
	}
	
	/**
	 * Creates the SQL script to add a new field to an existing table
	 */
	private function getAddFieldScript($table, $field, $after) {
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
			$key = " PRIMARY KEY {$field->extra}";
		} else if ($field->key == 'MUL') {
			$key = ", ADD KEY `{$field->name}` (`{$field->name}`)";
		} else if ($field->key == 'UNI') {
			$key = ", ADD UNIQUE (`{$field->name}`)";	
		}
		
		if (empty($after)) {
			$SQL = "ALTER TABLE `$table` ADD `{$field->name}` {$field->type} $options $key FIRST;";
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
	private function getAlterFieldScript ($table, $field, $reason, $differentKey) {
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
		if ($differentKey) {
            if ($field->key == "PRI") {
                $key = ", ADD PRIMARY KEY (`{$field->name}`) {$field->extra}";
            } else if ($field->key == 'MUL') {
                $key = ", ADD KEY `{$field->name}` (`{$field->name}`)";
            } else if ($field->key == 'UNI') {
                $key = ", ADD UNIQUE (`{$field->name}`)";
            }
        }

		$SQL = "ALTER TABLE `$table` CHANGE `{$field->name}` `{$field->name}` {$field->type} $options $key;";
		
		$script .= "-- MODIFY FIELD {$field->name} in table $table".PHP_EOL."-- Reason: $reason".PHP_EOL;
		$script .= $SQL.PHP_EOL;
		
		return $script;
	}

    /**
     * Creates the SQL script to add a new field to an existing table
     */
    private function getDropFieldScript($table, $field) {
        $script = "-- OBSOLETE FIELD {$field->name} in table $table".PHP_EOL;
        $script .= "-- **** IF THE FIELD HAS BEEN RENAMED, uncomment this script: ****".PHP_EOL;
        $script .= "-- ALTER TABLE `$table` CHANGE COLUMN `{$field->name}` `<new_name>` <definition>;".PHP_EOL;
        $script .= "-- **** IF THE FIELD HAS BEEN DROPPED, uncomment this script: ****".PHP_EOL;
        $script .= "-- ALTER TABLE `$table` DROP COLUMN `{$field->name}`;".PHP_EOL;
        $script .= PHP_EOL;

        return $script;
    }

	/**
	 * Creates the SQL script to add a new key to an existing table
	 */
	private function addKeyScript($table, $field) {
		$script = "";
		
		$SQL = null;
		if ($field->key == "PRI") {
			$SQL = "ALTER TABLE `$table` ADD PRIMARY KEY (`{$field->name}`) {$field->extra};";
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
	private function getCreateViewScript($db, $view, $src_username, $target_username) {
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
			$scripts[] = $this->getAlterViewScript($db, $table1->name, $msg, $src_username, $target_username);
		}
		
		return $msg;
	}
	
	/**
	 * Creates the SQL script to migrate the view on the target DB to match the one on the source DB
	 */
	private function getAlterViewScript($db, $view, $msgs, $src_username, $target_username) {
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

    /**
     * @param \mysqli $db
     * @param string $name
     * @param string $src_username
     * @param string $target_username
     *
     * @return string
     */
    private function getCreateTriggerScript($db, $name, $src_username, $target_username)
    {
        $script = "";

        $result = $db->query("SHOW CREATE TRIGGER $name");
        if ($result) {
            $row = $result->fetch_assoc();
            if (!empty($row['SQL Original Statement'])) {
                $script .= "-- NEW TRIGGER: $name".PHP_EOL;
                $script .= 'delimiter //'.PHP_EOL;
                $script .= str_replace($src_username, $target_username, $row['SQL Original Statement']).';//'.PHP_EOL;
                $script .= 'delimiter ;'.PHP_EOL.PHP_EOL;
            } else {
                echo "ERROR: CREATE TRIGGER script not found for '$name'".PHP_EOL;
            }
            $result->free();
        } else {
            echo "ERROR: Failed to add CREATE TRIGGER script".PHP_EOL;
        }

        return $script;
    }

    /**
     * @param \mysqli $db
     * @param string $name
     * @param string $src_username
     * @param string $target_username
     *
     * @return string
     */
    private function getAlterTriggerScript($db, $name, $src_username, $target_username)
    {
        $script = "";

        $result = $db->query("SHOW CREATE TRIGGER $name");
        if ($result) {
            $row = $result->fetch_assoc();
            if (!empty($row['SQL Original Statement'])) {
                $script .= "-- ALTER TRIGGER: $name".PHP_EOL;

                $script .= "DROP TRIGGER IF EXISTS $name;".PHP_EOL;
                $script .= 'delimiter //'.PHP_EOL;
                // replace the trigger owner
                $sql = str_replace($src_username, $target_username, $row['SQL Original Statement']);
                $script .= $sql.';//'.PHP_EOL;
                $script .= 'delimiter ;'.PHP_EOL.PHP_EOL;
            } else {
                echo "ERROR: CREATE TRIGGER script not found for '$name'".PHP_EOL;
            }
            $result->free();
        } else {
            echo "ERROR: Failed to add ALTER TRIGGER script".PHP_EOL;
        }

        return $script;
    }

    private function getDropTriggerScript($name)
    {
        $script = "-- OBSOLETE TRIGGER: $name".PHP_EOL;
        $script .= "-- CHECK IF THE TRIGGER AS BEEN RENAMED. If not, uncomment the following script to drop it".PHP_EOL;
        $script .= "-- DROP TRIGGER IF EXISTS $name".PHP_EOL;

        return $script;
    }

    /**
     * @param \mysqli $db
     * @param DbForeignKey $key
     * @param string $src_username
     * @param string $target_username
     *
     * @return string
     */
    private function getCreateForeignKeyScript($db, $key, $src_username, $target_username)
    {
        $table = $key->getChildTable();
        $name = $key->getName();

        $script = "";

        $result = $db->query("SHOW CREATE TABLE $table");
        if ($result) {
            $row = $result->fetch_assoc();
            if (!empty($row['Create Table'])) {
                $lines = explode("\n", $row['Create Table']);
                $found = false;
                foreach ($lines as $line) {
                    if (strpos($line, "CONSTRAINT `$name` FOREIGN KEY")) {
                        $line = trim($line, ' ,');
                        $script .= "-- NEW FOREIGN KEY: $name".PHP_EOL;
                        $script .= "ALTER TABLE $table ADD $line;".PHP_EOL.PHP_EOL;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    echo "ERROR: CONSTRAINT line not found for '$name'".PHP_EOL;
                }
            } else {
                echo "ERROR: CREATE TABLE script not found for '$table'".PHP_EOL;
            }
            $result->free();
        } else {
            echo "ERROR: Failed to add CREATE FOREIGN KEY script for '$table'".PHP_EOL;
        }

        return $script;
    }

    /**
     * @param \mysqli $db
     * @param DbForeignKey $key
     * @param string $src_username
     * @param string $target_username
     *
     * @return string
     */
    private function getAlterForeignKeyScript($db, $key, $src_username, $target_username)
    {
        $table = $key->getChildTable();
        $name = $key->getName();

        $script = "";

        $result = $db->query("SHOW CREATE TABLE $table");
        if ($result) {
            $row = $result->fetch_assoc();
            if (!empty($row['Create Table'])) {
                $lines = explode("\n", $row['Create Table']);
                $found = false;
                foreach ($lines as $line) {
                    if (strpos($line, "CONSTRAINT `$name` FOREIGN KEY")) {
                        $line = trim($line, ' ,');
                        $script .= "-- MODIFIED FOREIGN KEY: $name".PHP_EOL;
                        $script .= "ALTER TABLE $table DROP $name;".PHP_EOL.PHP_EOL;
                        $script .= "ALTER TABLE $table ADD $line;".PHP_EOL.PHP_EOL;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    echo "ERROR: CONSTRAINT line not found for '$name'".PHP_EOL;
                }
            } else {
                echo "ERROR: CREATE TABLE script not found for '$table'".PHP_EOL;
            }
            $result->free();
        } else {
            echo "ERROR: Failed to add CREATE FOREIGN KEY script for '$table'".PHP_EOL;
        }

        return $script;
    }

    private function getDropForeignKeyScript(DbForeignKey $key)
    {
        $table = $key->getChildTable();
        $name = $key->getName();
        $script = "-- OBSOLETE FOREIGN KEY: $name".PHP_EOL;
        $script .= "-- CHECK IF THE FOREIGN KEY AS BEEN RENAMED. If not, uncomment the following script to drop it".PHP_EOL;
        $script .= "-- ALTER TABLE $table DROP FOREIGN KEY $name;".PHP_EOL;

        return $script;
    }

}