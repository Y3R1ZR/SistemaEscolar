<?php
$servername = "localhost";
$username = "root";
$password = ""; // XAMPP no usa contraseña por defecto
$database = "escuela"; // asegúrate que exista en phpMyAdmin

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
