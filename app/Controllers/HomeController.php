<?php
namespace App\Controllers; use App\Core\{Request,Response}; use App\Models\Appointment;
class HomeController{
  public function index(Request $r, Response $res){ return $res->view('home/index',['title'=>'Bienvenido']); }
  public function dashboard(Request $r, Response $res){
    $u=$_SESSION['user']??null; $up=[]; if($u){ $up=Appointment::upcomingForUser((int)$u['id'],5); }
    return $res->view('home/dashboard',['title'=>'Dashboard','user'=>$u,'upcoming'=>$up]);
  }
}