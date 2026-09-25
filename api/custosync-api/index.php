<?php

// ─── CORS Headers ────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ─── Database Config ─────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'CustoSync_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

// ─── Database Connection ─────────────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit();
        }
    }
    return $pdo;
}

// ─── Response Helpers ────────────────────────────────────────────────────────
function respond(mixed $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function respondError(string $message, int $status = 400): void {
    respond(['error' => $message], $status);
}

// ─── Router ──────────────────────────────────────────────────────────────────
$method     = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath   = '/custosync-api';
$path       = str_replace($basePath, '', $requestUri);
$path       = rtrim($path, '/') ?: '/';
$segments   = explode('/', ltrim($path, '/'));

// Dispatch: /customers or /customers/{id}
if ($segments[0] === 'customers') {
    $id = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;

    switch ($method) {
        case 'GET':
            if ($id !== null) {
                showCustomer($id);
            } else {
                listCustomers();
            }
            break;
        case 'POST':
            createCustomer();
            break;
        case 'PUT':
        case 'PATCH':
            if ($id !== null) {
                updateCustomer($id);
            } else {
                respondError('ID required for update', 400);
            }
            break;
        case 'DELETE':
            if ($id !== null) {
                deleteCustomer($id);
            } else {
                respondError('ID required for delete', 400);
            }
            break;
        default:
            respondError('Method not allowed', 405);
    }
} else {
    respondError('Not found', 404);
}

// ─── Handlers ────────────────────────────────────────────────────────────────

function listCustomers(): void {
    $db     = getDB();
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $db->prepare(
            'SELECT * FROM customers
             WHERE first_name LIKE :q OR last_name LIKE :q2 OR email LIKE :q3
             ORDER BY id DESC'
        );
        $stmt->execute([':q' => $like, ':q2' => $like, ':q3' => $like]);
    } else {
        $stmt = $db->query('SELECT * FROM customers ORDER BY id DESC');
    }

    $customers = $stmt->fetchAll();
    respond(['data' => $customers]);
}

function showCustomer(int $id): void {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        respondError('Customer not found', 404);
    }

    respond(['data' => $customer]);
}

function createCustomer(): void {
    $body = json_decode(file_get_contents('php://input'), true);

    $firstName     = trim($body['first_name'] ?? '');
    $lastName      = trim($body['last_name'] ?? '');
    $email         = trim($body['email'] ?? '');
    $contactNumber = trim($body['contact_number'] ?? '');

    if (!$firstName || !$lastName || !$email) {
        respondError('first_name, last_name, and email are required', 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respondError('Invalid email address', 422);
    }

    $db = getDB();

    // Check duplicate email
    $check = $db->prepare('SELECT id FROM customers WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        respondError('Email already exists', 422);
    }

    $stmt = $db->prepare(
        'INSERT INTO customers (first_name, last_name, email, contact_number) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$firstName, $lastName, $email, $contactNumber ?: null]);
    $newId = (int)$db->lastInsertId();

    showCustomer($newId);
}

function updateCustomer(int $id): void {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        respondError('Customer not found', 404);
    }

    $body = json_decode(file_get_contents('php://input'), true);

    $firstName     = trim($body['first_name'] ?? $existing['first_name']);
    $lastName      = trim($body['last_name'] ?? $existing['last_name']);
    $email         = trim($body['email'] ?? $existing['email']);
    $contactNumber = trim($body['contact_number'] ?? $existing['contact_number'] ?? '');

    if (!$firstName || !$lastName || !$email) {
        respondError('first_name, last_name, and email are required', 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respondError('Invalid email address', 422);
    }

    // Check duplicate email (excluding current)
    $check = $db->prepare('SELECT id FROM customers WHERE email = ? AND id != ?');
    $check->execute([$email, $id]);
    if ($check->fetch()) {
        respondError('Email already exists', 422);
    }

    $update = $db->prepare(
        'UPDATE customers SET first_name = ?, last_name = ?, email = ?, contact_number = ? WHERE id = ?'
    );
    $update->execute([$firstName, $lastName, $email, $contactNumber ?: null, $id]);

    showCustomer($id);
}

function deleteCustomer(int $id): void {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM customers WHERE id = ?');
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        respondError('Customer not found', 404);
    }

    $db->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
    http_response_code(204);
    exit();
}
