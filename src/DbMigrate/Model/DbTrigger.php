<?php
namespace DbMigrate\Model;

/**
 * Class DbTrigger
 * @package DbMigrate\Model
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class DbTrigger
{
    private $name;
    private $event;
    private $table;
    private $statement;
    private $timing;

    /**
     * DbTrigger constructor.
     * @param $name
     * @param $event
     * @param $table
     * @param $statement
     * @param $timing
     */
    public function __construct($name, $event, $table, $statement, $timing)
    {
        $this->name = $name;
        $this->event = $event;
        $this->table = $table;
        $this->statement = $statement;
        $this->timing = $timing;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param mixed $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @return mixed
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * @param mixed $statement
     */
    public function setStatement($statement)
    {
        $this->statement = $statement;
    }

    /**
     * @return mixed
     */
    public function getTiming()
    {
        return $this->timing;
    }

    /**
     * @param mixed $timing
     */
    public function setTiming($timing)
    {
        $this->timing = $timing;
    }

    /**
     * Checks if 2 triggers are equal
     * @param DbTrigger $trigger
     *
     * @return bool
     */
    public function equals(DbTrigger $trigger) {
        if ($this->name != $trigger->getName()) {
            return false;
        }

        if ($this->event != $trigger->getEvent()) {
            return false;
        }

        if ($this->table != $trigger->getTable()) {
            return false;
        }

        if (str_replace("\r\n", "\n", $this->statement) != str_replace("\r\n", "\n", $trigger->getStatement())) {
            return false;
        }

        if ($this->timing != $trigger->getTiming()) {
            return false;
        }

        return true;
    }
}