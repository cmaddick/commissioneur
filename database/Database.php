<?php
/**
 * Created by PhpStorm.
 * User: ZHIQIN
 * Date: 03/04/2018
 * Time: 22:31
 */

class Database{
    private $hostname = 'localhost';// Remote database server Domain name.
    private $username = 'something';// as specified in the GRANT command at that server.
    private $password = 'something';// as specified in the GRANT command at that server.
    private $dbname = 'something';// Database name at the database server.
    private $conn;

    public function createConnection(){
        $this->conn = new mysqli($this->hostname, $this->username, $this->password, $this->dbname);

        // check connection
        if ($this->conn->connect_error) {
            die("connection fail: " . $this->conn->connect_error);
        }
        return $this->conn;
    }
}
?>
