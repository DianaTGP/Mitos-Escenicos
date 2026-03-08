<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$pageTitle = 'Términos y condiciones · Aviso de privacidad | Mitos Escénicos';
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
    <h1 style="font-size: 1.75rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">Términos y condiciones de uso y aviso de privacidad</h1>
    <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 2rem;">Última actualización: <?php echo date('d/m/Y'); ?></p>

    <div style="color: rgba(255,255,255,0.85); line-height: 1.75; font-size: 0.9375rem;">
      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">1. Objeto</h2>
      <p style="margin-bottom: 1rem;">Los presentes términos y condiciones (en adelante, los «Términos») regulan el acceso y la utilización del sitio web y servicios de Mitos Escénicos (en adelante, el «Sitio» o la «Plataforma»). El uso del Sitio implica la aceptación íntegra de los Términos. Si el usuario no acepta los Términos, deberá abstenerse de utilizar el Sitio.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">2. Aceptación</h2>
      <p style="margin-bottom: 1rem;">Al registrarse, navegar o realizar cualquier transacción en el Sitio, el usuario manifiesta haber leído, comprendido y aceptado los presentes Términos y el aviso de privacidad que se integra a los mismos. La aceptación se entiende realizada en el momento de la primera utilización del Sitio o del registro de una cuenta.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">3. Datos personales y responsable del tratamiento</h2>
      <p style="margin-bottom: 1rem;">Mitos Escénicos es responsable del tratamiento de los datos personales que el usuario proporcione a través del Sitio (nombre, correo electrónico, teléfono, dirección, datos de facturación y, en su caso, información relacionada con medios de pago conforme a lo dispuesto en los términos de seguridad). Los datos serán tratados con la finalidad de gestionar la cuenta del usuario, procesar compras de boletos y mercancía, enviar confirmaciones y comunicaciones relativas a los servicios, y cumplir con las obligaciones legales aplicables.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">4. Cookies y tecnologías similares</h2>
      <p style="margin-bottom: 1rem;">El Sitio puede utilizar cookies y tecnologías de almacenamiento local con la finalidad de garantizar el correcto funcionamiento de la sesión del usuario, recordar preferencias y mejorar la experiencia de navegación. El usuario puede configurar su navegador para rechazar o limitar el uso de cookies; en tal caso, algunas funcionalidades del Sitio podrían verse afectadas.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">5. Cesión y compartimiento de datos</h2>
      <p style="margin-bottom: 1rem;">Los datos personales no serán cedidos a terceros con fines comerciales sin consentimiento previo del usuario. Podrán ser compartidos con proveedores de servicios necesarios para la operación del Sitio (por ejemplo, procesadores de pago) en la medida y con las garantías que exija la normativa aplicable. En ningún caso se venderán los datos personales.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">6. Derechos de acceso, rectificación, cancelación y oposición (ARCO)</h2>
      <p style="margin-bottom: 1rem;">El usuario puede ejercer sus derechos de acceso, rectificación, cancelación u oposición al tratamiento de sus datos personales, así como revocar su consentimiento cuando este haya sido la base del tratamiento, mediante solicitud dirigida al responsable a través de los medios de contacto publicados en el Sitio. La respuesta se atenderá en los plazos que establezca la legislación vigente.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">7. Contacto</h2>
      <p style="margin-bottom: 1rem;">Para cualquier consulta relacionada con los presentes Términos, el aviso de privacidad o el tratamiento de datos personales, el usuario podrá contactar a Mitos Escénicos a través de la sección de contacto o ayuda disponible en el Sitio.</p>

      <h2 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-top: 1.5rem; margin-bottom: 0.5rem;">8. Modificaciones</h2>
      <p style="margin-bottom: 1rem;">Mitos Escénicos se reserva el derecho de modificar los presentes Términos y el aviso de privacidad en cualquier momento. Las modificaciones serán efectivas desde su publicación en el Sitio. Se recomienda al usuario revisar periódicamente esta página. El uso continuado del Sitio con posterioridad a las modificaciones constituye la aceptación de las mismas.</p>
    </div>

    <p style="margin-top: 2rem;"><a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" style="color: var(--primary);">Volver al inicio</a></p>
  </section>
</main>

<?php mitos_footer(); ?>
</body>
</html>
