<?php

use App\Database as AppDatabase;
//Contains all actions common for all controllers
class PrimeController{
    public $conn=null;

    public function __construct(){
        $this->conn=new App\Database();
    }
}