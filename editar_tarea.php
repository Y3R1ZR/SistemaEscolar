<?php
include_once("conexion.php");

// 1. Obtener ID de la Tarea y del Grupo (para redirección)
$id_tarea = isset($_GET['id_tarea']) ? intval($_GET['id_tarea']) : 0;
$id_grupo = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;

if ($id_tarea === 0 || $id_grupo === 0) {
    die("Error: Faltan parámetros (ID de Tarea o ID de Grupo).");
}

// 2. CONSULTAR DETALLES EXISTENTES de la Tarea y del Grupo
// 🚨 ATENCIÓN: Asumimos que la columna 'calificacion' EXISTE en tu tabla tareas AHORA.
$stmt_tarea = $conn->prepare("SELECT grupo, fecha, descripcion, calificacion FROM tareas WHERE id_tarea = ?");
$stmt_tarea->bind_param("i", $id_tarea);
$stmt_tarea->execute();
$tarea_data = $stmt_tarea->get_result()->fetch_assoc();
$stmt_tarea->close();

if (!$tarea_data) {
    die("Error: Tarea no encontrada.");
}

$stmt_grupo = $conn->prepare("SELECT materia, clave_grupo FROM grupos WHERE id_grupo = ?");
$stmt_grupo->bind_param("i", $id_grupo);
$stmt_grupo->execute();
$detalles_grupo = $stmt_grupo->get_result()->fetch_assoc();
$stmt_grupo->close();

$msg = "";
$msg_type = "";

// 3. PROCESAR EL FORMULARIO DE ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_tarea = trim($_POST['nombre_tarea']);
    $descripcion_detallada = trim($_POST['descripcion_detallada']);
    $fecha_limite = $_POST['fecha_limite'];
    $calificacion_valor = floatval($_POST['calificacion_valor']); // Nuevo campo de valor/peso
    
    // Unimos los campos en el formato 'Nombre | Descripción Detallada'
    $descripcion_final = $nombre_tarea . " | " . $descripcion_detallada;

    if (!empty($nombre_tarea) && !empty($descripcion_detallada) && !empty($fecha_limite)) {
        
        // 🚨 ACTUALIZACIÓN CRUCIAL: Incluimos la actualización del valor de 'calificacion'
        $update = $conn->prepare("UPDATE tareas SET descripcion = ?, fecha = ?, calificacion = ? WHERE id_tarea = ?");
        
        // Tipos: s (descripcion_final), s (fecha_limite), d (calificacion_valor), i (id_tarea)
        $update->bind_param("ssdi", $descripcion_final, $fecha_limite, $calificacion_valor, $id_tarea); 

        if ($update->execute()) {
            $msg = "✅ Tarea actualizada correctamente.";
            $msg_type = "assign";
            // Redirigir para evitar reenvío y actualizar la vista
            header("Location: ver_materia.php?id_grupo=" . $id_grupo);
            exit;
        } else {
            $msg = "❌ Error al actualizar la tarea: " . $update->error;
            $msg_type = "delete";
        }
        $update->close();
    } else {
        $msg = "⚠️ Por favor, complete todos los campos requeridos.";
        $msg_type = "delete";
    }
}

// Separar la descripción para precargar los campos del formulario
$partes = explode(' | ', $tarea_data['descripcion'], 2);
$nombre_tarea_existente = $partes[0] ?? $tarea_data['descripcion'];
$descripcion_detallada_existente = $partes[1] ?? '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Tarea</title>
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
            <div class="logo">TA</div>
            <div><h1>Editar Tarea</h1></div>
        </div>
        <div class="nav">
            <a href="ver_materia.php?id_grupo=<?= $id_grupo ?>">Volver</a>
        </div>
    </div>
    
    <div class="grid grid-solo">
        <div class="card form-content">
            <h2>Modificar Tarea: <?= htmlspecialchars($nombre_tarea_existente) ?></h2>
            
            <?php if($msg): ?>
                <div class="msg <?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>"><br>
                <div class="form-grid">
                    
                    <div>
                        <label for="nombre_tarea">1. Nombre de la Tarea:</label>
                        <input type="text" name="nombre_tarea" id="nombre_tarea" 
                               value="<?= htmlspecialchars($nombre_tarea_existente) ?>" required>
                    </div>
                    
                    <div>
                        <label for="descripcion_detallada">2. Descripción Detallada:</label>
                        <input type="text" name="descripcion_detallada" id="descripcion_detallada" 
                               value="<?= htmlspecialchars($descripcion_detallada_existente) ?>" required>
                    </div>

                    <div>
                        <label for="fecha_limite">3. Fecha Límite:</label>
                        <input type="date" name="fecha_limite" id="fecha_limite" 
                               value="<?= htmlspecialchars($tarea_data['fecha']) ?>" required>
                    </div>
                    
                    <div>
                        <label for="calificacion_valor">4. Valor/Peso de la Tarea (%):</label>
                        <input type="number" name="calificacion_valor" id="calificacion_valor" 
                               value="<?= htmlspecialchars($tarea_data['calificacion']) ?>" 
                               step="0.01" min="0" max="100" required>
                    </div>
                    
                </div>

                <div style="margin-top: 25px;">
                    <button type="submit" class="primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>