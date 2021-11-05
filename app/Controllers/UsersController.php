<?php
include_once "PrimeController.php";
use Rakit\Validation\Validator;
//Handles Usermanagement
class UsersController extends PrimeController{
    //Create new user
    public function create(){
        $data=input();
        $validator=new Validator();
        $validation=$validator->make($data,array(
            "email"=>"required|email",
            "birthDate"=>"required"
        ));
        $validation->validate();
        if($validation->fails()){
            return response(0,implode(", ",$validation->errors()->all()),"Data validation failed","Data validation failed");
        }
        $response=$this->conn->insert("users",input());
        return $response;
    }

    //View all users
    public function index(){
        $users=$this->conn->selectAll('users');
        return response(1,$users);
    }
    //See one user
    public function show($username){
        $user=$this->conn->selectWhere("users",['username'=>$username]);
        return response(1,$user);
    }
    //Update user details
    public function edit($username){
        $data=input();
        list($result,$newData)=$this->conn->update("users",$data,["username"=>$username]);
        return response($result,$newData,"the username was/can not (be) updated");
    }

    //Delete user
    public function delete($username){
        return $this->conn->delete("users",["username"=>$username]);
    }
    
    //View all users
    // public function index2(){
    //     $user2=array(
    //         'username'=>'Rick Matt',
    //         'firstName'=>"Rick",
    //         'password'=>"Mattee",
    //         'lastName'=>"Mteo",
    //         'email'=>"me@me.com",
    //         'birthDate'=>date('Y-m-d H:i:s'),
    //     );
    //     if(is_string($missingvals=$this->conn->insert('users',$user2))){
    //         return response(0,[],"",$missingvals);
    //     }
    //     $users=$this->conn->selectAll('users');
    //     return response(1,$users);
    // }
}