<?php
// index.php — panel de inicio
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Sistema Escolar - Panel</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="brand">
        <div class="logo">SE</div>
        <div>
          <h1>Sistema Escolar — Panel</h1>
          <small style="color:#64748b">Alumnos · Grupos · Asistencias</small>
        </div>
      </div>
      <div class="nav">
        <a href="listar_alumnos.php">Alumnos</a>
        <a href="listar_grupos.php">Grupos</a>
        <a href="ver_asistencias.php">Asistencias</a>
        <a href="horario.php">Horarios</a>
      </div>
    </div>

    <div class="grid">
      <div class="card">
        <h2>Bienvenido</h2>
        <p>Desde aquí puedes administrar alumnos, crear/editar grupos, asignar alumnos y registrar asistencias.</p>
        <div style="margin-top:14px">
          <a class="nav" href="listar_alumnos.php"><div class="button-ghost">Ir a Alumnos</div></a>
        </div>
      </div>

      <aside>
        <div class="card stats">
          <div class="stat-item">
            <h3>Guía rápida</h3>
            <p>Agregar / Editar / Eliminar alumnos y grupos. Asignar alumnos a grupos y registrar asistencias por fecha.</p>
          </div>
        </div>
      </aside>
    </div>
  </div>
</body>
</html>
