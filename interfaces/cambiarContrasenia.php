<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$rol_requerido = [1, 2, 3, 4, 5];
require_once('../Logica/General/verificarSesion.php');

$nombre = $_SESSION['nombre'];
$apellido = $_SESSION['apellido'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cambiar contraseña | Gestión de turnos</title>
  <link rel="stylesheet" href="../css/principalPac.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css"/>
<!--  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .container {
      border: 1px solid #000;
      padding: 30px;
      width: 350px;
      text-align: center;
    }

    h2 {
      text-align: center;
      font-size: 20px;
      margin-bottom: 20px;
    }

    input[type="email"] {
      width: 80%;
      padding: 8px;
      margin: 15px 0;
      border: 1px solid #000;
    }

    button {
      padding: 8px 15px;
      border: 1px solid #000;
      background-color: #fff;
      cursor: pointer;
      margin-bottom: 15px;
    }

    a {
      display: block;
      font-size: 14px;
      color: #0000ee;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }
  </style>
-->

</head>
<body>
  <?php
$rol = (int)($_SESSION['rol_id'] ?? 0);

switch ($rol) {
    case 1: // Paciente
        include(__DIR__ . '/Paciente/navPac.php');
        break;

    case 2: // Médico
        include(__DIR__ . '/Medico/navMedico.php');
        break;

    case 3: // Administrador
        include(__DIR__ . '/Administrador/navAdministrador.php');
        break;

    case 4: // Técnico
        include(__DIR__ . '/tecnico/navTecnico.php');
        break;

    case 5: // Administrativo
        include(__DIR__ . '/Administrativo/navAdministrativo.php');
        break;

    default:
        // Sin rol válido → no mostrar menú
        break;
}
?>


  <div class="form-recuperar">
    <h2>Cambiar Contraseña</h2>
    <p>Introduce tu correo electrónico para recibir el enlace de recuperación de contraseña.</p>

    <form action="../Logica/General/procesarOlvido.php" method="POST">
      <div>
        <label for="email">Correo electrónico:</label>
        <input type="email" name="email" id="email" placeholder="usuario@example.com" required>
      </div>

      <div>
        <button type="submit">Enviar enlace</button>
      </div>
    </form>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>

</body>

</html>