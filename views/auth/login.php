<div style="max-width: 400px; margin: 4rem auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
  <!-- Título -->
  <h1 style="font-size: 2rem; font-weight: 600; color: #FF0063; text-align: center; margin-bottom: 1.5rem;">
    Ingresar
  </h1>

  <!-- Error -->
  <?php if(!empty($error)): ?>
    <div style="
      margin-bottom: 1rem; 
      padding: 0.75rem 1rem; 
      background-color: #FFE6E6; 
      border: 1px solid #FFCCCC; 
      border-radius: 6px; 
      color: #B00000;
      text-align: center;
    ">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- Formulario -->
  <form method="POST" action="/login" style="display: flex; flex-direction: column; gap: 1rem;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

    <input name="email" type="email" placeholder="Email" required 
           style="padding: 0.5rem 0.75rem; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem;">

    <input name="password" type="password" placeholder="Contraseña" required 
           style="padding: 0.5rem 0.75rem; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem;">

    <button type="submit" style="
      padding: 0.6rem 1.2rem; 
      background-color: #FF0063; 
      color: #fff; 
      border: none; 
      border-radius: 6px; 
      font-weight: 500; 
      cursor: pointer;
      transition: background 0.3s;
    " onmouseover="this.style.backgroundColor='#357ABD';" onmouseout="this.style.backgroundColor='#4A90E2';">
      Entrar
    </button>
  </form>
</div>
