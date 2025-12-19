<?php
require_once('../../interfaces/mostrarAlerta.php');
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar nuevo paciente | Gestión de turnos</title>
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

        /* Contenedor para el password y el ojo */
        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            border: none;
            background: none;
            color: #6c757d;
            display: flex;
            align-items: center;
            padding: 0;
            z-index: 10;
        }

        .toggle-password:hover {
            color: #0d6efd;
        }

        /* Texto informativo de contraseña */
        #password-feedback {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: none; /* Oculto por defecto */
            transition: all 0.3s ease;
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

        .is-invalid {
            border-color: #dc3545 !important;
        }
    </style>
</head>

<body class="login-page">
    
        <form class="card register-card" action="../../Logica/Paciente/registroPaciente.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm(event)">
            <h2 class="card-title text-center mb-4">REGISTRO DE PACIENTE</h2>

            <div id="validation-errors" class="alert alert-danger" style="display: none;"></div>

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre:</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="100" pattern="[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+" title="Solo letras y espacios.">
                <span class="error-message" id="error-nombre"></span>
            </div>

            <div class="mb-3">
                <label for="apellido" class="form-label">Apellido:</label>
                <input type="text" id="apellido" name="apellido" class="form-control" required maxlength="100" pattern="[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+" title="Solo letras y espacios.">
                <span class="error-message" id="error-apellido"></span>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Tipo de documento:</label>
                <div id="tipo_documento_options">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="tipo_documento" id="docDNI" value="DNI" required checked>
                        <label class="form-check-label" for="docDNI">DNI</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="tipo_documento" id="docPasaporte" value="Pasaporte">
                        <label class="form-check-label" for="docPasaporte">Pasaporte</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="tipo_documento" id="docOtro" value="Otro">
                        <label class="form-check-label" for="docOtro">Otro</label>
                    </div>
                </div>
                <span class="error-message" id="error-tipo_documento"></span>
            </div>

            <div class="mb-3">
                <label for="numero_documento" class="form-label">Número de documento (solo números):</label>
                <input type="text" id="numero_documento" name="numero_documento" class="form-control" required maxlength="50" pattern="[0-9]{7,10}" title="Debe contener entre 7 y 10 dígitos.">
                <span class="error-message" id="error-numero_documento"></span>
            </div>

             <div class="mb-3">
                <label for="imagen_dni" class="form-label">Imagen del DNI (frente y dorso):</label>                
                <input type="file" id="imagen_dni" name="imagen_dni[]" class="form-control" accept="image/jpeg, image/png, image/webp" required multiple>
                <span class="error-message" id="error-imagen_dni"></span>
            </div>

            <div class="mb-3">
                <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento (Mín. 18 años):</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" title="Debes ser mayor de 18 años.">
                <span class="error-message" id="error-fecha_nacimiento"></span>
            </div>
            
            <div class="mb-3">
                <label class="form-label d-block">Género:</label>
                <div id="genero_options">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="genero" id="genMasculino" value="Masculino" required>
                        <label class="form-check-label" for="genMasculino">Masculino</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="genero" id="genFemenino" value="Femenino">
                        <label class="form-check-label" for="genFemenino">Femenino</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="genero" id="genOtro" value="Otro">
                        <label class="form-check-label" for="genOtro">Otro</label>
                    </div>
                </div>
                <span class="error-message" id="error-genero"></span>
            </div>
            
            <div class="mb-3">
                <label for="estado_civil" class="form-label">Estado Civil:</label>
                <select id="estado_civil" name="estado_civil" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="Soltero">Soltero/a</option>
                    <option value="Casado">Casado/a</option>
                    <option value="Divorciado">Divorciado/a</option>
                    <option value="Viudo">Viudo/a</option>
                    <option value="Union Civil">Unión Civil</option>
                </select>
                <span class="error-message" id="error-estado_civil"></span>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico:</label>
                <input type="email" id="email" name="email" class="form-control" required maxlength="150">
                <span class="error-message" id="error-email"></span>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña:</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="8" maxlength="255">
                <div id="password-feedback" class="password-feedback">Mínimo 8 caracteres, incluir mayúsculas, minúsculas y números.</div>
                <span class="error-message" id="error-password"></span>
            </div>

            <div class="mb-3">
                <label for="domicilio" class="form-label">Domicilio:</label>
                <input type="text" id="domicilio" name="domicilio" class="form-control" required maxlength="255">
                <span class="error-message" id="error-domicilio"></span>
            </div>

            <div class="mb-3">
                <label for="numero_contacto" class="form-label">Número de contacto (Ej: 1123456789):</label>
                <input type="tel" id="numero_contacto" name="numero_contacto" class="form-control" required maxlength="50" pattern="[0-9+\s()-]{6,}" title="Solo números, +, espacios y guiones. Mínimo 6 caracteres.">
                <span class="error-message" id="error-numero_contacto"></span>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Cobertura de salud:</label>
                <div id="cobertura_options">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="cobertura_salud" id="cobUOM" value="UOM" checked required>
                        <label class="form-check-label" for="cobUOM">UOM</label>
                    </div>                    
                </div>
                <span class="error-message" id="error-cobertura_salud"></span>
            </div>

            <div class="mb-3">
                <label for="numero_afiliado" class="form-label">Número de afiliado (requerido para validación):</label>
                <input type="text" id="numero_afiliado" name="numero_afiliado" class="form-control" required maxlength="30" pattern="[A-Za-z0-9-]+" title="Solo letras, números y guiones.">
                <span class="error-message" id="error-numero_afiliado"></span>
            </div>           
            
            <div class="mb-3 form-check">
                <input type="checkbox" name="terminos" id="terminos" class="form-check-input" required>
                <label class="form-check-label" for="terminos">Acepto los términos y condiciones</label>
                <span class="error-message" id="error-terminos"></span>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-primary">REGISTRARSE</button>
            </div>

            <div class="text-center">
                ¿Ya tenes cuenta? <a href="login.php">Inicia sesión </a> |
                <a href="../../index.php">Volver al inicio</a>
            </div>        
        </form>
    

    <!-- Script de Bootstrap (Opcional, pero recomendado si usa componentes JS) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const passwordInput = document.getElementById('password');
            const passwordFeedback = document.getElementById('password-feedback');
            const dateInput = document.getElementById('fecha_nacimiento');
            const allInputs = form.querySelectorAll('input, select, textarea');
            
            // Set max date for age validation (>= 18 years old)
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            dateInput.max = maxDate.toISOString().split('T')[0];

            // --- Password Strength Feedback ---
            function checkPasswordStrength(password) {
                let strength = 0;
                let message = "Mínimo 8 caracteres, incluir mayúsculas, minúsculas y números.";
                let className = "text-muted"; // Bootstrap default color

                if (password.length >= 8) strength += 1;
                if (/[A-Z]/.test(password)) strength += 1;
                if (/[a-z]/.test(password)) strength += 1;
                if (/[0-9]/.test(password)) strength += 1;
                if (/[^A-Za-z0-9]/.test(password)) strength += 1;

                if (strength >= 4) {
                    message = "Fuerte";
                    className = "text-success";
                } else if (strength >= 2) {
                    message = "Media";
                    className = "text-warning";
                } else if (password.length > 0) {
                     message = "Débil";
                     className = "text-danger";
                }

                passwordFeedback.textContent = message;
                passwordFeedback.className = `password-feedback ${className}`;
            }

            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                validateField(this); 
            });
            checkPasswordStrength(passwordInput.value); 

            // --- Dynamic Validation Logic (onblur) ---

            function showFieldError(input, message) {
                const name = input.name || input.id;
                const errorElement = document.getElementById(`error-${name}`);
                
                if (errorElement) {
                    errorElement.textContent = message;
                }

                // Usamos la clase 'is-invalid' de Bootstrap para marcar el control
                if (input.type !== 'radio' && input.type !== 'checkbox') {
                    input.classList.toggle('is-invalid', !!message);
                }
            }

            function validateField(input) {
                const name = input.name;
                const value = input.value;
                let errorMessage = '';

                // 1. Check native HTML validity
                if (!input.checkValidity()) {
                    if (input.validity.valueMissing) {
                        errorMessage = 'Este campo es obligatorio.';
                    } else if (input.validity.patternMismatch) {
                        errorMessage = `El formato es incorrecto. ${input.title}`;
                    } else if (input.validity.tooShort) {
                        errorMessage = `Mínimo ${input.minLength} caracteres.`;
                    } else if (input.validity.rangeOverflow) {
                        errorMessage = input.title || 'Valor fuera de rango.';
                    } else if (input.validity.typeMismatch && input.type === 'email') {
                         errorMessage = 'Ingrese un correo electrónico válido.';
                    } else {
                        errorMessage = 'El valor ingresado no es válido.';
                    }
                } 
                
                // 2. Specific/Custom Validation Overrides
                else if (name === 'fecha_nacimiento') {
                    const ageLimit = 18;
                    const birthDate = new Date(value);
                    const today = new Date();
                    const minAllowedDate = new Date(today.getFullYear() - ageLimit, today.getMonth(), today.getDate());
                    
                    if (birthDate > minAllowedDate) {
                        errorMessage = `Debes ser mayor de ${ageLimit} años.`;
                    }
                }
                else if (name === 'password') {
                    const isComplex = /[A-Z]/.test(value) && /[a-z]/.test(value) && /[0-9]/.test(value);
                    if (!isComplex && value.length >= 8) {
                        errorMessage = 'Falta incluir mayúsculas, minúsculas o números.';
                    } else if (value.length > 0 && value.length < 8) {
                         errorMessage = 'Mínimo 8 caracteres.';
                    }
                }
                else if (input.type === 'file' && name === 'imagen_dni' && input.files.length > 0) {
                    const file = input.files[0];
                    const maxSize = 2 * 1024 * 1024; // 2MB
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

                    if (file.size > maxSize) {
                        errorMessage = 'La imagen excede el límite de 2MB.';
                    } else if (!allowedTypes.includes(file.type)) {
                        errorMessage = 'Tipo de imagen no permitido (solo JPG, PNG, WEBP).';
                    }
                }
                else if (name === 'terminos' && !input.checked) {
                     errorMessage = 'Debe aceptar los términos y condiciones.';
                }
                
                // 3. Display Result
                showFieldError(input, errorMessage);
                return errorMessage === '';
            }

            // Attach blur listeners to all relevant fields
            allInputs.forEach(input => {
                if (input.type !== 'submit' && input.type !== 'radio' && input.type !== 'checkbox') {
                    input.addEventListener('blur', () => validateField(input));
                }
            });
            
            // Attach change listeners for non-text inputs (select, file, checkbox)
            form.querySelectorAll('select, input[type="file"], input[type="checkbox"]').forEach(input => {
                 input.addEventListener('change', () => validateField(input));
            });

            // Handle radio button validation (on change)
            const radioGroups = ['tipo_documento', 'genero', 'cobertura_salud'];
            radioGroups.forEach(groupName => {
                const inputs = form.querySelectorAll(`input[name="${groupName}"]`);
                const errorElement = document.getElementById(`error-${groupName}`);

                inputs.forEach(input => {
                    input.addEventListener('change', () => {
                        const isChecked = Array.from(inputs).some(radio => radio.checked);
                        if (!isChecked && inputs[0].required) {
                           errorElement.textContent = 'Debe seleccionar una opción.';
                        } else {
                            errorElement.textContent = '';
                        }
                    });
                });
            });

            // Helper function for radio group validation on final submit
            window.validateRadioGroup = function(groupName) {
                const inputs = form.querySelectorAll(`input[name="${groupName}"]`);
                const errorElement = document.getElementById(`error-${groupName}`);
                const isChecked = Array.from(inputs).some(radio => radio.checked);

                if (!isChecked && inputs[0].required) {
                    errorElement.textContent = 'Debe seleccionar una opción.';
                    return false;
                } else {
                    errorElement.textContent = '';
                    return true;
                }
            }
        });

        // --- Final Form Submit Validation ---
        function validateForm(event) {
            let isValid = true;
            let firstInvalidInput = null;
            const form = document.querySelector('form');
            const errorSummary = document.getElementById('validation-errors');
            const allInputs = form.querySelectorAll('input, select, textarea');
            
            errorSummary.style.display = 'none';
            errorSummary.textContent = '';
            
            // 1. Clear previous visual invalid states
            allInputs.forEach(input => {
                if (input.type !== 'radio' && input.type !== 'checkbox') {
                    input.classList.remove('is-invalid');
                }
            });

            // 2. Validate all fields
            allInputs.forEach(input => {
                if (input.type === 'submit' || input.type === 'radio' || input.type === 'checkbox') return; 

                const fieldIsValid = validateField(input);
                if (!fieldIsValid) {
                    isValid = false;
                    if (!firstInvalidInput) {
                        firstInvalidInput = input;
                    }
                }
            });
            
            // 3. Validate Radio Groups (custom logic)
            const radioGroups = ['tipo_documento', 'genero', 'cobertura_salud'];
            radioGroups.forEach(groupName => {
                if (!validateRadioGroup(groupName)) {
                    isValid = false;
                }
            });
            
            // 4. Validate Checkbox (Términos)
            const terminosInput = document.getElementById('terminos');
            if (!terminosInput.checked) {
                showFieldError(terminosInput, 'Debe aceptar los términos y condiciones.');
                isValid = false;
            } else {
                 showFieldError(terminosInput, '');
            }

            if (!isValid) {
                event.preventDefault();
                errorSummary.innerHTML = '<strong>Error de Validación:</strong> Por favor, corrija los campos marcados en rojo antes de continuar.';
                errorSummary.style.display = 'block';
                
                if (firstInvalidInput) {
                    firstInvalidInput.focus();
                } else {
                    errorSummary.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                return false;
            }

            return true;
        }
    </script>
</body>
</html>