<?php
/**
 * Fragmentos de layout: header y footer.
 */

if (!function_exists('mitos_header')) {
    function mitos_header(string $pageTitle = 'Mitos Escénicos', bool $showCart = true): void {
        $user = mitos_usuario_actual();
        $foto_perfil = null;
        if ($user) {
            try {
                $pdo = mitos_pdo();
                $stmt = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
                $stmt->execute([$user['id']]);
                $foto_perfil = $stmt->fetchColumn();
            } catch (Exception $e) {}
        }

        $currentPath = basename($_SERVER['PHP_SELF'] ?? '');
        $parentPath  = basename(dirname($_SERVER['PHP_SELF'] ?? ''));

        // Cuenta items del carrito
        $cartCount = 0;
        if (!empty($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $item) {
                $cartCount += (int)($item['cantidad'] ?? 1);
            }
        }
        ?>
<header class="site-header">
  <div class="container">
    <a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" class="brand" title="Inicio" style="display:flex; align-items:center; gap:0.25rem; text-decoration:none;">
      <?php if (file_exists(__DIR__ . '/../media/logos/logo-isotipo.png')): ?>
          <img src="<?php echo htmlspecialchars(mitos_url('media/logos/logo-isotipo.png')); ?>" alt="Mitos Escénicos" style="height:52px; width:auto; margin-right:0.1rem;">
          <span style="font-size:1rem; font-weight:600; color:#fff; letter-spacing:0.01em; white-space:nowrap;">Mitos Escénicos</span>
      <?php elseif (file_exists(__DIR__ . '/../media/logos/logo-horizontal.png')): ?>
          <img src="<?php echo htmlspecialchars(mitos_url('media/logos/logo-horizontal.png')); ?>" alt="Mitos Escénicos" style="max-height: 45px; width:auto; border-radius:4px; padding:2px; background:rgba(255,255,255,0.9);">
      <?php else: ?>
          <span class="material-symbols-outlined">theater_comedy</span>
          <h1>Mitos Escénicos</h1>
      <?php endif; ?>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Abrir menú" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <nav id="mainNav">
      <a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>"
         <?php if ($currentPath === 'cartelera.php') echo 'class="active"'; ?>>Cartelera</a>
      <a href="<?php echo htmlspecialchars(mitos_url('sobre-nosotros.php')); ?>"
         <?php if ($currentPath === 'sobre-nosotros.php') echo 'class="active"'; ?>>Nosotros</a>
      <a href="<?php echo htmlspecialchars(mitos_url('talleres.php')); ?>"
         <?php if ($currentPath === 'talleres.php') echo 'class="active"'; ?>>Talleres</a>
      <a href="<?php echo htmlspecialchars(mitos_url('tienda.php')); ?>"
         <?php if ($currentPath === 'tienda.php') echo 'class="active"'; ?>>Tienda</a>
      <?php if ($user): ?>
        <a href="<?php echo htmlspecialchars(mitos_url('perfil.php')); ?>"
           <?php if ($currentPath === 'perfil.php') echo 'class="active"'; ?> style="display:flex; align-items:center; gap:0.4rem;">
           <?php if ($foto_perfil): ?>
               <img src="<?php echo htmlspecialchars(mitos_url($foto_perfil)); ?>" alt="Avatar" style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:2px solid var(--gold); margin-top:-2px; background:rgba(255,255,255,0.1);">
           <?php else: ?>
               <div style="width:28px; height:28px; border-radius:50%; border:1px solid var(--border-dark); background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; margin-top:-2px;">
                   <span class="material-symbols-outlined" style="font-size:1rem; color:var(--text-muted);">person</span>
               </div>
           <?php endif; ?>
           Mi perfil
        </a>
        <?php if ($user['rol'] === 'admin'): ?>
          <a href="<?php echo htmlspecialchars(mitos_url('admin/index.php')); ?>"
             class="nav-admin <?php if ($parentPath === 'admin') echo 'active'; ?>">Administrador</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="<?php echo htmlspecialchars(mitos_url('login.php')); ?>"
           <?php if ($currentPath === 'login.php') echo 'class="active"'; ?>>Iniciar sesión</a>
        <a href="<?php echo htmlspecialchars(mitos_url('registro.php')); ?>"
           <?php if ($currentPath === 'registro.php') echo 'class="active"'; ?>>Registro</a>
      <?php endif; ?>
    </nav>

    <div class="header-actions">
      <?php if ($showCart): ?>
        <a href="<?php echo htmlspecialchars(mitos_url('carrito.php')); ?>"
           class="header-cart-icon" title="Carrito de compras">
          <span class="material-symbols-outlined">shopping_bag</span>
          <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?php echo $cartCount; ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
      <?php if ($user): ?>
        <a href="<?php echo htmlspecialchars(mitos_url('logout.php')); ?>"
           class="header-user-link" title="Cerrar sesión">Salir</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<script>
(function(){
  const btn = document.getElementById('navToggle');
  const nav = document.getElementById('mainNav');
  if(btn && nav) {
    btn.addEventListener('click', function(){
      const open = nav.classList.toggle('open');
      btn.setAttribute('aria-expanded', open);
    });
  }
})();
</script>
        <?php
    }
}

if (!function_exists('mitos_footer')) {
    function mitos_footer(): void {
        ?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <?php if (file_exists(__DIR__ . '/../media/logos/logo-isotipo.png')): ?>
          <div style="display:flex; align-items:center; gap:0.25rem; margin-bottom:1rem;">
            <img src="<?php echo htmlspecialchars(mitos_url('media/logos/logo-isotipo.png')); ?>" alt="Mitos Escénicos" style="height:52px; width:auto;">
            <span style="font-size:1rem; font-weight:600; color:#fff; letter-spacing:0.01em; white-space:nowrap;">Mitos Escénicos</span>
          </div>
        <?php elseif (file_exists(__DIR__ . '/../media/logos/logo-horizontal.png')): ?>
          <div class="brand" style="margin-bottom:1rem; display:inline-flex; align-items:center; background:rgba(255,255,255,0.9); padding:0.5rem; border-radius:0.5rem;">
            <img src="<?php echo htmlspecialchars(mitos_url('media/logos/logo-horizontal.png')); ?>" alt="Mitos Escénicos" style="max-height: 50px; width:auto;">
          </div>
        <?php else: ?>
          <div class="brand" style="margin-bottom:1rem;">
            <span class="material-symbols-outlined">theater_comedy</span>
            <h2>Mitos Escénicos</h2>
          </div>
        <?php endif; ?>
        <p>Dedicados a crear experiencias teatrales únicas que transforman al espectador. Arte, pasión y cultura escénica desde el corazón de la ciudad.</p>
      </div>
      <div class="footer-col">
        <h4>Explorar</h4>
        <ul>
          <li><a href="<?php echo htmlspecialchars(mitos_url('cartelera.php')); ?>">Cartelera</a></li>
          <li><a href="<?php echo htmlspecialchars(mitos_url('talleres.php')); ?>">Talleres</a></li>
          <li><a href="<?php echo htmlspecialchars(mitos_url('tienda.php')); ?>">Tienda</a></li>
          <li><a href="<?php echo htmlspecialchars(mitos_url('sobre-nosotros.php')); ?>">Sobre nosotros</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Mi cuenta</h4>
        <ul>
          <li><a href="<?php echo htmlspecialchars(mitos_url('login.php')); ?>">Iniciar sesión</a></li>
          <li><a href="<?php echo htmlspecialchars(mitos_url('registro.php')); ?>">Registrarse</a></li>
          <li><a href="<?php echo htmlspecialchars(mitos_url('mis-boletos.php')); ?>">Mis boletos</a></li>
          <li><a href="<?php echo htmlspecialchars(mitos_url('mis-compras.php')); ?>">Mis compras</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Legal</h4>
        <ul>
          <li><a href="<?php echo htmlspecialchars(mitos_url('terminos-condiciones-privacidad.php')); ?>">Términos y privacidad</a></li>
          <li><a href="<?php echo htmlspecialchars(mitos_url('terminos-seguridad.php')); ?>">Seguridad</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© <?php echo date('Y'); ?> Mitos Escénicos. Todos los derechos reservados.</p>
      <div class="footer-links">
        <a href="<?php echo htmlspecialchars(mitos_url('terminos-condiciones-privacidad.php')); ?>">Privacidad</a>
        <a href="<?php echo htmlspecialchars(mitos_url('terminos-seguridad.php')); ?>">Seguridad</a>
        <a href="<?php echo htmlspecialchars(mitos_url('sobre-nosotros.php')); ?>">Contacto</a>
      </div>
    </div>
  </div>
</footer>
        <?php
    }
}

if (!function_exists('mitos_admin_sidebar')) {
    /**
     * Renders the admin sidebar. Use mitos_admin_sidebar($currentPage) where
     * $currentPage is one of: 'dashboard', 'obras', 'funciones', 'lugares', 'mercaderia'.
     */
    function mitos_admin_sidebar(string $current = 'dashboard'): void {
        $user = mitos_usuario_actual();
        $links = [
            'dashboard'    => ['url' => 'admin/index.php',        'icon' => 'dashboard',    'label' => 'Dashboard'],
            'obras'        => ['url' => 'admin/obras.php',         'icon' => 'theaters',     'label' => 'Obras'],
            'funciones'    => ['url' => 'admin/funciones.php',     'icon' => 'event',        'label' => 'Funciones'],
            'lugares'      => ['url' => 'admin/lugares.php',       'icon' => 'place',        'label' => 'Lugares'],
            'artistas'     => ['url' => 'admin/artistas.php',      'icon' => 'group',        'label' => 'Artistas'],
            'talleres'     => ['url' => 'admin/talleres.php',      'icon' => 'school',       'label' => 'Talleres'],
            'mercaderia'   => ['url' => 'admin/mercaderia.php',    'icon' => 'inventory_2',  'label' => 'Mercancía'],
            'reportes'     => ['url' => 'admin/reportes.php',      'icon' => 'bar_chart',    'label' => 'Reportes'],
            'transacciones'=> ['url' => 'admin/transacciones.php', 'icon' => 'receipt_long', 'label' => 'Transacciones'],
        ];

        ?>
<aside class="admin-sidebar">
  <div class="admin-sidebar-brand">
    <div class="admin-sidebar-brand-icon">
      <span class="material-symbols-outlined" style="font-size:1.2rem;">theater_comedy</span>
    </div>
    <div>
      <h1>Mitos Escénicos</h1>
      <p>Admin Panel</p>
    </div>
  </div>

  <nav class="admin-sidebar-nav">
    <?php foreach ($links as $key => $link): ?>
      <a href="<?php echo htmlspecialchars(mitos_url($link['url'])); ?>"
         class="admin-sidebar-link <?php echo ($current === $key) ? 'active' : ''; ?>">
        <span class="material-symbols-outlined"><?php echo $link['icon']; ?></span>
        <?php echo $link['label']; ?>
      </a>
    <?php endforeach; ?>
    <hr class="divider" style="margin: 0.75rem 0;">
    <a href="<?php echo htmlspecialchars(mitos_url('index.php')); ?>" class="admin-sidebar-link">
      <span class="material-symbols-outlined">open_in_new</span>
      Ver sitio
    </a>
  </nav>

  <div class="admin-sidebar-footer">
    <div class="admin-sidebar-user">
      <div class="admin-sidebar-avatar"></div>
      <div class="admin-sidebar-user-info">
        <p><?php echo htmlspecialchars($user['nombre'] ?? 'Admin'); ?></p>
        <span><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
      </div>
      <a href="<?php echo htmlspecialchars(mitos_url('logout.php')); ?>"
         title="Cerrar sesión" style="color: var(--text-muted); display:flex; align-items:center;">
        <span class="material-symbols-outlined" style="font-size:1.1rem;">logout</span>
      </a>
    </div>
  </div>
</aside>
        <?php
    }
}
