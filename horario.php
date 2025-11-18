<?php
// horario.php
include_once("conexion.php");

// filtros
$periodo = $_GET['periodo'] ?? '';
$grupo_sel = $_GET['grupo'] ?? '';

// listas para selects
$carreras = $conn->query("SELECT DISTINCT carrera FROM grupos ORDER BY carrera ASC");
$periodos = $conn->query("SELECT DISTINCT periodo FROM grupos ORDER BY periodo ASC");
$grupos = $conn->query("SELECT id_grupo, clave_grupo, materia, periodo FROM grupos ORDER BY clave_grupo ASC");

// consulta de horarios (filtro dinámico)
$sql = "SELECT h.*, g.clave_grupo, g.materia, g.periodo 
        FROM horario h
        JOIN grupos g ON g.id_grupo = h.id_grupo
        WHERE 1=1";

$params = [];
$types = "";

if ($periodo !== '') {
    $sql .= " AND g.periodo = ?";
    $params[] = $periodo; $types .= "s";
}
if ($grupo_sel !== '') {
    $sql .= " AND g.id_grupo = ?";
    $params[] = $grupo_sel; $types .= "i";
}
$sql .= " ORDER BY FIELD(h.dia,'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'), h.hora_inicio ASC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$horarios = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Horario — Sistema</title>
<style>
  /* Reset / base */
  *{box-sizing:border-box;font-family:"Segoe UI",sans-serif}
  body{background: linear-gradient(180deg,#f6f8ff,#f1f5f9);color:#0f172a;margin:0;padding:0}
  header{background:linear-gradient(90deg,#4f46e5,#3b82f6);color:#fff;padding:14px 22px;display:flex;justify-content:space-between;align-items:center}
  header h1{font-size:1.25rem}
  header nav a{color:#fff;text-decoration:none;margin-left:10px;padding:8px 12px;border-radius:8px;background:rgba(255,255,255,0.08)}
  header nav a:hover{background:#fff;color:#3b82f6;font-weight:600}
  .wrap{max-width:1160px;margin:28px auto;padding:18px}
  .card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 6px 20px rgba(15,23,42,0.06);margin-bottom:18px}
  .filters{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
  label{font-weight:600;color:#233044;font-size:0.95rem}
  select,input[type="time"],input[type="text"]{padding:8px 10px;border-radius:8px;border:1px solid #d1d5db;background:#fff}
  button.primary{background:linear-gradient(90deg,#4f46e5,#3b82f6);color:#fff;border:none;padding:9px 14px;border-radius:8px;cursor:pointer;font-weight:700}
  button.ghost{background:transparent;border:1px solid #cbd5e1;padding:8px 12px;border-radius:8px;cursor:pointer}
  .grid{display:grid;grid-template-columns:1fr 380px;gap:16px}
  /* formulario pequeño de agregar rápido */
  .form-small{display:flex;flex-direction:column;gap:8px}
  .form-row{display:flex;gap:8px}
  .form-row > *{flex:1}
  .table-wrap{overflow:auto;margin-top:10px}
  table{width:100%;border-collapse:collapse;min-width:900px}
  th,td{padding:10px;text-align:left;border-bottom:1px solid #e6eef8}
  thead th{background:linear-gradient(90deg,#eef2ff,#eef6ff);color:#0f172a;font-weight:700}
  tr:hover td{background:linear-gradient(90deg,#fbfdff,#f2f8ff)}
  .actions a{display:inline-block;margin-right:6px;padding:6px 9px;border-radius:8px;text-decoration:none;color:#fff}
  .btn-edit{background:#0ea5e9}
  .btn-delete{background:#ef4444}
  .meta{font-size:0.9rem;color:#475569}
  .small{font-size:0.85rem;color:#64748b}
  /* responsive */
  @media (max-width:980px){.grid{grid-template-columns:1fr;}.table-wrap{overflow:auto}}
</style>
</head>
<body>
<header>
  <h1>Horario — Administración</h1>
  <nav>
    <a href="index.php">Inicio</a>
    <a href="listar_grupos.php">Grupos</a>
    <a href="listar_alumnos.php">Alumnos</a>
    <a href="listar_asistencias.php">Asistencias</a>
  </nav>
</header>

<div class="wrap">
  <div class="card">
    <form method="get" class="filters" style="align-items:center">
      <div>
        <label for="periodo">Periodo</label><br>
        <select id="periodo" name="periodo">
          <option value="">Todos</option>
          <?php while($p = $periodos->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($p['periodo']) ?>" <?= ($periodo === $p['periodo'])? 'selected':'' ?>>
              <?= htmlspecialchars($p['periodo']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label for="grupo">Grupo</label><br>
        <select id="grupo" name="grupo">
          <option value="">Todos</option>
          <?php while($g = $grupos->fetch_assoc()): ?>
            <option value="<?= intval($g['id_grupo']) ?>" <?= ($grupo_sel == $g['id_grupo'])? 'selected':'' ?>>
              <?= htmlspecialchars($g['clave_grupo'] . " — " . $g['materia']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div style="display:flex;align-items:end;gap:8px">
        <button type="submit" class="primary">&#9660; Filtrar</button>
        <a href="horario.php" class="ghost" style="display:inline-block;padding:8px 12px;border-radius:8px">Limpiar</a>
      </div>
    </form>
  </div>

  <div class="grid">
    <!-- izquierda: lista de horarios -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <div>
          <h3 style="margin:0">Horarios</h3>
          <div class="small">Lista de horarios existentes — editar o eliminar según necesites</div>
        </div>
        <div class="meta">Total: <?= count($horarios) ?></div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Dia</th>
              <th>Hora</th>
              <th>Aula</th>
              <th>Grupo</th>
              <th>Materia (Periodo)</th>
              <th style="text-align:center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($horarios)): ?>
              <tr><td colspan="6" style="text-align:center;padding:14px">No hay horarios registrados.</td></tr>
            <?php else: foreach($horarios as $h): ?>
              <tr>
                <td><?= htmlspecialchars($h['dia']) ?></td>
                <td><?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?> — <?= htmlspecialchars(substr($h['hora_fin'],0,5)) ?></td>
                <td><?= htmlspecialchars($h['aula'] ?: '-') ?></td>
                <td><?= htmlspecialchars($h['clave_grupo']) ?></td>
                <td><?= htmlspecialchars($h['materia']) ?> <span class="small">(<?= htmlspecialchars($h['periodo']) ?>)</span></td>
                <td style="text-align:center">
                  <div class="actions">
                    <a class="btn-edit" href="editar_horario.php?id=<?= $h['id_horario'] ?>">✏️ Editar</a>
                    <a class="btn-delete" href="eliminar_horario.php?id=<?= $h['id_horario'] ?>" onclick="return confirm('Eliminar horario?')"> 🗑️ Eliminar</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- derecha: formulario rápido de agregar -->
    <div class="card">
      <h3>➕ Agregar horario</h3>
      <form class="form-small" action="agregar_horario.php" method="post">
        <input type="hidden" name="redirect" value="horario.php">
        <div>
          <label>Grupo</label><br>
          <select name="id_grupo" required>
            <option value="">Seleccione grupo</option>
            <?php
            // recargar lista de grupos para este select
            $gsel = $conn->query("SELECT id_grupo, clave_grupo, materia FROM grupos ORDER BY clave_grupo ASC");
            while($gg = $gsel->fetch_assoc()):
            ?>
              <option value="<?= intval($gg['id_grupo']) ?>"><?= htmlspecialchars($gg['clave_grupo'] . " — " . $gg['materia']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-row">
          <div>
            <label>Día</label><br>
            <select name="dia" required>
              <option value="Lunes">Lunes</option>
              <option value="Martes">Martes</option>
              <option value="Miércoles">Miércoles</option>
              <option value="Jueves">Jueves</option>
              <option value="Viernes">Viernes</option>
              <option value="Sábado">Sábado</option>
            </select>
          </div>
          <div>
            <label>Aula</label><br>
            <input type="text" name="aula" placeholder="Ej. A101">
          </div>
        </div>

        <div class="form-row">
          <div>
            <label>Hora inicio</label><br>
            <input type="time" name="hora_inicio" required>
          </div>
          <div>
            <label>Hora fin</label><br>
            <input type="time" name="hora_fin" required>
          </div>
        </div>

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
          <button type="submit" class="primary"> &#128190; Guardar horario</button>
        </div>
      </form>

      <hr style="margin:14px 0">

      <div class="small">Consejo: usa el filtro de <strong>Periodo</strong> para ver solo horarios de un ciclo específico.</div>
    </div>
  </div>
</div>

</body>
</html>
