<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Barang.php';
require_once __DIR__ . '/../app/controllers/KasirController.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Metode permintaan tidak didukung.');
}

try {
    $connection = databaseConnection();
} catch (PDOException $exception) {
    http_response_code(503);
    require __DIR__ . '/../app/views/errors/database.php';
    exit;
}

$controller = new KasirController(new Barang($connection));
$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if ($method === 'POST') {
    $controller->proses($_POST, $isAjax);
    exit;
}

$controller->tampilkan();
