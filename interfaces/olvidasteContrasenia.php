<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar contraseña | Gestión de turnos</title>
  <link rel="stylesheet" href="../css/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css"/>
  <!-- Incluir CSS de Bootstrap para los estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
<body class="login-page">
  <form class="login-card" action="../Logica/General/procesarOlvido.php" method="POST">
    <h1>RECUPERAR CONTRASEÑA</h1>

    <div>
      <label for="email">Correo electrónico:</label>
      <input type="email" name="email" id="email" placeholder="Email@example.com" required>
    </div>

    <div>
      <button type="submit">Enviar enlace de recuperación</button>
    </div>

    <div class="login-card-last-child">
      ¿No tenés cuenta? <br><a href="Paciente/registrarPaciente.php">Regístrate </a> |
      <a href="/index.php">Volver al inicio</a>
    </div>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
</body>
</html>