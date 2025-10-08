<?php
/**
 * Script de diagnóstico para conexión a SQL Server
 * Ejecuta este archivo para diagnosticar problemas de conexión
 */

// Configuración - AJUSTA ESTOS VALORES SEGÚN TU CONFIGURACIÓN
$config = [
    'driver' => 'sqlsrv',
    'host' => 'localhost', // o la IP de tu servidor SQL Server
    'port' => 1433,
    'database' => 'tu_base_de_datos', // Cambia por el nombre de tu BD
    'username' => 'tu_usuario', // Cambia por tu usuario
    'password' => 'tu_contraseña' // Cambia por tu contraseña
];

echo "<h2>🔍 Diagnóstico de Conexión SQL Server</h2>\n";
echo "<pre>\n";

// 1. Verificar extensión PDO SQLSRV
echo "1. Verificando extensión PDO SQLSRV...\n";
if (extension_loaded('pdo_sqlsrv')) {
    echo "   ✅ Extensión pdo_sqlsrv está cargada\n";
} else {
    echo "   ❌ Extensión pdo_sqlsrv NO está cargada\n";
    echo "   💡 Instala: https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server\n";
}

// 2. Verificar drivers disponibles
echo "\n2. Drivers PDO disponibles:\n";
$drivers = PDO::getAvailableDrivers();
foreach ($drivers as $driver) {
    echo "   - $driver\n";
}

// 3. Verificar conectividad de red
echo "\n3. Verificando conectividad de red...\n";
$host = $config['host'];
$port = $config['port'];

$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if ($connection) {
    echo "   ✅ Puerto $port en $host está abierto\n";
    fclose($connection);
} else {
    echo "   ❌ No se puede conectar a $host:$port\n";
    echo "   💡 Verifica que SQL Server esté ejecutándose y escuchando en el puerto $port\n";
}

// 4. Intentar conexión PDO
echo "\n4. Intentando conexión PDO...\n";
try {
    $dsn = "sqlsrv:Server={$config['host']},{$config['port']};Database={$config['database']}";
    echo "   DSN: $dsn\n";
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "   ✅ Conexión exitosa!\n";
    
    // 5. Verificar información del servidor
    echo "\n5. Información del servidor:\n";
    $stmt = $pdo->query("SELECT @@VERSION as version");
    $version = $stmt->fetch();
    echo "   Versión: " . $version['version'] . "\n";
    
    // 6. Verificar tablas existentes
    echo "\n6. Tablas existentes:\n";
    $stmt = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
    $tables = $stmt->fetchAll();
    if (empty($tables)) {
        echo "   ⚠️  No hay tablas en la base de datos\n";
        echo "   💡 Ejecuta el script sql/SQLServer_Español/schema.sql\n";
    } else {
        foreach ($tables as $table) {
            echo "   - " . $table['TABLE_NAME'] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "   ❌ Error de conexión: " . $e->getMessage() . "\n";
    
    // Diagnóstico específico de errores comunes
    $message = $e->getMessage();
    if (strpos($message, 'TCP Provider') !== false) {
        echo "\n   🔧 Diagnóstico TCP Provider:\n";
        echo "   - Verifica que SQL Server esté ejecutándose\n";
        echo "   - Verifica que el puerto 1433 esté abierto\n";
        echo "   - Verifica la configuración de firewall\n";
        echo "   - Verifica que SQL Server Browser esté ejecutándose\n";
    } elseif (strpos($message, 'Login failed') !== false) {
        echo "\n   🔧 Diagnóstico Login:\n";
        echo "   - Verifica usuario y contraseña\n";
        echo "   - Verifica que el usuario tenga permisos en la BD\n";
        echo "   - Verifica que la autenticación SQL esté habilitada\n";
    } elseif (strpos($message, 'Cannot open database') !== false) {
        echo "\n   🔧 Diagnóstico Base de Datos:\n";
        echo "   - Verifica que la base de datos existe\n";
        echo "   - Verifica que el usuario tenga acceso a la BD\n";
    }
}

echo "\n</pre>\n";

echo "<h3>📋 Checklist de Solución:</h3>\n";
echo "<ul>\n";
echo "<li>✅ SQL Server está instalado y ejecutándose</li>\n";
echo "<li>✅ Puerto 1433 está abierto</li>\n";
echo "<li>✅ Firewall permite conexiones al puerto 1433</li>\n";
echo "<li>✅ SQL Server Browser está ejecutándose</li>\n";
echo "<li>✅ Autenticación SQL está habilitada</li>\n";
echo "<li>✅ Usuario tiene permisos en la base de datos</li>\n";
echo "<li>✅ Base de datos existe</li>\n";
echo "<li>✅ Extensión pdo_sqlsrv está instalada</li>\n";
echo "</ul>\n";

echo "<h3>🔧 Comandos útiles para SQL Server:</h3>\n";
echo "<pre>\n";
echo "# Verificar servicios SQL Server\n";
echo "sc query MSSQLSERVER\n";
echo "sc query SQLBrowser\n\n";

echo "# Verificar puertos abiertos\n";
echo "netstat -an | findstr 1433\n\n";

echo "# Conectar con sqlcmd\n";
echo "sqlcmd -S localhost,1433 -E\n";
echo "</pre>\n";
?>
