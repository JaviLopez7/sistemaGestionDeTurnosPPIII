<?php
declare(strict_types=1);
session_start();

$isLogged = isset($_SESSION['id_usuario']) || !empty($_SESSION['usuario']);
$rolId    = isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : 0;

$dashboard = './interfaces/Paciente/login.php';
if ($isLogged) {
  switch ($rolId) {
    case 1: $dashboard = './interfaces/Paciente/principalPac.php'; break;
    case 2: $dashboard = './interfaces/Medico/principalMed.php'; break;
    case 3: $dashboard = './interfaces/Administrador/principalAdmi.php'; break;
    case 4: $dashboard = './interfaces/tecnico/panelTecnico.php'; break;
    default: $dashboard = './interfaces/Paciente/principalPac.php'; break;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contacto - Sistema de Gestión de Turnos</title>
  
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* --- VARIABLES Y ESTILOS BASE --- */
    :root {
      --azul-primario: #0aa1dd;
      --azul-hover: #087fb3;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .navbar {
      background: rgba(255, 255, 255, 0.98) !important;
      backdrop-filter: blur(10px);
      transition: var(--transition);
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    /* Centrado de los links en escritorio */
    @media (min-width: 992px) {
      .navbar-nav {
        width: 100%;
        justify-content: center;
        gap: 10px;
      }
    }

    /* --- EFECTO HOVER PARA NAV-LINK --- */
    .nav-link {
      color: var(--azul-primario) !important;
      font-weight: 600;
      padding: 8px 15px !important;
      position: relative;
      transition: var(--transition);
    }

    /* Subrayado animado */
    .nav-link::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: 0;
      left: 50%;
      background-color: var(--azul-hover);
      transition: var(--transition);
      transform: translateX(-50%);
    }

    .nav-link:hover {
      color: var(--azul-hover) !important;
      transform: translateY(-2px);
    }

    .nav-link:hover::after {
      width: 70%; /* Ancho de la línea al hacer hover */
    }

    /* --- BOTÓN DE SESIÓN --- */
    .btn-navbar-login {
      background-color: var(--azul-primario);
      color: white !important;
      border-radius: 8px;
      padding: 10px 25px !important;
      font-weight: bold;
      transition: var(--transition);
      border: none;
      box-shadow: 0 4px 10px rgba(10, 161, 221, 0.2);
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-navbar-login:hover {
      background-color: var(--azul-hover);
      transform: translateY(-3px); /* Movimiento un poco más pronunciado */
      box-shadow: 0 8px 20px rgba(10, 161, 221, 0.3);
      color: white !important;
    }

    /* Ajuste para anclas con navbar sticky */
    .section-anchor { 
      scroll-margin-top: 100px; 
    }

    .navbar-toggler {
      border: none;
      padding: 0;
    }
    .navbar-toggler:focus {
      box-shadow: none;
    }
  </style>

</head>
<body>
   <!-- NAVBAR BOOTSTRAP -->
  <nav class="navbar navbar-expand-lg sticky-top shadow-sm py-2">
    <div class="container">
      <!-- Toggler para móviles -->
      <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Menú -->
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav align-items-center">
          <li class="nav-item">
            <a class="nav-link" href="/index.php#inicio">Inicio</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/index.php#turnos">Turnos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/index.php#quienes-somos">Quiénes somos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/index.php#objetivo">Nuestro objetivo</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/index.php#consultas">Consultas</a>
          </li>
          
          <!-- Botón de Acción -->
          <li class="nav-item ms-lg-4 mt-3 mt-lg-0">
            <?php if ($isLogged): ?>
              <a href="<?php echo htmlspecialchars($dashboard); ?>" class="btn-navbar-login">
                <i class="fa-solid fa-circle-user me-2"></i> Mi cuenta
              </a>
            <?php else: ?>
              <a href="/Paciente/login.php" class="btn-navbar-login">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Iniciar Sesión
              </a>
            <?php endif; ?>
          </li>
        </ul>
      </div>
    </div>
  </nav>




  <!-- SECCIÓN DE CONTACTO -->
  <section class="contact section-anchor" style="margin-top: 80px; min-height: calc(100vh - 200px);">
    <h2>Contactanos</h2>
    <p style="max-width: 600px;">Estamos aquí para ayudarte. Envianos tu consulta y te responderemos a la brevedad.</p>
    
    <form action="enviarConsulta.php" method="POST" class="contact-form">
      <input type="text" name="nombre" placeholder="Tu nombre completo" required>
      
      <input type="email" name="email" placeholder="Tu correo electrónico" required>
      
      <input type="tel" name="telefono" placeholder="Teléfono (opcional)">
      
      <select name="asunto" required>
        <option value="">Seleccioná un tema</option>
        <option value="consulta">Consulta general</option>
        <option value="turno">Problema con turno</option>
        <option value="sugerencia">Sugerencia</option>
        <option value="reclamo">Reclamo</option>
        <option value="otro">Otro</option>
      </select>
      
      <textarea name="mensaje" placeholder="Escribí tu consulta..." required></textarea>
      
      <button type="submit" class="btn">
        <i class="fa-solid fa-paper-plane"></i> Enviar Mensaje
      </button>
    </form>

    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <div class="mensaje-exito">
      <h3>¡Mensaje enviado con éxito!</h3>
      <p>Gracias por contactarnos. Te responderemos pronto.</p>
    </div>
    <?php endif; ?>
  </section>

  <!-- FOOTER REUTILIZABLE -->
  <?php include 'footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>