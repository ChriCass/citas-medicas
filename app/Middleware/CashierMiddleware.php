<?php
namespace App\Middleware;

use App\Core\{Request, Response};

class CashierMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, Response $response)
    {
        // Verificar si el usuario está autenticado
        if (!isset($_SESSION['user'])) {
            return $response->redirect('/login')->with('error', 'Debes iniciar sesión para acceder a esta sección');
        }
        
        $user = $_SESSION['user'];
        $role = $user['rol'] ?? $user['role'] ?? null;
        
        // Normalizar rol
        $roleNorm = $role ? mb_strtolower((string)$role) : '';
        $map = [
            'cajero' => 'cashier',
            'cashier' => 'cashier'
        ];
        if (isset($map[$roleNorm])) {
            $roleNorm = $map[$roleNorm];
        }
        
        // Verificar si es cajero
        if ($roleNorm !== 'cashier') {
            return $response->redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección. Solo los cajeros pueden gestionar pagos.');
        }
        
        // Verificar si tiene información de cajero
        if (!isset($user['cajero_id']) && isset($user['id'])) {
            // Intentar obtener el ID del cajero desde la base de datos
            try {
                $db = \App\Core\SimpleDatabase::getInstance();
                $cajero = $db->fetchOne("SELECT id FROM cajeros WHERE usuario_id = ?", [$user['id']]);
                if ($cajero) {
                    $_SESSION['user']['cajero_id'] = $cajero['id'];
                }
            } catch (\Exception $e) {
                // Si hay error, continuar sin el cajero_id
            }
        }
        
        // Si aún no tiene cajero_id, crear uno temporal
        if (!isset($_SESSION['user']['cajero_id']) && isset($user['id'])) {
            try {
                $db = \App\Core\SimpleDatabase::getInstance();
                $db->query("INSERT INTO cajeros (usuario_id, numero_caja, activo) VALUES (?, ?, ?)", [
                    $user['id'],
                    'CAJA-' . $user['id'],
                    1
                ]);
                $_SESSION['user']['cajero_id'] = $db->getPdo()->lastInsertId();
            } catch (\Exception $e) {
                // Si hay error, continuar sin el cajero_id
            }
        }
        
        return $next($request);
    }
}
