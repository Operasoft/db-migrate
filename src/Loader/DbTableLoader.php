<?php
namespace DbMigrate\Loader;

use DbMigrate\Model\DbTable;

/**
 * Class DbTableLoader
 * @package DbMigrate\Loader
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class DbTableLoader
{
    /**
     * @param \mysqli $mysqli
     * @param DbTable $table
     *
     */
    static public function loadConstants($mysqli, $table) {
        $primary = 'id';
        $name = 'name';

        // Look for the primary ID field;
        $field = $table->getPrimaryField();
        if (!empty($field)) {
            $primary = $field->getName();
        }

        $constants = array();

        $result = $mysqli->query("SELECT * FROM {$table->getName()} ORDER BY $primary ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $constants[$row[$primary]] = array();

                $constants[$row[$primary]]['id'] = $row[$primary];
                if (!empty($row['name'])) {
                    $constants[$row[$primary]]['name'] = $row['name'];
                } else {
                    $constants[$row[$primary]]['name'] = $row[$primary];
                }

                if (!empty($row['description'])) {
                    $constants[$row[$primary]]['description'] = $row['description'];
                } else {
                    $constants[$row[$primary]]['description'] = '';
                }
            }
            $result->free();
        }

        $table->setData($constants);
    }
}