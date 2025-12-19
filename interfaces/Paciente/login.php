<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); // Iniciar sesión

// Incluir el archivo con la función mostrarAlerta
require_once('../../interfaces/mostrarAlerta.php');

// Verificar si hay un mensaje de error en la sesión
if (isset($_SESSION['error_message'])) {
  // Mostrar la alerta con el mensaje de error
  mostrarAlerta('error', $_SESSION['error_message']);
  // Limpiar el mensaje de error después de mostrarlo
  unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar sesión - Gestión de turnos</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <!-- Incluir CSS de Bootstrap para los estilos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <!-- Tu archivo de estilos (para el body, login-page, etc.) -->
  <link rel="stylesheet" href="../../css/style.css" />
  <style>
    /* Estilos específicos para la página de login/registro */
    .login-page {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      /* Asegúrate de que style.css define el fondo si es necesario */
    }

    /* Contenedor principal: Usamos card de Bootstrap, anulando el estilo original */
    .register-card {
      width: 100%;
      max-width: 420px;
      /* Ancho compacto de Bootstrap */
      padding: 2rem;
      border: none;
      /* Dejamos que la clase card maneje el borde */
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
      /* Sombra de Bootstrap */
    }

    /* Ajustes menores de validación dinámica */
    .password-feedback {
      font-size: 0.85rem;
      margin-top: 0.25rem;
    }

    .form-check-label {
      margin-left: 0.5rem;
    }

    /* Forzamos que los mensajes de error tomen el color danger de Bootstrap */
    .error-message {
      color: var(--bs-danger);
      font-size: 0.85rem;
      margin-top: 0.25rem;
    }
  </style>
</head>

<body class="login-page">
  <!-- <div class="card register-card"> -->
    <form class="card login-card" action="../../Logica/General/iniciarSesion.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm(event)">
      <h1>INICIAR SESIÓN</h1>
      <hr class="mb-3">
      <div>
        <label for="email">Correo electrónico:</label>
        <input type="text" id="email" name="email" required>
      </div>

      <div>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
        <a href="../olvidasteContrasenia.php">¿Olvidaste tu contraseña?</a>
      </div>

      <div>
        <button type="submit">INICIAR SESIÓN</button>
      </div>

      <div class="login-card-last-child">
        ¿No tenés cuenta? <br><a href="./registrarPaciente.php">Regístrate </a> |
        <a href="../../index.php">Volver al inicio</a>
      </div>
    </form>
  <!-- </div> -->
</body>

</html>