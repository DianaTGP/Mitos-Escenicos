<?php
/**
 * Helper para llamadas a la API REST de Openpay (México).
 * Autenticación: HTTP Basic con la llave privada como usuario (sin contraseña).
 */
declare(strict_types=1);

if (!defined('MITOS_APP')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

function openpay_request(string $method, string $path, array $body = []): array
{
    $merchantId = OPENPAY_MERCHANT_ID;
    $privateKey = OPENPAY_PRIVATE_KEY;
    $baseUrl = OPENPAY_SANDBOX ? 'https://sandbox-api.openpay.mx/v1' : 'https://api.openpay.mx/v1';
    $url = $baseUrl . '/' . ltrim($path, '/');

    $opts = [
        'http' => [
            'method'  => $method,
            'header'  => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($privateKey . ':'),
            ],
            'ignore_errors' => true,
        ],
    ];
    if ($body !== [] && in_array($method, ['POST', 'PUT'], true)) {
        $opts['http']['content'] = json_encode($body);
    }

    $ctx = stream_context_create($opts);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        return ['error' => 'No se pudo conectar con Openpay'];
    }
    $decoded = json_decode($response, true);
    return $decoded ?: ['error' => 'Respuesta inválida'];
}

/**
 * Crear cargo con token (tarjeta nueva).
 */
function openpay_charge_token(string $tokenId, float $amount, string $description, string $deviceSessionId = ''): array
{
    $merchantId = OPENPAY_MERCHANT_ID;
    $body = [
        'source_id'       => $tokenId,
        'method'          => 'card',
        'amount'          => round($amount, 2),
        'currency'        => 'MXN',
        'description'     => $description,
    ];
    if ($deviceSessionId !== '') {
        $body['device_session_id'] = $deviceSessionId;
    }
    return openpay_request('POST', '/' . $merchantId . '/charges', $body);
}

/**
 * Crear cargo con tarjeta guardada (customer + card).
 */
function openpay_charge_saved_card(string $customerId, string $cardId, float $amount, string $description): array
{
    $merchantId = OPENPAY_MERCHANT_ID;
    $body = [
        'source_id'   => $cardId,
        'method'      => 'card',
        'amount'      => round($amount, 2),
        'currency'    => 'MXN',
        'description' => $description,
    ];
    return openpay_request('POST', '/' . $merchantId . '/customers/' . $customerId . '/charges', $body);
}

/**
 * Crear cliente en Openpay (para guardar tarjetas).
 */
function openpay_create_customer(string $nombre, string $email): array
{
    $merchantId = OPENPAY_MERCHANT_ID;
    $body = [
        'name'             => $nombre,
        'email'            => $email,
        'requires_account' => false,
    ];
    return openpay_request('POST', '/' . $merchantId . '/customers', $body);
}

/**
 * Guardar tarjeta para un cliente (usando token de una transacción).
 * Openpay: después de un cargo exitoso se puede guardar la tarjeta al cliente.
 * Ver documentación "Guardar tarjeta" - a veces se hace con el token creando la tarjeta en el customer.
 */
function openpay_save_card_for_customer(string $customerId, string $tokenId): array
{
    $merchantId = OPENPAY_MERCHANT_ID;
    $body = [
        'token_id' => $tokenId,
    ];
    return openpay_request('POST', '/' . $merchantId . '/customers/' . $customerId . '/cards', $body);
}
