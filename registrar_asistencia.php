<?php
// registrar_asistencia.php
include_once("conexion.php");
$msg = "";

/* ------------------------------
   1) Datos base para los selects
--------------------------------*/
$periodos_res = $conn->query("SELECT DISTINCT periodo FROM grupos ORDER BY periodo DESC");
$periodos = [];
while ($row = $periodos_res->fetch_assoc()) {
  $periodos[] = $row['periodo'];
}

/* ------------------------------
   2) Helpers de fecha / HTML
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
$sel_periodo   = $_GET['periodo'] ?? ($_POST['periodo'] ?? '');
$sel_id_grupo  = 0;
$sel_fecha_iso = date('Y-m-d');
$sel_fecha_ui  = date('d/m/Y');
$accion        = $_GET['accion'] ?? '';

// si viene desde GET (cargar lista)
if (isset($_GET['id_grupo']) && $_GET['id_grupo'] !== '') {
  $sel_id_grupo = (int)$_GET['id_grupo'];
  if (!empty($_GET['fecha'])) {
    $sel_fecha_ui  = $_GET['fecha'];
    $sel_fecha_iso = toISO($sel_fecha_ui);
  }
}

// si viene desde POST (guardar)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['guardar'])) {
  $sel_periodo   = $_POST['periodo'] ?? '';
  $sel_id_grupo  = (int)$_POST['id_grupo'];
  $sel_fecha_ui  = $_POST['fecha'];
  $sel_fecha_iso = toISO($sel_fecha_ui);

  // alumnos del grupo
  $qAl = $conn->prepare("
    SELECT a.matricula
    FROM alumno_grupo ag
    JOIN alumnos a ON a.matricula = ag.matricula
    WHERE ag.id_grupo = ?
  ");
  $qAl->bind_param("i", $sel_id_grupo);
  $qAl->execute();
  $rsAl = $qAl->get_result();

  $present = $_POST['present'] ?? [];

  // IMPORTANTE: tener UNIQUE(matricula,id_grupo,fecha) en asistencias
  $ins = $conn->prepare("
    INSERT INTO asistencias (matricula, id_grupo, fecha, estado)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE estado = VALUES(estado)
  ");

  while ($al = $rsAl->fetch_assoc()) {
    $mat    = $al['matricula'];
    $estado = isset($present[$mat]) ? 'Asistió' : 'Faltó';
    $ins->bind_param("siss", $mat, $sel_id_grupo, $sel_fecha_iso, $estado);
    $ins->execute();
  }

  $msg = "Asistencias guardadas para la fecha ".h(toUI($sel_fecha_iso)).".";
}

/* ------------------------------
   4) Confirmación si ya existía asistencia
--------------------------------*/
$requiere_confirmacion = false;
$ya_existia_registro   = false;

if ($accion === 'cargar' && $sel_id_grupo > 0 && $sel_fecha_iso) {
  $stmt = $conn->prepare("SELECT COUNT(*) FROM asistencias WHERE id_grupo=? AND fecha=?");
  $stmt->bind_param("is", $sel_id_grupo, $sel_fecha_iso);
  $stmt->execute();
  $stmt->bind_result($cuantos);
  $stmt->fetch();
  $stmt->close();

  if ($cuantos > 0) {
    $ya_existia_registro = true;
    if (($_GET['confirmado'] ?? '') !== 'si') {
      $requiere_confirmacion = true;
    }
  }
}

/* ------------------------------
   5) Cargar lista de alumnos+estado
--------------------------------*/
$mostrar_lista = false;
if ($sel_id_grupo > 0 && !$requiere_confirmacion && ($accion === 'cargar' || isset($_POST['guardar']))) {
  $mostrar_lista = true;
}

$lista = [];
if ($mostrar_lista) {
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

/* ------------------------------
   6) Traer todos los grupos (para filtrar por periodo en el front)
--------------------------------*/
$all_grupos = $conn->query("
  SELECT id_grupo, clave_grupo, materia, periodo
  FROM grupos
  ORDER BY materia ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar Asistencia</title>
  <link rel="stylesheet" href="estilo.css">
  <style>
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{border:1px solid #e5e7eb;padding:8px}
    th{text-align:left;background:#f8fafc}
    .right{display:flex;justify-content:flex-end;gap:8px}
    .warning{background:#fef3c7;border:1px solid #facc15;color:#92400e;padding:8px 12px;border-radius:8px}
    .info{background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;padding:6px 10px;border-radius:8px;margin-bottom:8px}
  </style>
</head>
<body>
<div class="container">

  <!-- Header -->
  <div class="header">
    <div class="brand">
      <div class="logo">AS</div>
      <div><h1>Registrar Asistencia</h1></div>
    </div>
    <div class="nav">
      <a href="index.php">Inicio</a>
      <a href="ver_asistencias.php" class="button-ghost">Ver asistencias</a>
    </div>
  </div>

  <!-- Filtros -->
  <div class="card">
    <?php if($msg): ?><div class="success"><?= $msg ?></div><?php endif; ?>

    <form method="get" class="form-grid">
      <input type="hidden" name="accion" value="cargar">

      <div>
        <label>Periodo</label>
        <select name="periodo" id="periodoSelect">
          <option value="">Selecciona periodo</option>
          <?php foreach($periodos as $p): ?>
            <option value="<?= h($p) ?>" <?= ($sel_periodo===$p)?'selected':'' ?>>
              <?= h($p) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>Grupo / Materia</label>
        <select name="id_grupo" id="grupoSelect" required>
          <option value="">Selecciona grupo</option>
          <?php while($g = $all_grupos->fetch_assoc()): ?>
            <option value="<?= $g['id_grupo'] ?>"
                    data-periodo="<?= h($g['periodo']) ?>">
              <?= h($g['clave_grupo'].' - '.$g['materia'].' ('.$g['periodo'].')') ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label>Fecha</label>
        <input type="text" name="fecha" value="<?= h($sel_fecha_ui) ?>" placeholder="dd/mm/aaaa" required>
      </div>

      <div style="grid-column:span 3" class="right">
        <button class="primary" type="submit">Cargar lista</button>
      </div>

      <div style="grid-column:span 3; color:#64748b; font-size:13px">
        Nota: al guardar, los marcados se registran como <b>'Asistió'</b> y los no marcados como <b>'Faltó'</b>.
      </div>
    </form>
  </div>

  <!-- Confirmación -->
  <?php if ($requiere_confirmacion): ?>
    <div class="card" style="margin-top:12px">
      <div class="warning">
        Ya existe asistencia registrada para este <b>grupo</b> y <b>fecha</b>.<br>
        ¿Estás seguro de que deseas <b>modificarla</b>?
      </div>
      <form method="get" class="right" style="margin-top:10px">
        <input type="hidden" name="accion" value="cargar">
        <input type="hidden" name="periodo" value="<?= h($sel_periodo) ?>">
        <input type="hidden" name="id_grupo" value="<?= (int)$sel_id_grupo ?>">
        <input type="hidden" name="fecha" value="<?= h($sel_fecha_ui) ?>">
        <button class="primary" type="submit" name="confirmado" value="si">Modificar</button>
        <a href="registrar_asistencia.php" class="button-ghost">Cancelar</a>
      </form>
    </div>
  <?php endif; ?>

  <!-- Checklist -->
  <?php if ($mostrar_lista): ?>
  <div class="card" style="margin-top:12px">
    <?php if ($ya_existia_registro): ?>
      <div class="info">Estás modificando una asistencia que ya estaba registrada para este grupo y fecha.</div>
    <?php endif; ?>

    <?php if ($lista && $lista->num_rows>0): ?>
      <form method="post">
        <input type="hidden" name="periodo" value="<?= h($sel_periodo) ?>">
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
      1) Selecciona <b>periodo</b>, <b>grupo/materia</b> y <b>fecha</b> y pulsa <b>Cargar lista</b>.<br>
      2) Si la fecha ya tenía asistencia, se te pedirá confirmación antes de modificarla.<br>
      3) Marca/desmarca la asistencia y pulsa <b>Guardar asistencias</b>.<br>
      Puedes volver a cargar la misma fecha para editar; el sistema actualizará los registros.
    </p>
  </div>
</div>

<!-- ================= JS: filtrar grupos por periodo ================= -->
<script>
(function() {
  const selPeriodo = document.getElementById('periodoSelect');
  const selGrupo   = document.getElementById('grupoSelect');
  if (!selPeriodo || !selGrupo) return;

  const opcionesOriginales = Array.from(selGrupo.options);
  const grupoPHP = <?= (int)$sel_id_grupo ?>;

  function reconstruirGrupos() {
    const per = selPeriodo.value;
    const valorActual = selGrupo.value || (grupoPHP ? String(grupoPHP) : "");

    selGrupo.innerHTML = '';
    // opción por defecto
    const base = opcionesOriginales[0].cloneNode(true);
    selGrupo.appendChild(base);

    opcionesOriginales.slice(1).forEach(opt => {
      const optPeriodo = opt.getAttribute('data-periodo') || '';
      if (!per || optPeriodo === per) {
        selGrupo.appendChild(opt.cloneNode(true));
      }
    });

    if (valorActual) {
      const existe = Array.from(selGrupo.options).some(o => o.value === valorActual);
      if (existe) selGrupo.value = valorActual;
    }
  }

  selPeriodo.addEventListener('change', function() {
    reconstruirGrupos();
    selGrupo.value = ''; // limpia selección al cambiar de periodo
  });

  // aplicar al cargar la página (por si ya venías con periodo/grupo seleccionado)
  reconstruirGrupos();
})();
</script>

</body>
</html>
