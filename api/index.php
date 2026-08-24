<?php
$route = $_GET['route'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$route = is_string($route) ? $route : '/';

if (preg_match('#^/order/([a-fA-F0-9]{64})/?$#', $route, $matches)) {
    $_GET['token'] = $matches[1];
    require __DIR__ . '/../order.php';
    exit;
}

if ($route === '/__blocked') {
    require __DIR__ . '/../not-found.php';
    exit;
}

switch ($route) {
    case '/':
    case '/index.php':
        require __DIR__ . '/../index.php';
        break;
    case '/admin':
    case '/admin.php':
        require __DIR__ . '/../admin.php';
        break;
    case '/admin/products':
        require __DIR__ . '/../admin-products.php';
        break;
    case '/admin/archive':
        require __DIR__ . '/../admin-archive.php';
        break;
    case '/admin/orders':
        require __DIR__ . '/../admin-orders.php';
        break;
    case '/admin/insights':
        require __DIR__ . '/../admin-insights.php';
        break;
    case '/admin/settings':
        require __DIR__ . '/../admin-settings.php';
        break;
    case '/shop':
        require __DIR__ . '/../shop.php';
        break;
    case '/about':
        require __DIR__ . '/../about.php';
        break;
    case '/finder':
        require __DIR__ . '/../finder.php';
        break;
    case '/faq':
        require __DIR__ . '/../faq.php';
        break;
    case '/track':
        require __DIR__ . '/../track.php';
        break;
    case '/privacy':
        require __DIR__ . '/../privacy.php';
        break;
    case '/terms':
        require __DIR__ . '/../terms.php';
        break;
    case '/404':
        require __DIR__ . '/../not-found.php';
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
        require __DIR__ . '/../not-found.php';
}
