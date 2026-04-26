<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// ===== GET =====
if ($method === 'GET') {
    $role     = isset($_GET['role'])     ? $_GET['role']     : '';
    $user_id  = isset($_GET['user_id'])  ? $_GET['user_id']  : '';
    $staff_id = isset($_GET['staff_id']) ? $_GET['staff_id'] : '';

    $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.service_type,
                   a.status, a.created_at,
                   c.name AS customer, c.id AS customer_id,
                   s.name AS service, s.price,
                   st.name AS staff, st.id AS staff_id
            FROM appointments a
            JOIN users c    ON a.customer_id = c.id
            JOIN services s ON a.service_id  = s.id
            LEFT JOIN users st ON a.staff_id = st.id";

    $params = [];
    if ($role === 'customer' && $user_id) {
        $sql .= " WHERE a.customer_id = ?";
        $params[] = $user_id;
    } elseif ($role === 'staff' && $staff_id) {
        $sql .= " WHERE a.staff_id = ?";
        $params[] = $staff_id;
    }

    $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ===== POST — create appointment =====
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $customer_id = $data['customer_id'];
    $service_id  = $data['service_id'];
    $staff_id    = !empty($data['staff_id']) ? $data['staff_id'] : null;
    $svc_type    = $data['service_type'];
    $date        = $data['date'];
    $time        = $data['time'];

    // ===== CASE 1: Specific staff selected — check double booking =====
    if ($staff_id) {
        $check = $pdo->prepare("SELECT id FROM appointments
                                WHERE staff_id = ? 
                                AND appointment_date = ? 
                                AND appointment_time = ?
                                AND status NOT IN ('cancelled')");
        $check->execute([$staff_id, $date, $time]);
        if ($check->fetch()) {
            // Get staff name for better error message
            $staffName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $staffName->execute([$staff_id]);
            $sname = $staffName->fetchColumn();
            echo json_encode([
                'success' => false,
                'message' => $sname . ' is already booked on that date and time. Please choose a different time or staff.'
            ]);
            exit;
        }
    }

    // ===== CASE 2: Any Available Staff — auto-assign =====
    if (!$staff_id) {
        // Get all staff
        $allStaff = $pdo->prepare("SELECT id FROM users WHERE role = 'staff'");
        $allStaff->execute();
        $staffList = $allStaff->fetchAll(PDO::FETCH_COLUMN);

        if (empty($staffList)) {
            echo json_encode(['success' => false, 'message' => 'No staff available. Please contact the salon.']);
            exit;
        }

        // Find staff NOT booked at that date+time
        $busyStmt = $pdo->prepare("SELECT DISTINCT staff_id FROM appointments
                                   WHERE appointment_date = ? 
                                   AND appointment_time = ?
                                   AND status NOT IN ('cancelled')
                                   AND staff_id IS NOT NULL");
        $busyStmt->execute([$date, $time]);
        $busyStaff = $busyStmt->fetchAll(PDO::FETCH_COLUMN);

        $availableStaff = array_diff($staffList, $busyStaff);

        if (empty($availableStaff)) {
            echo json_encode([
                'success' => false,
                'message' => 'No staff available at ' . $time . ' on ' . $date . '. Please choose a different time.'
            ]);
            exit;
        }

        // Pick a random available staff
        $staff_id = $availableStaff[array_rand($availableStaff)];
    }

    // ===== INSERT appointment =====
    $stmt = $pdo->prepare("INSERT INTO appointments
                            (customer_id, service_id, staff_id, service_type, appointment_date, appointment_time, status)
                            VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$customer_id, $service_id, $staff_id, $svc_type, $date, $time]);

    // Get assigned staff name for confirmation
    $staffNameStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $staffNameStmt->execute([$staff_id]);
    $assignedName = $staffNameStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully! Assigned staff: ' . $assignedName
    ]);
    exit;
}

// ===== PUT — update status =====
if ($method === 'PUT') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $id     = $data['id'];
    $status = $data['status'];

    $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!in_array($status, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    echo json_encode(['success' => true, 'message' => 'Status updated.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
