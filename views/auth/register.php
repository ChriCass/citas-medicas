<div style="max-width: 450px; margin: 4rem auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
  <!-- Título -->
  <h1 style="font-size: 2rem; font-weight: 600; color: #FF0063; text-align: center; margin-bottom: 1.5rem;">
    Registro
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
  <form method="POST" action="/register" style="display: flex; flex-direction: column; gap: 1rem;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

    <input name="nombre" type="text" placeholder="Nombre" required
           style="padding: 0.5rem 0.75rem; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem;">

    <input name="apellido" type="text" placeholder="Apellido"
           style="padding: 0.5rem 0.75rem; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem;">

    <input name="email" type="email" placeholder="Email" required
           style="padding: 0.5rem 0.75rem; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem;">

    <input name="dni" type="text" placeholder="DNI"
           style="padding: 0.5rem 0.75rem; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem;">

    <input name="telefono" type="tel" placeholder="Teléfono"
           style="padding: 0.5rem 0.75rem; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem;">

    <input name="password" type="password" placeholder="Contraseña" required
           style="padding: 0.5rem 0.75rem; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem;">

    <input name="password_confirmation" type="password" placeholder="Confirmar" required
           style="padding: 0.5rem 0.75rem; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem;">

    <button type="submit" style="
      padding: 0.6rem 1.2rem; 
      background-color: #50E3C2; 
      color: #fff; 
      border: none; 
      border-radius: 6px; 
      font-weight: 500; 
      cursor: pointer;
      transition: background 0.3s;
    " onmouseover="this.style.backgroundColor='#3ABFA1';" onmouseout="this.style.backgroundColor='#50E3C2';">
      Crear cuenta
    </button>
  </form>
</div>
