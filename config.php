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
$dbDsn = trim(getenv('BL_DB_DSN') ?: 'pgsql:host=aws-1-eu-west-1.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require');
$dbUser = getenv('BL_DB_USER') ?: '';
$adminEmail = strtolower(trim(getenv('BL_ADMIN_EMAIL') ?: ''));
return [
    'db' => [
        'dsn' => $dbDsn,
        'user' => $dbUser,
        'pass' => getenv('BL_DB_PASSWORD') ?: '',
    ],
    'site_url' => rtrim(getenv('BL_SITE_URL') ?: 'https://YOUR-DOMAIN.com', '/'),
    'whatsapp' => preg_replace('/\D+/', '', getenv('BL_WHATSAPP') ?: '2349067956221'),
    'whatsapp_display' => trim(getenv('BL_WHATSAPP_DISPLAY') ?: '0906 795 6221'),
    'call_display' => trim(getenv('BL_CALL_DISPLAY') ?: '0703 234 8639'),
    'supabase_url' => $validSupabaseUrl ? $supabaseUrl : '',
    'supabase_anon_key' => $publicSupabaseKey ? $supabaseKey : '',
    'admin_email' => $adminEmail,
];
