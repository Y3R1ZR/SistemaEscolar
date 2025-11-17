<?php
include_once("conexion.php");

// 1. Obtener parámetros de la URL
$id_apartado = isset($_GET['id_apartado']) ? intval($_GET['id_apartado']) : 0;
$id_grupo = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;
$matricula = isset($_GET['matricula']) ? $_GET['matricula'] : '';

if ($id_apartado === 0 || $id_grupo === 0 || empty($matricula)) {
    die("Error: Faltan parámetros para la gestión de calificaciones.");
}

// 2. Procesar el formulario POST (Edición Masiva)
$msg = "";
$msg_type = "";
$guardado_exitoso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notas = $_POST['notas'] ?? []; // Array de notas [id_tarea => nota]
    $stmt_save = null;
    $errores = 0;

    // Preparamos la consulta INSERT...ON DUPLICATE KEY UPDATE
    // (Requiere que la tabla 'calificaciones' tenga UNIQUE KEY en id_tarea y matricula)
    $sql = "
        INSERT INTO calificaciones (id_tarea, matricula, nota_obtenida) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            nota_obtenida = VALUES(nota_obtenida)";
    
    $stmt_save = $conn->prepare($sql);
    
    foreach ($notas as $id_tarea => $nota_str) {
        $id_tarea_int = intval($id_tarea);
        $nota_float = is_numeric($nota_str) ? floatval($nota_str) : null;
        
        // Solo guardamos si hay un valor numérico ingresado
        if ($nota_float !== null && $nota_float >= 0 && $nota_float <= 10) {
            // Bind: i=id_tarea, s=matricula, d=nota_obtenida
            $stmt_save->bind_param("isd", $id_tarea_int, $matricula, $nota_float); 
            if (!$stmt_save->execute()) {
                $errores++;
            }
        }
    }
    
    if ($stmt_save) {
        $stmt_save->close();
    }

    if ($errores === 0) {
        $msg = "✅ Calificaciones guardadas correctamente.";
        $msg_type = "assign";
        $guardado_exitoso = true;
    } else {
        $msg = "❌ Hubo errores al guardar algunas calificaciones.";
        $msg_type = "delete";
    }
}


// 3. Consultar información del alumno, grupo y apartado
$sql_info = "
    SELECT 
        a.nombre, a.apellido_paterno, ae.nombre_apartado, 
        ae.ponderacion_total, 
        g.materia, g.clave_grupo
    FROM alumnos a
    JOIN apartados_escala ae ON 1=1
    JOIN grupos g ON g.id_grupo = ae.id_grupo
    WHERE a.matricula = ? AND ae.id_apartado = ? AND g.id_grupo = ?";

$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("sii", $matricula, $id_apartado, $id_grupo);
$stmt_info->execute();
$info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

if (!$info) {
    die("Error: Información no encontrada.");
}


// 4. Consultar TAREAS y sus CALIFICACIONES actuales para mostrar el formulario
$sql_tareas = "
    SELECT t.id_tarea, t.nombre_tarea, 
           (SELECT c.nota_obtenida FROM calificaciones c WHERE c.id_tarea = t.id_tarea AND c.matricula = ?) AS nota_actual
    FROM tareas t
    WHERE t.id_apartado = ?
    ORDER BY t.id_tarea ASC";

$stmt_tareas = $conn->prepare($sql_tareas);
$stmt_tareas->bind_param("si", $matricula, $id_apartado);
$stmt_tareas->execute();
$tareas_resultado = $stmt_tareas->get_result();
$tareas_data = $tareas_resultado->fetch_all(MYSQLI_ASSOC);
$stmt_tareas->close();

// 5. CALCULAR PROMEDIO PONDERADO DEL APARTADO (Para fines informativos)
$total_tareas = count($tareas_data);
$suma_notas = 0;
$tareas_calificadas = 0;

foreach ($tareas_data as $tarea) {
    if ($tarea['nota_actual'] !== null) {
        $suma_notas += floatval($tarea['nota_actual']);
        $tareas_calificadas++;
    }
}

$promedio_simple = ($tareas_calificadas > 0) ? ($suma_notas / $tareas_calificadas) : 0;
$contribucion_ponderada = $promedio_simple * (floatval($info['ponderacion_total']) / 100);

// Si se guardó con éxito, recargamos la página para actualizar los promedios
if ($guardado_exitoso) {
    // Redireccionamos para evitar reenvío de formulario y forzar la recarga de datos
    header("Location: calificaciones_apartado.php?id_apartado={$id_apartado}&id_grupo={$id_grupo}&matricula={$matricula}&msg={$msg}");
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calificar Apartado: <?= htmlspecialchars($info['nombre_apartado']) ?></title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 600; }
        .msg.assign { background: #d4f7e9; color: #007d4b; border: 1px solid #a6e3c9; }
        .msg.delete { background: #ffe4e6; color: #cc0000; border: 1px solid #ffc0c7; }
        
        .grid-solo { grid-template-columns: 1fr; max-width: 800px; margin: 0 auto; }
        .promedio-box { background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .promedio-box h3 { margin: 0; color: #856404; }

        .tarea-item-input { 
            display: flex; 
            align-items: center; 
            padding: 10px 0; 
            border-bottom: 1px solid #eee;
            gap: 20px;
        }
        .tarea-item-input label { flex-basis: 70%; margin: 0; }
        .tarea-item-input input { 
            flex-basis: 30%; 
            text-align: right; 
            padding: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="brand">
            <div class="logo">CF</div>
            <div><h1>Calificación por Apartado</h1></div>
        </div>
        <div class="nav">
            <a href="ver_materia.php?id=<?= $id_grupo ?>">Volver a <?= htmlspecialchars($info['clave_grupo']) ?></a>
        </div>
    </div>
    
    <div class="grid grid-solo">
        <div class="card">
            <h2><?= htmlspecialchars($info['nombre_apartado']) ?></h2>
            <p>Alumno: <strong><?= htmlspecialchars($info['nombre'] . ' ' . $info['apellido_paterno']) ?></strong></p>
            
            <div class="promedio-box">
                <h3>Promedio Actual del Apartado: <?= number_format($promedio_simple, 2) ?></h3>
                <p>Tareas calificadas: <?= $tareas_calificadas ?> de <?= $total_tareas ?></p>
                <p>Contribución al total del curso: <?= number_format($contribucion_ponderada * 10, 2) ?> puntos</p>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="msg assign"><?= htmlspecialchars($_GET['msg']) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <ul style="list-style-type: none; padding: 0;">
                    <?php if ($total_tareas === 0): ?>
                        <p style="text-align: center;">No hay tareas en este apartado para calificar.</p>
                    <?php endif; ?>
                    
                    <?php foreach ($tareas_data as $tarea): ?>
                        <li class="tarea-item-input">
                            <label for="nota_<?= $tarea['id_tarea'] ?>">
                                <?= htmlspecialchars($tarea['nombre_tarea']) ?>
                            </label>
                            <input type="text" 
                                   name="notas[<?= $tarea['id_tarea'] ?>]" 
                                   id="nota_<?= $tarea['id_tarea'] ?>"
                                   value="<?= htmlspecialchars($tarea['nota_actual'] ?? '') ?>"
                                   placeholder="0.00 - 10.00"
                                   pattern="[0-9]*\.?[0-9]{0,2}"
                                   title="Solo números (0-10) con hasta 2 decimales">
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div style="margin-top: 25px;">
                    <button type="submit" class="primary">Guardar Calificaciones</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>