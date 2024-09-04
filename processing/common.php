<?php
ini_set('display_errors', 0);
//error_reporting(E_ALL);
define('ROOT_PATH', dirname(__DIR__) . '/');
define('PROC_PATH', dirname(__DIR__) . '/processing/');


function write_to_file($filename, $data){
    file_put_contents($filename, $data . PHP_EOL, FILE_APPEND);
}
function read_from_file($filename){
    return file_get_contents($filename);
}

// Function to log data to a file
function write_to_config($data){
    file_put_contents(PROC_PATH.'config.cnf', $data . PHP_EOL);
}
function read_from_config(){
    return file_get_contents(PROC_PATH.'config.cnf');
}



?>