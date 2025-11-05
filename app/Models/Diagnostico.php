<?php

namespace App\Models;

class Diagnostico
{
    /**
     * Busca diagnósticos que coincidan con una consulta.
     *
     * @param string $query La consulta de búsqueda.
     * @param int $limit El número máximo de resultados a devolver.
     * @return array Lista de descripciones de diagnósticos.
     */
    public static function search(string $query = ''): array
    {
        // Intentar obtener PDO desde App\Core\Database; si no está inicializada, usar Eloquent
        try {
            $pdo = \App\Core\Database::pdo();
        } catch (\Throwable $e) {
            // Fallback a Eloquent's PDO
            try {
                $pdo = \App\Core\Eloquent::connection()->getPdo();
            } catch (\Throwable $e2) {
                throw new \RuntimeException('No se pudo obtener conexión a la base de datos');
            }
        }

        $stmt = $pdo->prepare("SELECT id, nombre_enfermedad FROM diagnosticos WHERE nombre_enfermedad LIKE :query ORDER BY nombre_enfermedad");
        $stmt->bindValue(':query', "%{$query}%", \PDO::PARAM_STR);
        $stmt->execute();

        // Devolver filas asociativas con id y nombre_enfermedad
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}