<!doctype html>
<?php
  // Sesión
  $auth = $_SESSION['user'] ?? null;

  // ===== Normalización de rol =====
  $role = null;
  if ($auth) {
    $candidates = [
      $auth['rol']        ?? null,
      $auth['role']       ?? null,
      $auth['role_slug']  ?? null,
    ];

    // Si viene como arreglo de roles (roles/tiene_roles)
    if (empty(array_filter($candidates))) {
      $roleArrays = [];
      if (!empty($auth['roles']) && is_array($auth['roles']))        $roleArrays[] = $auth['roles'];
      if (!empty($auth['tiene_roles']) && is_array($auth['tiene_roles'])) $roleArrays[] = $auth['tiene_roles'];

      foreach ($roleArrays as $arr) {
        $first = $arr[0] ?? [];
        $candidates[] = $first['slug'] ?? $first['nombre'] ?? $first['name'] ?? null;
      }
    }

    foreach ($candidates as $cand) {
      if (is_string($cand) && $cand !== '') { $role = mb_strtolower($cand); break; }
    }

    // Mapeo de sinónimos/español→inglés si aplica
    $map = [
      'paciente'   => 'patient',
      'cajero'     => 'cashier',
      'super'      => 'superadmin',
      'administrador' => 'superadmin',
    ];
    if ($role && isset($map[$role])) $role = $map[$role];
  }

  // ===== Nombre para mostrar (nombre+apellido | name | email) =====
  $fullName = '';
  if ($auth) {
    $n = trim(($auth['nombre'] ?? '') . ' ' . ($auth['apellido'] ?? ''));
    $fullName = $n !== '' ? $n : ($auth['name'] ?? ($auth['nombre_completo'] ?? ($auth['email'] ?? '')));
  }

  // Ruta activa
  $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  $isActive = function (string $p) use ($path) {
      return rtrim($path,'/') === rtrim($p,'/');
  };

  // Helpers de rol
  $isSuper   = in_array($role, ['superadmin'], true);
  $isCashier = in_array($role, ['cashier','cajero'], true);
  $isDoctor  = in_array($role, ['doctor'], true);
  // detectar si el query param today está presente (para destacar 'Mis citas del día')
  $todayParam = false;
  if (isset($_GET['today'])) {
    $todayRaw = (string)$_GET['today'];
    $todayParam = in_array(strtolower($todayRaw), ['1','true','yes'], true);
  }
?>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= isset($title) ? htmlspecialchars($title) : 'App' ?></title>
  <link rel="stylesheet" href="/assets/styles.css?v=palette-teal-ff0063" />
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    /* Estilos personalizados para el modal de éxito */
    .swal2-popup-custom {
      z-index: 99999 !important;
      position: fixed !important;
      top: 50% !important;
      left: 50% !important;
      transform: translate(-50%, -50%) !important;
    }
    .swal2-title-custom {
      color: #28a745 !important;
      font-size: 1.5rem !important;
      font-weight: bold !important;
    }
    .swal2-content-custom {
      font-size: 1.1rem !important;
      color: #333 !important;
    }
    .swal2-confirm-custom {
      background-color: #28a745 !important;
      border: none !important;
      padding: 10px 20px !important;
      font-size: 1rem !important;
      font-weight: bold !important;
    }
    .swal2-backdrop {
      z-index: 99998 !important;
    }
  </style>
</head>

<body class="<?= $auth ? 'with-sidebar' : 'no-auth' ?>">
<?php if ($auth): ?>
  <!-- ===== SIDEBAR ===== -->
  <aside class="sidebar" data-collapsed="false" aria-label="Barra lateral de navegación">
    <div class="sb-header">
      <button class="sb-toggle" id="sbToggle" aria-label="Colapsar menú" title="Colapsar menú">☰</button>
      <a class="brand" href="/dashboard">Scaffold</a>
    </div>

    <div class="sb-user" role="region" aria-label="Usuario actual">
      <div class="sb-user-name"><?= htmlspecialchars($fullName) ?></div>
      <div class="sb-user-role sb-user-role badge"><?= htmlspecialchars($role ?? '-') ?></div>
    </div>

    <nav class="sb-nav" role="navigation" aria-label="Menú principal">
      <a href="/dashboard" class="sb-link <?= $isActive('/dashboard') ? 'active':'' ?>">🏠 Dashboard</a>
      <a href="/citas" class="sb-link <?= ($isActive('/citas') && !$isActive('/citas/today')) ? 'active':'' ?>">📅 Citas</a>
      <?php if ($isDoctor): ?>
        <a href="/citas/today" class="sb-link <?= $isActive('/citas/today') ? 'active':'' ?>">📅 Mis citas del día</a>
      <?php endif; ?>

      <?php if ($isSuper): ?>
        <a href="/citas/create" class="sb-link <?= $isActive('/citas/create') ? 'active':'' ?>">➕ Reservar cita</a>
        <a href="/doctor-schedules" class="sb-link <?= $isActive('/doctor-schedules') ? 'active':'' ?>">🕑 Horarios Doctores</a>
        <a href="/users" class="sb-link <?= $isActive('/users') ? 'active':'' ?>">👨‍⚕️ Usuarios</a>
      <?php endif; ?>

      <?php if ($isCashier): ?>
        <!-- Enlace opcional para cajero -->
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
    // Toggle de sidebar con persistencia
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

  <!-- SweetAlert2 para mensajes de sesión -->
  <script>
    // Mostrar alertas basadas en mensajes de sesión
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
      // Debug: Verificar que el parámetro GET existe
      console.log('Parámetro success=1 encontrado en URL');
      
      // Función para mostrar el modal de éxito
      function showSuccessModal() {
        console.log('Mostrando modal de éxito...');
        
        // Verificar que SweetAlert2 está disponible
        if (typeof Swal === 'undefined') {
          console.error('SweetAlert2 no está disponible');
          return;
        }
        
        // Configuración ultra simple y robusta
        Swal.fire({
          icon: 'success',
          title: '¡Éxito!',
          text: 'Cita creada exitosamente',
          showConfirmButton: true,
          confirmButtonText: 'Entendido',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showCloseButton: false,
          focusConfirm: true,
          backdrop: true,
          // Configuración para evitar cierre automático
          timer: null,
          timerProgressBar: false,
          // Callbacks para debug
          didOpen: () => {
            console.log('Modal abierto correctamente');
          },
          willClose: () => {
            console.log('Modal se va a cerrar');
          }
        }).then((result) => {
          console.log('Modal cerrado por usuario:', result);
          // Limpiar la URL removiendo el parámetro success
          const url = new URL(window.location);
          url.searchParams.delete('success');
          window.history.replaceState({}, '', url);
        });
      }
      
      // Esperar a que la página esté completamente cargada
      function waitForPageLoad() {
        if (document.readyState === 'loading') {
          console.log('Página aún cargando, esperando DOMContentLoaded...');
          document.addEventListener('DOMContentLoaded', () => {
            console.log('DOMContentLoaded ejecutado, esperando SweetAlert2...');
            waitForSweetAlert();
          });
        } else {
          console.log('Página ya cargada, esperando SweetAlert2...');
          waitForSweetAlert();
        }
      }
      
      // Esperar a que SweetAlert2 esté completamente cargado
      function waitForSweetAlert() {
        if (typeof Swal !== 'undefined' && Swal.fire) {
          console.log('SweetAlert2 está disponible, ejecutando modal...');
          showSuccessModal();
        } else {
          console.log('SweetAlert2 aún no está disponible, esperando...');
          setTimeout(waitForSweetAlert, 100);
        }
      }
      
      // Iniciar la espera
      console.log('Iniciando espera para página y SweetAlert2...');
      waitForPageLoad();
    <?php endif; ?>

    <?php if (isset($_GET['cancel_creation']) && $_GET['cancel_creation'] == '1'): ?>
      // Debug: Verificar que el parámetro cancel_creation existe
      console.log('Parámetro cancel_creation=1 encontrado en URL');
      
      // Función para mostrar el modal de cancelación de creación
      function showCancelCreationModal() {
        console.log('Mostrando modal de cancelación de creación...');
        
        // Verificar que SweetAlert2 está disponible
        if (typeof Swal === 'undefined') {
          console.error('SweetAlert2 no está disponible');
          return;
        }
        
        // Configuración ultra simple y robusta
        Swal.fire({
          icon: 'info',
          title: 'Cancelado',
          text: 'Creación de cita cancelada exitosamente',
          showConfirmButton: true,
          confirmButtonText: 'Entendido',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showCloseButton: false,
          focusConfirm: true,
          backdrop: true,
          // Configuración para evitar cierre automático
          timer: null,
          timerProgressBar: false,
          // Callbacks para debug
          didOpen: () => {
            console.log('Modal de cancelación de creación abierto correctamente');
          },
          willClose: () => {
            console.log('Modal de cancelación de creación se va a cerrar');
          }
        }).then((result) => {
          console.log('Modal de cancelación de creación cerrado por usuario:', result);
          // Limpiar la URL removiendo el parámetro cancel_creation
          const url = new URL(window.location);
          url.searchParams.delete('cancel_creation');
          window.history.replaceState({}, '', url);
        });
      }
      
      // Esperar a que la página esté completamente cargada
      function waitForPageLoadCancelCreation() {
        if (document.readyState === 'loading') {
          console.log('Página aún cargando, esperando DOMContentLoaded...');
          document.addEventListener('DOMContentLoaded', () => {
            console.log('DOMContentLoaded ejecutado, esperando SweetAlert2...');
            waitForSweetAlertCancelCreation();
          });
        } else {
          console.log('Página ya cargada, esperando SweetAlert2...');
          waitForSweetAlertCancelCreation();
        }
      }
      
      // Esperar a que SweetAlert2 esté completamente cargado
      function waitForSweetAlertCancelCreation() {
        if (typeof Swal !== 'undefined' && Swal.fire) {
          console.log('SweetAlert2 está disponible, ejecutando modal de cancelación de creación...');
          showCancelCreationModal();
        } else {
          console.log('SweetAlert2 aún no está disponible, esperando...');
          setTimeout(waitForSweetAlertCancelCreation, 100);
        }
      }
      
      // Iniciar la espera
      console.log('Iniciando espera para página y SweetAlert2...');
      waitForPageLoadCancelCreation();
    <?php endif; ?>

    <?php if (isset($_GET['canceled']) && $_GET['canceled'] == '1'): ?>
      // Debug: Verificar que el parámetro canceled existe
      console.log('Parámetro canceled=1 encontrado en URL');
      
      // Función para mostrar el modal de cita cancelada
      function showCanceledModal() {
        console.log('Mostrando modal de cita cancelada...');
        
        // Verificar que SweetAlert2 está disponible
        if (typeof Swal === 'undefined') {
          console.error('SweetAlert2 no está disponible');
          return;
        }
        
        // Configuración ultra simple y robusta
        Swal.fire({
          icon: 'success',
          title: '¡Cita Cancelada!',
          text: 'La cita ha sido cancelada exitosamente',
          showConfirmButton: true,
          confirmButtonText: 'Entendido',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showCloseButton: false,
          focusConfirm: true,
          backdrop: true,
          // Configuración para evitar cierre automático
          timer: null,
          timerProgressBar: false,
          // Callbacks para debug
          didOpen: () => {
            console.log('Modal de cita cancelada abierto correctamente');
          },
          willClose: () => {
            console.log('Modal de cita cancelada se va a cerrar');
          }
        }).then((result) => {
          console.log('Modal de cita cancelada cerrado por usuario:', result);
          // Limpiar la URL removiendo el parámetro canceled
          const url = new URL(window.location);
          url.searchParams.delete('canceled');
          window.history.replaceState({}, '', url);
        });
      }
      
      // Esperar a que la página esté completamente cargada
      function waitForPageLoadCanceled() {
        if (document.readyState === 'loading') {
          console.log('Página aún cargando, esperando DOMContentLoaded...');
          document.addEventListener('DOMContentLoaded', () => {
            console.log('DOMContentLoaded ejecutado, esperando SweetAlert2...');
            waitForSweetAlertCanceled();
          });
        } else {
          console.log('Página ya cargada, esperando SweetAlert2...');
          waitForSweetAlertCanceled();
        }
      }
      
      // Esperar a que SweetAlert2 esté completamente cargado
      function waitForSweetAlertCanceled() {
        if (typeof Swal !== 'undefined' && Swal.fire) {
          console.log('SweetAlert2 está disponible, ejecutando modal de cancelación...');
          showCanceledModal();
        } else {
          console.log('SweetAlert2 aún no está disponible, esperando...');
          setTimeout(waitForSweetAlertCanceled, 100);
        }
      }
      
      // Iniciar la espera
      console.log('Iniciando espera para página y SweetAlert2...');
      waitForPageLoadCanceled();
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'cancel_time'): ?>
      // Debug: Verificar que el parámetro error existe
      console.log('Parámetro error=cancel_time encontrado en URL');
      
      // Función para mostrar el modal de error
      function showErrorModal() {
        console.log('Mostrando modal de error...');
        
        // Verificar que SweetAlert2 está disponible
        if (typeof Swal === 'undefined') {
          console.error('SweetAlert2 no está disponible');
          return;
        }
        
        // Configuración ultra simple y robusta
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Solo puedes cancelar hasta 24 horas antes de la cita',
          showConfirmButton: true,
          confirmButtonText: 'Entendido',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showCloseButton: false,
          focusConfirm: true,
          backdrop: true,
          // Configuración para evitar cierre automático
          timer: null,
          timerProgressBar: false,
          // Callbacks para debug
          didOpen: () => {
            console.log('Modal de error abierto correctamente');
          },
          willClose: () => {
            console.log('Modal de error se va a cerrar');
          }
        }).then((result) => {
          console.log('Modal de error cerrado por usuario:', result);
          // Limpiar la URL removiendo el parámetro error
          const url = new URL(window.location);
          url.searchParams.delete('error');
          window.history.replaceState({}, '', url);
        });
      }
      
      // Esperar a que la página esté completamente cargada
      function waitForPageLoadError() {
        if (document.readyState === 'loading') {
          console.log('Página aún cargando, esperando DOMContentLoaded...');
          document.addEventListener('DOMContentLoaded', () => {
            console.log('DOMContentLoaded ejecutado, esperando SweetAlert2...');
            waitForSweetAlertError();
          });
        } else {
          console.log('Página ya cargada, esperando SweetAlert2...');
          waitForSweetAlertError();
        }
      }
      
      // Esperar a que SweetAlert2 esté completamente cargado
      function waitForSweetAlertError() {
        if (typeof Swal !== 'undefined' && Swal.fire) {
          console.log('SweetAlert2 está disponible, ejecutando modal de error...');
          showErrorModal();
        } else {
          console.log('SweetAlert2 aún no está disponible, esperando...');
          setTimeout(waitForSweetAlertError, 100);
        }
      }
      
      // Iniciar la espera
      console.log('Iniciando espera para página y SweetAlert2...');
      waitForPageLoadError();
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?= addslashes($_SESSION['error']) ?>',
        timer: 4000,
        showConfirmButton: true
      });
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
      Swal.fire({
        icon: 'warning',
        title: 'Advertencia',
        text: '<?= addslashes($_SESSION['warning']) ?>',
        timer: 3000,
        showConfirmButton: false
      });
      <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['info'])): ?>
      // Esperar a que la página esté completamente cargada
      setTimeout(() => {
        Swal.fire({
          icon: 'info',
          title: 'Información',
          text: '<?= addslashes($_SESSION['info']) ?>',
          showConfirmButton: true,
          confirmButtonText: 'Entendido',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showCloseButton: false,
          focusConfirm: true,
          backdrop: true
        });
      }, 2000);
      <?php unset($_SESSION['info']); ?>
    <?php endif; ?>
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
