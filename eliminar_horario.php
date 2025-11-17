<?php
include_once("conexion.php");
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: horario.php"); exit; }

$stmt = $conn->prepare("DELETE FROM horario WHERE id_horario = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    $stmt->close();
    header("Location: horario.php?msg=deleted");
    exit;
} else {
    echo "Error: " . $conn->error;
}
