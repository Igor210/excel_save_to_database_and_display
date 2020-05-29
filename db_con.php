<?php


$DB_host = "localhost";
$DB_user = "root";
$DB_pass = "";
$DB_name = "importexceltomysql";

 $conn = mysqli_connect($DB_host, $DB_user, $DB_pass, $DB_name);
 if (!$conn) {
	die("Connection failed3: " . mysqli_connect_error());
}