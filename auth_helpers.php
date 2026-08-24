<?php
function supabase_user($accessToken, $config)
{
    if (!function_exists('curl_init') || !$config['supabase_url'] || !$config['supabase_anon_key']) return null;
    $curl = curl_init($config['supabase_url'] . '/auth/v1/user');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10, CURLOPT_HTTPHEADER => ['Accept: application/json', 'apikey: ' . $config['supabase_anon_key'], 'Authorization: Bearer ' . $accessToken]]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($body === false || $status !== 200) return null;
    $user = json_decode($body, true);
    return is_array($user) ? $user : null;
}
