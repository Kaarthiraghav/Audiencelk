<?php

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';  // Default XAMPP has no password for root user
$DB_NAME = 'audiencelk';


$connection = mysqli_connect($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);

if (!$connection){
    die("Connection Failed");

}
?>