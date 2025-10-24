<?php
namespace App\Controllers; 
use App\Core\{Request,Response,Csrf}; 
use App\Models\{User,Paciente};

class AuthController{
  public function showLogin(Request $r, Response $res){ return $res->view('auth/login',['csrf'=>Csrf::token()]); }
  public function login(Request $r, Response $res){
    $email=trim((string)($_POST['email']??'')); $password=(string)($_POST['password']??''); $t=(string)($_POST['_csrf']??'');
    
    if(!\App\Core\Csrf::verify($t)) return $res->view('auth/login',['error'=>'CSRF inválido','csrf'=>Csrf::token()]);
    $u=User::findByEmail($email); if(!$u || !$u->verifyPassword($password)) return $res->view('auth/login',['error'=>'Credenciales inválidas','csrf'=>Csrf::token()]);
    
    $userData = ['id'=>(int)$u->id,'nombre'=>$u->nombre,'apellido'=>$u->apellido,'email'=>$u->email,'rol'=>$u->getRoleName()];
    
    // Si es cajero, obtener el cajero_id
    if ($u->getRoleName() === 'cajero' || $u->getRoleName() === 'cashier') {
      $db = \App\Core\SimpleDatabase::getInstance();
      $cajero = $db->fetchOne("SELECT id FROM cajeros WHERE usuario_id = ?", [(int)$u->id]);
      if ($cajero) {
        $userData['cajero_id'] = (int)$cajero['id'];
      }
    }
    
    $_SESSION['user'] = $userData;
    return $res->redirect('/dashboard');
  }
  public function showRegister(Request $r, Response $res){ return $res->view('auth/register',['csrf'=>Csrf::token()]); }
  public function register(Request $r, Response $res){
    $nombre=trim((string)($_POST['nombre']??'')); $apellido=trim((string)($_POST['apellido']??'')); $email=trim((string)($_POST['email']??'')); $p=(string)($_POST['password']??''); $pc=(string)($_POST['password_confirmation']??''); $t=(string)($_POST['_csrf']??'');
    $dni=trim((string)($_POST['dni']??'')); $telefono=trim((string)($_POST['telefono']??''));
    if(!\App\Core\Csrf::verify($t)) return $res->view('auth/register',['error'=>'CSRF inválido','csrf'=>Csrf::token()]);
    if($p!==$pc) return $res->view('auth/register',['error'=>'Las contraseñas no coinciden','csrf'=>Csrf::token()]);
    if(User::findByEmail($email)) return $res->view('auth/register',['error'=>'El email ya está registrado','csrf'=>Csrf::token()]);
    try {
      $hash=password_hash($p, PASSWORD_DEFAULT); 
      $user = new User();
      $user->nombre = $nombre;
      $user->apellido = $apellido;
      $user->email = $email;
      $user->contrasenia = $hash;
      $user->dni = $dni;
      $user->telefono = $telefono;
      $id = $user->create($user->toArray());
      
      // Asignar rol de paciente
      $db = \App\Core\SimpleDatabase::getInstance();
      $role = $db->fetchOne("SELECT id FROM roles WHERE nombre = 'paciente'");
      if ($role) {
        $db->query("INSERT INTO tiene_roles (usuario_id, rol_id) VALUES (?, ?)", [$id, $role['id']]);
      }
      
      // Crear registro de paciente
      $db->query("INSERT INTO pacientes (usuario_id) VALUES (?)", [$id]);
      
      $_SESSION['user']=['id'=>$id,'nombre'=>$nombre,'apellido'=>$apellido,'email'=>$email,'rol'=>'paciente']; return $res->redirect('/dashboard');
    } catch(\Exception $e) {
      return $res->view('auth/register',['error'=>'Error al crear la cuenta: '.$e->getMessage(),'csrf'=>Csrf::token()]);
    }
  }
  public function logout(Request $r, Response $res){ $_SESSION=[]; session_destroy(); return $res->redirect('/login'); }
}