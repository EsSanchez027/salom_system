<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

//GET
if ($method === 'GET') {
    $role = isset($_GET['role']) ? $_GET['role'] : '';

    if ($role) {
        $stmt = $pdo->prepare("SELECT id, name, email, role, phone, created_at FROM users WHERE role = ? ORDER BY name");
        $stmt->execute([$role]);
    } else {
        $stmt = $pdo->query("SELECT id, name, email, role, phone, created_at FROM users ORDER BY role, name");
    }

    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

//POST — add user
if ($method === 'POST') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $name  = trim($data['name']);
    $email = trim($data['email']);
    $role  = $data['role'];
    $phone = trim($data['phone']);
    $pass  = isset($data['password']) && $data['password'] ? $data['password'] : 'salon123';

    if (!$name || !$email || !$role) {
        echo json_encode(['success' => false, 'message' => 'Name, email, and role are required.']);
        exit;
    }

    // Check if email already exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists.']);
        exit;
    }

    $hashed = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hashed, $role, $phone]);

    echo json_encode(['success' => true, 'message' => 'User added. Default password: ' . $pass]);
    exit;
}

//PUT — update user
if ($method === 'PUT') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $id    = $data['id'];
    $name  = trim($data['name']);
    $email = trim($data['email']);
    $role  = $data['role'];
    $phone = trim($data['phone']);

    if (!$name || !$email) {
        echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, phone=? WHERE id=?");
    $stmt->execute([$name, $email, $role, $phone, $id]);

    echo json_encode(['success' => true, 'message' => 'User updated.']);
    exit;
}

//DELETE
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = $data['id'];

    // Prevent deleting yourself
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'User deleted.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
