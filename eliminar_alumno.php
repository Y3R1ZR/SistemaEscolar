<?php
include_once("conexion.php");
if (!isset($_GET['matricula'])) {
    header("Location: listar_alumnos.php");
    exit;
}
$mat = $_GET['matricula'];

// Eliminación simple; las FK con ON DELETE CASCADE borran asistencias y asignaciones
$stmt = $conn->prepare("DELETE FROM alumnos WHERE matricula=?");
$stmt->bind_param("s",$mat);
if ($stmt->execute()) {
    header("Location: listar_alumnos.php?msg=Alumno eliminado");
    exit;
} else {
    echo "Error al eliminar: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>
