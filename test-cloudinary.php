<?php

/**
 * Script de prueba para verificar la configuración de Cloudinary
 * 
 * Ejecutar con: php test-cloudinary.php
 */

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Verificación de Configuración de Cloudinary ===\n\n";

// Verificar variables de entorno
$cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? null;
$apiKey = $_ENV['CLOUDINARY_API_KEY'] ?? null;
$apiSecret = $_ENV['CLOUDINARY_API_SECRET'] ?? null;
$cloudinaryUrl = $_ENV['CLOUDINARY_URL'] ?? null;

echo "Cloud Name: " . ($cloudName ? "✓ Configurado ($cloudName)" : "✗ No configurado") . "\n";
echo "API Key: " . ($apiKey ? "✓ Configurado (" . substr($apiKey, 0, 10) . "...)" : "✗ No configurado") . "\n";
echo "API Secret: " . ($apiSecret ? "✓ Configurado (" . substr($apiSecret, 0, 10) . "...)" : "✗ No configurado") . "\n";
echo "Cloudinary URL: " . ($cloudinaryUrl ? "✓ Configurado" : "✗ No configurado") . "\n\n";

if (!$cloudName || !$apiKey || !$apiSecret) {
    echo "❌ Error: Faltan credenciales de Cloudinary en el archivo .env\n";
    echo "\nAgrega las siguientes variables a tu archivo .env:\n";
    echo "CLOUDINARY_CLOUD_NAME=tu_cloud_name\n";
    echo "CLOUDINARY_API_KEY=tu_api_key\n";
    echo "CLOUDINARY_API_SECRET=tu_api_secret\n";
    echo "CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name\n";
    exit(1);
}

echo "✅ Todas las credenciales están configuradas correctamente\n\n";

// Verificar que el paquete esté instalado
if (class_exists('CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary')) {
    echo "✅ Paquete cloudinary-labs/cloudinary-laravel instalado correctamente\n";
} else {
    echo "❌ Error: El paquete cloudinary-labs/cloudinary-laravel no está instalado\n";
    echo "Ejecuta: composer require cloudinary-labs/cloudinary-laravel\n";
    exit(1);
}

echo "\n=== Configuración Completa ===\n";
echo "El endpoint de subida de imágenes está listo para usar:\n";
echo "POST /api/images/upload\n";
echo "POST /api/images/delete\n";
echo "POST /api/images/optimize\n";
