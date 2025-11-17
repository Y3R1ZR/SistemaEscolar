<?php
include_once("conexion.php");

// 1. Obtener ID del Apartado (para editar)
$id_apartado = isset($_GET['id_apartado']) ? intval($_GET['id_apartado']) : 0;

if ($id_apartado === 0) {
    die("Error: Se requiere el ID del apartado para editar.");
}

// 2. Consultar datos existentes del Apartado
$stmt_apartado = $conn->prepare("SELECT id_grupo, nombre_apartado, ponderacion_total FROM apartados_escala WHERE id_apartado = ?");
$stmt_apartado->bind_param("i", $id_apartado);
$stmt_apartado->execute();
$apartado_data = $stmt_apartado->get_result()->fetch_assoc();
$stmt_apartado->close();

if (!$apartado_data) {
    die("Error: Apartado no encontrado.");
}

$id_grupo = $apartado_data['id_grupo'];

// 3. Consultar detalles del Grupo (para navegación)
$stmt_grupo = $conn->prepare("SELECT clave_grupo FROM grupos WHERE id_grupo = ?");
$stmt_grupo->bind_param("i", $id_grupo);
$stmt_grupo->execute();
$detalles_grupo = $stmt_grupo->get_result()->fetch_assoc();
$stmt_grupo->close();


$msg = "";
$msg_type = "";

// 4. Procesar el formulario POST (Actualizar Apartado)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_apartado = trim($_POST['nombre_apartado']);
    $ponderacion_total = floatval($_POST['ponderacion_total']);

    if (!empty($nombre_apartado) && $ponderacion_total >= 0) {
        $update = $conn->prepare("UPDATE apartados_escala SET nombre_apartado = ?, ponderacion_total = ? WHERE id_apartado = ?");
        $update->bind_param("sdi", $nombre_apartado, $ponderacion_total, $id_apartado);
        
        if ($update->execute()) {
            $msg = "✅ Apartado actualizado correctamente.";
            $msg_type = "assign";
            
            // Redireccionar de vuelta a la vista de la materia
            header("Location: ver_materia.php?id=" . $id_grupo);
            exit;
        } else {
            $msg = "❌ Error al actualizar el apartado: " . $update->error;
            $msg_type = "delete";
        }
        $update->close();
    } else {
        $msg = "⚠️ Por favor, complete todos los campos requeridos.";
        $msg_type = "delete";
    }
    
    // Si la actualización falla, recargamos la data antigua para mostrarla en el formulario
    $apartado_data['nombre_apartado'] = $nombre_apartado;
    $apartado_data['ponderacion_total'] = $ponderacion_total;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Escala</title>
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
            <div><h1>Editar Apartado de Escala</h1></div>
        </div>
        <div class="nav">
            <a href="ver_materia.php?id=<?= $id_grupo ?>">Volver</a>
        </div>
    </div>
    
    <div class="grid grid-solo">
        <div class="card form-content">
            <h2>Editando: <?= htmlspecialchars($apartado_data['nombre_apartado']) ?></h2>
            
            <?php if($msg): ?>
                <div class="msg <?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-grid">
                    
                    <div>
                        <label for="nombre_apartado">Nombre del Apartado:</label>
                        <input type="text" name="nombre_apartado" id="nombre_apartado" 
                               value="<?= htmlspecialchars($apartado_data['nombre_apartado']) ?>" required>
                    </div>
                    
                    <div>
                        <label for="ponderacion_total">Ponderación Total de esta Categoría (%):</label>
                        <input type="text" name="ponderacion_total" id="ponderacion_total" 
                               value="<?= htmlspecialchars($apartado_data['ponderacion_total']) ?>" 
                               pattern="[0-9]*\.?[0-9]+" title="Solo números y punto decimal" required>
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