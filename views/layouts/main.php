<!doctype html>
<?php
  // Sesión / rol / ruta activa
  $auth = $_SESSION['user'] ?? null;
  $role = $auth['role'] ?? null;
  $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  $isActive = function (string $p) use ($path) {
      return rtrim($path,'/') === rtrim($p,'/');
  };
?>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= isset($title) ? htmlspecialchars($title) : 'App' ?></title>
  <!-- Forza cache bust si cambiaste tu CSS -->
  <link rel="stylesheet" href="/assets/styles.css?v=palette-teal-ff0063" />
</head>

<body class="<?= $auth ? 'with-sidebar' : 'no-auth' ?>">
<?php if ($auth): ?>
  <!-- ===== SIDEBAR (solo si hay sesión) ===== -->
  <aside class="sidebar" data-collapsed="false" aria-label="Barra lateral de navegación">
    <div class="sb-header">
      <button class="sb-toggle" id="sbToggle" aria-label="Colapsar menú" title="Colapsar menú">☰</button>
      <a class="brand" href="/dashboard">Scaffold</a>
    </div>

    <div class="sb-user" role="region" aria-label="Usuario actual">
      <div class="sb-user-name"><?= htmlspecialchars($auth['name'] ?? '') ?></div>
      <div class="sb-user-role sb-user-role badge"><?= htmlspecialchars($role ?? '-') ?></div>
    </div>

    <nav class="sb-nav" role="navigation" aria-label="Menú principal">
      <a href="/dashboard" class="sb-link <?= $isActive('/dashboard') ? 'active':'' ?>">🏠 Dashboard</a>
      <a href="/citas" class="sb-link <?= $isActive('/citas') ? 'active':'' ?>">📅 Citas</a>

      <?php if ($role === 'superadmin'): ?>
        <a href="/citas/create" class="sb-link <?= $isActive('/citas/create') ? 'active':'' ?>">➕ Reservar cita</a>
        <a href="/doctor-schedules" class="sb-link <?= $isActive('/doctor-schedules') ? 'active':'' ?>">🕑 Horarios Doctores</a>
      <?php endif; ?>

      <?php if ($role === 'cashier'): ?>
        <!-- Enlace opcional para cajero; actualmente el listado de citas ya permite cambiar estado/pago -->
        <!-- <a href="/billing" class="sb-link <?= $isActive('/billing') ? 'active':'' ?>">💳 Caja</a> -->
      <?php endif; ?>
    </nav>

    <div class="sb-footer">
      <form method="POST" action="/logout">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>" />
        <button type="submit" class="btn danger w-full">Cerrar sesión</button>
      </form>
    </div>
  </aside>

  <!-- ===== CONTENIDO ===== -->
  <div class="page">
    <header class="topbar">
      <button class="sb-toggle mobile-only" id="sbToggleMobile" aria-label="Abrir menú" title="Abrir menú">☰</button>
      <div class="topbar-title"><?= htmlspecialchars($title ?? '') ?></div>
    </header>

    <main class="container" role="main">
      <?= $content ?? '' ?>
    </main>
  </div>

  <script>
    // Toggle de sidebar con persistencia en localStorage
    const sidebar = document.querySelector('.sidebar');
    const btns = [document.getElementById('sbToggle'), document.getElementById('sbToggleMobile')];

    function setCollapsed(v){
      if(!sidebar) return;
      sidebar.dataset.collapsed = String(v);
      document.body.classList.toggle('sidebar-collapsed', v);
      try{ localStorage.setItem('sb-collapsed', v ? '1' : '0'); }catch(e){}
    }

    (function init(){
      let saved = null;
      try{ saved = localStorage.getItem('sb-collapsed'); }catch(e){}
      setCollapsed(saved === '1');
    })();

    btns.forEach(b => b && b.addEventListener('click', () => {
      const now = sidebar.dataset.collapsed === 'true';
      setCollapsed(!now);
    }));
  </script>

<?php else: ?>
  <!-- ===== NAV SIMPLE (visitante sin sesión) ===== -->
  <header class="nav">
    <div class="wrap">
      <a class="brand" href="/">Scaffold</a>
      <nav class="actions" aria-label="Acciones de sesión">
        <a class="link" href="/login">Ingresar</a>
        <a class="link" href="/register">Registro</a>
      </nav>
    </div>
  </header>

  <main class="container" role="main">
    <?= $content ?? '' ?>
  </main>
<?php endif; ?>
</body>
</html>
