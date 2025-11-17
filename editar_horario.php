<?php
include_once("conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) { header("Location: horario.php"); exit; }

    $stmt = $conn->prepare("SELECT h.*, g.clave_grupo, g.materia FROM horario h JOIN grupos g ON g.id_grupo = h.id_grupo WHERE h.id_horario = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $hor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$hor) { die("Horario no encontrado."); }
    // traer lista de grupos para select
    $gsel = $conn->query("SELECT id_grupo, clave_grupo, materia FROM grupos ORDER BY clave_grupo ASC");
    ?>
    <!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Editar horario</title>
    <style>
      body{font-family:"Segoe UI",sans-serif;background:#f3f6ff;padding:30px}
      .card{max-width:720px;margin:auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 6px 20px rgba(2,6,23,0.06)}
      label{font-weight:600}
      input,select{width:100%;padding:8px;border-radius:8px;border:1px solid #d1d5db;margin-bottom:10px}
      button{background:#4f46e5;color:#fff;padding:9px 14px;border:none;border-radius:8px;cursor:pointer}
    </style></head><body>
    <div class="card">
      <h2>Editar horario</h2>
      <form method="post" action="editar_horario.php">
        <input type="hidden" name="id_horario" value="<?= $hor['id_horario'] ?>">
        <label>Grupo</label>
        <select name="id_grupo" required>
          <?php while($gg = $gsel->fetch_assoc()): ?>
            <option value="<?= $gg['id_grupo'] ?>" <?= $gg['id_grupo'] == $hor['id_grupo'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($gg['clave_grupo']." — ".$gg['materia']) ?>
            </option>
          <?php endwhile; ?>
        </select>
        <label>Día</label>
        <select name="dia" required>
          <?php foreach(['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'] as $d): ?>
            <option value="<?= $d ?>" <?= $d == $hor['dia'] ? 'selected' : '' ?>><?= $d ?></option>
          <?php endforeach; ?>
        </select>
        <label>Hora inicio</label>
        <input type="time" name="hora_inicio" value="<?= substr($hor['hora_inicio'],0,5) ?>" required>
        <label>Hora fin</label>
        <input type="time" name="hora_fin" value="<?= substr($hor['hora_fin'],0,5) ?>" required>
        <label>Aula</label>
        <input type="text" name="aula" value="<?= htmlspecialchars($hor['aula']) ?>">
        <div style="text-align:right"><button type="submit">Guardar cambios</button></div>
      </form>
    </div>
    </body></html>
    <?php
    exit;
}

// POST -> procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id_horario'] ?? 0);
    $id_grupo = intval($_POST['id_grupo'] ?? 0);
    $dia = $_POST['dia'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fin = $_POST['hora_fin'] ?? '';
    $aula = trim($_POST['aula'] ?? '');

    if ($id<=0 || $id_grupo<=0 || !$dia || !$hora_inicio || !$hora_fin) {
        die("Faltan datos.");
    }
    if (strtotime($hora_inicio) >= strtotime($hora_fin)) {
        die("Hora inicio debe ser menor que hora fin.");
    }

    $stmt = $conn->prepare("UPDATE horario SET id_grupo=?, dia=?, hora_inicio=?, hora_fin=?, aula=? WHERE id_horario=?");
    $stmt->bind_param("issssi", $id_grupo, $dia, $hora_inicio, $hora_fin, $aula, $id);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: horario.php?msg=updated");
        exit;
    } else {
        echo "Error: ".$conn->error;
    }
}
