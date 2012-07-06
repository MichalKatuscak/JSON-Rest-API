<?php

final class Db 
{
    
    private $mysqli = true;
    
    public function __construct($server, $username, $password, $database) {
        $this->mysqli = new MySQLi($server, $username, $password, $database);
    }
    
    public function query()
    {
        $args = func_get_args();
        $sql  = $args[0];
        if (isset($args[1])) {
            unset($args[0]);
            foreach($args as $key => $arg) {
                $args[$key] = "'".$this->mysqli->real_escape_string($arg)."'";
            }
            $sql = vsprintf($sql, $args);
        }
        if ($result = $this->mysqli->query($sql)) {
            if (substr($sql, 0, 6) == 'SELECT') {
                if ($result->num_rows != 0) { 
                    return $result;
                }
            } else {
                return $result;
            }
        }
    }
    
}