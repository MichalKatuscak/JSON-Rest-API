<?php
session_start();
final class Rest 
{
    private $db = true;
    
    public function __construct ()
    {
        $json = file_get_contents('php://input');
        if ($data = @json_decode($json)) {
                // Connect to DB
            if ($db_settings = $this->getDbSettings()) {
                if ($db = new Db($db_settings['host'],$db_settings['user'],$db_settings['password'],$db_settings['dbname'])) {
                    $this->db = $db;
                } else {
                    new Status(500);
                    return false;
                }
            } else {
                new Status(500);
                return false;
            }
            
                // Token exists
            if (isset($data->token)) {

                    // Is token valid and token time < 10 second
                if ($user_id = $this->validToken($data->token)) {
                    switch ($_SERVER['REQUEST_METHOD'])
                    {
                        case 'GET':
                            echo "Získání dat";
                            break;
                        case 'POST':
                            echo "Zápis dat";
                            break;
                        case 'PUT':
                            echo "Úprava dat";
                            break;
                        case 'DELETE':
                            echo "Smazání dat";
                            break;
                    }
                } else {
                    new Status(401);
                }
            } elseif (isset($data->login)) {
                if (isset($data->username) && isset($data->password)) {
                    if ($user_id = $this->login($data->username, $data->password)) {
                        $return = array('token'=>$this->createToken($user_id));
                    }
                } else {
                    new Status(400);
                }
            } else {
                new Status(400);
            }
        } else {
            new Status(400);
        }
        if (isset($return) && is_array($return)) {
            echo json_encode($return);
        }
    }
    
    private function login ($username, $password) 
    {
        $result = $this->db->query("SELECT * FROM users WHERE login = %s AND password = %s LIMIT 1", $username, $password);
        if ($result) {
            return $result->fetch_object()->id_user;
        }
        return false;
    }
    
    private function createToken ($user_id)
    {
            // Remove old tokens
        $this->db->query("DELETE FROM api_token WHERE time < %s", time()-10);
                
        $token = md5($user_id.microtime());
        $result = $this->db->query("INSERT INTO api_token VALUES (%s, %s, %s)", time(), $token, $user_id);
        if ($result) {
            return $token;
        }
        return false;
    }
    
    private function validToken ($token)
    {
        $result = $this->db->query("SELECT * FROM api_token WHERE token = %s LIMIT 1", $token);
        if ($result) {
            $data = $result->fetch_object();
            if ($data->time >= time()-10) {
                return $data->user_id;
            }
        }
        return false;
    }
    
    private function getDbSettings ()
    {
        if ($conf = file_get_contents(__DIR__.'/../../Muj kancl/app/config/config.neon')) {
            $data = array(Neon::decode($conf));
            if ($_SERVER['SERVER_ADDR'] == '::1' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') $d = 'development < common';
            else $d = 'production < common';
            $db = $data[0][$d]['parameters']['database'];
            return $db;
        }
        return false;
    }
    
}
