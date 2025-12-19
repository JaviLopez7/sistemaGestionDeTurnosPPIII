<?php
// Mostrar errores (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$rol_requerido = [1, 3];
require_once '../../../Persistencia/conexionBD.php';
require_once '../../../Logica/General/verificarSesion.php';
require_once '../../../interfaces/mostrarAlerta.php';

$conn = ConexionBD::conectar();
$paciente_id = $_SESSION['id_paciente_token'] ?? null;

// Inicializamos mensaje
$mensaje = "";

// Procesar cancelación de turno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['turno_id'])) {
    $turno_id = (int)$_POST['turno_id'];

    // Verificar que el turno pertenece al paciente y es cancelable
    $stmt = $conn->prepare("
        SELECT es.nombre_estado AS estado
        FROM turnos t
        LEFT JOIN estados es ON t.id_estado = es.id_estado
        WHERE t.id_turno = ? AND t.id_paciente = ?
    ");
    $stmt->bind_param("ii", $turno_id, $paciente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $turno = $result->fetch_assoc();

    $cancelable = $turno && in_array(strtoupper($turno['estado']), ['CONFIRMADO', 'REPROGRAMADO', 'DERIVADO']);

    if ($cancelable) {
        // 1️⃣ Marcar el turno como cancelado
        $stmt = $conn->prepare("UPDATE turnos SET id_estado = 4 WHERE id_turno = ?");
        $stmt->bind_param("i", $turno_id);
        $stmt->execute();

        // 2️⃣ Liberar el horario en la agenda (volverlo disponible)
        $stmt = $conn->prepare("
            UPDATE agenda a
            INNER JOIN turnos t ON
                a.fecha = t.fecha
                AND a.hora_inicio = t.hora
                AND (
                    (a.id_medico IS NOT NULL AND a.id_medico = t.id_medico)
                    OR (a.id_tecnico IS NOT NULL AND a.id_tecnico = t.id_tecnico)
                    OR (a.id_recurso IS NOT NULL AND a.id_recurso = t.id_recurso)
                )
            SET a.disponible = 1
            WHERE t.id_turno = ?
        ");
        $stmt->bind_param("i", $turno_id);
        $stmt->execute();

        $mensaje = "Turno cancelado correctamente y disponibilidad liberada.";
    } else {
        $mensaje = "No se puede cancelar este turno.";
    }
}

// Consultar los turnos del paciente (incluye afiliado si corresponde)
$sql = "
    SELECT
        t.id_turno AS id,
        t.fecha,
        t.hora,
        es.nombre_estado AS estado,

        -- ✅ NUEVO: Beneficiario (titular o afiliado)
        t.id_afiliado,
        CASE
            WHEN t.id_afiliado IS NULL THEN 'Titular'
            ELSE CONCAT(a.nombre, ' ', a.apellido)
        END AS paciente_display,
        CASE
            WHEN t.id_afiliado IS NULL THEN 'titular'
            ELSE 'afiliado'
        END AS paciente_tipo,

        t.id_estudio,
        e.nombre AS nombre_estudio,
        t.id_medico,
        um.nombre AS nombre_medico,
        um.apellido AS apellido_medico,
        r.nombre AS recurso,
        s.nombre AS sede
    FROM turnos t
    LEFT JOIN estudios e   ON t.id_estudio = e.id_estudio
    LEFT JOIN medicos m    ON t.id_medico  = m.id_medico
    LEFT JOIN usuarios um  ON m.id_usuario = um.id_usuario
    LEFT JOIN estados es   ON t.id_estado  = es.id_estado
    LEFT JOIN recursos r   ON r.id_recurso = t.id_recurso
    LEFT JOIN sedes s      ON s.id_sede    = r.id_sede
    LEFT JOIN afiliados a  ON a.id = t.id_afiliado
    WHERE t.id_paciente = ?
    ORDER BY t.fecha DESC, t.hora DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $paciente_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mis turnos | Gestión de turnos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
    <link rel="stylesheet" href="../../../css/misTurnos.css">
    <link rel="stylesheet" href="../../../css/principalPac.css">
</head>
<body>
    <?php include('../navPac.php'); ?>

    <div class="container">
        <h1>Mis Turnos</h1>

        <?php if ($mensaje): ?>
            <p style="background-color: #e6f7e6; color: #4caf50; padding: 10px; border-radius: 5px; border: 1px solid #4caf50; font-weight: bold; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">
                <?= htmlspecialchars($mensaje) ?>
            </p>
        <?php endif; ?>

        <?php if ($result->num_rows === 0): ?>
            <p style="color: #888; font-style: italic;">No tenés turnos registrados.</p>
        <?php else: ?>
            <table border="1" cellpadding="5">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Paciente</th> <!-- ✅ NUEVO -->
                        <th>Estudio / Médico / Recurso</th>
                        <th>Sede</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($turno = $result->fetch_assoc()): ?>
                        <?php $estadoUpper = strtoupper($turno['estado'] ?? ''); ?>
                        <tr>
                            <td><?= htmlspecialchars($turno['fecha']); ?></td>
                            <td><?= htmlspecialchars($turno['hora']); ?></td>

                            <!-- ✅ NUEVO: columna Paciente -->
                            <td>
                                <?php if (($turno['paciente_tipo'] ?? '') === 'titular'): ?>
                                    <span style="font-weight:700;color:#075985;">Titular</span>
                                <?php else: ?>
                                    <span style="font-weight:700;color:#92400e;">
                                        Afiliado: <?= htmlspecialchars($turno['paciente_display'] ?? 'Afiliado'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                if (!empty($turno['id_estudio'])) {
                                    echo "Estudio: " . htmlspecialchars($turno['nombre_estudio'] ?? 'No especificado');
                                } elseif (!empty($turno['id_medico'])) {
                                    $nombreCompleto = trim(($turno['nombre_medico'] ?? '') . ' ' . ($turno['apellido_medico'] ?? ''));
                                    echo "Médico: " . htmlspecialchars($nombreCompleto ?: 'No especificado');
                                } elseif (!empty($turno['recurso'])) {
                                    echo "Recurso: " . htmlspecialchars($turno['recurso']);
                                } else {
                                    echo "Sin asignar";
                                }
                                ?>
                            </td>

                            <td><?= htmlspecialchars($turno['sede'] ?? 'Sin sede'); ?></td>

                            <td style="color:
                                <?php
                                    switch ($estadoUpper) {
                                        case 'CONFIRMADO':   echo '#28a745'; break; // verde
                                        case 'REPROGRAMADO': echo '#fd7e14'; break; // naranja
                                        case 'DERIVADO':     echo '#ffc107'; break; // amarillo
                                        case 'ATENDIDO':     echo '#007bff'; break; // azul
                                        case 'CANCELADO':    echo '#dc3545'; break; // rojo
                                        default:             echo '#000';    break; // negro
                                    }
                                ?>
                            ">
                                <?= ucfirst(htmlspecialchars($turno['estado'] ?? '')); ?>
                            </td>

                            <td>
                                <?php if (in_array($estadoUpper, ['CONFIRMADO','REPROGRAMADO','DERIVADO'])): ?>
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="turno_id" value="<?= (int)$turno['id']; ?>" />
                                        <button type="button" class="btn-cancelar-turno" data-turno-id="<?= (int)$turno['id']; ?>">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    No disponible
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Cargar SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    document.querySelectorAll('.btn-cancelar-turno').forEach(btn => {
        btn.addEventListener('click', function() {
            const turnoId = this.dataset.turnoId;

            Swal.fire({
                title: 'Cancelar turno',
                text: '¿Seguro que querés cancelar este turno?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Si',
                cancelButtonText: 'No',
                confirmButtonColor: '#4caf50',
                cancelButtonColor: '#f44336',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'turno_id';
                    input.value = turnoId;
                    form.appendChild(input);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
</body>
</html>
