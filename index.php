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
        <h2>👋 Bienvenido Docente 👋</h2> <br>
        <p style =" overflow-wrap: break-word; text-align: justify;">Este sistema esta diseñado para apoyar al control de alumnos universitarios de la UNAM, enfocado al público docente de cualquier diciplina; los cuales podran asignar alumnos a sus grupos a impartir, registrar asistencias por fecha, contabilizar faltas, clasificarlos por grupos y muchas funciones más, diviertete y explora todo su pontencial <strong> ¡¡¡ Mucho Éxito 😋 !!!</strong></p>
        <div style="margin-top:16px">
          <a class="nav" href="listar_alumnos.php"><div class="button-ghost">Ir a Alumnos</div></a>
        </div>
      </div>

      <aside>
        <div class="card stats">
          <div class="stat-item">
            <h3>Información Necesaria del Alumno:</h3> <br>
            <p2> Número de cuenta, Nombre completo, Clave del grupo, Periodo escolar , Carrera, Semestre y Materia a impartir</p2>
          </div>
        </div>
      </aside>
    </div>
  </div>
</body>
</html>
