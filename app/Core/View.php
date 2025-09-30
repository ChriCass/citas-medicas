<?php
namespace App\Core;
class View {
  public static function render(string $view,array $params=[],?string $layout=null): string{
    $vp=BASE_PATH.'/views/'.trim($view,'/').'.php';
    if(!is_file($vp)){ http_response_code(404); return 'Vista no encontrada: '.htmlspecialchars($view); }
    extract($params, EXTR_OVERWRITE); ob_start(); include $vp; $content=ob_get_clean();
    if($layout){ $lp=BASE_PATH.'/views/'.trim($layout,'/').'.php'; if(is_file($lp)){ ob_start(); include $lp; return ob_get_clean(); } }
    return $content;
}}