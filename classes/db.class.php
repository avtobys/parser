<?php

class DB
{
    protected $connection;
    public $insert_id;
    public $affected_rows;
    public function __construct($host = DB['host'], $username = DB['username'], $password = DB['password'], $db_name = DB['db_name'])
    {
        $this->connection = @new mysqli($host, $username, $password, $db_name);
        if (!$this->connection) {
            throw new Exception('Could not connect to DB ');
        }
        if ($this->connection->connect_error) {
            die('Ошибка подключения (' . $this->connection->connect_errno . ') '
                    . $this->connection->connect_error);
        }
        $this->connection->set_charset("utf8");
        $this->connection->query('SET time_zone="' . date('P') . '"');
    }
 
    public function query($sql)
    {
        if (!$this->connection) {
            return false;
        }
 
        $result = $this->connection->query($sql);
 
        if (mysqli_error($this->connection)) {
            throw new Exception(mysqli_error($this->connection));
        }
 
        $this->insert_id = $this->connection->insert_id;
        $this->affected_rows = $this->connection->affected_rows;
 
        return $result;
    }

    public function close()
    {
        return $this->connection->close();
    }
 
    public function esc($str)
    {
        $str = strtr($str, array(
            chr(0) => '',
            chr(1) => '',
            chr(2) => '',
            chr(3) => '',
            chr(4) => '',
            chr(5) => '',
            chr(6) => '',
            chr(7) => '',
            chr(8) => '',
            chr(9) => '',
            chr(11) => '',
            chr(12) => '',
            chr(14) => '',
            chr(15) => '',
            chr(16) => '',
            chr(17) => '',
            chr(18) => '',
            chr(19) => '',
            chr(20) => '',
            chr(21) => '',
            chr(22) => '',
            chr(23) => '',
            chr(24) => '',
            chr(25) => '',
            chr(26) => '',
            chr(27) => '',
            chr(28) => '',
            chr(29) => '',
            chr(30) => '',
            chr(31) => ''
        ));
        return mysqli_escape_string($this->connection, $str);
    }
}