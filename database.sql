-- ============================================
-- SALON APPOINTMENT AND CUSTOMER SERVICE SYSTEM
-- Database: salon_db
-- ============================================

CREATE DATABASE IF NOT EXISTS salon_db;
USE salon_db;

-- ===== USERS TABLE =====
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','staff','customer') NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===== SERVICES TABLE =====
CREATE TABLE IF NOT EXISTS services (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('In-Salon','Home Service','Both') NOT NULL DEFAULT 'In-Salon',
    price DECIMAL(10,2) NOT NULL,
    duration INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===== APPOINTMENTS TABLE =====
CREATE TABLE IF NOT EXISTS appointments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    customer_id INT(11) NOT NULL,
    service_id INT(11) NOT NULL,
    staff_id INT(11),
    service_type ENUM('In-Salon','Home Service') NOT NULL DEFAULT 'In-Salon',
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id)  REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id)    REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Default password for all demo accounts: salon123
-- (hashed using password_hash in PHP — below is the pre-hashed value)
-- To regenerate: echo password_hash('salon123', PASSWORD_DEFAULT);
-- Using a fixed hash for demo purposes:

INSERT INTO users (name, email, password, role, phone) VALUES
('Admin User',     'admin@salon.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',    '09100000001'),
('Ana Dela Cruz',  'ana@salon.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff',    '09100000002'),
('Maria Cruz',     'maria@salon.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff',    '09100000003'),
('Carla Bautista', 'carla@salon.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff',    '09100000004'),
('Joy Santos',     'joy@salon.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff',    '09100000005'),
('Maria Santos',   'maria_s@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '09200000001'),
('Jose Reyes',     'jose@email.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '09200000002');

INSERT INTO services (name, type, price, duration) VALUES
('Basic Haircut',      'Both',         250.00,  30),
('Hair Coloring',      'In-Salon',     800.00, 120),
('Manicure',           'Both',         180.00,  60),
('Pedicure',           'Both',         200.00,  60),
('Facial',             'In-Salon',     550.00,  90),
('Hair Treatment',     'In-Salon',     650.00,  75),
('Blowdry',            'In-Salon',     200.00,  40),
('Eyebrow Threading',  'Both',          80.00,  20),
('Home Massage',       'Home Service', 900.00,  60);

INSERT INTO appointments (customer_id, service_id, staff_id, service_type, appointment_date, appointment_time, status) VALUES
(6, 1, 2, 'In-Salon',     '2026-04-10', '14:00:00', 'confirmed'),
(7, 2, 3, 'In-Salon',     '2026-04-10', '16:00:00', 'pending'),
(6, 3, 4, 'Home Service', '2026-04-11', '10:00:00', 'confirmed'),
(7, 5, 5, 'In-Salon',     '2026-04-11', '13:00:00', 'pending'),
(6, 4, 4, 'Home Service', '2026-04-12', '09:00:00', 'completed'),
(7, 7, 2, 'In-Salon',     '2026-04-12', '15:00:00', 'cancelled'),
(6, 6, 3, 'In-Salon',     '2026-04-13', '11:00:00', 'pending'),
(7, 1, 2, 'In-Salon',     '2026-04-13', '14:00:00', 'confirmed');
