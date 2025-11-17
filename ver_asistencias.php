<?php
include_once("conexion.php");

// filtros
$filtro_grupo = isset($_GET['grupo']) ? intval($_GET['grupo']) : 0;
$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';

$grupos = $conn->query("SELECT id_grupo, clave_grupo, materia FROM grupos ORDER BY materia ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Ver asistencias</title><link rel="stylesheet" href="estilo.css"></head>
<body>
<div class="container">
  <div class="header">
    <div class="brand"><div class="logo">VA</div><div><h1>Asistencias</h1></div></div>
    <div class="nav"><a href="index.php">Inicio</a><a class="button-ghost" href="registrar_asistencia.php">Registrar</a></div>
  </div>

  <div class="card">
    <form method="get" class="form-grid">
      <div>
        <label>Grupo</label>
        <select name="grupo">
          <option value="0">Todos</option>
          <?php while($g=$grupos->fetch_assoc()): ?>
            <option value="<?=$g['id_grupo']?>" <?= $filtro_grupo==$g['id_grupo'] ? 'selected':'' ?>><?=htmlspecialchars($g['clave_grupo'].' - '.$g['materia'])?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label>Fecha</label>
        <input type="date" name="fecha" value="<?=htmlspecialchars($filtro_fecha)?>">
      </div>
      <div style="grid-column:span 2; display:flex; justify-content:flex-end">
        <button class="primary" type="submit">Filtrar</button>
        
      </div>
    </form>
  </div>

  <div class="card" style="margin-top:12px">
    <?php
    // consulta principal
    $sql = "SELECT a.matricula, a.nombre, a.apellido_paterno, g.clave_grupo, s.fecha, s.estado
            FROM asistencias s
            JOIN alumnos a ON s.matricula=a.matricula
            JOIN grupos g ON s.id_grupo=g.id_grupo";
    $where = []; $params = []; $types = "";
    if ($filtro_grupo) { $where[] = "s.id_grupo=?"; $params[] = $filtro_grupo; $types .= "i"; }
    if ($filtro_fecha) { $where[] = "s.fecha=?"; $params[] = $filtro_fecha; $types .= "s"; }
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY s.fecha DESC, a.nombre ASC";
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    ?>

    <table class="table">
      <thead><tr><th>Fecha</th><th>Matrícula</th><th>Alumno</th><th>Grupo</th><th>Estado</th></tr></thead>
      <tbody>
        <?php while($r = $res->fetch_assoc()): ?>
          <tr>
            <td><?=htmlspecialchars($r['fecha'])?></td>
            <td><?=htmlspecialchars($r['matricula'])?></td>
            <td><?=htmlspecialchars($r['nombre'].' '.$r['apellido_paterno'])?></td>
            <td><?=htmlspecialchars($r['clave_grupo'])?></td>
            <td><?=htmlspecialchars($r['estado'])?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <hr style="margin:16px 0">
    <h3>Conteo de faltas por alumno (total)</h3>
    <?php
      $q = $conn->query("SELECT a.matricula, a.nombre, a.apellido_paterno, SUM(CASE WHEN s.estado='Faltó' THEN 1 ELSE 0 END) AS faltas
                         FROM alumnos a
                         LEFT JOIN asistencias s ON a.matricula = s.matricula
                         GROUP BY a.matricula ORDER BY faltas DESC, a.nombre ASC");
    ?>
    <table class="table">
      <thead><tr><th>Matrícula</th><th>Alumno</th><th>Faltas</th></tr></thead>
      <tbody>
        <?php while($row = $q->fetch_assoc()): ?>
          <tr>
            <td><?=htmlspecialchars($row['matricula'])?></td>
            <td><?=htmlspecialchars($row['nombre'].' '.$row['apellido_paterno'])?></td>
            <td><?=htmlspecialchars($row['faltas'])?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
<?php $stmt->close(); ?>
