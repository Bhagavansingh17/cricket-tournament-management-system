<?php
// --- DATABASE CONNECTION ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ctms_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// THIS IS THE FIXED LINE (Line 11):
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>