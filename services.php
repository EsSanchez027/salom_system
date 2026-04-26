<?php
// api/services.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// ===== GET =====
if ($method === 'GET') {
    $type = isset($_GET['type']) ? $_GET['type'] : '';

    if ($type && $type !== '') {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE type = ? OR type = 'Both' ORDER BY name");
        $stmt->execute([$type]);
    } else {
        $stmt = $pdo->query("SELECT * FROM services ORDER BY name");
    }

    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ===== POST — add service =====
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name']);
    $type = $data['type'];
    $price = floatval($data['price']);
    $duration = intval($data['duration']);

    if (!$name || !$price || !$duration) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO services (name, type, price, duration) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $type, $price, $duration]);

    echo json_encode(['success' => true, 'message' => 'Service added.']);
    exit;
}

// ===== PUT — update service =====
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $name = trim($data['name']);
    $type = $data['type'];
    $price = floatval($data['price']);
    $duration = intval($data['duration']);

    if (!$name || !$price || !$duration) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE services SET name=?, type=?, price=?, duration=? WHERE id=?");
    $stmt->execute([$name, $type, $price, $duration, $id]);

    echo json_encode(['success' => true, 'message' => 'Service updated.']);
    exit;
}

// ===== DELETE =====
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];

    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Service deleted.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
