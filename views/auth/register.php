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
  <form method="POST" action="/register" style="display: flex; flex-direction: column; gap: 1rem;" onsubmit="return validateRegisterForm(event, this);">
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

<script>
// Función para validar formulario de registro con SweetAlert2
function validateRegisterForm(event, form) {
  event.preventDefault();
  
  const nombre = form.querySelector('input[name="nombre"]').value.trim();
  const apellido = form.querySelector('input[name="apellido"]').value.trim();
  const email = form.querySelector('input[name="email"]').value.trim();
  const dni = form.querySelector('input[name="dni"]').value.trim();
  const telefono = form.querySelector('input[name="telefono"]').value.trim();
  const password = form.querySelector('input[name="password"]').value;
  const passwordConfirmation = form.querySelector('input[name="password_confirmation"]').value;
  
  if (!nombre) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor ingresa tu nombre'
    });
    return false;
  }
  
  if (!email) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor ingresa tu email'
    });
    return false;
  }
  
  if (!password) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor ingresa una contraseña'
    });
    return false;
  }
  
  if (!passwordConfirmation) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor confirma tu contraseña'
    });
    return false;
  }
  
  // Validar formato de email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Por favor ingresa un email válido'
    });
    return false;
  }
  
  // Validar que las contraseñas coincidan
  if (password !== passwordConfirmation) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Las contraseñas no coinciden'
    });
    return false;
  }
  
  // Validar longitud de contraseña
  if (password.length < 6) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'La contraseña debe tener al menos 6 caracteres'
    });
    return false;
  }
  
  // Validar DNI si se proporciona
  if (dni && !/^\d{8}$/.test(dni)) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'El DNI debe tener 8 dígitos'
    });
    return false;
  }
  
  // Mostrar loading
  Swal.fire({
    title: 'Creando cuenta...',
    text: 'Por favor espera',
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  // Enviar formulario
  form.submit();
  
  return false;
}
</script>
