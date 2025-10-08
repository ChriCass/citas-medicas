<?php
/**
 * Diagnóstico completo de SQL Server
 */

echo "<h2>🔍 Diagnóstico Completo de SQL Server</h2>\n";
echo "<pre>\n";

// 1. Verificar servicios SQL Server
echo "1. Verificando servicios SQL Server...\n";
$services = [
    'MSSQLSERVER' => 'SQL Server (instancia por defecto)',
    'MSSQL$SQLEXPRESS' => 'SQL Server Express',
    'SQLBrowser' => 'SQL Server Browser'
];

foreach ($services as $service => $description) {
    $output = shell_exec("sc query $service 2>&1");
    if (strpos($output, 'RUNNING') !== false) {
        echo "   ✅ $service ($description) - EJECUTÁNDOSE\n";
    } elseif (strpos($output, 'STOPPED') !== false) {
        echo "   ⚠️  $service ($description) - DETENIDO\n";
    } else {
        echo "   ❌ $service ($description) - NO ENCONTRADO\n";
    }
}

// 2. Verificar puertos
echo "\n2. Verificando puertos...\n";
$ports = [1433, 1434];
foreach ($ports as $port) {
    $connection = @fsockopen('localhost', $port, $errno, $errstr, 2);
    if ($connection) {
        echo "   ✅ Puerto $port está abierto\n";
        fclose($connection);
    } else {
        echo "   ❌ Puerto $port no está abierto\n";
    }
}

// 3. Verificar configuración de red
echo "\n3. Verificando configuración de red...\n";
$netstat = shell_exec("netstat -an | findstr :1433");
if ($netstat) {
    echo "   Puerto 1433 está escuchando:\n";
    echo "   $netstat\n";
} else {
    echo "   ❌ Puerto 1433 no está escuchando\n";
}

// 4. Probar diferentes formatos de conexión
echo "\n4. Probando diferentes formatos de conexión...\n";

$connection_strings = [
    'localhost,1433' => 'localhost con puerto',
    '127.0.0.1,1433' => 'IP local con puerto',
    'LAPTOP-7B6GIHB6\\SQLEXPRESS,1433' => 'Nombre de instancia con puerto',
    'LAPTOP-7B6GIHB6\\SQLEXPRESS' => 'Solo nombre de instancia',
    'localhost\\SQLEXPRESS' => 'localhost con instancia',
    '127.0.0.1\\SQLEXPRESS' => 'IP con instancia'
];

foreach ($connection_strings as $server => $description) {
    try {
        $dsn = "sqlsrv:Server=$server;Database=master";
        $pdo = new PDO($dsn, 'sa', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo "   ✅ $description - CONEXIÓN EXITOSA\n";
        $pdo = null;
        break;
    } catch (PDOException $e) {
        echo "   ❌ $description - " . $e->getMessage() . "\n";
    }
}

// 5. Verificar configuración de SQL Server
echo "\n5. Verificando configuración de SQL Server...\n";
echo "   Para verificar la configuración:\n";
echo "   1. Abre SQL Server Configuration Manager\n";
echo "   2. Ve a SQL Server Network Configuration > Protocols for SQLEXPRESS\n";
echo "   3. Asegúrate de que 'TCP/IP' esté habilitado\n";
echo "   4. Click derecho en TCP/IP > Properties > IP Addresses\n";
echo "   5. Verifica que 'IPAll' tenga TCP Port = 1433\n";

// 6. Comandos útiles
echo "\n6. Comandos útiles para solucionar:\n";
echo "   # Iniciar SQL Server Express:\n";
echo "   net start MSSQL$SQLEXPRESS\n\n";
echo "   # Iniciar SQL Server Browser:\n";
echo "   net start SQLBrowser\n\n";
echo "   # Verificar estado:\n";
echo "   sc query MSSQL$SQLEXPRESS\n";
echo "   sc query SQLBrowser\n\n";
echo "   # Conectar con sqlcmd:\n";
echo "   sqlcmd -S LAPTOP-7B6GIHB6\\SQLEXPRESS -E\n";
echo "   sqlcmd -S localhost\\SQLEXPRESS -E\n";

echo "\n</pre>\n";

echo "<h3>🔧 Soluciones más comunes:</h3>\n";
echo "<ul>\n";
echo "<li><strong>Iniciar servicios:</strong> net start MSSQL$SQLEXPRESS</li>\n";
echo "<li><strong>Habilitar TCP/IP:</strong> SQL Server Configuration Manager</li>\n";
echo "<li><strong>Verificar firewall:</strong> Permitir puerto 1433</li>\n";
echo "<li><strong>Usar instancia local:</strong> localhost\\SQLEXPRESS</li>\n";
echo "</ul>\n";
?>
