<?php

/**
 * Script para descargar y configurar certificados SSL en Laragon
 * 
 * Ejecutar con: php fix-ssl-certificates.php
 */

echo "=== Fix SSL Certificates para Cloudinary ===\n\n";

// Detectar el directorio de Laragon
$larag onDir = 'C:\\laragon';
$phpVersion = PHP_VERSION;
$phpMajorMinor = substr($phpVersion, 0, 3); // e.g., "8.2"

// Buscar el directorio de PHP
$phpDir = "{$laragonDir}\\bin\\php\\php-{$phpVersion}";
if (!is_dir($phpDir)) {
    // Intentar con versión corta
    $phpDir = "{$laragonDir}\\bin\\php\\php-{$phpMajorMinor}";
}

if (!is_dir($phpDir)) {
    echo "❌ No se pudo encontrar el directorio de PHP en Laragon\n";
    echo "Buscado en: {$laragonDir}\\bin\\php\\\n";
    exit(1);
}

echo "✓ PHP encontrado en: {$phpDir}\n";

// Crear directorio para certificados si no existe
$sslDir = "{$laragonDir}\\etc\\ssl";
if (!is_dir($sslDir)) {
    mkdir($sslDir, 0755, true);
    echo "✓ Directorio SSL creado: {$sslDir}\n";
}

// Descargar cacert.pem
$cacertUrl = 'https://curl.se/ca/cacert.pem';
$cacertPath = "{$sslDir}\\cacert.pem";

echo "\nDescargando certificados CA...\n";

// Deshabilitar verificación SSL temporalmente para descargar el certificado
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

$cacertContent = @file_get_contents($cacertUrl, false, $context);

if ($cacertContent === false) {
    echo "❌ Error al descargar certificados desde {$cacertUrl}\n";
    echo "Intenta descargar manualmente desde: https://curl.se/ca/cacert.pem\n";
    exit(1);
}

file_put_contents($cacertPath, $cacertContent);
echo "✓ Certificados descargados: {$cacertPath}\n";

// Leer php.ini
$phpIniPath = "{$phpDir}\\php.ini";
if (!file_exists($phpIniPath)) {
    echo "❌ No se encontró php.ini en: {$phpIniPath}\n";
    exit(1);
}

echo "\nActualizando php.ini...\n";

$phpIni = file_get_contents($phpIniPath);
$modified = false;

// Configurar curl.cainfo
if (strpos($phpIni, 'curl.cainfo') === false) {
    $phpIni .= "\n; Configuración de certificados SSL para cURL\n";
    $phpIni .= "curl.cainfo = \"{$cacertPath}\"\n";
    $modified = true;
    echo "✓ Agregada configuración curl.cainfo\n";
} else {
    // Reemplazar línea existente
    $phpIni = preg_replace(
        '/;?\s*curl\.cainfo\s*=.*$/m',
        "curl.cainfo = \"{$cacertPath}\"",
        $phpIni
    );
    $modified = true;
    echo "✓ Actualizada configuración curl.cainfo\n";
}

// Configurar openssl.cafile
if (strpos($phpIni, 'openssl.cafile') === false) {
    $phpIni .= "openssl.cafile = \"{$cacertPath}\"\n";
    $modified = true;
    echo "✓ Agregada configuración openssl.cafile\n";
} else {
    $phpIni = preg_replace(
        '/;?\s*openssl\.cafile\s*=.*$/m',
        "openssl.cafile = \"{$cacertPath}\"",
        $phpIni
    );
    $modified = true;
    echo "✓ Actualizada configuración openssl.cafile\n";
}

if ($modified) {
    // Hacer backup del php.ini original
    $backupPath = "{$phpIniPath}.backup." . date('Y-m-d_H-i-s');
    copy($phpIniPath, $backupPath);
    echo "✓ Backup creado: {$backupPath}\n";
    
    // Guardar php.ini modificado
    file_put_contents($phpIniPath, $phpIni);
    echo "✓ php.ini actualizado\n";
}

echo "\n=== Configuración Completada ===\n\n";
echo "IMPORTANTE: Debes reiniciar Laragon para que los cambios surtan efecto.\n\n";
echo "Pasos siguientes:\n";
echo "1. Detén todos los servicios en Laragon\n";
echo "2. Cierra Laragon completamente\n";
echo "3. Abre Laragon nuevamente\n";
echo "4. Inicia Apache y MySQL\n";
echo "5. Prueba el endpoint de subida de imágenes\n\n";

echo "Para verificar la configuración, ejecuta:\n";
echo "  php -i | findstr \"curl.cainfo\"\n";
echo "  php -i | findstr \"openssl.cafile\"\n\n";

echo "✅ Script completado exitosamente\n";
