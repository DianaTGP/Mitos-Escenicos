<?php
require_once __DIR__ . '/php/init.php';
require_once __DIR__ . '/php/layout.php';

$pdo = mitos_pdo();
$baseUrl = rtrim(mitos_url(''), '/');

$taller_id = (int)($_GET['id'] ?? 0);
if ($taller_id <= 0) {
    header("Location: $baseUrl/talleres.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT t.*, 
    (SELECT COUNT(*) FROM inscripciones_talleres WHERE taller_id = t.id AND estado = 'confirmada') as confirmados 
    FROM talleres t 
    WHERE t.id = ? AND t.activo = 1
");
$stmt->execute([$taller_id]);
$taller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$taller) {
    header("Location: $baseUrl/talleres.php");
    exit;
}

$cuposMax = (int)($taller['cupo_maximo'] ?? 0);
$confirmados = (int)($taller['confirmados'] ?? 0);
$agotado = ($cuposMax > 0 && $confirmados >= $cuposMax);

// Extraer info de sesión si existe
$usuario_id = $_SESSION['usuario_id'] ?? null;
$nombre_predef = '';
$email_predef = '';
if ($usuario_id) {
    $uStmt = $pdo->prepare('SELECT nombre, email FROM usuarios WHERE id = ?');
    $uStmt->execute([$usuario_id]);
    $uData = $uStmt->fetch(PDO::FETCH_ASSOC);
    if ($uData) {
        $nombre_predef = $uData['nombre'];
        $email_predef = $uData['email'];
    }
}

$inscritoExito = false;
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $experiencia = trim($_POST['experiencia'] ?? '');

    if ($agotado) {
        $msgError = "Lamentablemente este taller se ha llenado y ya no puede aceptar más inscripciones.";
    } else if (empty($nombre) || empty($email)) {
        $msgError = "El nombre y el correo son obligatorios.";
    } else {
        // Prevenir doble inscripción temporal (sólo si no está en aprobado para pago)
        $chk = $pdo->prepare('SELECT id, estado FROM inscripciones_talleres WHERE taller_id = ? AND email = ?');
        $chk->execute([$taller_id, $email]);
        $existente = $chk->fetch();

        if ($existente && $existente['estado'] !== 'cancelada') {
            if ($existente['estado'] === 'confirmada') {
                 $msgError = "Ya estás confirmado para este taller. Ingresa a tu perfil.";
            } else if ($existente['estado'] === 'aprobado_para_pago' || !empty($taller['cobro_automatico'])) {
                 // Redirigir al pago si está aprobado o es cobro automático directo
                 $_SESSION['carrito'] = [
                     [
                         'tipo' => 'taller', 
                         'taller_id' => $taller_id,
                         'nombre' => 'Inscripción: ' . $taller['titulo'],
                         'precio_unitario' => $taller['precio'], 
                         'cantidad' => 1
                     ]
                 ];
                 header('Location: ' . $baseUrl . '/pago.php');
                 exit;
            } else {
                 // Está pendiente
                 $msgError = "Ya solicitaste inscripción previa. Por favor, espera a que evaluemos tu postulación.";
            }
        } else {
             try {
                $estadoInicial = !empty($taller['cobro_automatico']) ? 'aprobado_para_pago' : 'pendiente';
                $stmtIns = $pdo->prepare('INSERT INTO inscripciones_talleres (usuario_id, taller_id, nombre_completo, email, telefono, experiencia, estado) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmtIns->execute([$usuario_id ?: null, $taller_id, $nombre, $email, $telefono, $experiencia, $estadoInicial]);
                
                if (!empty($taller['cobro_automatico'])) {
                    // Pasa directamente al carrito
                    $_SESSION['carrito'] = [
                         [
                             'tipo' => 'taller', 
                             'taller_id' => $taller_id,
                             'nombre' => 'Inscripción: ' . $taller['titulo'],
                             'precio_unitario' => $taller['precio'], 
                             'cantidad' => 1
                         ]
                    ];
                    header('Location: ' . $baseUrl . '/pago.php');
                    exit;
                } else {
                    $inscritoExito = true;
                }
             } catch (PDOException $e) {
                 $msgError = "Error al procesar la inscripción. Intenta nuevamente.";
             }
        }
    }
}

$pageTitle = 'Inscripción: ' . $taller['titulo'] . ' | Mitos Escénicos';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700;900&family=Forum&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(mitos_url('css/estilos.css')); ?>"/>
  <style>
      .ins-container {
          max-width: 800px;
          margin: 4rem auto;
          background: rgba(255,255,255,0.03);
          border: 1px solid var(--border-subtle);
          border-radius: 1rem;
          overflow: hidden;
          display: flex;
          flex-direction: column;
      }
      .ins-header {
          padding: 2rem;
          background: linear-gradient(135deg, rgba(149,12,19,0.2) 0%, rgba(227,176,75,0.1) 100%);
          border-bottom: 1px solid var(--border-subtle);
      }
      .ins-body {
          padding: 2.5rem;
      }
      .form-group {
          margin-bottom: 1.5rem;
      }
      .form-group label {
          display: block;
          font-size: 0.85rem;
          color: var(--text-muted);
          margin-bottom: 0.5rem;
      }
      .form-group input, .form-group textarea {
          width: 100%;
          padding: 0.75rem 1rem;
          background: rgba(0,0,0,0.3);
          border: 1px solid var(--border-dark);
          border-radius: 0.5rem;
          color: #fff;
          font-family: inherit;
      }
      .form-group input:focus, .form-group textarea:focus {
          border-color: var(--gold);
          outline: none;
      }
      
      @media (min-width: 768px) {
          .ins-container {
              flex-direction: row;
          }
          .ins-header {
              flex: 1;
              border-bottom: none;
              border-right: 1px solid var(--border-subtle);
          }
          .ins-body {
              flex: 2;
          }
      }
  </style>
</head>
<body>
<?php mitos_header($pageTitle); ?>

<main style="min-height: 80vh; display:flex; align-items:center; justify-content:center; padding: 2rem 1.5rem;">
    
    <div class="ins-container">
        
        <?php if ($inscritoExito): ?>
            <div style="padding: 4rem 2rem; text-align: center; width:100%;">
                <span class="material-symbols-outlined" style="font-size: 5rem; color: var(--gold); margin-bottom: 1rem;">check_circle</span>
                <h2 style="font-family: var(--font-theatrical); font-size: 2.5rem; color: #fff; margin:0 0 1rem;">¡Solicitud Recibida!</h2>
                <p style="color: var(--text-muted); font-size: 1.1rem; max-width:400px; margin:0 auto 2rem; line-height:1.6;">
                    Hemos registrado tu inscripción para <strong><?php echo htmlspecialchars($taller['titulo']); ?></strong>. 
                     Pronto recibirás un correo comunicándote el siguiente paso.
                </p>
                <a href="<?php echo htmlspecialchars(mitos_url('talleres.php')); ?>" class="btn-primary">Volver al catálogo</a>
            </div>
            
        <?php else: ?>
        
        <div class="ins-header">
            <?php if (!empty($taller['imagen_url'])): ?>
                <img src="<?php echo htmlspecialchars($taller['imagen_url']); ?>" alt="Portada" style="width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:0.5rem; margin-bottom:1.5rem;"/>
            <?php endif; ?>
            <span style="display:inline-block; padding:0.25rem 0.75rem; background:var(--primary); color:#fff; font-size:0.75rem; font-weight:700; text-transform:uppercase; border-radius:1rem; margin-bottom:1rem;">
                <?php echo htmlspecialchars($taller['modalidad']); ?>
            </span>
            <h1 style="font-family: var(--font-theatrical); font-size: 2rem; color: #fff; line-height: 1.1; margin:0 0 1rem;">
                <?php echo htmlspecialchars($taller['titulo']); ?>
            </h1>
             <?php if (!empty($taller['instructor'])): ?>
                <p style="font-size:0.9rem; color:var(--text-muted);">Instructor: <strong style="color:#fff;"><?php echo htmlspecialchars($taller['instructor']); ?></strong></p>
             <?php endif; ?>
             
             <div style="margin-top:2rem; padding-top:1.5rem; border-top:1px solid rgba(255,255,255,0.1);">
                 <p style="font-size:0.85rem; color:#aaa; margin:0 0 0.5rem;">Inversión total:</p>
                 <p style="font-size:1.75rem; font-weight:800; color:var(--gold); margin:0;">
                     $<?php echo number_format((float)$taller['precio'], 2); ?>
                 </p>
             </div>
        </div>
        
        <div class="ins-body">
            <h2 style="font-size: 1.5rem; font-weight:700; color: #fff; margin-bottom: 0.5rem;">Completa tu solicitud</h2>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom: 2rem;">Tus datos serán enviados al equipo académico para su evaluación.</p>
            
            <?php if ($msgError): ?>
                <div style="background:rgba(239, 68, 68, 0.1); border:1px solid #ef4444; color:#ef4444; padding:1rem; border-radius:0.5rem; margin-bottom:1.5rem; font-size:0.9rem;">
                    <?php echo $msgError; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label>Nombre Completo *</label>
                    <input type="text" name="nombre_completo" required value="<?php echo htmlspecialchars($_POST['nombre_completo'] ?? $nombre_predef); ?>">
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label>Correo Electrónico *</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? $email_predef); ?>">
                    </div>
                    <div class="form-group">
                        <label>Teléfono Opcional</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Experiencia previa (si aplica)</label>
                    <textarea name="experiencia" rows="4" placeholder="Cuéntanos brevemente sobre ti o por qué deseas tomar el taller..."><?php echo htmlspecialchars($_POST['experiencia'] ?? ''); ?></textarea>
                </div>
                
                <?php if ($agotado): ?>
                    <button type="button" disabled class="btn-secondary" style="width:100%; margin-top:1rem; padding:1rem; font-size:1.1rem; opacity:0.6; cursor:not-allowed; border-color:#ef4444; color:#ef4444;">
                        No hay cupos disponibles
                    </button>
                    <p style="text-align:center; margin-top:1rem; font-size:0.85rem; color:var(--text-muted);">
                        Vuelve pronto o contacta soporte para saber sobre listas de espera.
                    </p>
                <?php else: ?>
                    <button type="submit" class="btn-gold" style="width:100%; margin-top:1rem; padding:1rem; font-size:1.1rem;">
                        <?php echo !empty($taller['cobro_automatico']) ? 'Continuar al Pago' : 'Enviar Solicitud'; ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>
        
        <?php endif; ?>
    </div>
    
</main>

<?php mitos_footer(); ?>
</body>
</html>
