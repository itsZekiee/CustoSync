<?php

// ─── CORS Headers ────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
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

// ─── PDO Connection ───────────────────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            json_out(['error' => 'DB error: ' . $e->getMessage()], 500);
        }
    }
    return $pdo;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function json_out(mixed $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// ─── Dispatch ─────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$search = trim($_GET['search'] ?? '');

match(true) {
    $method === 'GET'    && $id === null => list_customers($search),
    $method === 'GET'    && $id !== null => show_customer($id),
    $method === 'POST'                   => create_customer(),
    ($method === 'PUT' || $method === 'PATCH') && $id !== null => update_customer($id),
    $method === 'DELETE' && $id !== null => delete_customer($id),
    default => json_out(['error' => 'Not found'], 404),
};

// ─── Handlers ─────────────────────────────────────────────────────────────────
function list_customers(string $search): void {
    $db = db();
    if ($search) {
        $like = '%' . $search . '%';
        $q = $db->prepare('SELECT * FROM customers WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? ORDER BY id DESC');
        $q->execute([$like, $like, $like]);
    } else {
        $q = $db->query('SELECT * FROM customers ORDER BY id DESC');
    }
    json_out(['data' => $q->fetchAll()]);
}

function show_customer(int $id): void {
    $q = db()->prepare('SELECT * FROM customers WHERE id = ?');
    $q->execute([$id]);
    $row = $q->fetch();
    $row ? json_out(['data' => $row]) : json_out(['error' => 'Not found'], 404);
}

function create_customer(): void {
    $b = body();
    $fn = trim($b['first_name'] ?? '');
    $ln = trim($b['last_name']  ?? '');
    $em = trim($b['email']      ?? '');
    $cn = trim($b['contact_number'] ?? '');

    if (!$fn || !$ln || !$em) json_out(['error' => 'first_name, last_name, email are required'], 422);
    if (!filter_var($em, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'Invalid email'], 422);

    $db = db();
    $ck = $db->prepare('SELECT id FROM customers WHERE email = ?');
    $ck->execute([$em]);
    if ($ck->fetch()) json_out(['error' => 'Email already exists'], 422);

    $ins = $db->prepare('INSERT INTO customers (first_name,last_name,email,contact_number) VALUES(?,?,?,?)');
    $ins->execute([$fn, $ln, $em, $cn ?: null]);
    show_customer((int)$db->lastInsertId());
}

function update_customer(int $id): void {
    $db = db();
    $q  = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $q->execute([$id]);
    $ex = $q->fetch();
    if (!$ex) json_out(['error' => 'Not found'], 404);

    $b  = body();
    $fn = trim($b['first_name']     ?? $ex['first_name']);
    $ln = trim($b['last_name']      ?? $ex['last_name']);
    $em = trim($b['email']          ?? $ex['email']);
    $cn = trim($b['contact_number'] ?? $ex['contact_number'] ?? '');

    if (!$fn || !$ln || !$em) json_out(['error' => 'first_name, last_name, email are required'], 422);
    if (!filter_var($em, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'Invalid email'], 422);

    $ck = $db->prepare('SELECT id FROM customers WHERE email = ? AND id != ?');
    $ck->execute([$em, $id]);
    if ($ck->fetch()) json_out(['error' => 'Email already exists'], 422);

    $db->prepare('UPDATE customers SET first_name=?,last_name=?,email=?,contact_number=? WHERE id=?')
       ->execute([$fn, $ln, $em, $cn ?: null, $id]);

    show_customer($id);
}

function delete_customer(int $id): void {
    $db = db();
    $q  = $db->prepare('SELECT id FROM customers WHERE id = ?');
    $q->execute([$id]);
    if (!$q->fetch()) json_out(['error' => 'Not found'], 404);
    $db->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
    http_response_code(204); exit();
}
