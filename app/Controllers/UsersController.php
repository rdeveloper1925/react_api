<?php

include_once "PrimeController.php";
//Handles Usermanagement
class UsersController extends PrimeController{
    //View all users
    public function index(){
        $users=$this->conn->selectAll('users');
        return response(1,$users);
    }

    public function show($id){
        
    }
    
    //View all users
    public function index2(){
        $user2=array(
            'username'=>'Rick Matt',
            'firstName'=>"Rick",
            'password'=>"Mattee",
            'lastName'=>"Mteo",
            'email'=>"me@me.com",
            'birthDate'=>date('Y-m-d H:i:s'),
        );
        if(is_string($missingvals=$this->conn->insert('users',$user2))){
            return response(0,[],"",$missingvals);
        }
        $users=$this->conn->selectAll('users');
        return response(1,$users);
    }
}