<?php
include_once("conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: horario.php");
    exit;
}

$id_grupo = isset($_POST['id_grupo']) ? intval($_POST['id_grupo']) : 0;
$dia = $_POST['dia'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fin = $_POST['hora_fin'] ?? '';
$aula = trim($_POST['aula'] ?? '');

if ($id_grupo <= 0 || !$dia || !$hora_inicio || !$hora_fin) {
    die("Faltan datos obligatorios.");
}

// validación básica: hora_inicio < hora_fin
if (strtotime($hora_inicio) >= strtotime($hora_fin)) {
    die("La hora de inicio debe ser menor a la hora fin.");
}

$stmt = $conn->prepare("INSERT INTO horario (id_grupo, dia, hora_inicio, hora_fin, aula) VALUES (?,?,?,?,?)");
$stmt->bind_param("issss", $id_grupo, $dia, $hora_inicio, $hora_fin, $aula);

if ($stmt->execute()) {
    $stmt->close();
    $redirect = $_POST['redirect'] ?? 'horario.php';
    header("Location: " . $redirect . "?msg=ok");
    exit;
} else {
    echo "Error al guardar: " . $conn->error;
}
