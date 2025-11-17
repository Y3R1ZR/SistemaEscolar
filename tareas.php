<?php
include_once("conexion.php");

// El ID del grupo es necesario
$id_grupo = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;
if ($id_grupo === 0) {
    die("Error: Se requiere el ID del grupo.");
}

$stmt_grupo = $conn->prepare("SELECT materia, clave_grupo FROM grupos WHERE id_grupo = ?");
$stmt_grupo->bind_param("i", $id_grupo);
$stmt_grupo->execute();
$detalles_grupo = $stmt_grupo->get_result()->fetch_assoc();
$stmt_grupo->close();

if (!$detalles_grupo) {
    die("Error: Grupo no encontrado.");
}

// Consultamos los apartados existentes para que el usuario pueda vincular la nueva tarea
$apartados = $conn->query("SELECT id_apartado, nombre_apartado FROM apartados_escala WHERE id_grupo = $id_grupo ORDER BY nombre_apartado ASC");


$msg = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{ 
    // Capturamos el valor del apartado desde el formulario
    $id_apartado = intval($_POST['id_apartado']); 
    $nombre_tarea = trim($_POST['nombre_tarea']);
    $fecha_limite = $_POST['fecha_limite']; 

    // Validamos que se haya seleccionado un apartado y se haya nombrado la tarea
    if ($id_apartado > 0 && !empty($nombre_tarea)) {
        
        // La consulta INSERT: ¡AQUÍ $id_apartado YA TIENE EL VALOR DEL FORMULARIO!
        $insert = $conn->prepare("INSERT INTO tareas (id_apartado, nombre_tarea, fecha_limite) VALUES (?, ?, ?)");
        // El tipo de dato es 'iss': i=integer (id_apartado), s=string (nombre_tarea), s=string (fecha_limite)
        $insert->bind_param("iss", $id_apartado, $nombre_tarea, $fecha_limite); 
        
        if ($insert->execute()) {
            $msg = "✅ Tarea agregada correctamente.";
            $msg_type = "assign";
            header("Location: ver_materia.php?id=" . $id_grupo);
            exit;
        } else {
            $msg = "❌ Error al agregar la tarea: " . $insert->error;
            $msg_type = "delete";
        }
        $insert->close();
    } else {
        $msg = "⚠️ Por favor, seleccione un apartado y nombre la tarea.";
        $msg_type = "delete";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Tarea</title>
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
            <div><h1>Crear Tarea</h1></div>
        </div>
        <div class="nav">
            <a href="ver_materia.php?id=<?= $id_grupo ?>">Volver</a>
        </div>
    </div>
    
    <div class="grid grid-solo">
        <div class="card form-content">
            <h2>Nueva Tarea para: <?= htmlspecialchars($detalles_grupo['materia']) ?></h2>
            
            <?php if($msg): ?>
                <div class="msg <?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                <div class="form-grid">
                    
                    <div>
                        <label for="id_apartado">Vincular a Apartado de Escala:</label>
                        <select name="id_apartado" id="id_apartado" required>
                            <option value="">-- Seleccione Apartado --</option>
                            <?php if ($apartados->num_rows > 0): ?>
                                <?php $apartados->data_seek(0); ?>
                                <?php while($a = $apartados->fetch_assoc()): ?>
                                    <option value="<?= $a['id_apartado'] ?>">
                                        <?= htmlspecialchars($a['nombre_apartado']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label for="nombre_tarea">Nombre de la Tarea (Ej: Examen Parcial 1, Reporte 3):</label>
                        <input type="text" name="nombre_tarea" id="nombre_tarea" required>
                    </div>
                    
                    <div>
                        <label for="fecha_limite">Fecha Límite de Entrega (Opcional):</label>
                        <input type="date" name="fecha_limite" id="fecha_limite">
                    </div>
                </div>

                <div style="margin-top: 25px;">
                    <button type="submit" class="primary">Guardar Tarea</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>