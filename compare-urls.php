<?php

echo "=== Comparación de URLs ===\n\n";

// URL que funciona (del verify script)
$workingUrl = "https://checkout.wompi.co/p/?public-key=pub_test_0U24tlKbBAfOF2WfvmCasgrDlLjfvS2X&currency=COP&amount-in-cents=15000000&reference=TEST-ORDER-123&signature:integrity=d17c9b237fb6f82ce8446f4edb4ad442b7b624e1d89723876dd574876a33cc7f&redirect-url=https%3A%2F%2Fexample.com%2Fresult&customer-data:email=test%40example.com";

// URL que NO funciona (de tu orden)
$failingUrl = "https://checkout.wompi.co/p/?public-key=pub_test_0U24tlKbBAfOF2WfvmCasgrDlLjfvS2X&currency=COP&amount-in-cents=10709&reference=019ca066-65ec-7192-a1bc-093eeece5924&signature:integrity=01012c0c85e5678c3286119fd7990cd2ba8c59b735981c074d56c81128a05a02&redirect-url=http%3A%2F%2Flocalhost%3A5173%2Forder-confirmation%2F019ca066-65ec-7192-a1bc-093eeece5924&customer-data:email=armandocamposf%40gmail.com&customer-data:full-name=John+Doe";

// Parsear URLs
parse_str(parse_url($workingUrl, PHP_URL_QUERY), $workingParams);
parse_str(parse_url($failingUrl, PHP_URL_QUERY), $failingParams);

echo "Parámetros en URL que FUNCIONA:\n";
foreach ($workingParams as $key => $value) {
    echo "  - $key: $value\n";
}

echo "\nParámetros en URL que NO FUNCIONA:\n";
foreach ($failingParams as $key => $value) {
    echo "  - $key: $value\n";
}

echo "\nDIFERENCIAS:\n";

// Parámetros solo en failing
foreach ($failingParams as $key => $value) {
    if (!isset($workingParams[$key])) {
        echo "  ⚠️  '$key' solo está en la URL que falla\n";
    }
}

// Valores diferentes
foreach ($workingParams as $key => $value) {
    if (isset($failingParams[$key]) && $failingParams[$key] !== $value) {
        echo "  ℹ️  '$key' tiene valores diferentes (esperado)\n";
    }
}

echo "\n=== POSIBLE PROBLEMA ===\n";
echo "La URL que falla tiene 'customer-data:full-name' pero la que funciona NO.\n";
echo "Wompi puede estar rechazando este parámetro adicional.\n\n";

echo "SOLUCIÓN: Intenta sin el parámetro 'customer-data:full-name'\n";
