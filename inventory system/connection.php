<?php
    // Database connection details
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "divisional_secretariat_inventory";
    
// Create a database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optionally, you can set the character set for proper data encoding
$conn->set_charset("utf8");

// It's important to include this file at the beginning of your PHP scripts to establish a database connection.
?>
