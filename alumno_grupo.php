<?php
include_once("conexion.php");

// Obtener alumnos para el dropdown principal
$alumnos = $conn->query("SELECT matricula, nombre, apellido_paterno, apellido_materno FROM alumnos ORDER BY nombre ASC");

// Obtener grupos para el dropdown principal
$grupos = $conn->query("SELECT id_grupo, clave_grupo, materia, semestre, carrera, periodo FROM grupos ORDER BY materia ASC");

// Mensaje de estado
$msg = "";
$msg_type = ""; 

// Procesar asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['matricula'];
    $id_grupo = $_POST['id_grupo'];

    // Evitar duplicados
    $check = $conn->prepare("SELECT id FROM alumno_grupo WHERE matricula = ? AND id_grupo = ?");
    $check->bind_param("si", $matricula, $id_grupo);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $msg = "⚠️ Este alumno ya está asignado a ese grupo.";
        $msg_type = "delete"; 
    } else {
        $insert = $conn->prepare("INSERT INTO alumno_grupo (matricula, id_grupo) VALUES (?, ?)");
        $insert->bind_param("si", $matricula, $id_grupo);
        if ($insert->execute()) {
            $msg = "✅ Alumno asignado correctamente.";
            $msg_type = "assign"; 
        } else {
            $msg = "❌ Error al asignar: " . $insert->error;
            $msg_type = "delete";
        }
        $insert->close();
    }
    $check->close();
    
    // Recargar los resultados
    $alumnos->data_seek(0);
    $grupos->data_seek(0);
}

// Consulta para el contador de alumnos y grupos
$total_alumnos = $conn->query("SELECT COUNT(*) AS t FROM alumnos")->fetch_assoc()['t'];
$total_grupos = $conn->query("SELECT COUNT(*) AS t FROM grupos")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Alumnos a Grupos</title>
    <link rel="stylesheet" href="estilo.css">
    
    <style>
        /* CSS Mínimo para el formulario y mensajes (mantenido para consistencia) */
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 600; }
        .msg.assign { background: #d4f7e9; color: #007d4b; border: 1px solid #a6e3c9; }
        .msg.delete { background: #ffe4e6; color: #cc0000; border: 1px solid #ffc0c7; }
        
        .form-asignacion { display: flex; flex-direction: column; gap: 15px; padding: 15px 0; }
        .form-asignacion label { font-weight: 600; margin-bottom: 5px; display: block; color: #0f172a;}
        .form-actions { margin-top: 15px; }
        .form-actions button.primary { width: 100%; padding: 12px; font-size: 16px;}
        .card.form-card { padding: 30px;}
        .stats-grid { display: grid; grid-template-columns: 1fr; gap: 10px;}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="brand">
            <div class="logo">AG</div>
            <div><h1>Asignar Alumno a Grupo</h1></div>
        </div>
        <div class="nav">
            <a href="index.php">Inicio</a>
            <a href="listar_grupos.php">Volver</a> 
        </div>
    </div>
    
    <div class="grid">
        
        <div class="card form-card">
            <h2>Seleccionar Alumno y Grupo</h2>
            
            <?php if($msg): ?>
                <div class="msg <?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <form method="post" class="form-asignacion">
                
                <div>
                    <label for="matricula">Seleccionar Alumno:</label>
                    <select name="matricula" id="matricula" required>
                        <option value="">-- Seleccione un Alumno --</option>
                        <?php while($a = $alumnos->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($a['matricula']) ?>">
                                [<?= htmlspecialchars($a['matricula']) ?>] - <?= htmlspecialchars($a['nombre'] . " " . $a['apellido_paterno']." " . $a['apellido_materno']) ?> 
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label for="id_grupo">Seleccionar Grupo (Materia):</label>
                    <select name="id_grupo" id="id_grupo" required>
                        <option value="">-- Seleccione un Grupo --</option>
                        <?php while($g = $grupos->fetch_assoc()): ?>
                            <option value="<?= $g['id_grupo'] ?>">
                                <?= htmlspecialchars($g['materia']) ?> (<?= htmlspecialchars($g['clave_grupo']) ?>) - <?= htmlspecialchars($g['semestre']) ?>°
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="primary">Asignar Alumno a Grupo</button>
                </div>
            </form>
            
        </div>

        <div class="stats stats-grid">
            <div class="card stat-item">
                <h3><?= $total_alumnos ?></h3>
                <p>Total de Alumnos Registrados</p>
            </div>
            <div class="card stat-item">
                <h3><?= $total_grupos ?></h3>
                <p>Total de Grupos Activos</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>