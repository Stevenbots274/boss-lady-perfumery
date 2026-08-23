<?php
$route = $_GET['route'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$route = is_string($route) ? $route : '/';

if (preg_match('#^/order/([a-fA-F0-9]{64})/?$#', $route, $matches)) {
    $_GET['token'] = $matches[1];
    require __DIR__ . '/../order.php';
    exit;
}

switch ($route) {
    case '/':
    case '/index.php':
        require __DIR__ . '/../index.php';
        break;
    case '/admin.php':
        require __DIR__ . '/../admin.php';
        break;
    case '/order.php':
        require __DIR__ . '/../order.php';
        break;
    case '/api/create-order.php':
        require __DIR__ . '/create-order.php';
        break;
    case '/api/track-order.php':
        require __DIR__ . '/track-order.php';
        break;
    default:
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Not found.';
}
