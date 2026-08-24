<?php
function imagekit_configured($config)
{
    return function_exists('curl_init')
        && $config['imagekit_url_endpoint']
        && $config['imagekit_public_key']
        && $config['imagekit_private_key'];
}

function imagekit_upload_auth($config, $mediaType, $scope = '')
{
    if (!imagekit_configured($config) || !in_array($mediaType, ['image', 'video'], true)) return null;
    $token = bin2hex(random_bytes(16));
    $expire = time() + 600;
    $folder = '/boss-lady/testimonials/' . ($mediaType === 'video' ? 'videos' : 'images');
    if (is_string($scope) && preg_match('/^[a-z0-9-]{1,80}$/', $scope)) $folder .= '/' . $scope;
    return [
        'token' => $token,
        'expire' => $expire,
        'signature' => hash_hmac('sha1', $token . $expire, $config['imagekit_private_key']),
        'public_key' => $config['imagekit_public_key'],
        'folder' => $folder,
        'upload_url' => 'https://upload.imagekit.io/api/v1/files/upload',
    ];
}

function imagekit_request($method, $path, $config)
{
    if (!imagekit_configured($config)) return null;
    $curl = curl_init('https://api.imagekit.io' . $path);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_USERPWD => $config['imagekit_private_key'] . ':',
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    return $body !== false ? ['status' => $status, 'body' => $body] : null;
}

function imagekit_file_details($fileId, $config)
{
    if (!is_string($fileId) || $fileId === '' || strlen($fileId) > 255) return null;
    $response = imagekit_request('GET', '/v1/files/' . rawurlencode($fileId) . '/details', $config);
    if (!$response || $response['status'] !== 200) return null;
    $details = json_decode($response['body'], true);
    return is_array($details) ? $details : null;
}

function imagekit_delete_file($fileId, $config)
{
    if (!is_string($fileId) || $fileId === '' || strlen($fileId) > 255) return false;
    $response = imagekit_request('DELETE', '/v1/files/' . rawurlencode($fileId), $config);
    return $response && in_array($response['status'], [200, 204, 404], true);
}

function imagekit_asset_details($fileId, $mediaType, $config, $expectedFolder = '')
{
    $details = imagekit_file_details($fileId, $config);
    if (!$details || !in_array($mediaType, ['image', 'video'], true)) return null;
    $filePath = '/' . ltrim((string) ($details['filePath'] ?? ''), '/');
    $folder = '/' . trim($expectedFolder ?: '/boss-lady/testimonials/', '/') . '/';
    if (strpos($filePath, $folder) !== 0) return null;

    $mime = strtolower((string) ($details['mime'] ?? $details['metadata']['mime'] ?? ''));
    $fileName = strtolower((string) ($details['name'] ?? $details['filePath'] ?? ''));
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $imageMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $videoMimes = ['video/mp4', 'video/quicktime'];
    $isImage = in_array($mime, $imageMimes, true) || (!$mime && in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true));
    $isVideo = in_array($mime, $videoMimes, true) || (!$mime && in_array($extension, ['mp4', 'mov'], true));
    if (($mediaType === 'image' && !$isImage) || ($mediaType === 'video' && !$isVideo)) return null;

    $size = filter_var($details['size'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $maxSize = $mediaType === 'video' ? 50 * 1024 * 1024 : 10 * 1024 * 1024;
    if ($size === false || $size > $maxSize) return null;

    $duration = null;
    if ($mediaType === 'video') {
        $durationValue = $details['duration'] ?? $details['metadata']['duration'] ?? $details['metadata']['videoDuration'] ?? null;
        if (!is_numeric($durationValue) || (float) $durationValue <= 0 || (float) $durationValue > 60) return null;
        $duration = round((float) $durationValue, 3);
    }

    $endpointHost = parse_url($config['imagekit_url_endpoint'], PHP_URL_HOST);
    $url = (string) ($details['url'] ?? '');
    $thumbnail = (string) ($details['thumbnailUrl'] ?? '');
    if (!imagekit_public_url($url, $endpointHost)) return null;
    if ($thumbnail !== '' && !imagekit_public_url($thumbnail, $endpointHost)) $thumbnail = '';
    return [
        'file_id' => (string) ($details['fileId'] ?? $fileId),
        'url' => $url,
        'thumbnail_url' => $thumbnail ?: ($mediaType === 'image' ? $url : null),
        'file_size' => (int) $size,
        'duration_seconds' => $duration,
        'mime_type' => $mime ?: ($mediaType === 'image' ? 'image/' . $extension : 'video/' . $extension),
        'media_type' => $mediaType,
    ];
}

function imagekit_public_url($url, $expectedHost)
{
    $parts = parse_url($url);
    return is_array($parts)
        && strtolower($parts['scheme'] ?? '') === 'https'
        && $expectedHost
        && strcasecmp($parts['host'] ?? '', $expectedHost) === 0
        && !isset($parts['user'])
        && !isset($parts['pass']);
}
