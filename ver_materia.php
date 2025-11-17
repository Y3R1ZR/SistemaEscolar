<?php
include_once("conexion.php");

// Parámetros de filtro
$id_grupo = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '';

// Obtener lista de carreras y periodos disponibles
$carreras = $conn->query("SELECT DISTINCT carrera FROM grupos ORDER BY carrera ASC")->fetch_all(MYSQLI_ASSOC);
$periodos = $conn->query("SELECT DISTINCT periodo FROM grupos ORDER BY periodo ASC")->fetch_all(MYSQLI_ASSOC);

// Obtener grupos filtrados
$sql_filtro = "SELECT id_grupo, clave_grupo, materia, semestre, carrera, periodo FROM grupos WHERE 1=1";
$params = [];
$types = '';

if ($periodo) {
    $sql_filtro .= " AND periodo = ?";
    $params[] = $periodo;
    $types .= 's';
}
$stmt = $conn->prepare($sql_filtro);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$grupos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$grupo = null;
$tareas = [];
$alumnos = [];
$calificaciones = [];
$faltas = [];

if ($id_grupo > 0) {
    // Obtener información del grupo
    $stmt = $conn->prepare("SELECT clave_grupo, materia, semestre, carrera, periodo FROM grupos WHERE id_grupo = ?");
    $stmt->bind_param("i", $id_grupo);
    $stmt->execute();
    $grupo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Obtener tareas del grupo
    $stmt = $conn->prepare("SELECT id_tarea, descripcion, fecha, calificacion FROM tareas WHERE grupo = ?");
    $stmt->bind_param("s", $grupo['clave_grupo']);
    $stmt->execute();
    $tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Obtener alumnos
    $stmt = $conn->prepare("
        SELECT a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno
        FROM alumnos a
        JOIN alumno_grupo ag ON a.matricula = ag.matricula
        WHERE ag.id_grupo = ?
        ORDER BY a.apellido_paterno ASC
    ");
    $stmt->bind_param("i", $id_grupo);
    $stmt->execute();
    $alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Calificaciones
    $stmt = $conn->prepare("
        SELECT c.matricula, c.id_examen AS id_tarea, c.calificacion
        FROM calificaciones c
        JOIN tareas t ON c.id_examen = t.id_tarea
        WHERE t.grupo = ?
    ");
    $stmt->bind_param("s", $grupo['clave_grupo']);
    $stmt->execute();
    $res_calif = $stmt->get_result();
    while ($row = $res_calif->fetch_assoc()) {
        $calificaciones[$row['matricula']][$row['id_tarea']] = $row['calificacion'];
    }
    $stmt->close();

    // Faltas (asistencias)
    $stmt = $conn->prepare("
        SELECT matricula, COUNT(*) AS total_faltas
        FROM asistencias
        WHERE id_grupo = ? AND estado = 'Falta'
        GROUP BY matricula
    ");
    $stmt->bind_param("i", $id_grupo);
    $stmt->execute();
    $res_faltas = $stmt->get_result();
    while ($f = $res_faltas->fetch_assoc()) {
        $faltas[$f['matricula']] = $f['total_faltas'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Materia</title>
    <style>
        body {
            background: #f8fafc;
            font-family: 'Segoe UI', sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        header {
            background: linear-gradient(90deg, #1e3a8a, #312e81);
            color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 { margin: 0; font-size: 22px; }
        nav a {
            color: #fff;
            text-decoration: none;
            margin-left: 15px;
            padding: 8px 14px;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            transition: 0.3s;
        }
        nav a:hover {
            background: rgba(255,255,255,0.35);
        }
        .container {
            max-width: 1200px;
            margin: 25px auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h2 {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 5px;
            color: #1e40af;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: linear-gradient(90deg, #4f46e5, #3b82f6);
            color: #fff;
            padding: 10px;
        }
        td {
            background: #f1f5f9;
            padding: 8px;
            border-bottom: 1px solid #cbd5e1;
        }
        tr:hover td {
            background: #e0e7ff;
        }
        select, button {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #94a3b8;
            font-size: 14px;
        }
        button {
            background: #2563eb;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        button:hover { background: #1d4ed8; }
        .form-filtros {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }
        .acciones {
            margin-top: 15px;
        }
        .acciones button {
            background: #10b981;
            margin-right: 8px;
        }
        .acciones button.eliminar {
            background: #ef4444;
        }
        .acciones button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
<header>
    <h1>Panel de Materias</h1>
    <nav>
        <a href="index.php">🏠 Inicio</a>
        <a href="listar_alumnos.php">👩‍🎓 Alumnos</a>
        <a href="listar_grupos.php">📘 Grupos</a>
        <a href="registrar_asistencia.php">🗓️ Asistencias</a>
    </nav>
</header>

<div class="container">
    <form class="form-filtros" method="get">
        <label>Periodo:</label>
        <select name="periodo">
            <option value="">-- Todos --</option>
            <?php foreach ($periodos as $p): ?>
                <option value="<?= htmlspecialchars($p['periodo']) ?>" <?= $p['periodo'] == $periodo ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['periodo']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Grupo:</label>
        <select name="id_grupo">
            <option value="">-- Seleccione --</option>
            <?php foreach ($grupos as $g): ?>
                <option value="<?= $g['id_grupo'] ?>" <?= $g['id_grupo'] == $id_grupo ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['clave_grupo'] . " - " . $g['materia']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Filtrar</button>
    </form>

    <?php if ($grupo): ?>
        <h2>Materia: <?= htmlspecialchars($grupo['materia']) ?></h2>
        <p><strong>Clave:</strong> <?= htmlspecialchars($grupo['clave_grupo']) ?> |
           <strong>Carrera:</strong> <?= htmlspecialchars($grupo['carrera']) ?> |
           <strong>Semestre:</strong> <?= htmlspecialchars($grupo['semestre']) ?> |
           <strong>Periodo:</strong> <?= htmlspecialchars($grupo['periodo']) ?></p>

        <div class="acciones">
            <button onclick="alert('Agregar tarea próximamente...')">➕ Agregar tarea</button>
            <button onclick="alert('Modificar tarea próximamente...')">✏️ Modificar</button>
            <button class="eliminar" onclick="alert('Eliminar tarea próximamente...')">🗑️ Eliminar</button>
        </div>

        <h3>📘 Tareas registradas</h3>
        <?php if (empty($tareas)): ?>
            <p>No hay tareas registradas.</p>
        <?php else: ?>
            <table>
                <tr><th>Descripción</th><th>Fecha</th><th>Valor</th></tr>
                <?php foreach ($tareas as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['descripcion']) ?></td>
                        <td><?= htmlspecialchars($t['fecha']) ?></td>
                        <td><?= htmlspecialchars($t['calificacion']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h3>📊 Calificaciones por alumno</h3>
        <table>
            <tr>
                <th>Alumno</th>
                <?php foreach ($tareas as $t): ?>
                    <th><?= htmlspecialchars($t['descripcion']) ?></th>
                <?php endforeach; ?>
                <th>Promedio</th>
                <th>Faltas</th>
            </tr>
            <?php foreach ($alumnos as $a): 
                $matricula = $a['matricula'];
                $total = 0; $count = 0;
            ?>
                <tr>
                    <td><?= htmlspecialchars($a['nombre'] . ' ' . $a['apellido_paterno'] . ' ' . $a['apellido_materno']) ?></td>
                    <?php foreach ($tareas as $t): 
                        $nota = $calificaciones[$matricula][$t['id_tarea']] ?? '—';
                        if (is_numeric($nota)) { $total += $nota; $count++; }
                    ?>
                        <td><?= htmlspecialchars($nota) ?></td>
                    <?php endforeach; ?>
                    <td><strong><?= $count > 0 ? round($total / $count, 2) : '—' ?></strong></td>
                    <td><?= $faltas[$matricula] ?? 0 ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
