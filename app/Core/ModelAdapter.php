<?php

namespace App\Core;

class ModelAdapter
{
    private static $useEloquent = true;

    public static function setUseEloquent(bool $use): void
    {
        self::$useEloquent = $use;
    }

    public static function isUsingEloquent(): bool
    {
        return self::$useEloquent;
    }

    public static function getModel(string $modelName)
    {
        if (self::$useEloquent) {
            $eloquentModel = "App\\Models\\{$modelName}";
            if (class_exists($eloquentModel)) {
                return new $eloquentModel();
            }
        }
        
        // Fallback a SimpleModel
        $simpleModel = "App\\Models\\{$modelName}";
        if (class_exists($simpleModel)) {
            return new $simpleModel();
        }
        
        throw new \Exception("Modelo {$modelName} no encontrado");
    }

    public static function callStatic(string $modelName, string $method, array $args = [])
    {
        if (self::$useEloquent) {
            $eloquentModel = "App\\Models\\{$modelName}";
            if (class_exists($eloquentModel)) {
                return call_user_func_array([$eloquentModel, $method], $args);
            }
        }
        
        // Fallback a SimpleModel
        $simpleModel = "App\\Models\\{$modelName}";
        if (class_exists($simpleModel)) {
            return call_user_func_array([$simpleModel, $method], $args);
        }
        
        throw new \Exception("Método {$method} no encontrado en {$modelName}");
    }
}
