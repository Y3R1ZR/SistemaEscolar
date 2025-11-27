<?php
include_once("conexion.php");

// 1. Obtener IDs de la Tarea y el Grupo (para redirección)
$id_tarea = isset($_GET['id_tarea']) ? intval($_GET['id_tarea']) : 0;
$id_grupo = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;

if ($id_tarea === 0 || $id_grupo === 0) {
    die("Error: Faltan parámetros para eliminar la tarea.");
}

// 2. Ejecutar la eliminación
$delete = $conn->prepare("DELETE FROM tareas WHERE id_tarea = ?");
$delete->bind_param("i", $id_tarea);

if ($delete->execute()) {
    $msg_type = "success";
    // Nota: Si la tabla 'calificaciones' tiene una clave foránea ON DELETE CASCADE 
    // apuntando a 'tareas', las calificaciones de esta tarea se eliminarán automáticamente.
} else {
    $msg_type = "error";
}
$delete->close();

// 3. Redireccionar de vuelta a la vista de la materia
header("Location: ver_materia.php?id_grupo=" . $id_grupo . "&msg_delete=" . $msg_type);
exit;
?>