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
            //CHECK for availability of all fields
            if(is_array($missingFields=$this->sanitizeInputs($tablename,$data))){ //when it returns array then we have missing values
                return "The following are required: ".implode(", ",$missingFields);
            }
            //check for uniqueness of username
            if($this->checkExists($tablename,"username",$data['username'])){
                return "Selected Username already exists";
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
            foreach($data as $key=>$value){
                see([$key,$value]);
                $stmt->bindParam(":$key",$value);
            }
            see($query);
            $result=$stmt->execute();
            see($result);
            return $query;
        }catch(Exception $e){
            see($e);
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
}