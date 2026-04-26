<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data  = json_decode(file_get_contents('php://input'), true);
$name  = isset($data['name'])     ? trim($data['name'])     : '';
$email = isset($data['email'])    ? trim($data['email'])    : '';
$pass  = isset($data['password']) ? $data['password']       : '';
$phone = isset($data['phone'])    ? trim($data['phone'])    : '';
$role  = isset($data['role'])     ? trim($data['role'])     : 'customer';

// Only allow customer or staff — never admin via registration
$allowed_roles = ['customer', 'staff'];
if (!in_array($role, $allowed_roles)) {
    $role = 'customer';
}

// Validation
if (!$name || !$email || !$pass) {
    echo json_encode(['success' => false, 'message' => 'Name, email, and password are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

if (strlen($pass) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email is already registered.']);
    exit;
}

// Insert new user as customer
$hashed = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$name, $email, $hashed, $role, $phone]);

echo json_encode(['success' => true, 'message' => 'Account created successfully! You can now login.']);
?>