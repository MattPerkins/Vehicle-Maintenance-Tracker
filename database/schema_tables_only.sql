
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS maintenance_records;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS maintenance_types;
DROP TABLE IF EXISTS service_locations;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    vin VARCHAR(17) NULL,
    current_mileage INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE maintenance_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE service_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(150) NULL,
    city VARCHAR(80) NULL,
    state CHAR(2) NULL,
    phone VARCHAR(25) NULL
) ENGINE=InnoDB;

CREATE TABLE maintenance_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    maintenance_type_id INT UNSIGNED NOT NULL,
    service_location_id INT UNSIGNED NULL,
    service_date DATE NOT NULL,
    mileage INT UNSIGNED NOT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_records_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    CONSTRAINT fk_records_type FOREIGN KEY (maintenance_type_id) REFERENCES maintenance_types(id),
    CONSTRAINT fk_records_location FOREIGN KEY (service_location_id) REFERENCES service_locations(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Week 2 sample login: student@example.com / Student123!
INSERT INTO users (first_name, last_name, email, password_hash) VALUES
('Student', 'User', 'student@example.com', '$2y$12$Bd4fq5J09cYLV77J0tNmkeE0QrD/VrEF1VOfq5A0f5SFKIeYqRfxa');

INSERT INTO vehicles (user_id, year, make, model, vin, current_mileage) VALUES
(1, 2018, 'Ford', 'F-150 XLT', '1FTFW1E50JFC00001', 98250),
(1, 2013, 'Honda', 'Civic LX', '2HGFB2F50DH000001', 143200);

INSERT INTO maintenance_types (name, description) VALUES
('Oil Change', 'Engine oil and filter replacement'),
('Tire Rotation', 'Rotate tires and inspect tire condition'),
('Brake Service', 'Brake inspection or component replacement'),
('Battery Service', 'Battery test, service, or replacement'),
('Coolant Service', 'Cooling system inspection or coolant service'),
('General Repair', 'Other repair or maintenance work');

INSERT INTO service_locations (name, address, city, state, phone) VALUES
('Home Garage', NULL, 'Virginia Beach', 'VA', NULL),
('Independent Auto Shop', '100 Service Road', 'Virginia Beach', 'VA', '757-555-0101'),
('Ford Service Center', '200 Auto Lane', 'Virginia Beach', 'VA', '757-555-0102'),
('Quick Lube Center', '300 Maintenance Blvd', 'Chesapeake', 'VA', '757-555-0103');

INSERT INTO maintenance_records (vehicle_id, maintenance_type_id, service_location_id, service_date, mileage, cost, notes) VALUES
(1, 1, 4, '2026-09-20', 97500, 79.99, 'Full synthetic oil and filter change.'),
(1, 2, 2, '2026-08-15', 96000, 29.99, 'Rotated tires and checked air pressure.'),
(1, 5, 1, '2026-07-28', 94800, 46.50, 'Checked coolant level and inspected hoses.'),
(2, 1, 4, '2026-09-12', 142400, 64.99, 'Oil and filter change.'),
(2, 3, 2, '2026-06-02', 139800, 325.00, 'Front brake pads replaced.'),
(2, 4, 2, '2026-03-18', 136900, 189.00, 'Battery replaced after failed load test.');
