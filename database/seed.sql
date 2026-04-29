USE lab_reservation_early;

SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM reservation_status_history;
DELETE FROM reservations;
DELETE FROM equipment_instances;
DELETE FROM equipment_types;
DELETE FROM workstations;
DELETE FROM station_types;
DELETE FROM laboratories;
DELETE FROM student_profiles;
DELETE FROM departments;
DELETE FROM faculties;
DELETE FROM users;
DELETE FROM roles;

ALTER TABLE reservation_status_history AUTO_INCREMENT = 1;
ALTER TABLE reservations AUTO_INCREMENT = 1;
ALTER TABLE equipment_instances AUTO_INCREMENT = 1;
ALTER TABLE equipment_types AUTO_INCREMENT = 1;
ALTER TABLE workstations AUTO_INCREMENT = 1;
ALTER TABLE station_types AUTO_INCREMENT = 1;
ALTER TABLE laboratories AUTO_INCREMENT = 1;
ALTER TABLE departments AUTO_INCREMENT = 1;
ALTER TABLE faculties AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE roles AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (role_id, role_name, description) VALUES
(1, 'student', 'Sistemde rezervasyon yapabilen öğrenci kullanıcısı'),
(2, 'admin', 'Sistemde laboratuvar, istasyon, cihaz ve rezervasyon yönetebilen yönetici');

INSERT INTO users (
    user_id, role_id, first_name, last_name, email,
    password_hash, password_salt, phone, is_active
) VALUES
(1, 2, 'Admin', 'Kullanıcı', 'admin@lab.local',
 'a86f9067e8f738efc670010f9fa28eb36ad74d47b69c22f73f54f8d84bc1873e',
 'salt_admin_2026', '0370 000 0000', 1),

(2, 1, 'Onur', 'Demo', 'onur.demo@ogrenci.karabuk.edu.tr',
 '137f948fb1b97bdcefc3e2e9680f8ff32d571a60f19365ca5505ccdc9063cd38',
 'salt_student_2026', '0555 111 2233', 1),

(3, 1, 'Ayşe', 'Yılmaz', 'ayse.yilmaz@ogrenci.karabuk.edu.tr',
 'e5ff025feee9daeaa07d5a8283ed30d7aa6748f9dd877bf1a8f9c03fa828494b',
 'salt_student2_2026', '0555 222 3344', 1);

INSERT INTO faculties (faculty_id, faculty_name, is_active) VALUES
(1, 'Mühendislik Fakültesi', 1),
(2, 'Teknoloji Fakültesi', 1);

INSERT INTO departments (department_id, faculty_id, department_name, is_active) VALUES
(1, 1, 'Bilgisayar Mühendisliği', 1),
(2, 1, 'Elektrik Elektronik Mühendisliği', 1),
(3, 2, 'İmalat Mühendisliği', 1);

INSERT INTO student_profiles (
    user_id, student_no, faculty_id, department_id, class_year, program_type
) VALUES
(2, '2026000001', 1, 1, 2, '%100 Türkçe'),
(3, '2026000002', 1, 2, 3, '%100 Türkçe');

INSERT INTO laboratories (
    lab_id, department_id, lab_name, lab_code, lab_type,
    location, phone, description, is_active
) VALUES
(1, 1, 'Bilgisayar Mühendisliği Laboratuvarı', 'CENG-LAB', 'computer',
 'Mühendislik Fakültesi Zemin Kat', '1001',
 'Bilgisayar uygulamaları için kullanılan laboratuvar.', 1),

(2, 1, 'Bilgisayar Ağları Laboratuvarı', 'NET-LAB', 'network',
 'Mühendislik Fakültesi 1. Kat', '1002',
 'Ağ cihazları ve bilgisayar ağları uygulamaları için kullanılan laboratuvar.', 1),

(3, 2, 'Elektrik Elektronik Laboratuvarı', 'EEE-LAB', 'electronics',
 'Mühendislik Fakültesi 2. Kat', '1003',
 'Elektronik devre, ölçüm ve deney uygulamaları için kullanılan laboratuvar.', 1),

(4, 3, 'Makina İmalat Laboratuvarı', 'MFG-LAB', 'machine',
 'Teknoloji Fakültesi Atölye Alanı', '2001',
 'CNC ve imalat uygulamaları için kullanılan laboratuvar.', 1);

INSERT INTO station_types (station_type_id, type_name, description) VALUES
(1, 'computer_desk', 'Bilgisayar laboratuvarı masa türü'),
(2, 'network_desk', 'Ağ cihazları içeren çalışma masası'),
(3, 'electronics_bench', 'Elektronik deney masası'),
(4, 'machine_station', 'Makine/CNC çalışma istasyonu'),
(5, 'general_study_desk', 'Genel çalışma masası');

INSERT INTO workstations (
    station_id, lab_id, station_type_id, station_code,
    station_name, capacity, status, notes
) VALUES
(1, 1, 1, 'CENG-PC-01', 'Masa 01', 1, 'active', 'Bilgisayar uygulamaları için uygundur.'),
(2, 1, 1, 'CENG-PC-02', 'Masa 02', 1, 'active', 'Bilgisayar uygulamaları için uygundur.'),
(3, 1, 1, 'CENG-PC-03', 'Masa 03', 1, 'maintenance', 'Bakımda olan bilgisayar masası.'),

(4, 2, 2, 'NET-01', 'Ağ Masası 01', 2, 'active', 'Router ve switch içeren ağ masası.'),
(5, 2, 2, 'NET-02', 'Ağ Masası 02', 2, 'active', 'Router ve switch içeren ağ masası.'),

(6, 3, 3, 'EEE-01', 'Elektronik Deney Masası 01', 2, 'active', 'Osiloskop ve güç kaynağı içerir.'),
(7, 3, 3, 'EEE-02', 'Elektronik Deney Masası 02', 2, 'active', 'Elektronik devre deneyleri için uygundur.'),

(8, 4, 4, 'MFG-CNC-01', 'CNC Freze İstasyonu', 1, 'active', 'CNC freze uygulamaları için kullanılır.'),
(9, 4, 4, 'MFG-CNC-02', 'CNC Torna İstasyonu', 1, 'active', 'CNC torna uygulamaları için kullanılır.');

INSERT INTO equipment_types (
    equipment_type_id, equipment_name, category, description
) VALUES
(1, 'Bilgisayar', 'computer', 'Laboratuvar bilgisayarı'),
(2, 'Monitör', 'computer', 'Bilgisayar monitörü'),
(3, 'Router', 'network', 'Ağ yönlendirici cihazı'),
(4, 'Switch', 'network', 'Ağ anahtarlama cihazı'),
(5, 'Dijital Osiloskop', 'electronics', 'Elektronik ölçüm cihazı'),
(6, 'DC Güç Kaynağı', 'electronics', 'Elektronik deney güç kaynağı'),
(7, 'CNC Freze', 'machine', 'CNC freze cihazı'),
(8, 'CNC Torna', 'machine', 'CNC torna cihazı');

INSERT INTO equipment_instances (
    equipment_id, equipment_type_id, lab_id, station_id,
    asset_code, brand, model, status, notes
) VALUES
(1, 1, 1, 1, 'PC-CENG-001', 'Lenovo', 'ThinkCentre', 'available', 'Masa 01 bilgisayarı'),
(2, 2, 1, 1, 'MON-CENG-001', 'AOC', '24B2XH', 'available', 'Masa 01 monitörü'),

(3, 1, 1, 2, 'PC-CENG-002', 'HP', 'ProDesk', 'available', 'Masa 02 bilgisayarı'),
(4, 2, 1, 2, 'MON-CENG-002', 'Dell', 'P2419H', 'available', 'Masa 02 monitörü'),

(5, 1, 1, 3, 'PC-CENG-003', 'Dell', 'OptiPlex', 'maintenance', 'Bakımda bilgisayar'),

(6, 1, 2, 4, 'PC-NET-001', 'HP', 'ProDesk', 'available', 'Ağ Masası 01 bilgisayarı'),
(7, 3, 2, 4, 'RTR-NET-001', 'Cisco', 'ISR-900', 'available', 'Ağ Masası 01 router'),
(8, 4, 2, 4, 'SWT-NET-001', 'Cisco', '2960', 'available', 'Ağ Masası 01 switch'),

(9, 1, 2, 5, 'PC-NET-002', 'Lenovo', 'ThinkCentre', 'available', 'Ağ Masası 02 bilgisayarı'),
(10, 3, 2, 5, 'RTR-NET-002', 'Cisco', 'ISR-900', 'available', 'Ağ Masası 02 router'),
(11, 4, 2, 5, 'SWT-NET-002', 'Cisco', '2960', 'available', 'Ağ Masası 02 switch'),

(12, 5, 3, 6, 'OSC-EEE-001', 'Rigol', 'DS1054Z', 'available', 'Elektronik Deney Masası 01 osiloskop'),
(13, 6, 3, 6, 'PWR-EEE-001', 'GW Instek', 'GPS-3303', 'available', 'Elektronik Deney Masası 01 güç kaynağı'),

(14, 5, 3, 7, 'OSC-EEE-002', 'Tektronix', 'TBS1052B', 'available', 'Elektronik Deney Masası 02 osiloskop'),
(15, 6, 3, 7, 'PWR-EEE-002', 'GW Instek', 'GPS-3303', 'available', 'Elektronik Deney Masası 02 güç kaynağı'),

(16, 7, 4, 8, 'CNC-MFG-001', 'Haas', 'Mini Mill', 'available', 'CNC freze istasyonu'),
(17, 8, 4, 9, 'CNC-MFG-002', 'Haas', 'ST-10', 'available', 'CNC torna istasyonu');

INSERT INTO reservations (
    reservation_id, user_id, lab_id, station_id,
    start_time, end_time, purpose, status
) VALUES
(1, 2, 1, 1, '2026-05-04 10:00:00', '2026-05-04 12:00:00',
 'Programlama laboratuvar çalışması', 'active'),

(2, 3, 1, 2, '2026-05-04 13:00:00', '2026-05-04 15:00:00',
 'Veritabanı uygulama çalışması', 'active'),

(3, 2, 2, 4, '2026-05-05 09:00:00', '2026-05-05 11:00:00',
 'Bilgisayar ağları router/switch uygulaması', 'active'),

(4, 3, 3, 6, '2026-05-06 14:00:00', '2026-05-06 16:00:00',
 'Elektronik devre ölçüm uygulaması', 'cancelled'),

(5, 2, 4, 8, '2026-05-07 14:00:00', '2026-05-07 16:00:00',
 'CNC freze uygulama çalışması', 'active');

INSERT INTO reservation_status_history (
    reservation_id, old_status, new_status, changed_by, changed_at, note
) VALUES
(1, NULL, 'active', 2, '2026-05-01 09:00:00', 'Rezervasyon oluşturuldu.'),
(2, NULL, 'active', 3, '2026-05-01 09:15:00', 'Rezervasyon oluşturuldu.'),
(3, NULL, 'active', 2, '2026-05-01 10:00:00', 'Rezervasyon oluşturuldu.'),
(4, NULL, 'active', 3, '2026-05-01 10:30:00', 'Rezervasyon oluşturuldu.'),
(4, 'active', 'cancelled', 3, '2026-05-02 12:00:00', 'Kullanıcı tarafından iptal edildi.'),
(5, NULL, 'active', 2, '2026-05-02 13:00:00', 'Rezervasyon oluşturuldu.');