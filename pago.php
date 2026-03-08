<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

mitos_requiere_login('pago.php');

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    header('Location: ' . mitos_url('carrito.php'));
    exit;
}

$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += (float) $item['precio_unitario'] * (int) $item['cantidad'];
}
$envio = 0;
$tieneMercancia = false;
foreach ($carrito as $item) {
    if (isset($item['tipo']) && $item['tipo'] === 'mercancia') {
        $tieneMercancia = true;
        break;
    }
}
if ($tieneMercancia && $subtotal > 0) {
    $envio = 12.50;
}
$impuestos = $subtotal > 0 ? round($subtotal * 0.075, 2) : 0;
$total = $subtotal + $envio + $impuestos;

$user = mitos_usuario_actual();
$pdo = mitos_pdo();
$tarjetas = [];
if ($user) {
    $stmt = $pdo->prepare('SELECT id, ultimos_4, marca, alias FROM tarjetas_guardadas WHERE usuario_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
    $tarjetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Pago | Mitos Escénicos';
$openpayMerchantId = OPENPAY_MERCHANT_ID;
$openpayPublicKey = OPENPAY_PUBLIC_KEY;
$openpaySandbox = OPENPAY_SANDBOX;
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
  <h1 style="font-size: 1.75rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">Finalizar compra</h1>
  <p style="color: var(--text-muted); margin-bottom: 2rem;">Total a pagar: <strong style="color: var(--primary);">$<?php echo number_format($total, 2); ?></strong></p>

  <form id="form-pago" style="max-width: 600px;">
    <input type="hidden" name="total" value="<?php echo htmlspecialchars((string)$total); ?>"/>
    <input type="hidden" name="device_session_id" id="device_session_id" value=""/>

    <div style="margin-bottom: 1.5rem;">
      <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">Nombre completo (facturación)</label>
      <input type="text" name="nombre_factura" id="nombre_factura" required placeholder="Juan Pérez" value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>"/>
    </div>
    <div style="margin-bottom: 1.5rem;">
      <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">Correo electrónico</label>
      <input type="email" name="email_factura" id="email_factura" required placeholder="juan@ejemplo.com" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"/>
    </div>
    <div style="margin-bottom: 1.5rem;">
      <label style="display: block; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">Dirección (opcional, para envío)</label>
      <input type="text" name="direccion_factura" id="direccion_factura" placeholder="Calle, número, ciudad" value="<?php echo htmlspecialchars($user['direccion'] ?? ''); ?>"/>
    </div>

    <div style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 0.5rem;">
      <p style="font-size: 0.875rem; font-weight: 700; color: #fff; margin-bottom: 0.75rem;">Método de pago</p>
      <?php if (!empty($tarjetas)): ?>
        <div style="margin-bottom: 1rem;">
          <?php foreach ($tarjetas as $t): ?>
            <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; cursor: pointer;">
              <input type="radio" name="metodo_pago" value="guardada_<?php echo (int)$t['id']; ?>" data-card-id="<?php echo (int)$t['id']; ?>"/>
              <span style="color: #fff;"><?php echo htmlspecialchars($t['marca'] ?: 'Tarjeta'); ?> ****<?php echo htmlspecialchars($t['ultimos_4']); ?><?php if (!empty($t['alias'])): ?> (<?php echo htmlspecialchars($t['alias']); ?>)<?php endif; ?></span>
            </label>
          <?php endforeach; ?>
          <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; cursor: pointer;">
            <input type="radio" name="metodo_pago" value="nueva" checked/>
            <span style="color: #fff;">Usar tarjeta nueva</span>
          </label>
        </div>
      <?php else: ?>
        <input type="hidden" name="metodo_pago" value="nueva"/>
      <?php endif; ?>

      <div id="form-tarjeta-nueva" style="<?php echo !empty($tarjetas) ? 'display:none;' : ''; ?> margin-top: 1rem;">
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.5rem;">Datos de la tarjeta (no se almacenan en nuestro servidor; se procesan de forma segura con Openpay).</p>
        <div style="margin-bottom: 0.75rem;">
          <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Número de tarjeta</label>
          <input type="text" id="card_number" data-openpay-card="card_number" placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="off" style="width: 100%;"/>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
          <div>
            <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Vencimiento (MM/AA)</label>
            <input type="text" id="card_expiration_month" data-openpay-card="expiration_month" placeholder="MM" maxlength="2" style="width: 3rem; display: inline-block;"/> / <input type="text" id="card_expiration_year" data-openpay-card="expiration_year" placeholder="AA" maxlength="2" style="width: 3rem; display: inline-block;"/>
          </div>
          <div>
            <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">CVV (no se guarda)</label>
            <input type="password" id="card_cvv2" data-openpay-card="cvv2" placeholder="***" maxlength="4" style="width: 4rem;"/>
          </div>
        </div>
        <div style="margin-top: 0.75rem;">
          <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="guardar_tarjeta" id="guardar_tarjeta" value="1"/>
            <span style="font-size: 0.875rem; color: rgba(255,255,255,0.8);">Guardar tarjeta para próximas compras (no se guarda el CVV)</span>
          </label>
        </div>
      </div>
    </div>

    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;">
      <span class="material-symbols-outlined" style="font-size: 1rem; color: var(--primary);">verified_user</span>
      Pago procesado de forma segura con Openpay.
    </div>

    <button type="submit" id="btn-pagar" class="btn-primary" style="width: 100%; padding: 1rem;">Pagar $<?php echo number_format($total, 2); ?></button>
  </form>
</main>

<?php mitos_footer(); ?>
<?php if (OPENPAY_MERCHANT_ID && OPENPAY_PUBLIC_KEY): ?>
<script src="https://openpay.s3.amazonaws.com/js/openpay.v1.min.js"></script>
<script src="https://openpay.s3.amazonaws.com/js/openpay-data.v1.min.js"></script>
<?php endif; ?>
<script>
(function() {
  var form = document.getElementById('form-pago');
  var btn = document.getElementById('btn-pagar');
  var baseUrl = '<?php echo htmlspecialchars(mitos_url('')); ?>'.replace(/\/$/, '');
  var total = <?php echo json_encode((string)$total); ?>;
  var tarjetasGuardadas = <?php echo json_encode($tarjetas); ?>;
  var usarTarjetaNueva = true;

  document.querySelectorAll('input[name="metodo_pago"]').forEach(function(r) {
    r.addEventListener('change', function() {
      usarTarjetaNueva = this.value === 'nueva';
      document.getElementById('form-tarjeta-nueva').style.display = usarTarjetaNueva ? 'block' : 'none';
    });
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    btn.disabled = true;
    btn.textContent = 'Procesando...';

    if (usarTarjetaNueva && typeof OpenPay !== 'undefined') {
      OpenPay.setId('<?php echo htmlspecialchars(OPENPAY_MERCHANT_ID); ?>');
      OpenPay.setApiKey('<?php echo htmlspecialchars(OPENPAY_PUBLIC_KEY); ?>');
      OpenPay.setSandboxMode(<?php echo OPENPAY_SANDBOX ? 'true' : 'false'; ?>);
      var deviceSessionId = document.getElementById('device_session_id').value || OpenPay.deviceData.setup();
      document.getElementById('device_session_id').value = deviceSessionId;

      OpenPay.token.create({
        'card_number': document.getElementById('card_number').value.replace(/\s/g, ''),
        'holder_name': document.getElementById('nombre_factura').value,
        'expiration_year': '20' + document.getElementById('card_expiration_year').value,
        'expiration_month': document.getElementById('card_expiration_month').value,
        'cvv2': document.getElementById('card_cvv2').value
      }, successToken, errorToken);
    } else {
      var metodo = document.querySelector('input[name="metodo_pago"]:checked');
      var data = new FormData(form);
      data.append('total', total);
      if (metodo && metodo.value.indexOf('guardada_') === 0) {
        data.append('tarjeta_guardada_id', metodo.getAttribute('data-card-id'));
      }
      fetch(baseUrl + '/api/openpay-cargo.php', { method: 'POST', body: data })
        .then(function(r) { return r.json(); })
        .then(function(res) {
          if (res.ok) {
            window.location.href = baseUrl + '/confirmacion.php?orden_id=' + res.orden_id;
          } else {
            alert(res.error || 'Error al procesar el pago');
            btn.disabled = false;
            btn.textContent = 'Pagar $' + total;
          }
        })
        .catch(function() {
          alert('Error de conexión');
          btn.disabled = false;
          btn.textContent = 'Pagar $' + total;
        });
    }
  });

  function successToken(response) {
    var data = new FormData(form);
    data.append('token_id', response.data.id);
    data.append('total', total);
    data.append('device_session_id', document.getElementById('device_session_id').value);
    if (document.getElementById('guardar_tarjeta').checked) {
      data.append('guardar_tarjeta', '1');
    }
    fetch(baseUrl + '/api/openpay-cargo.php', { method: 'POST', body: data })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.ok) {
          window.location.href = baseUrl + '/confirmacion.php?orden_id=' + res.orden_id;
        } else {
          alert(res.error || 'Error al procesar el pago');
          btn.disabled = false;
          btn.textContent = 'Pagar $' + total;
        }
      })
      .catch(function() {
        alert('Error de conexión');
        btn.disabled = false;
        btn.textContent = 'Pagar $' + total;
      });
  }

  function errorToken(response) {
    alert(response.data ? (response.data.description || 'Error con la tarjeta') : 'Error al tokenizar');
    btn.disabled = false;
    btn.textContent = 'Pagar $' + total;
  }

  if (typeof OpenPay !== 'undefined') {
    OpenPay.deviceData.setup('form-pago', 'device_session_id');
  }
})();
</script>
</body>
</html>
