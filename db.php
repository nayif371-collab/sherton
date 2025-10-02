<?php
// db.php
$DB_HOST = 'localhost';
$DB_NAME = 'dbpzn3emqpie07';
$DB_USER = 'uhbgtuxfhy4wg';
$DB_PASS = 'oramgejfijqa';
 
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    die("DB Connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
?>
