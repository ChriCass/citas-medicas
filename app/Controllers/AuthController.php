<?php
namespace App\Controllers; use App\Core\{Request,Response,Csrf}; use App\Models\User;
class AuthController{
  public function showLogin(Request $r, Response $res){ return $res->view('auth/login',['csrf'=>Csrf::token()]); }
  public function login(Request $r, Response $res){
    $email=trim((string)($_POST['email']??'')); $password=(string)($_POST['password']??''); $t=(string)($_POST['_csrf']??'');
    if(!\App\Core\Csrf::verify($t)) return $res->view('auth/login',['error'=>'CSRF inválido','csrf'=>Csrf::token()]);
    $u=User::findByEmail($email); if(!$u || !password_verify($password,$u['password'])) return $res->view('auth/login',['error'=>'Credenciales inválidas','csrf'=>Csrf::token()]);
    $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']]; return $res->redirect('/dashboard');
  }
  public function showRegister(Request $r, Response $res){ return $res->view('auth/register',['csrf'=>Csrf::token()]); }
  public function register(Request $r, Response $res){
    $name=trim((string)($_POST['name']??'')); $email=trim((string)($_POST['email']??'')); $p=(string)($_POST['password']??''); $pc=(string)($_POST['password_confirmation']??''); $t=(string)($_POST['_csrf']??'');
    if(!\App\Core\Csrf::verify($t)) return $res->view('auth/register',['error'=>'CSRF inválido','csrf'=>Csrf::token()]);
    if($p!==$pc) return $res->view('auth/register',['error'=>'Las contraseñas no coinciden','csrf'=>Csrf::token()]);
    if(User::findByEmail($email)) return $res->view('auth/register',['error'=>'El email ya está registrado','csrf'=>Csrf::token()]);
    $hash=password_hash($p, PASSWORD_DEFAULT); $id=User::create($name,$email,$hash,'patient'); $_SESSION['user']=['id'=>$id,'name'=>$name,'email'=>$email,'role'=>'patient']; return $res->redirect('/dashboard');
  }
  public function logout(Request $r, Response $res){ $_SESSION=[]; session_destroy(); return $res->redirect('/login'); }
}