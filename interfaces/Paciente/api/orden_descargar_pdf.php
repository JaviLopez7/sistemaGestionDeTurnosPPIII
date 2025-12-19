<?php
/**
 * ========================================
 * API: Descargar orden como PDF usando FPDF
 * ========================================
 * Ruta: /interfaces/Paciente/api/orden_descargar_pdf.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

/**
 * FPDF no maneja UTF-8.
 * Convertimos a Windows-1252 y eliminamos emojis / caracteres no soportados.
 */
function pdf_strip_emoji($s) {
    if ($s === null) return '';
    $s = (string)$s;
    // Rango amplio de emojis/símbolos comunes
    $s = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $s);
    return $s;
}

function pdf_txt($s) {
    if ($s === null) return '';
    $s = pdf_strip_emoji((string)$s);
    $s = str_replace(["\r\n", "\r"], "\n", $s);

    // Preferible para tildes/ñ en entornos Windows
    $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
    if ($out === false) {
        // fallback
        $out = utf8_decode($s);
    }
    return $out;
}

try {
    // ===== SEGURIDAD Y SESIÓN =====
    $rol_requerido = 1; // Paciente
    $verifPath = dirname(__DIR__, 3) . '/Logica/General/verificarSesion.php';
    if (file_exists($verifPath)) { require_once $verifPath; }

    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    $id_paciente = $_SESSION['id_paciente_token'] ?? null;
    if (!$id_paciente) { die('No autorizado'); }

    // ===== CONEXIÓN BD =====
    $conexionPath = dirname(__DIR__, 3) . '/Persistencia/conexionBD.php';
    require_once $conexionPath;

    $conn = ConexionBD::conectar();
    $conn->set_charset('utf8mb4');

    // ===== PARÁMETROS =====
    $id_orden = (int)($_GET['id_orden'] ?? 0);
    if ($id_orden <= 0) { die('ID de orden inválido'); }

    // ===== OBTENER DATOS =====
    $sql = "
        SELECT
            om.*,
            CONCAT(u.apellido, ', ', u.nombre) AS medico_nombre,
            m.matricula AS medico_matricula,

            CASE
                WHEN om.id_paciente IS NOT NULL THEN CONCAT(up.apellido, ', ', up.nombre)
                WHEN om.id_afiliado IS NOT NULL THEN CONCAT(a.apellido, ', ', a.nombre)
                ELSE 'N/A'
            END AS paciente_nombre,

            CASE
                WHEN om.id_paciente IS NOT NULL THEN p.nro_documento
                WHEN om.id_afiliado IS NOT NULL THEN a.numero_documento
                ELSE NULL
            END AS paciente_dni,

            CASE
                WHEN om.id_paciente IS NOT NULL THEN p.fecha_nacimiento
                WHEN om.id_afiliado IS NOT NULL THEN a.fecha_nacimiento
                ELSE NULL
            END AS fecha_nacimiento,

            CASE
                WHEN om.id_paciente IS NOT NULL THEN p.telefono
                ELSE NULL
            END AS telefono

        FROM ordenes_medicas om
        INNER JOIN medicos m ON m.id_medico = om.id_medico
        LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
        LEFT JOIN pacientes p ON p.id_paciente = om.id_paciente
        LEFT JOIN usuarios up ON up.id_usuario = p.id_usuario
        LEFT JOIN afiliados a ON a.id = om.id_afiliado
        WHERE om.id_orden = ?
          AND (om.id_paciente = ? OR om.id_titular = ?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $id_orden, $id_paciente, $id_paciente);
    $stmt->execute();
    $result = $stmt->get_result();
    $orden = $result->fetch_assoc();
    $stmt->close();

    if (!$orden) { die('Orden no encontrada'); }

    // ===== DECODIFICAR ESTUDIOS =====
    $estudios_array = json_decode($orden['estudios_indicados'], true);

    // ===== CARGAR FPDF =====
    require_once dirname(__DIR__, 3) . '/librerias/fpdf/fpdf.php';

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);

    // ===== HEADER =====
    $pdf->SetTextColor(102,126,234); // azul
    $pdf->Cell(0,10, pdf_txt('ORDEN MÉDICA'), 0, 1, 'C');

    $fecha_emision = date('d/m/Y H:i', strtotime($orden['fecha_emision']));
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8, pdf_txt("Orden N° {$id_orden} | {$fecha_emision}"), 0, 1, 'C');
    $pdf->Ln(5);

    // ===== DATOS DEL PACIENTE Y MÉDICO =====
    $pdf->SetTextColor(0,0,0);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(40,8, pdf_txt('PACIENTE:'), 0, 0);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8, pdf_txt($orden['paciente_nombre']), 0, 1);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(40,8, pdf_txt('DNI:'), 0, 0);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8, pdf_txt($orden['paciente_dni']), 0, 1);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(40,8, pdf_txt('MÉDICO:'), 0, 0);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8, pdf_txt($orden['medico_nombre']), 0, 1);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(40,8, pdf_txt('MATRÍCULA:'), 0, 0);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8, pdf_txt($orden['medico_matricula']), 0, 1);

    $pdf->Ln(5);

    // ===== DIAGNÓSTICO =====
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8, pdf_txt('DIAGNÓSTICO'), 0, 1);
    $pdf->SetFont('Arial','',12);
    $pdf->MultiCell(0,8, pdf_txt($orden['diagnostico']));
    $pdf->Ln(2);

    // ===== ESTUDIOS =====
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8, pdf_txt('ESTUDIOS INDICADOS'), 0, 1);
    $pdf->SetFont('Arial','',12);

    if (is_array($estudios_array)) {
        foreach ($estudios_array as $estudio) {
            $nombreEst = $estudio['nombre'] ?? '';
            $pdf->Cell(5,8, "", 0, 0);
            $pdf->Cell(0,8, pdf_txt("- ".$nombreEst), 0, 1);
        }
    }
    $pdf->Ln(5);

    // ===== OBSERVACIONES =====
    if (!empty($orden['observaciones'])) {
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,8, pdf_txt('OBSERVACIONES'), 0, 1);
        $pdf->SetFont('Arial','',12);
        $pdf->MultiCell(0,8, pdf_txt($orden['observaciones']));
        $pdf->Ln(5);
    }

    // ===== FIRMA =====
    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8, pdf_txt('Firma Digital'), 0, 1, 'C');

    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,6, pdf_txt($orden['medico_nombre']), 0, 1, 'C');
    $pdf->Cell(0,6, pdf_txt("Matrícula: {$orden['medico_matricula']}"), 0, 1, 'C');

    $fecha_firma = date('d/m/Y, h:i a', strtotime($orden['fecha_emision']));
    $pdf->Cell(0,6, pdf_txt("Firmado digitalmente el {$fecha_firma}"), 0, 1, 'C');
    $pdf->Ln(5);

    // ===== FOOTER =====
    $pdf->SetFont('Arial','',8);
    $pdf->SetTextColor(100,100,100);
    $footer = "Sistema de Gestión de Turnos Médicos\n"
            . "Documento generado electrónicamente | {$fecha_emision}\n"
            . "Este documento ha sido firmado digitalmente. La autenticidad puede verificarse en el sistema.";
    $pdf->MultiCell(0,5, pdf_txt($footer), 0, 'C');

    // ===== ENVIAR PDF =====
    $pdf->Output("I", "orden_medica_{$id_orden}.pdf");

} catch (Throwable $e) {
    die('Error al generar PDF: ' . $e->getMessage());
}
?>
