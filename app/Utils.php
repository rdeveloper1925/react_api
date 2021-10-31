<?php

function see($variable){
    $trace=debug_backtrace();
    $cut_trace=array_shift($trace);
    $line=$cut_trace['line'];
    $file=$cut_trace['file'];
    echo "Seeing var at Line: $line :: $file <br/><pre>".var_dump($variable)."</pre>";
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