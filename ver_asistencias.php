<?php
include_once("conexion.php");

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function fecha_iso($f){
  if (preg_match('~^(\d{2})/(\d{2})/(\d{4})$~',$f,$m)) return "$m[3]-$m[2]-$m[1]";
  return $f;
}
function fecha_ui($f){
  if (preg_match('~^(\d{4})-(\d{2})-(\d{2})$~',$f,$m)) return "$m[3]/$m[2]/$m[1]";
  return $f;
}

// filtros generales
$filtro_periodo   = $_GET['periodo'] ?? '';
$filtro_grupo     = isset($_GET['grupo']) ? intval($_GET['grupo']) : 0;
$filtro_fecha_ui  = $_GET['fecha'] ?? '';
$filtro_fecha     = $filtro_fecha_ui ? fecha_iso($filtro_fecha_ui) : '';
$se_aplico_filtro = isset($_GET['filtrar']);

// parámetros de detalle (cuando das clic en "Ver fechas")
$detalle_matricula = $_GET['detalle_matricula'] ?? '';
$detalle_grupo     = isset($_GET['detalle_grupo']) ? intval($_GET['detalle_grupo']) : 0;
$detalle_periodo   = $_GET['detalle_periodo'] ?? '';
$hay_detalle       = !empty($detalle_matricula);

// combos (solo se usan en modo normal, no en detalle)
$periodos = $conn->query("SELECT DISTINCT periodo FROM grupos ORDER BY periodo DESC");
$grupos   = $conn->query("SELECT id_grupo, clave_grupo, materia, periodo FROM grupos ORDER BY materia ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Ver asistencias</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">AS</div><div><h1>Asistencias</h1></div>
    </div>
    <div class="nav">
      <a href="index.php">Inicio</a>
      <a href="registrar_asistencia.php" class="button-ghost">Registrar</a>
    </div>
  </div>

  <?php if (!$hay_detalle): ?>
  <!-- ================== MODO NORMAL: filtros + tabla + conteo ================== -->
  <div class="card">
    <form method="get" class="form-grid">
      <div>
        <label>Periodo</label>
        <select name="periodo">
          <option value="">Todos</option>
          <?php while($p = $periodos->fetch_assoc()): ?>
            <option value="<?= h($p['periodo']) ?>" <?= ($filtro_periodo===$p['periodo'])?'selected':'' ?>>
              <?= h($p['periodo']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label>Grupo</label>
        <select name="grupo">
          <option value="0">Todos</option>
          <?php while($g = $grupos->fetch_assoc()): ?>
            <?php if ($filtro_periodo && $g['periodo'] !== $filtro_periodo) continue; ?>
            <option value="<?= $g['id_grupo'] ?>" <?= $filtro_grupo == $g['id_grupo'] ? 'selected' : '' ?>>
              <?= h($g['clave_grupo'].' - '.$g['materia'].' ('.$g['periodo'].')') ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label>Fecha</label>
        <input type="text" name="fecha" value="<?= h($filtro_fecha_ui) ?>" placeholder="dd/mm/aaaa">
      </div>

      <div style="grid-column:span 3; display:flex; justify-content:flex-end; gap:8px">
        <button class="primary" type="submit" name="filtrar" value="1">&#9660; Filtrar</button>
        <!-- Regresa al inicio: sin filtros -->
        <a href="ver_asistencias.php" class="button-ghost">Regresar</a>
      </div>
    </form>
  </div>

  <div class="card" style="margin-top:12px">
    <?php
    /* ----------- TABLA DETALLADA (solo si se aplica filtro) ----------- */
    if ($se_aplico_filtro) {
      $sql = "SELECT s.fecha,
                     a.matricula,
                     a.nombre,
                     a.apellido_paterno,
                     g.id_grupo,
                     g.clave_grupo,
                     g.materia,
                     g.periodo,
                     s.estado
              FROM asistencias s
              JOIN alumnos a ON a.matricula = s.matricula
              JOIN grupos  g ON g.id_grupo = s.id_grupo";
      $where  = [];
      $params = [];
      $types  = "";

      if ($filtro_periodo) {
        $where[]  = "g.periodo = ?";
        $params[] = $filtro_periodo;
        $types   .= "s";
      }
      if ($filtro_grupo) {
        $where[]  = "s.id_grupo = ?";
        $params[] = $filtro_grupo;
        $types   .= "i";
      }
      if ($filtro_fecha) {
        $where[]  = "s.fecha = ?";
        $params[] = $filtro_fecha;
        $types   .= "s";
      }
      if ($where) {
        $sql .= " WHERE ".implode(" AND ", $where);
      }
      $sql .= " ORDER BY s.fecha DESC, g.periodo DESC, g.materia ASC, a.nombre ASC";

      $stmt = $conn->prepare($sql);
      if ($params) { $stmt->bind_param($types, ...$params); }
      $stmt->execute();
      $res = $stmt->get_result();
    ?>
      <table class="table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Matrícula</th>
            <th>Alumno</th>
            <th>Grupo</th>
            <th>Materia</th>
            <th>Periodo</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php while($r = $res->fetch_assoc()): ?>
            <tr>
              <td><?= h(fecha_ui($r['fecha'])) ?></td>
              <td><?= h($r['matricula']) ?></td>
              <td><?= h($r['nombre'].' '.$r['apellido_paterno']) ?></td>
              <td><?= h($r['clave_grupo']) ?></td>
              <td><?= h($r['materia']) ?></td>
              <td><?= h($r['periodo']) ?></td>
              <td><?= h($r['estado']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <hr style="margin:16px 0">
    <?php
    } // fin tabla detallada

    /* ----------- CONTEO DE FALTAS ----------- */
    $sqlC = "SELECT a.matricula,
                    a.nombre,
                    a.apellido_paterno,
                    g.id_grupo,
                    g.clave_grupo,
                    g.materia,
                    g.periodo,
                    SUM(CASE WHEN s.estado='Faltó' THEN 1 ELSE 0 END) AS faltas
             FROM asistencias s
             JOIN alumnos a ON a.matricula = s.matricula
             JOIN grupos  g ON g.id_grupo = s.id_grupo";
    $whereC  = [];
    $paramsC = [];
    $typesC  = "";

    if ($filtro_periodo) {
      $whereC[]  = "g.periodo = ?";
      $paramsC[] = $filtro_periodo;
      $typesC   .= "s";
    }
    if ($filtro_grupo) {
      $whereC[]  = "s.id_grupo = ?";
      $paramsC[] = $filtro_grupo;
      $typesC   .= "i";
    }
    if ($filtro_fecha) {
      $whereC[]  = "s.fecha = ?";
      $paramsC[] = $filtro_fecha;
      $typesC   .= "s";
    }
    if ($whereC) {
      $sqlC .= " WHERE ".implode(" AND ", $whereC);
    }
    $sqlC .= " GROUP BY a.matricula, g.id_grupo, g.periodo
               ORDER BY g.periodo DESC, g.materia ASC, a.nombre ASC";

    $stmtC = $conn->prepare($sqlC);
    if ($paramsC) { $stmtC->bind_param($typesC, ...$paramsC); }
    $stmtC->execute();
    $q = $stmtC->get_result();
    ?>
    <h3>Conteo de faltas por alumno</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Matrícula</th>
          <th>Alumno</th>
          <th>Grupo</th>
          <th>Materia</th>
          <th>Periodo</th>
          <th>Faltas</th>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $q->fetch_assoc()): ?>
          <tr>
            <td><?= h($row['matricula']) ?></td>
            <td><?= h($row['nombre'].' '.$row['apellido_paterno']) ?></td>
            <td><?= h($row['clave_grupo']) ?></td>
            <td><?= h($row['materia']) ?></td>
            <td><?= h($row['periodo']) ?></td>
            <td><?= h($row['faltas']) ?></td>
            <td>
              <a href="ver_asistencias.php?detalle_matricula=<?= urlencode($row['matricula']) ?>
                       &detalle_grupo=<?= intval($row['id_grupo']) ?>
                       &detalle_periodo=<?= urlencode($row['periodo']) ?>#detalle">
                Ver fechas
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <?php else: ?>
  <!-- ================== MODO DETALLE: SOLO FECHAS DE FALTA + REGRESAR ================== -->
  <div class="card" style="margin-top:12px">
    <?php
      $sqlD = "SELECT s.fecha, s.estado,
                      g.clave_grupo, g.materia, g.periodo,
                      a.nombre, a.apellido_paterno
               FROM asistencias s
               JOIN grupos  g ON g.id_grupo = s.id_grupo
               JOIN alumnos a ON a.matricula = s.matricula
               WHERE s.matricula = ?
                 AND s.estado = 'Faltó'";
      $paramsD = [$detalle_matricula];
      $typesD  = "s";

      if ($detalle_grupo) {
        $sqlD .= " AND s.id_grupo = ?";
        $paramsD[] = $detalle_grupo;
        $typesD   .= "i";
      }
      if ($detalle_periodo) {
        $sqlD .= " AND g.periodo = ?";
        $paramsD[] = $detalle_periodo;
        $typesD   .= "s";
      }
      $sqlD .= " ORDER BY s.fecha ASC";

      $stmtD = $conn->prepare($sqlD);
      $stmtD->bind_param($typesD, ...$paramsD);
      $stmtD->execute();
      $det = $stmtD->get_result();

      if ($rowDet = $det->fetch_assoc()) {
        $alumnoNombre = $rowDet['nombre'].' '.$rowDet['apellido_paterno'];
        $materiaDet   = $rowDet['materia'];
        $grupoDet     = $rowDet['clave_grupo'];
        $periodoDet   = $rowDet['periodo'];
        $det->data_seek(0);
    ?>
      <h3 id="detalle">Fechas de falta: <?= h($alumnoNombre) ?> — <?= h($materiaDet) ?> (<?= h($grupoDet) ?>, <?= h($periodoDet) ?>)</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php while($d = $det->fetch_assoc()): ?>
            <tr>
              <td><?= h(fecha_ui($d['fecha'])) ?></td>
              <td><?= h($d['estado']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php
      } else {
        echo "<p>No se encontraron faltas para este alumno.</p>";
      }
    ?>
      <div style="margin-top:10px; display:flex; justify-content:flex-end">
        <a href="ver_asistencias.php" class="button-ghost">Regresar</a>
      </div>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
