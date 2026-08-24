<?php
function customer_auth_user($accessToken, $config)
{
    if (!function_exists('curl_init') || !$config['supabase_url'] || !$config['supabase_anon_key'] || !is_string($accessToken) || $accessToken === '') return null;
    $curl = curl_init($config['supabase_url'] . '/auth/v1/user');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'apikey: ' . $config['supabase_anon_key'], 'Authorization: Bearer ' . $accessToken],
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($body === false || $status !== 200) return null;
    $user = json_decode($body, true);
    if (!is_array($user) || !is_string($user['email'] ?? null)) return null;
    $confirmedAt = $user['email_confirmed_at'] ?? $user['confirmed_at'] ?? null;
    return is_string($confirmedAt) && $confirmedAt !== '' ? $user : null;
}

function customer_auth_refresh($refreshToken, $config)
{
    if (!function_exists('curl_init') || !$config['supabase_url'] || !$config['supabase_anon_key'] || !is_string($refreshToken) || $refreshToken === '') return null;
    $curl = curl_init($config['supabase_url'] . '/auth/v1/token?grant_type=refresh_token');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_POSTFIELDS => json_encode(['refresh_token' => $refreshToken]),
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'apikey: ' . $config['supabase_anon_key']],
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($body === false || $status !== 200) return null;
    $session = json_decode($body, true);
    return is_array($session) && is_string($session['access_token'] ?? null) ? $session : null;
}

function customer_auth_cookie($name, $value, $expires)
{
    setcookie($name, $value, ['expires' => $expires, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
}

function customer_auth_clear()
{
    customer_auth_cookie('__Host-bl_customer_access', '', time() - 3600);
    customer_auth_cookie('__Host-bl_customer_refresh', '', time() - 3600);
}

function customer_auth_store($accessToken, $refreshToken, $config)
{
    if (!is_string($accessToken) || strlen($accessToken) > 4096) return null;
    $user = customer_auth_user($accessToken, $config);
    if (!$user) return null;
    customer_auth_cookie('__Host-bl_customer_access', $accessToken, time() + 3600);
    if (is_string($refreshToken) && $refreshToken !== '' && strlen($refreshToken) <= 4096) customer_auth_cookie('__Host-bl_customer_refresh', $refreshToken, time() + 2592000);
    return $user;
}

function customer_auth_session($config)
{
    $accessToken = is_string($_COOKIE['__Host-bl_customer_access'] ?? null) ? $_COOKIE['__Host-bl_customer_access'] : '';
    if (strlen($accessToken) <= 4096 && ($user = customer_auth_user($accessToken, $config))) return $user;

    $refreshToken = is_string($_COOKIE['__Host-bl_customer_refresh'] ?? null) ? $_COOKIE['__Host-bl_customer_refresh'] : '';
    if (strlen($refreshToken) <= 4096 && ($session = customer_auth_refresh($refreshToken, $config))) {
        $user = customer_auth_store($session['access_token'], $session['refresh_token'] ?? $refreshToken, $config);
        if ($user) return $user;
    }
    customer_auth_clear();
    return null;
}
