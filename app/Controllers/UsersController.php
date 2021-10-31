<?php

include_once "PrimeController.php";
//Handles Usermanagement
class UsersController extends PrimeController{
    
    //View all users
    public function index(){
        if(is_string($missingvals=$this->conn->insert('users',[]))){
            return response(0,[],"",$missingvals);
        }
        $users=$this->conn->selectAll('users');
        return response(1,$users);
    }
}