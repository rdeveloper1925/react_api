<?php
namespace App;

use Exception;
use PDO, PDOException;

include_once "Config.php";
include_once "Utils.php";

//Holds prime fuctions relating to the database
class Database {
    private $conn=null;
    public function __construct(){
        try {
            $host="localhost";
            $db="react_api";
            $pass="";
            $user="root";
            $dsn = "mysql:dbname=$db;host=$host";
            $options  = array(
                PDO::ATTR_ERRMODE =>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            );
            $this->conn = new PDO($dsn, $user, $pass, $options);
            //see($this->conn);
            return $this->conn;

        } catch (PDOException $e) {
            echo 'Connection error: ' . $e->getMessage();
        }    
    }

    public function selectAll($tablename){
        try{
            $query="SELECT * FROM $tablename";
            $stmt=$this->conn->prepare($query);
            $stmt->execute();
            $result=$stmt->fetchAll();
            return ($result);
        }catch(Exception $e){
            see($e);
        }
    }

    public function getRequiredFields($tablename){
        try{
            $query="desc $tablename ";
            $stmt=$this->conn->prepare($query);
            $stmt->execute();
            $result=$stmt->fetchAll();
            $result=array_map(function($value){
                if($value['Null']==="NO"&&$value['Extra']!="auto_increment"&&$value['Default']==null){
                    return $value['Field'];
                }
            },$result);
            $result=array_filter($result);
            return ($result);
        }catch(Exception $e){
            see($e);
        }
    }

    public function checkExists($tablename,$criteria,$value){
        try{
            $query="SELECT * FROM $tablename WHERE $criteria=:value";
            $stmt=$this->conn->prepare($query);
            $stmt->bindParam(':value',$value);
            $stmt->execute();
            return $stmt->rowCount()>0 ? true : false;
        }catch(Exception $e){
            see($e);
        }
    }

    public function insert($tablename,$data){
        try{
            $requiredFields=$this->getRequiredFields($tablename);
            $missingFields=array();
            foreach($requiredFields as $key){
                if(!array_key_exists($key,$data)){
                    $missingFields[]=ucwords($key);
                }
            }
            if(!empty($missingFields)){
                return "The following are required: ".implode(", ",$missingFields);
            }
        }catch(Exception $e){
            see($e);
        }
        

    }
}