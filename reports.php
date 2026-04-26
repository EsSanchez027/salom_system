<?php
require 'db.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? 'appointments';
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

try {

    /* ── APPOINTMENTS REPORT  */
    if ($type === 'appointments') {
        $stmt = $pdo->prepare("
            SELECT
                a.id,
                u.name                          AS customer,
                s.name                          AS service,
                a.service_type,
                a.appointment_date,
                a.appointment_time,
                a.status,
                COALESCE(st.name, 'Not Assigned') AS staff
            FROM appointments a
            JOIN  users    u  ON a.customer_id = u.id
            JOIN  services s  ON a.service_id  = s.id
            LEFT  JOIN users st ON a.staff_id  = st.id
            WHERE a.appointment_date BETWEEN ? AND ?
            ORDER BY a.appointment_date, a.appointment_time
        ");
        $stmt->execute([$from, $to]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    /* ── SERVICES REPORT ─────────────────────────────────────────── */
    } elseif ($type === 'services') {
        $stmt = $pdo->prepare("
            SELECT
                s.name,
                s.type,
                s.price,
                s.duration,
                COUNT(a.id)                                              AS total_bookings,
                SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN a.status = 'pending'   THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN a.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed
            FROM services s
            LEFT JOIN appointments a
                ON s.id = a.service_id
               AND a.appointment_date BETWEEN ? AND ?
            GROUP BY s.id
            ORDER BY total_bookings DESC
        ");
        $stmt->execute([$from, $to]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    /* ── SUMMARY / DASHBOARD REPORT ─────────────────────────────── */
    } elseif ($type === 'summary') {

        // Counts per status
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) AS total
            FROM appointments
            WHERE appointment_date BETWEEN ? AND ?
            GROUP BY status
        ");
        $stmt->execute([$from, $to]);
        $byStatus = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $byStatus[$row['status']] = (int)$row['total'];
        }

        // Revenue from completed appointments
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(s.price), 0) AS revenue
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.status = 'completed'
              AND a.appointment_date BETWEEN ? AND ?
        ");
        $stmt->execute([$from, $to]);
        $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

        // Top 5 services by bookings
        $stmt = $pdo->prepare("
            SELECT s.name,
                   COUNT(a.id) AS bookings,
                   SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.appointment_date BETWEEN ? AND ?
            GROUP BY s.id
            ORDER BY bookings DESC
            LIMIT 5
        ");
        $stmt->execute([$from, $to]);
        $topServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Monthly breakdown
        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(appointment_date, '%b %Y') AS month,
                COUNT(*)                                AS total,
                SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled
            FROM appointments
            WHERE appointment_date BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
            ORDER BY MIN(appointment_date)
        ");
        $stmt->execute([$from, $to]);
        $monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success'     => true,
            'byStatus'    => $byStatus,
            'revenue'     => $revenue,
            'topServices' => $topServices,
            'monthly'     => $monthly
        ]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid report type.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}