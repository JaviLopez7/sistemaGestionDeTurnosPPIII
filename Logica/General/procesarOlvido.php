<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../../Persistencia/conexionBD.php');
require_once('envioNotif.php');

$conn = ConexionBD::conectar();

function salidaSweet(string $mensaje, string $redirect = '../../index.php'): void {
    // HTML mínimo, válido, con body real.
    ?>
    <!doctype html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Recuperación de contraseña</title>
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
      <div id="app" style="font-family:Arial;padding:16px;display:none;">
        <?= htmlspecialchars($mensaje) ?>
      </div>

      <script>
        (function () {
          function go() {
            // Si por algún motivo Swal no está cargado, fallback con alert.
            if (!window.Swal) {
              alert(<?= json_encode($mensaje) ?>);
              window.location.href = <?= json_encode($redirect) ?>;
              return;
            }

            Swal.fire({
              icon: 'info',
              title: 'Revisá tu correo',
              text: <?= json_encode($mensaje) ?>,
              confirmButtonText: 'Aceptar',
              confirmButtonColor: '#3085d6'
            }).then(function () {
              window.location.href = <?= json_encode($redirect) ?>;
            });
          }

          // Ejecutar solo cuando exista body y DOM.
          if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', go);
          } else {
            go();
          }
        })();
      </script>
    </body>
    </html>
    <?php
    exit;
}

$mensajeGenerico = 'Recibirás un enlace para restablecer tu contraseña.';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    salidaSweet($mensajeGenerico);
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
    salidaSweet($mensajeGenerico);
}

try {
    $stmt = $conn->prepare("SELECT id_usuario, nombre, apellido FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id_usuario, $nombre, $apellido);
    $encontrado = $stmt->fetch();
    $stmt->close();

    if ($encontrado) {
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $conn->prepare("
          INSERT INTO recuperacion_password (id_usuario, token, fecha_expiracion)
          VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $id_usuario, $token, $expiracion);
        $stmt->execute();
        $stmt->close();

        // Enviar correo (si falla, no rompe UX)
        try {
            $datosCorreo = [
                'nombre'   => $nombre,
                'apellido' => $apellido,
                'email'    => $email,
                'token'    => $token
            ];
            enviarNotificacion('recupero', $datosCorreo);
        } catch (Throwable $mailErr) {
            // error_log("Error email recupero: " . $mailErr->getMessage());
        }
    }

    salidaSweet($mensajeGenerico);

} catch (Throwable $e) {
    // error_log("Error procesarOlvido: " . $e->getMessage());
    salidaSweet($mensajeGenerico);
}
