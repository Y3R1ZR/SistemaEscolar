<?php
// registrar_asistencia.php
include_once("conexion.php");
$msg = "";

/* ------------------------------
   1) Datos base para los selects
--------------------------------*/
$grupos = $conn->query("
  SELECT id_grupo, clave_grupo, materia
  FROM grupos
  ORDER BY materia ASC
");

/* ------------------------------
   2) Normalizadores / helpers
--------------------------------*/
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function toISO($f){ // dd/mm/yyyy -> yyyy-mm-dd  ó si ya viene yyyy-mm-dd, lo deja
  if (preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', $f, $m)) {
    return "{$m[3]}-{$m[2]}-{$m[1]}";
  }
  return $f;
}
function toUI($f){ // yyyy-mm-dd -> dd/mm/yyyy
  if (preg_match('~^(\d{4})-(\d{2})-(\d{2})$~', $f, $m)) {
    return "{$m[3]}/{$m[2]}/{$m[1]}";
  }
  return $f;
}

/* ------------------------------
   3) Leer selección actual
--------------------------------*/
$sel_id_grupo = 0;
$sel_fecha_iso = date('Y-m-d');           // para SQL
$sel_fecha_ui  = date('d/m/Y');           // para input

// si viene por GET (Cargar lista)
if (isset($_GET['id_grupo'])) {
  $sel_id_grupo = (int)$_GET['id_grupo'];
  $sel_fecha_ui = $_GET['fecha'] ?? $sel_fecha_ui;   // dd/mm/aaaa o yyyy-mm-dd
  $sel_fecha_iso = toISO($sel_fecha_ui);
}

// si viene por POST (Guardar)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['guardar'])) {
  $sel_id_grupo = (int)$_POST['id_grupo'];
  // el input date nativo manda yyyy-mm-dd; si usas el de texto, podría venir dd/mm/yyyy
  $sel_fecha_ui  = $_POST['fecha'];
  $sel_fecha_iso = toISO($sel_fecha_ui);

  // alumnos del grupo
  $qAl = $conn->prepare("
    SELECT a.matricula
    FROM alumno_grupo ag
    JOIN alumnos a ON a.matricula=ag.matricula
    WHERE ag.id_grupo=?
  ");
  $qAl->bind_param("i", $sel_id_grupo);
  $qAl->execute();
  $rsAl = $qAl->get_result();

  // checkboxes del form: present[matricula] => Asistió
  $present = isset($_POST['present']) ? $_POST['present'] : [];

  // Guardado con UPSERT (requiere índice único en asistencias)
  $ins = $conn->prepare("
    INSERT INTO asistencias (matricula, id_grupo, fecha, estado)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE estado = VALUES(estado)
  ");
  while($al = $rsAl->fetch_assoc()){
    $mat = $al['matricula'];
    $estado = isset($present[$mat]) ? 'Asistió' : 'Faltó';
    $ins->bind_param("siss", $mat, $sel_id_grupo, $sel_fecha_iso, $estado);
    $ins->execute();
  }
  $msg = "Asistencias guardadas para la fecha ".h(toUI($sel_fecha_iso)).".";
}

/* ------------------------------
   4) Cargar alumnos + estado para MOSTRAR SIEMPRE la tabla
--------------------------------*/
$lista = [];
if ($sel_id_grupo > 0) {
  $qLista = $conn->prepare("
    SELECT a.matricula,
           CONCAT_WS(' ', a.nombre, a.apellido_paterno) AS alumno,
           COALESCE(asist.estado, 'Faltó') AS estado
    FROM alumno_grupo ag
    JOIN alumnos a ON a.matricula = ag.matricula
    LEFT JOIN asistencias asist
           ON asist.matricula = a.matricula
          AND asist.id_grupo = ag.id_grupo
          AND asist.fecha = ?
    WHERE ag.id_grupo = ?
    ORDER BY a.nombre, a.apellido_paterno
  ");
  $qLista->bind_param("si", $sel_fecha_iso, $sel_id_grupo);
  $qLista->execute();
  $lista = $qLista->get_result();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar Asistencia</title>
  <!-- Tu estilo original -->
  <link rel="stylesheet" href="estilo.css">
  <style>
    /* refuerzos mínimos para la tabla */
    table { width:100%; border-collapse: collapse; margin-top:12px }
    th, td { border:1px solid #e5e7eb; padding:8px }
    th { background:#f8fafc; text-align:left }
    .right { display:flex; justify-content:flex-end; gap:8px }
  </style>
</head>
<body>
<div class="container">

  <!-- Header de tu diseño -->
  <div class="header">
    <div class="brand">
      <div class="logo">AS</div>
      <div><h1>Registrar Asistencia</h1></div>
    </div>
    <div class="nav">
      <a href="index.php"> 🏠 Inicio</a>
      <a href="ver_asistencias.php" class="button-ghost"> 🗓️ Ver asistencias</a>
    </div>
  </div>

  <!-- Filtros (Cargar lista) -->
  <div class="card">
    <?php if($msg): ?><div class="success"><?= $msg ?></div><?php endif; ?>

    <form method="get" class="form-grid">
      <div>
        <label>Grupo</label>
        <select name="id_grupo" required>
          <option value="">Selecciona grupo</option>
          <?php
          // Volvemos a recorrer el resultset de grupos (si ya se leyó arriba, reejecuta la consulta)
          $grupos2 = $conn->query("SELECT id_grupo, clave_grupo, materia FROM grupos ORDER BY materia ASC");
          while($g = $grupos2->fetch_assoc()):
          ?>
            <option value="<?= $g['id_grupo'] ?>" <?= ($sel_id_grupo==$g['id_grupo'])?'selected':'' ?>>
              <?= h($g['clave_grupo'].' - '.$g['materia']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label>Fecha</label>
        <!-- Puedes cambiar a type="date" si prefieres form yyyy-mm-dd -->
        <input type="text" name="fecha" value="<?= h($sel_fecha_ui) ?>" placeholder="dd/mm/aaaa" required>
      </div>

      <div style="grid-column:span 2" class="right">
        <button class="primary" type="submit">Cargar lista</button>
      </div>

      <div style="grid-column:span 2; color:#64748b; font-size:13px">
        Nota: al guardar, los marcados se registran como <b>'Asistió'</b> y los no marcados como <b>'Faltó'</b>.
      </div>
    </form>
  </div>

  <!-- TABLA con checkboxes + Guardar -->
  <?php if ($sel_id_grupo > 0): ?>
  <div class="card" style="margin-top:12px">
    <?php if ($lista && $lista->num_rows>0): ?>
      <form method="post">
        <input type="hidden" name="id_grupo" value="<?= (int)$sel_id_grupo ?>">
        <input type="hidden" name="fecha" value="<?= h($sel_fecha_ui) ?>">

        <table>
          <thead>
            <tr>
              <th style="width:140px">Matrícula</th>
              <th>Alumno</th>
              <th style="width:120px; text-align:center">Asistió</th>
            </tr>
          </thead>
          <tbody>
          <?php while($al = $lista->fetch_assoc()): ?>
            <tr>
              <td><?= h($al['matricula']) ?></td>
              <td><?= h($al['alumno']) ?></td>
              <td style="text-align:center">
                <input type="checkbox"
                       name="present[<?= h($al['matricula']) ?>]"
                       <?= ($al['estado']==='Asistió')?'checked':'' ?>>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>

        <div class="right" style="margin-top:10px">
          <button class="primary" type="submit" name="guardar" value="1">
            Guardar asistencias
          </button>
        </div>
      </form>
    <?php else: ?>
      <p style="margin:0">No hay alumnos asignados a este grupo.</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="card" style="margin-top:12px">
    <h3>Cómo funciona</h3>
    <p>
      1) Selecciona el grupo y la fecha y pulsa <b>Cargar lista</b>.<br>
      2) Marca/desmarca la asistencia y pulsa <b>Guardar asistencias</b>.<br>
      Puedes volver a cargar la misma fecha para editar; el sistema actualiza los registros.
    </p>
  </div>
</div>
</body>
</html>
