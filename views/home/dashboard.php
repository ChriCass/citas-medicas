<?php
// Normaliza $user desde la sesión si no te lo pasan
$u = $user ?? ($_SESSION['user'] ?? []);

// ===== Rol: role | rol | roles[] | tiene_roles[]
$role = $u['rol'] ?? $u['role'] ?? $u['role_slug'] ?? null;

// Si no viene plano, intenta desde arrays
if (!$role) {
  if (!empty($u['roles']) && is_array($u['roles'])) {
    $first = $u['roles'][0] ?? [];
    $role = $first['slug'] ?? $first['nombre'] ?? $first['name'] ?? null;
  } elseif (!empty($u['tiene_roles']) && is_array($u['tiene_roles'])) {
    $first = $u['tiene_roles'][0] ?? [];
    $role = $first['slug'] ?? $first['nombre'] ?? $first['name'] ?? null;
  }
}

// Normaliza a minúsculas y mapea sinónimos ES→EN cuando aplique
$roleNorm = $role ? mb_strtolower((string)$role) : '';
$map = [
  'paciente' => 'patient',
  'cajero' => 'cashier',
  'administrador' => 'superadmin',
  'super' => 'superadmin',
];
if (isset($map[$roleNorm])) $roleNorm = $map[$roleNorm];

// ===== Nombre para mostrar: name | nombre+apellido | email
$name = trim(($u['nombre'] ?? '').' '.($u['apellido'] ?? ''));
if ($name === '') $name = $u['name'] ?? ($u['nombre_completo'] ?? ($u['email'] ?? ''));

// $upcoming debería venir del controlador; si no, default
$upcoming = $upcoming ?? [];
?>
<div style="max-width: 800px; margin: 2rem auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
  <!-- Título -->
  <h1 style="font-size: 2.2rem; font-weight: 600; color: #333; margin-bottom: 0.5rem;">Dashboard</h1>

  <!-- Saludo -->
  <p style="font-size: 1rem; color: #555; margin-bottom: 1rem;">
    Hola, <strong><?= htmlspecialchars($name) ?></strong>
    (rol: <?= htmlspecialchars($roleNorm ?: '-') ?>).
  </p>

  <!-- Botones de acción -->
  <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
    <a href="/citas" style="
      padding: 0.5rem 1rem;
      background-color: #4A90E2;
      color: #fff;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 500;
      transition: background 0.3s;
    " onmouseover="this.style.backgroundColor='#357ABD';" onmouseout="this.style.backgroundColor='#4A90E2';">
      Ver Citas
    </a>

    <?php if ($roleNorm === 'superadmin'): ?>
      <a href="/citas/create" style="
        padding: 0.5rem 1rem;
        background-color: #50E3C2;
        color: #fff;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: background 0.3s;
      " onmouseover="this.style.backgroundColor='#3ABFA1';" onmouseout="this.style.backgroundColor='#50E3C2';">
        Reservar
      </a>
    <?php endif; ?>
  </div>

  <!-- Próximas citas -->
  <div style="margin-top: 1.5rem;">
    <h3 style="font-size: 1.5rem; font-weight: 600; color: #333; margin-bottom: 0.5rem;">Próximas</h3>
    <?php if (empty($upcoming)): ?>
      <p style="color: #666;">No tienes próximas citas.</p>
    <?php else: ?>
      <ul style="list-style: none; padding-left: 0; color: #555;">
        <?php foreach ($upcoming as $a): ?>
          <li style="
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
          " onmouseover="this.style.backgroundColor='#f9f9f9';" onmouseout="this.style.backgroundColor='transparent';">
            <?= htmlspecialchars($a['service_name'] ?? $a['servicio'] ?? 'Servicio') ?>
            — <?= htmlspecialchars(date('Y-m-d H:i', strtotime($a['starts_at'] ?? ($a['inicio'] ?? 'now')))) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
