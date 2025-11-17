<?php
include_once("conexion.php");

$id_grupo = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;
if ($id_grupo === 0) {
    die("Error: Se requiere el ID del grupo para añadir un apartado.");
}

$stmt_grupo = $conn->prepare("SELECT materia, clave_grupo FROM grupos WHERE id_grupo = ?");
$stmt_grupo->bind_param("i", $id_grupo);
$stmt_grupo->execute();
$detalles_grupo = $stmt_grupo->get_result()->fetch_assoc();
$stmt_grupo->close();

if (!$detalles_grupo) {
    die("Error: Grupo no encontrado.");
}

$msg = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_apartado = trim($_POST['nombre_apartado']);
    $ponderacion_total = floatval($_POST['ponderacion_total']);

    if (!empty($nombre_apartado) && $ponderacion_total > 0) {
        $insert = $conn->prepare("INSERT INTO apartados_escala (id_grupo, nombre_apartado, ponderacion_total) VALUES (?, ?, ?)");
        $insert->bind_param("isd", $id_grupo, $nombre_apartado, $ponderacion_total);
        
        if ($insert->execute()) {
            $msg = "✅ Apartado de escala agregado correctamente.";
            $msg_type = "assign";
            header("Location: ver_materia.php?id=" . $id_grupo);
            exit;
        } else {
            $msg = "❌ Error al agregar el apartado: " . $insert->error;
            $msg_type = "delete";
        }
        $insert->close();
    } else {
        $msg = "⚠️ Por favor, ingrese un nombre y una ponderación válida.";
        $msg_type = "delete";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Apartado de Escala</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 600; }
        .msg.assign { background: #d4f7e9; color: #007d4b; border: 1px solid #a6e3c9; }
        .msg.delete { background: #ffe4e6; color: #cc0000; border: 1px solid #ffc0c7; }
        .grid-solo { grid-template-columns: 1fr; max-width: 600px; margin: 0 auto; }
        .form-content { padding: 30px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="brand">
            <div class="logo">ES</div>
            <div><h1>Crear Apartado de Escala</h1></div>
        </div>
        <div class="nav">
            <a href="ver_materia.php?id=<?= $id_grupo ?>">Volver</a>
        </div>
    </div>
    
    <div class="grid grid-solo">
        <div class="card form-content">
            <h2>Nuevo Apartado para: <?= htmlspecialchars($detalles_grupo['materia']) ?></h2><br>
            
            <?php if($msg): ?>
                <div class="msg <?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-grid">
                    <div>
                        <label for="nombre_apartado">Nombre del Apartado (Ej: Exámenes, Tareas, Asistencia):</label>
                        <input type="text" name="nombre_apartado" id="nombre_apartado" required>
                    </div>
                    
                    <div>
                        <label for="ponderacion_total">Ponderación Total de esta Categoría (%):</label>
                        <input type="text" name="ponderacion_total" id="ponderacion_total" pattern="[0-9]*\.?[0-9]+" title="Solo números y punto decimal" required>
                    </div>
                </div>

                <div style="margin-top: 25px;">
                    <button type="submit" class="primary">Guardar Apartado</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>