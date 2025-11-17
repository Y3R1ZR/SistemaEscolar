<?php
include_once("conexion.php");

// 1. Obtener IDs del Apartado y del Grupo (para redirección)
$id_apartado = isset($_GET['id_apartado']) ? intval($_GET['id_apartado']) : 0;
$id_grupo = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;

if ($id_apartado === 0 || $id_grupo === 0) {
    die("Error: Faltan parámetros para eliminar el apartado.");
}

// 2. Ejecutar la eliminación
$delete = $conn->prepare("DELETE FROM apartados_escala WHERE id_apartado = ?");
$delete->bind_param("i", $id_apartado);

if ($delete->execute()) {
    $mensaje = "success";
} else {
    $mensaje = "error";
}
$delete->close();

// 3. Redireccionar de vuelta a la vista de la materia
header("Location: ver_materia.php?id=" . $id_grupo . "&msg_delete=" . $mensaje);
exit;
?>