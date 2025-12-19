<?php

require_once('../../Persistencia/conexionBD.php');
require_once('../General/envioNotif.php');
require_once('../../interfaces/mostrarAlerta.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definiciones de ENUM y Constantes de Validación (Deben coincidir con la DB y la vista)
const VALID_TIPOS_DOCUMENTO = ['DNI', 'Pasaporte', 'Otro'];
const VALID_GENEROS = ['Masculino', 'Femenino', 'Otro'];
const VALID_COBERTURAS = ['UOM', 'OSDE', 'Swiss Medical', 'Galeno', 'Otra'];
const VALID_ESTADOS_CIVIL = ['Soltero', 'Casado', 'Divorciado', 'Viudo', 'Union Civil'];


$conn = ConexionBD::conectar();
$conn->set_charset('utf8mb4');

// --- Helpers ---
function clean_input($s){ return htmlspecialchars(trim($s ?? '')); }

function get_post_data()
{
    $data = [];
    $required_fields = [
        'nombre',
        'apellido',
        'genero',
        'tipo_documento',
        'numero_documento',
        'fecha_nacimiento',
        'domicilio',
        'numero_contacto',
        'cobertura_salud',
        'numero_afiliado',
        'email',
        'password',
        'estado_civil',
        'terminos'
    ];

    // 1. Verificar que todos los campos requeridos estén presentes
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            // Para radio/checkbox, si no está en POST es porque no se seleccionó
            $type = $_POST[$field] ?? null;
            if (in_array($field, ['genero', 'tipo_documento', 'cobertura_salud']) && empty($type)) {
                throw new Exception("❌ Falta seleccionar una opción para $field.");
            }
            if ($field === 'terminos' && (!isset($_POST[$field]) || $_POST[$field] !== 'on')) {
                throw new Exception("❌ Debe aceptar los términos y condiciones.");
            }
            if (empty($_POST[$field])) {
                throw new Exception("❌ Falta el campo requerido: " . $field);
            }
        }
    }

    // 2. Limpiar y asignar datos
    $data['nombre']             = clean_input($_POST['nombre']);
    $data['apellido']           = clean_input($_POST['apellido']);
    $data['genero']             = clean_input($_POST['genero']);
    $data['tipo_documento']     = clean_input($_POST['tipo_documento']);
    $data['numero_documento']   = clean_input($_POST['numero_documento']);
    $data['fecha_nacimiento']   = clean_input($_POST['fecha_nacimiento']);
    $data['domicilio']          = clean_input($_POST['domicilio']);
    $data['numero_contacto']    = clean_input($_POST['numero_contacto']);
    $data['cobertura_salud']    = clean_input($_POST['cobertura_salud']);
    $data['numero_afiliado']    = clean_input($_POST['numero_afiliado']);
    $data['email']              = strtolower(clean_input($_POST['email']));
    $data['password']           = $_POST['password'] ?? '';
    $data['estado_civil']       = clean_input($_POST['estado_civil']);
    $data['terminos']           = $_POST['terminos'] ?? 'off';

    return $data;
}

function leer_img_dni_base64_multiple() {
    if (!isset($_FILES['imagen_dni']) || !isset($_FILES['imagen_dni']['tmp_name'])) {
        return null;
    }

    $imagenes = $_FILES['imagen_dni'];

    // 🛡️ CORRECCIÓN DEL ERROR count():
    // Forzamos que sea un arreglo incluso si suben un solo archivo sin [] en el HTML
    $tmp_names = is_array($imagenes['tmp_name']) ? $imagenes['tmp_name'] : [$imagenes['tmp_name']];

    if (count($tmp_names) > 2) {
        throw new Exception("❌ Solo puede cargar hasta 2 imágenes (frente y dorso).");
    }

    $imagenes_b64 = [];

    // Procesar cada imagen usando el arreglo normalizado
    for ($i = 0; $i < count($tmp_names); $i++) {
        if (!is_uploaded_file($tmp_names[$i])) continue;

        $tmp_path = $tmp_names[$i];
        $tipo_mime = mime_content_type($tmp_path);

        if (!in_array($tipo_mime, ['image/jpeg','image/jpg','image/png','image/webp'])) {
            throw new Exception("❌ Uno de los archivos no es una imagen válida (JPG, PNG, WEBP).");
        }

        switch ($tipo_mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $img_resource = imagecreatefromjpeg($tmp_path);
                break;
            case 'image/png':
                $img_resource = imagecreatefrompng($tmp_path);
                break;
            case 'image/webp':
                $img_resource = imagecreatefromwebp($tmp_path);
                break;
            default:
                continue 2; // Saltar si no es soportado
        }

        if (!$img_resource) {
            throw new Exception("❌ Error procesando imagen.");
        }

        // Redimensionar si es muy grande (opcional pero recomendado)
        $ancho_orig = imagesx($img_resource);
        $alto_orig = imagesy($img_resource);
        $max_ancho = 1200;

        if ($ancho_orig > $max_ancho) {
            $ratio = $max_ancho / $ancho_orig;
            $nuevo_ancho = $max_ancho;
            $nuevo_alto = (int) ($alto_orig * $ratio);
            $img_redim = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
            imagecopyresampled($img_redim, $img_resource, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho_orig, $alto_orig);
            imagedestroy($img_resource);
            $img_resource = $img_redim;
        }

        ob_start();
        imagejpeg($img_resource, null, 80); // Comprimir un poco
        $img_data = ob_get_clean();
        imagedestroy($img_resource);

        $imagenes_b64[] = base64_encode($img_data);
    }

    return count($imagenes_b64) > 0 ? json_encode($imagenes_b64) : null;
}

function validar_afiliado(mysqli $conn, $documento, $afiliado) {
    $sql = "SELECT 1 FROM afiliados
            WHERE numero_documento = ? AND numero_afiliado = ? AND estado='activo' LIMIT 1";
    $st = $conn->prepare($sql);
    $st->bind_param("ss", $documento, $afiliado);
    $st->execute(); $st->store_result();
    $ok = $st->num_rows > 0;
    $st->close();
    return $ok;
}

function obtener_afiliados_menores(mysqli $conn, $documento_titular) {
    $sql = "SELECT id FROM afiliados WHERE numero_documento = ? AND tipo_beneficiario = 'titular' LIMIT 1";
    $st = $conn->prepare($sql);
    $st->bind_param("s", $documento_titular);
    $st->execute();
    $result = $st->get_result();
    $row = $result->fetch_assoc();
    $st->close();
    
    if (!$row) return [];
    
    $id_titular_afiliado = $row['id'];
    
    $sql = "SELECT 
                numero_documento,
                nombre,
                apellido,
                fecha_nacimiento,
                tipo_beneficiario
            FROM afiliados
            WHERE id_titular = ? 
            AND tipo_beneficiario = 'hijo menor'
            AND estado = 'activo'
            AND fecha_nacimiento IS NOT NULL";
    
    $st = $conn->prepare($sql);
    $st->bind_param("i", $id_titular_afiliado);
    $st->execute();
    $result = $st->get_result();
    
    $menores = [];
    while ($row = $result->fetch_assoc()) {
        $fecha_nac = new DateTime($row['fecha_nacimiento']);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nac)->y;
        
        if ($edad < 18) {
            $menores[] = $row;
        }
    }
    
    $st->close();
    return $menores;
}

function calcular_tipo_documento_menor($fecha_nacimiento) {
    $fecha_nac = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac)->y;
    
    return 'DNI';
}

/* ================= VALIDACIONES DE LÓGICA DE NEGOCIO ================= */
function aplicar_validaciones_estrictas(array $d)
{
    // 1. Validación de Longitud y Formato
    if (!preg_match('/^[A-Za-zñÑáéíóúÁÉÍÓÚ\s]{1,100}$/', $d['nombre'])) {
        throw new Exception('❌ Nombre inválido (solo letras, máx. 100 caracteres).');
    }
    if (!preg_match('/^[A-Za-zñÑáéíóúÁÉÍÓÚ\s]{1,100}$/', $d['apellido'])) {
        throw new Exception('❌ Apellido inválido (solo letras, máx. 100 caracteres).');
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($d['email']) > 150) {
        throw new Exception('❌ Correo electrónico inválido o excede el máximo (150).');
    }

    // Validación de complejidad de contraseña (mín. 8, una mayúscula, una minúscula, un número)
    if (strlen($d['password']) < 8) {
        throw new Exception('❌ La contraseña debe tener al menos 8 caracteres.');
    }
    if (!preg_match('/[A-Z]/', $d['password']) || !preg_match('/[a-z]/', $d['password']) || !preg_match('/[0-9]/', $d['password'])) {
        throw new Exception('❌ La contraseña debe incluir mayúsculas, minúsculas y números.');
    }

    // Validación de Tipos de Documento
    if (!in_array($d['tipo_documento'], VALID_TIPOS_DOCUMENTO)) {
        throw new Exception('❌ Tipo de documento no permitido.');
    }
    if ($d['tipo_documento'] === 'DNI' && !preg_match('/^[0-9]{7,10}$/', $d['numero_documento'])) {
        throw new Exception('❌ Formato de DNI incorrecto (solo 7-10 dígitos).');
    }
    if (!preg_match('/^[0-9+\s()-]{6,}$/', $d['numero_contacto'])) {
        throw new Exception('❌ Número de contacto inválido.');
    }
    if (strlen($d['domicilio']) > 255) {
        throw new Exception('❌ Domicilio excede el máximo de 255 caracteres.');
    }
    if (!in_array($d['genero'], VALID_GENEROS)) {
        throw new Exception('❌ Género no permitido.');
    }
    if (!in_array($d['cobertura_salud'], VALID_COBERTURAS)) {
        throw new Exception('❌ Cobertura de salud no permitida.');
    }
    if (!in_array($d['estado_civil'], VALID_ESTADOS_CIVIL)) {
        throw new Exception('❌ Estado civil no permitido.');
    }
    if ($d['terminos'] !== 'on') {
        throw new Exception('❌ Debe aceptar los términos y condiciones.');
    }

    // 2. Validación de Edad (Lógica de negocio: Mínimo 18 años)
    $fechaNacimiento = new DateTime($d['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($fechaNacimiento)->y;
    if ($edad < 18 || $edad > 100) {
        throw new Exception('❌ La edad debe ser entre 18 y 100 años.');
    }
}

/* ================= MAIN ================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../interfaces/Paciente/registrarPaciente.php');
    exit;
}

// Verifica si el cuerpo fue demasiado grande (límite PHP)
if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
    $enviado = (int)$_SERVER['CONTENT_LENGTH'];
    exit("❌ El formulario ($enviado bytes) supera el límite de PHP. Aumentá post_max_size/upload_max_filesize o subí una imagen más chica.");
}

try {

    $d = get_post_data();
    aplicar_validaciones_estrictas($d); // Aplica todas las validaciones de formato y lógica

    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('❌ Email inválido.');
    }

    $img_dni_b64 = leer_img_dni_base64_multiple();
    if (!$img_dni_b64) {
        throw new Exception('❌ Debe subir una imagen del DNI.');
    }

    if (!validar_afiliado($conn, $d['numero_documento'], $d['numero_afiliado'])) {
        throw new Exception('❌ No estás registrado como afiliado activo.');
    }

    $afiliados_menores = obtener_afiliados_menores($conn, $d['numero_documento']);

    $conn->begin_transaction();

    $st = $conn->prepare("SELECT 1 FROM usuarios WHERE email=? LIMIT 1");
    $st->bind_param("s", $d['email']);
    $st->execute(); $st->store_result();
    if ($st->num_rows > 0) { $st->close(); throw new Exception('❌ El correo ya está registrado.'); }
    $st->close();

    $st = $conn->prepare("SELECT 1 FROM pacientes WHERE nro_documento=? LIMIT 1");
    $st->bind_param("s", $d['numero_documento']);
    $st->execute(); $st->store_result();
    if ($st->num_rows > 0) { $st->close(); throw new Exception('❌ El número de documento ya está registrado.'); }
    $st->close();

    $token_qr = bin2hex(random_bytes(16));
    $activo = 1;
    $password_hashed = password_hash($d['password'], PASSWORD_DEFAULT);
    
    $st = $conn->prepare("CALL insertar_usuario_paciente(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @id_paciente)");
    $st->bind_param(
        "ssssisssssssss",
        $d['nombre'], 
        $d['apellido'], 
        $d['email'], 
        $password_hashed,
        $activo,
        $d['genero'], 
        $d['tipo_documento'], 
        $d['numero_documento'],
        $d['fecha_nacimiento'], 
        $d['domicilio'], 
        $d['numero_contacto'],
        $d['estado_civil'], 
        $token_qr, 
        $img_dni_b64
    );
    $st->execute();
    $st->close();
    
    $result = $conn->query("SELECT @id_paciente as id_paciente");
    $row = $result->fetch_assoc();
    $id_paciente_titular = $row['id_paciente'];
    
    if (!$id_paciente_titular) {
        throw new Exception('❌ Error al obtener el ID del paciente registrado.');
    }

    $menores_registrados = 0;
    foreach ($afiliados_menores as $menor) {

        $st = $conn->prepare("SELECT 1 FROM pacientes WHERE nro_documento=? LIMIT 1");
        $st->bind_param("s", $menor['numero_documento']);
        $st->execute(); $st->store_result();
        
        if ($st->num_rows > 0) {
            $st->close();
            continue;
        }
        $st->close();
        
        $token_qr_menor = bin2hex(random_bytes(16));
        $tipo_doc_menor = calcular_tipo_documento_menor($menor['fecha_nacimiento']);
        
        $st = $conn->prepare("CALL insertar_paciente_afiliado_menor(?, ?, ?, ?, ?, ?, ?)");
        $st->bind_param(
            "issssss",
            $id_paciente_titular,
            $tipo_doc_menor,
            $menor['numero_documento'],
            $menor['nombre'],
            $menor['apellido'],
            $menor['fecha_nacimiento'],
            $token_qr_menor
        );
        $st->execute();
        $st->close();
        
        $menores_registrados++;
    }

    $conn->commit();

    $datosCorreo = [
        'email'    => $d['email'],
        'nombre'   => $d['nombre'],
        'apellido' => $d['apellido'],
        'menores_registrados' => $menores_registrados
    ];
    enviarNotificacion('registro', $datosCorreo);

    $mensaje = '✅ Registro exitoso.';
    //if ($menores_registrados > 0) {
    //    $mensaje .= " Se registraron $menores_registrados afiliado(s) menor(es) asociado(s).";
    //}

    // Llamada a la función mostrarAlerta
    mostrarAlerta('success', $mensaje);

} catch (Throwable $e) {
    if ($conn->errno) { $conn->rollback(); }
    $msg = $e->getMessage();
    echo "<script>alert('".addslashes($msg)."'); window.history.back();</script>";
} finally {
    $conn->close();
}
