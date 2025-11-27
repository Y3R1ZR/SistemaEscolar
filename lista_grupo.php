<?php
// lista_grupo.php
// Interfaz principal Lista de Grupo con panel de escala y exportaciones.
// Mantengo el estilo y formato que ya te gustó.

include_once "conexion.php";

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// recibir filtro
$id_grupo = isset($_GET['grupo']) ? intval($_GET['grupo']) : 0;
$aplicar = isset($_GET['filtrar']) ? true : false;

// Obtener lista de periodos y grupos para selects (mismo estilo que otras vistas)
$periodos_q = $conn->query("SELECT DISTINCT periodo FROM grupos ORDER BY periodo DESC");
$grupos_q = $conn->query("SELECT id_grupo, clave_grupo, materia, periodo FROM grupos ORDER BY materia ASC");

// Inicializar valores por defecto
$por_tareas = 0;
$por_asist  = 0;
$por_exams  = 0;

// Si hay un grupo seleccionado, recuperar porcentajes guardados
if ($id_grupo > 0) {

    $sql = "SELECT porcentaje_tareas, porcentaje_asistencia, porcentaje_examen
            FROM configuracion_porcentajes
            WHERE id_grupo = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_grupo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($cfg = $result->fetch_assoc()) {
        // Evitar NULL y forzar enteros
        $por_tareas = isset($cfg['porcentaje_tareas']) ? intval($cfg['porcentaje_tareas']) : 0;
        $por_asist  = isset($cfg['porcentaje_asistencia']) ? intval($cfg['porcentaje_asistencia']) : 0;
        $por_exams  = isset($cfg['porcentaje_examen']) ? intval($cfg['porcentaje_examen']) : 0;
    }

    $stmt->close();
}


// helpers: existe tabla?
function table_exists($conn, $tbl) {
    $t = $conn->real_escape_string($tbl);
    $r = $conn->query("SHOW TABLES LIKE '{$t}'");
    return ($r && $r->num_rows>0);
}

// Si se aplicó filtro y hay grupo, cargamos datos para la tabla
$grupo_info = null;
$alumnos = [];
$total_clases = 0;

if ($aplicar && $id_grupo > 0) {
    // info del grupo
    $st = $conn->prepare("SELECT * FROM grupos WHERE id_grupo = ?");
    $st->bind_param("i",$id_grupo);
    $st->execute();
    $grupo_info = $st->get_result()->fetch_assoc();
    $st->close();

    // total de clases: preferimos tabla 'clases', si no existe contamos fechas distintas en asistencias
    if (table_exists($conn,'clases')) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM clases WHERE id_grupo = ?");
        $stmt->bind_param("i",$id_grupo);
        $stmt->execute();
        $total_clases = intval($stmt->get_result()->fetch_assoc()['total']);
        $stmt->close();
    }
    if ($total_clases === 0) {
        // fallback a fechas distintas en asistencias
        if (table_exists($conn,'asistencias')) {
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT fecha) AS total FROM asistencias WHERE id_grupo = ?");
            $stmt->bind_param("i",$id_grupo);
            $stmt->execute();
            $total_clases = intval($stmt->get_result()->fetch_assoc()['total']);
            $stmt->close();
        } else {
            $total_clases = 0;
        }
    }

    // obtener alumnos del grupo ordenados alfabéticamente por apellidos (como pediste)
    $stmt = $conn->prepare("
        SELECT a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno
        FROM alumno_grupo ag
        JOIN alumnos a ON ag.matricula = a.matricula
        WHERE ag.id_grupo = ?
        ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre ASC
    ");
    $stmt->bind_param("i",$id_grupo);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $alumnos[] = $r;
    }
    $stmt->close();

    // Para rendimiento: pre-calcular faltas, tareas y examenes en batch (si existen tablas)
    $faltas_map = [];
    if (table_exists($conn,'asistencias')) {
        $stmt = $conn->prepare("
            SELECT matricula, SUM(CASE WHEN estado='Faltó' THEN 1 ELSE 0 END) AS faltas
            FROM asistencias
            WHERE id_grupo = ?
            GROUP BY matricula
        ");
        $stmt->bind_param("i",$id_grupo);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $faltas_map[$r['matricula']] = intval($r['faltas']);
        $stmt->close();
    }
    /*
    // tareas: si tabla existe
    $tareas_map = [];
if (table_exists($conn,'tareas')) {

    $stmt_tareas = $conn->prepare("
        SELECT matricula, COUNT(*) AS tareas_entregadas, IFNULL(AVG(calificacion),0) AS prom_tareas
        FROM tareas
        WHERE id_grupo = ?
        GROUP BY matricula
    ");

    $stmt_tareas->bind_param("i", $id_grupo);
    $stmt_tareas->execute();
    $res_tareas = $stmt_tareas->get_result();

    while ($r = $res_tareas->fetch_assoc()) {
        $tareas_map[$r['matricula']] = [
            'entregadas' => intval($r['tareas_entregadas']),
            'prom'       => floatval($r['prom_tareas'])
        ];
    }

    $stmt_tareas->close();
} else {
    // tabla no existe, dejar valores en 0
}

$exams_map = [];
if (table_exists($conn,'examenes')) {

    $stmt_exams = $conn->prepare("
        SELECT matricula, IFNULL(AVG(calificacion),0) AS prom_exams
        FROM examenes
        WHERE id_grupo = ?
        GROUP BY matricula
    ");

    $stmt_exams->bind_param("i", $id_grupo);
    $stmt_exams->execute();
    $res_exams = $stmt_exams->get_result();

    while ($r = $res_exams->fetch_assoc()) {
        $exams_map[$r['matricula']] = floatval($r['prom_exams']);
    }

    $stmt_exams->close();
}
*/

    // obtener prefiales/finales ya guardadas (calificaciones_finales)
    $saved_map = [];
    if (table_exists($conn,'calificaciones_finales')) {
        $stmt = $conn->prepare("SELECT matricula, calificacion_prefinal, calificacion_final FROM calificaciones_finales WHERE id_grupo = ?");
        $stmt->bind_param("i",$id_grupo);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $saved_map[$r['matricula']] = $r;
        $stmt->close();
    }
}

// función truncar 2 decimales sin redondeo
function truncar2($v){
    $f = floor(floatval($v) * 100) / 100;
    return number_format($f, 2, '.', '');
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lista de Grupo - Sistema Profesor</title>
<link rel="stylesheet" href="estilo.css">
<style>
/* Mantengo estilos previos, añado mínimos para iconos */
.export-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;border:1px solid #d1d5db;background:#fff;cursor:pointer}
.icon{width:16px;height:16px;display:inline-block}
.icon.excel{background:#16a34a;border-radius:3px}
.icon.pdf{background:#ef4444;border-radius:3px}
.small-muted{font-size:12px;color:#6b7280}
.right-panel .label{font-weight:700;margin-top:8px}
.tabla-tareas {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-family: Arial, sans-serif;
}

/* Bordes visibles */
.tabla-tareas th, 
.tabla-tareas td {
    border: 1px solid #333;
    padding: 8px 10px;
    text-align: center;
}

/* Encabezado */
.tabla-tareas th {
    background-color: #f0f0f0;
    font-weight: bold;
}

/* Color alternado de filas */
.tabla-tareas tr:nth-child(even) {
    background-color: #fafafa;
}

.tabla-tareas tr:hover {
    background-color: #e5f3ff;
}

/* Opcional: bordes redondeados arriba */
.tabla-tareas th:first-child {
    border-top-left-radius: 5px;
}

.tabla-tareas th:last-child {
    border-top-right-radius: 5px;
}

</style>
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <h2>Filtros</h2>
    <div class="filter">
      <form method="get" id="form-filtros">
        <label for="periodo">Periodo</label>
        <select name="periodo" id="periodo" onchange="this.form.submit()">
          <option value="">-- Todos --</option>
          <?php
          // Rewind and print periodos (from earlier query)
          $periodos_q->data_seek(0);
          while ($p = $periodos_q->fetch_assoc()):
          ?>
            <option value="<?= h($p['periodo']) ?>" <?= (isset($_GET['periodo']) && $_GET['periodo']===$p['periodo'])?'selected':'' ?>><?= h($p['periodo']) ?></option>
          <?php endwhile; ?>
        </select>

        <label for="grupo">Grupo</label>
        <select name="grupo" id="grupo">
          <option value="0">-- Elige grupo --</option>
          <?php
          $grupos_q->data_seek(0);
          while ($g = $grupos_q->fetch_assoc()):
            // si se filtró periodo, mostrar solo grupos de ese periodo
            if (isset($_GET['periodo']) && $_GET['periodo'] !== '' && $g['periodo'] !== $_GET['periodo']) continue;
          ?>
            <option value="<?= intval($g['id_grupo']) ?>" <?= $id_grupo==intval($g['id_grupo']) ? 'selected' : '' ?>>
              <?= h($g['clave_grupo'].' - '.$g['materia'].' ('.$g['periodo'].')') ?>
            </option>
          <?php endwhile; ?>
        </select>

        <div style="margin-top:12px">
          <button type="submit" name="filtrar" value="1" class="btn primary">Cargar lista</button>
          <a href="lista_grupo.php" class="btn">Limpiar</a>
        </div>
      </form>

      <div style="margin-top:16px">
        <button onclick="window.open('export_csv.php?grupo=<?= $id_grupo ?>','_blank')" class="export-btn">
          <span class="icon excel"></span> Exportar Excel
        </button>
        <button onclick="window.open('export_print.php?grupo=<?= $id_grupo ?>','_blank')" class="export-btn">
          <span class="icon pdf"></span> Exportar PDF
        </button>
      </div>
    </div>
  </aside>

  <main class="main">
    <header class="main-header"><h1>Lista general de grupo</h1></header>
    <section class="content">
      <div class="table-wrap">
        <?php if (!$aplicar || $id_grupo==0): ?>
          <p class="small-muted">Selecciona un periodo y un grupo y presiona <strong>Cargar lista</strong>.</p>
        <?php else: ?>
          <!-- Tabla generada por PHP -->
          <table id="tabla">
            <thead>
              <tr>
                <th>#</th>
                <th>Matrícula</th>
                <th>Nombre</th>
                <th>Faltas</th>
                <th>Tareas entregadas</th>
                <th>Prom. Tareas</th>
                <th>Prom. Exámenes</th>
                <th>Prefinal (calc)</th>
                <th>Prefinal (editar)</th>
                <th>Final (editar)</th>
                <th>Guardar</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $i = 1;
                foreach ($alumnos as $al):
                  $mat = $al['matricula'];
                  $faltas = $faltas_map[$mat] ?? 0;
                  $t_ent = ($tareas_map[$mat]['entregadas'] ?? 0);
                  $t_prom = ($tareas_map[$mat]['prom'] ?? 0.0);
                  $e_prom = ($exams_map[$mat] ?? 0.0);

                  // convertir asistencia a % y luego a escala 0-10
                  if ($total_clases > 0) {
                      $asistidas = max(0, $total_clases - $faltas);
                      $por_asistencia = ($asistidas / $total_clases) * 100.0; // 0-100
                  } else {
                      $por_asistencia = 0.0;
                  }
                  $asist_0_10 = ($por_asistencia / 100.0) * 10.0;

                  // Calculo prefinal en escala 0-10: usamos prom_tareas y prom_exams en 0-10 (si no existen quedan 0)
                  // Fórmula: pre = prom_tareas*(pt/100) + asist_0_10*(pa/100) + prom_exams*(pe/100)
                  $pref_calculada = 0.0;
                  if (($por_tareas + $por_assist = $por_asist) || true) {
                      // use configured percentages
                      $pt = $por_tareas;
                      $pa = $por_asist;
                      $pe = $por_exams;
                      // but beware variable name collision: $por_assist not used; use $pa correctly
                      $pref_calculada = ($t_prom * ($pt/100.0)) + ($asist_0_10 * ($pa/100.0)) + ($e_prom * ($pe/100.0));
                  } else {
                      $pref_calculada = 0.0;
                  }
                  $pref_t = truncar2($pref_calculada);

                  $saved_pref = isset($saved_map[$mat]['calificacion_prefinal']) ? number_format(floatval($saved_map[$mat]['calificacion_prefinal']), 2, '.', '') : '';
                  $saved_final = isset($saved_map[$mat]['calificacion_final']) ? number_format(floatval($saved_map[$mat]['calificacion_final']), 2, '.', '') : '';
              ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= h($mat) ?></td>
                  <td><?= h($al['apellido_paterno'].' '.$al['apellido_materno'].' '.$al['nombre']) ?></td>
                  <td style="text-align:center"><?= $faltas ?></td>
                  <td style="text-align:center"><?= $t_ent ?></td>
                  <td style="text-align:center"><?= number_format($t_prom,2,'.','') ?></td>
                  <td style="text-align:center"><?= number_format($e_prom,2,'.','') ?></td>
                  <td style="text-align:center"><?= $pref_t ?></td>
                  <td style="text-align:center">
                    <input type="text" class="inp-pref" data-mat="<?= h($mat) ?>" value="<?= $saved_pref !== '' ? $saved_pref : $pref_t ?>" size="6">
                  </td>
                  <td style="text-align:center">
                    <input type="text" class="inp-final" data-mat="<?= h($mat) ?>" value="<?= $saved_final !== '' ? $saved_final : '' ?>" size="6">
                  </td>
                  <td style="text-align:center">
                    <button class="btn-guardar" data-mat="<?= h($mat) ?>">Guardar</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <aside class="right-panel">
    <h3>Escala de evaluación</h3>
    <div class="scale-box">
      <input type="hidden" id="rp-id-grupo" value="<?= $id_grupo ?>">
      <div class="label">Total de clases</div>
      <input type="text" id="total_clases" readonly value="<?= $total_clases ?>">

      <div class="label">Porcentajes (deben sumar 100)</div>
      <label>% Tareas</label>
      <input type="number" id="por-tareas" min="0" max="100" value="<?= $por_tareas ?>">
      <label>% Asist.</label>
      <input type="number" id="por-asist" min="0" max="100" value="<?= $por_asist ?>">
      <label>% Exámenes</label>
      <input type="number" id="por-exams" min="0" max="100" value="<?= $por_exams ?>">

      <div style="margin-top:8px; display:flex; gap:8px">
        <button id="btn-guardar-porc" class="btn primary" <?= $id_grupo>0 ? '' : 'disabled' ?>>Guardar escala</button>
        <button id="btn-recalcular" class="btn" <?= $id_grupo>0 ? '' : 'disabled' ?>>Recalcular prefinales</button>
      </div>

      <div id="msg-scale" class="small-muted" style="margin-top:8px"></div>
    </div>
  </aside>
</div>

<script>
// JS mínimo para guardar prefinal/final por alumno y guardar escala
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.btn-guardar').forEach(btn=>{
    btn.addEventListener('click', async function(e){
      const mat = this.getAttribute('data-mat');
      const pref = document.querySelector('.inp-pref[data-mat="'+mat+'"]').value.trim();
      const fin  = document.querySelector('.inp-final[data-mat="'+mat+'"]').value.trim();
      const idg = <?= $id_grupo ?>;
      if (!idg) { alert('Grupo no seleccionado'); return; }
      const fd = new URLSearchParams();
      fd.append('matricula', mat);
      fd.append('id_grupo', idg);
      if (pref !== '') fd.append('prefinal', pref);
      if (fin !== '') fd.append('final', fin);
      const res = await fetch('guardar_final.php',{method:'POST',body:fd});
      const j = await res.json();
      if (j.success) {
        this.textContent = 'Guardado';
        setTimeout(()=> this.textContent = 'Guardar', 1200);
      } else {
        alert('Error al guardar');
      }
    });
  });

  // guardar escala
  const btnGuardar = document.getElementById('btn-guardar-porc');
  if (btnGuardar) btnGuardar.addEventListener('click', async ()=>{
    const idg = document.getElementById('rp-id-grupo').value;
    const pt = Number(document.getElementById('por-tareas').value) || 0;
    const pa = Number(document.getElementById('por-asist').value) || 0;
    const pe = Number(document.getElementById('por-exams').value) || 0;
    if ((pt+pa+pe) !== 100) { document.getElementById('msg-scale').textContent = 'Los porcentajes deben sumar 100.'; return; }
    const fd = new URLSearchParams({id_grupo: idg, por_tareas: pt, por_asist: pa, por_exams: pe});
    const res = await fetch('guardar_escala.php',{method:'POST', body: fd});
    const j = await res.json();
    if (j.success) {
      document.getElementById('msg-scale').textContent = j.message || 'Guardado';
      // recargar página para recalcular y mostrar nuevos prefines
      setTimeout(()=> location.reload(), 700);
    } else {
      document.getElementById('msg-scale').textContent = j.error || 'Error';
    }
  });

  // recalcular prefinales (llama al endpoint que ya existe)
  const btnRe = document.getElementById('btn-recalcular');
  if (btnRe) btnRe.addEventListener('click', async ()=>{
    if (!confirm('Recalcular todas las prefinales y guardarlas?')) return;
    const idg = <?= $id_grupo ?>;
    const res = await fetch('calcular_prefinal.php',{method:'POST', body: new URLSearchParams({id_grupo:idg})});
    const j = await res.json();
    if (j.success) {
      alert('Prefinales recalculadas');
      location.reload();
    } else alert('Error al recalcular');
  });

});
</script>
</body>
</html>
