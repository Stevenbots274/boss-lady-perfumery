<?php
// Boss Lady Perfumery configuration. Set these values as server environment variables.
$supabaseUrl = rtrim(getenv('BL_SUPABASE_URL') ?: '', '/');
$supabaseParts = parse_url($supabaseUrl);
$validSupabaseUrl = is_array($supabaseParts)
    && strtolower($supabaseParts['scheme'] ?? '') === 'https'
    && preg_match('/^[a-z0-9-]+\.supabase\.co$/i', $supabaseParts['host'] ?? '')
    && !isset($supabaseParts['port'])
    && !isset($supabaseParts['query'])
    && !isset($supabaseParts['fragment'])
    && !isset($supabaseParts['user'])
    && !isset($supabaseParts['pass']);
$supabaseKey = trim(getenv('BL_SUPABASE_ANON_KEY') ?: '');
$publicSupabaseKey = strpos($supabaseKey, 'sb_publishable_') === 0;
if (!$publicSupabaseKey && substr_count($supabaseKey, '.') === 2) {
    $keyParts = explode('.', $supabaseKey);
    $encodedPayload = strtr($keyParts[1], '-_', '+/');
    $encodedPayload .= str_repeat('=', (4 - strlen($encodedPayload) % 4) % 4);
    $payload = json_decode(base64_decode($encodedPayload), true);
    $publicSupabaseKey = is_array($payload) && ($payload['role'] ?? '') === 'anon';
}
return [
    'db' => [
        'dsn' => getenv('BL_DB_DSN') ?: 'mysql:host=localhost;dbname=boss_lady;charset=utf8mb4',
        'user' => getenv('BL_DB_USER') ?: '',
        'pass' => getenv('BL_DB_PASSWORD') ?: '',
    ],
    'site_url' => rtrim(getenv('BL_SITE_URL') ?: 'https://YOUR-DOMAIN.com', '/'),
    'whatsapp' => preg_replace('/\D+/', '', getenv('BL_WHATSAPP') ?: '2349067956221'),
    'supabase_url' => $validSupabaseUrl ? $supabaseUrl : '',
    'supabase_anon_key' => $publicSupabaseKey ? $supabaseKey : '',
    'admin_email' => strtolower(trim(getenv('BL_ADMIN_EMAIL') ?: '')),
];
