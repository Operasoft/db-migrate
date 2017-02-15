<?php
namespace DbMigrate\Config;

/**
 * Class DbConnection
 * @author Christian Labonté <clabonte@baselinetelematics.com>
 * @copyright Baseline Telematics
 */
class DbConnection
{
    /** @var string */
    private $name;

    /** @var string */
    private $host;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /**
     * DbConnection constructor.
     * @param string $name
     * @param string $host
     * @param string $username
     * @param string $password
     */
    public function __construct($name, $host, $username, $password)
    {
        $this->name = $name;
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Opens a database connection to the database provided
     * @param string $database The database to connect to
     *
     * @return \mysqli The database connection created
     * @throws \Exception If the connection failed
     */
    public function connect($database) {
        $mysqli = mysqli_init();
        $mysqli->real_connect($this->host, $this->username, $this->password, $database);
        if ($mysqli->connect_errno) {
            throw new \Exception("Failed to connect to $database on {$this->host}: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
        }

        return $mysqli;
    }
}