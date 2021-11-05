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
            if($tablename=="users"){//wouldnt want sharing passwords
                foreach($result as $key=>$val){
                    unset($result[$key]["password"]);
                }
            }
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
                return response(0,[],"The following are required: ".implode(", ",$missingFields),"The following are required: ".implode(", ",$missingFields));
            }
            //check for uniqueness of username
            if($this->checkExists($tablename,"username",$data['username'])){
                return response(0,[],"Selected Username already exists","Selected Username already exists");
            }
            //validate date coz its more prone to mistakes
            if(!validate("date",$data["birthDate"])){
                $msg="Malformed birth date";
                return response(0,[],"$msg","$msg");
            }
            //handling passwords
            if(array_key_exists('password',$data)){
                $unhashed=$data['password'];
                $data['password']=mask($unhashed);
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
            unset($data["password"]);
            return $result?response(true,$data,"User Created Successfully!"):response(0,[],"Sorry! An unknown error occured","Sorry! An unknown error occured");
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

    public function selectWhere($tablename, $conditions=array(),$glue="and"){ //['username'=>['=rodney'," and"]]
        try{
            //for 
            if(empty($conditions)){
                $query="SELECT * FROM $tablename ";
            }else{
                $query="SELECT * FROM $tablename WHERE ";
                //handling the conditions
                $query .= $this->implementFillables($conditions,$glue);
            }
            //seedie($query);
            $stmt=$this->conn->prepare($query);
            $stmt->execute($conditions);
            $result=$stmt->fetchAll();
            if($tablename=="users"){//wouldnt want sharing passwords
                foreach($result as $key=>$val){
                    unset($result[$key]["password"]);
                }
            }
            return $result;
        }catch(Exception $e){
            echo response(0,[$e],"",$e->getMessage());
            die();
        }
    }

    public function update($tablename,$data,$condition=array()){
        try{
            //dont update if condition is empty or data is empty
            if(empty($condition) || empty($data)){
                $msg="Looks like we have a missing condition for this update. Aborting Now";
                echo response(0,$data,$msg,$msg);die();
            }
            //check that the keys match db cols
            if(!empty($unexpectedCols=$this->checkCols($tablename,$data))){
                $msg="Found the following unexpected fields. Please remove from request: ".implode(", ",$unexpectedCols);
                echo response(0,$data,"$msg",$msg);die();
            }
            //creating a separate dataset without the condition being shown in the set clause
            $overallData=$data;
            $keysToEliminate=array_keys($condition);
            foreach($keysToEliminate as $k){ //iterate through condition array to see the keys there
                if(array_key_exists($k,$data)){ //if that key exists in the original dataset,.....
                    unset($data[$k]); //unset it from the original dataset
                }
            }//at the end of this, the original dataset doesnt have the keys being used in the condition as the keys to set
            
            $query="UPDATE $tablename SET ";
            $query .= $this->implementFillables($data); //implementing the fields to be updated/set
            $query.=" where ";
            $query .= $this->implementFillables($condition,"and");
            //see([$data,$condition]);
            //seedie($query);
            $stmt=$this->conn->prepare($query);
            $result=$stmt->execute(array_merge($data,$condition));
            return array($result,$this->selectWhere($tablename,$condition));
        }catch(Exception $e){
            echo response(0,[$e],"",$e->getMessage());
            die();
        }
    }

    //checks that the cols supplied in the data are available in the db
    public function checkCols($tablename,$data){
        try{
            $query="desc $tablename ";
            $stmt=$this->conn->prepare($query);
            $stmt->execute();
            $result=$stmt->fetchAll();
            $result=array_map(function($value){
                return $value["Field"];
            },$result);
            $result=array_flip($result); //make the values keys
            $unexpectedKeys=array();
            foreach($data as $key=>$val){
                if(!array_key_exists($key,$result)){
                    $unexpectedKeys[]=$key;
                }
            }
            return ($unexpectedKeys);
        }catch(Exception $e){
            echo response(0,[$e],"",$e->getMessage());
            die();
        }
    }

    //mimimal validation for this function. make certain its accurate (query)
    public function runQuery($query){
        try{
            $stmt=$this->conn->prepare($query);
            $rs=$stmt->execute();
            $result=$stmt->fetchAll();
            see([$result,$rs]);
        }catch(Exception $e){
            echo response(0,[$e],"",$e->getMessage());
            die();
        }
    }

    public function delete($tablename,$condition){
        try{
            $this->checkCondition($condition);
            $query="DELETE FROM $tablename WHERE ";
            $query .= $this->implementFillables($condition,"and");
            $stmt=$this->conn->prepare($query);
            $result=$stmt->execute($condition);
            return response($result,[],"The user has been deleted successfully");
        }catch(Exception $e){
            echo response(0,[$e],"",$e->getMessage());
            die();
        }
    }

    public function checkCondition($condition){
        //will check required condition and break if none is provided
        if(empty($condition)){
            $msg="Looks like your request is lacking parameters to complete it. Aborting Now";
            echo response(0,[],$msg,$msg);die();
        }else{
            return true;
        }
    }

    //organizes fillable elements preparing them to be appended into the sql statement
    public function implementFillables($condition, $glue=" , "){
        $this->checkCondition($condition); //condition must always be available
        $conditionPart="";
        //glue will determine the type of fillable: , for set then and for conditions
        //for the select, update and delete options to fill in the where clause glue=and
        //for the update options, filling in the set glue= , 
        foreach($condition as $key=>$val){
            if(array_key_last($condition)==$key){
                $conditionPart.="$key = :$key ";
            }else{
                $conditionPart.="$key = :$key $glue ";
            }
        }
        return $conditionPart;
    }
}