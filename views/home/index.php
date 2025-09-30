<div style="max-width: 700px; margin: 3rem auto; text-align: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
  <!-- Título -->
  <h1 style="font-size: 2.5rem; font-weight: 600; color: #333; margin-bottom: 0.75rem;">
    <?= htmlspecialchars($title ?? 'Bienvenido') ?>
  </h1>

  <!-- Descripción -->
  <p style="font-size: 1.1rem; color: #555; margin-bottom: 2rem;">
    Scaffold PHP MVC con roles y MySQL/SQL Server.
  </p>

  <!-- Botones -->
  <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
    <?php if(!empty($_SESSION['user'])): ?>
      <a href="/dashboard" style="
        padding: 0.6rem 1.2rem; 
        background-color: #4A90E2; 
        color: #fff; 
        border-radius: 6px; 
        text-decoration: none; 
        font-weight: 500; 
        transition: background 0.3s;
      " onmouseover="this.style.backgroundColor='#357ABD';" onmouseout="this.style.backgroundColor='#4A90E2';">
        Dashboard
      </a>
    <?php else: ?>
      <a href="/register" style="
        padding: 0.6rem 1.2rem; 
        background-color: #50E3C2; 
        color: #fff; 
        border-radius: 6px; 
        text-decoration: none; 
        font-weight: 500; 
        transition: background 0.3s;
      " onmouseover="this.style.backgroundColor='#3ABFA1';" onmouseout="this.style.backgroundColor='#50E3C2';">
        Crear cuenta
      </a>
      <a href="/login" style="
        padding: 0.6rem 1.2rem; 
        background-color: #F5A623; 
        color: #fff; 
        border-radius: 6px; 
        text-decoration: none; 
        font-weight: 500; 
        transition: background 0.3s;
      " onmouseover="this.style.backgroundColor='#D48817';" onmouseout="this.style.backgroundColor='#F5A623';">
        Ingresar
      </a>
    <?php endif; ?>
  </div>
</div>
