<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$pageTitle = 'Términos y condiciones de seguridad | Mitos Escénicos';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&amp;family=Forum&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&amp;display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
</head>
<body style="background-color: var(--background-dark); min-height: 100vh; display: flex; flex-direction: column;">
<?php mitos_header($pageTitle); ?>

<main class="container" style="padding-top: 2rem; padding-bottom: 3rem;">
  <section style="max-width: 800px; margin: 0 auto;">
    <h1 style="font-size: 1.75rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">Términos y condiciones de seguridad</h1>
    <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 2rem;">Última actualización: <?php echo date('d/m/Y'); ?></p>

    <div style="color: rgba(255,255,255,0.85); line-height: 1.75; font-size: 0.9375rem;">
      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">1. Objeto y ámbito</h2>
      <p style="margin-bottom: 1rem;">Los presentes términos y condiciones de seguridad (en adelante, los «Términos de Seguridad») describen de forma expresa las medidas técnicas y organizativas adoptadas por Mitos Escénicos en relación con la protección de los datos personales y la información de los usuarios del Sitio, en particular en lo referente al registro de cuentas, al procesamiento de pagos y al almacenamiento de información sensible. Su finalidad es informar al usuario de manera clara y formal sobre las prácticas de seguridad aplicadas.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">2. Protección de contraseñas</h2>
      <p style="margin-bottom: 1rem;">Las contraseñas de acceso al Sitio no son almacenadas en texto plano. Mitos Escénicos utiliza funciones de hash criptográfico (hashing) de un solo sentido, conforme a estándares ampliamente reconocidos, de modo que la contraseña original no puede ser recuperada a partir de los datos almacenados. La verificación del acceso se realiza mediante comparación segura contra el valor hasheado. El usuario es responsable de mantener la confidencialidad de su contraseña y de no compartirla con terceros.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">3. Seguridad de las consultas a base de datos</h2>
      <p style="margin-bottom: 1rem;">Todas las consultas a la base de datos que involucran datos introducidos por el usuario se realizan mediante consultas preparadas (prepared statements), con el fin de evitar la inyección de código o de consultas no autorizadas. Los parámetros son tratados de forma segura por el sistema, sin concatenación directa en las sentencias.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">4. Sesiones de usuario</h2>
      <p style="margin-bottom: 1rem;">Las sesiones de usuario se gestionan con identificadores de sesión regenerados tras el inicio de sesión, y las cookies de sesión se configuran con las opciones de seguridad recomendadas (por ejemplo, restricciones de acceso vía script cuando corresponda y parámetros de ámbito y duración adecuados). El cierre de sesión invalida el identificador correspondiente.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">5. Procesamiento de pagos con tarjeta</h2>
      <p style="margin-bottom: 1rem;">Los pagos con tarjeta de crédito o débito se procesan a través de la plataforma de pagos Openpay. Los datos sensibles de la tarjeta (número completo de tarjeta y código de seguridad CVV) no son transmitidos ni almacenados en los servidores de Mitos Escénicos. La tokenización de la tarjeta se realiza en el entorno seguro proporcionado por el procesador de pagos; el Sitio únicamente recibe y utiliza un identificador (token) asociado a la transacción para solicitar el cargo, sin tener acceso al número completo ni al CVV. Esta práctica se ajusta a las recomendaciones de seguridad en materia de datos de medios de pago.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">6. Tarjetas guardadas</h2>
      <p style="margin-bottom: 1rem;">Cuando el usuario opta por guardar un medio de pago para futuras compras, el almacenamiento de la referencia de la tarjeta se realiza exclusivamente en el lado del procesador de pagos (Openpay). En los sistemas de Mitos Escénicos solo se almacenan identificadores de referencia (por ejemplo, identificador de tarjeta en el proveedor), los últimos cuatro dígitos de la tarjeta y, en su caso, la marca, con fines de identificación y selección por parte del usuario. En ningún caso se almacena el número completo de la tarjeta ni el código de seguridad (CVV). Las operaciones posteriores con tarjeta guardada se ejecutan mediante dichos identificadores, sin reintroducción del CVV en los flujos que así lo permita el proveedor.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">7. Cifrado y transmisión de datos</h2>
      <p style="margin-bottom: 1rem;">Se recomienda y, en entornos de producción, es obligatorio utilizar el protocolo HTTPS para todas las comunicaciones con el Sitio, de modo que los datos transmitidos entre el navegador del usuario y el servidor estén cifrados. Mitos Escénicos no garantiza la seguridad de los datos transmitidos en conexiones no cifradas.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">8. Limitación de responsabilidad en el ámbito de seguridad</h2>
      <p style="margin-bottom: 1rem;">Mitos Escénicos aplica las medidas técnicas y organizativas descritas en los presentes Términos de Seguridad con la diligencia debida. No obstante, el usuario reconoce que ningún sistema o transmisión por Internet puede garantizar una seguridad absoluta. Mitos Escénicos no será responsable por daños derivados de accesos no autorizados, alteraciones, pérdida o divulgación de datos que se deban a causas ajenas a su control razonable (por ejemplo, fallos de terceros proveedores, actuaciones maliciosas de terceros o uso indebido por parte del usuario de sus credenciales). El usuario se compromete a utilizar el Sitio de buena fe y a notificar cualquier incidencia de seguridad de la que tenga conocimiento.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">9. Modificaciones</h2>
      <p style="margin-bottom: 1rem;">Mitos Escénicos se reserva el derecho de actualizar los presentes Términos de Seguridad cuando se implementen cambios en las medidas descritas. Las modificaciones serán efectivas desde su publicación en el Sitio. Se recomienda al usuario consultar periódicamente esta página.</p>
    </div>

    <p style="margin-top: 2rem;"><a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" style="color: var(--primary);">Volver al inicio</a> · <a href="<?php echo htmlspecialchars(mitos_url('terminos-condiciones-privacidad.php')); ?>" style="color: var(--primary);">Términos y privacidad</a></p>
  </section>
</main>

<?php mitos_footer(); ?>
</body>
</html>
