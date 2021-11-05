<?php

use Pecee\Http\Input\InputHandler;
use Pecee\SimpleRouter\SimpleRouter;

function see($variable){
    $trace=debug_backtrace();
    $cut_trace=array_shift($trace);
    $line=$cut_trace['line'];
    $file=$cut_trace['file'];
    echo "Seeing var at Line: $line :: $file <br/><pre>".var_dump($variable)."</pre>";
    return;
}

function seedie($variable){
    $trace=debug_backtrace();
    $cut_trace=array_shift($trace);
    $line=$cut_trace['line'];
    $file=$cut_trace['file'];
    echo "Seeing var at Line: $line :: $file <br/><pre>".var_dump($variable)."</pre>";
    die("because seedie");
    return;
}

//STANDARD RESPONSE
// {
//     'success':0,
//     'message':{
//         'data':{...}
//     },
//     'errors':{
        
//     }
// }
//offers standardized json responses across the api
function response(int $success,$data=[],$information="",$errors=""){
    $response=array(
        "success"=>$success,
        "message"=>array(
            "information"=>$information,
            "data"=>$data,
            "errors"=>$errors
        )
    );
    return json_encode($response);
}
//password hashing 
function mask($pass){
    return password_hash($pass,PASSWORD_BCRYPT);
}
//request input handling
function input($filter=[]){
    $input=new InputHandler(SimpleRouter::request());
    return $input->all($filter);
}
//request input validation
function validate($validateAs="string",$var){
    switch(strtolower($validateAs)){
        case "email":
            $result=filter_var($var,FILTER_VALIDATE_EMAIL)?true:false;
            break;

        case "string":
            $result=is_string($var)?true:false;
            break;

        case "integer":
            $result=is_integer($var)?true:false;
            break;

        case "date":
            $pregResult=preg_match("(^\d{4}\-[0|1]\d\-[0|1|2|3]\d$)",$var,$matches);
            $result=!empty($pregResult)?true:false;

        default:
            $result=false;
    }

    return $result;
}