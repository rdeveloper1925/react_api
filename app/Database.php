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
            echo response(0,[$e],"",$e->getMessage());
            die();
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
            echo response(0,[$e],"",$e->getMessage());
            die();
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
            echo response(0,[$e],"",$e->getMessage());
            die();
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
            echo response(0,[$e],"",$e->getMessage());
            die();
        }
    }


    public function insert($tablename,$data){
        try{
            $requiredFields=$this->getRequiredFields($tablename);
            //CHECK for availability of all fields
            if(is_array($missingFields=$this->sanitizeInputs($tablename,$data))){ //when it returns array then we have missing values
                return "The following are required: ".implode(", ",$missingFields);
            }
            //check for uniqueness of username
            if($this->checkExists($tablename,"username",$data['username'])){
                return "Selected Username already exists";
            }
            //handling passwords
            if(array_key_exists('password',$data)){
                $unhashed=$data['password'];
                $data['password']=\password_hash($unhashed,PASSWORD_ARGON2_DEFAULT_TIME_COST);
            }
            //now we know that all values required are present and username is unique
            $query="INSERT INTO $tablename (";
            $valuesPart="VALUES ("; //iterating the values part simultaneously
            foreach($requiredFields as $k=>$field){
                if($k==array_key_last($requiredFields)){ //closing the values part if this is the last iteration
                    $query.="`$field`) ";
                    $valuesPart .= ":$field)";
                }else{
                    $query.="`$field`, ";
                    $valuesPart .= ":$field, ";
                }
            }
            $query .= $valuesPart;
            //now bind the params to the query
            $stmt=$this->conn->prepare($query);
            $result=$stmt->execute($data);
            return $result;
        }catch(Exception $e){
            echo response(0,[$e],"",$e->getMessage());
            die();
        }
    }

    public function sanitizeInputs($tablename,$data){
        //checking for missing fields
        $requiredFields=$this->getRequiredFields($tablename);
        $missingFields=array();
        foreach($requiredFields as $key){
            if(!array_key_exists($key,$data)){ //checking that the key exists in the data supplied
                $missingFields[]=ucwords($key);
            }else{ //if it exists, proceed to check that it aint empty
                if(is_null($data[$key])){
                    $missingFields[]=ucwords($key);
                }
            }
        }
        if(!empty($missingFields)){
            return $missingFields;
        }else{
            return true;
        }
    }

    public function selectWhere($tablename, $conditions){ //['username'=>['=rodney'," and"]]
        try{
            $query="SELECT * FROM $tablename WHERE ";
            //handling the conditions
            foreach($conditions as $col=>$cond){
                $query.= "$col ".$cond[0]." ".$cond[1];
            }
            $stmt=$this->conn->prepare($query);
            $stmt->execute();
        }catch(Exception $e){
            echo response(0,[$e],"",$e->getMessage());
            die();
        }
    }
}