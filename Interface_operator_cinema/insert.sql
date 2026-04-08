-- Insert test data for cinema "Almaz"
-- Run this script to populate tables with sample records

USE project_Kozlov;

-- Films
INSERT INTO films (title, description, duration, poster, release_date) VALUES
('Матрица: Воскрешение', 'Новая часть культовой киберпанк-саги', 148, 'https://example.com/posters/matrix.jpg', '2025-03-20'),
('Дюна: Часть вторая', 'Продолжение эпической экранизации', 166, 'https://example.com/posters/dune2.jpg', '2025-05-15'),
('Бэтмен 2', 'Тёмный рыцарь возвращается', 155, 'https://example.com/posters/batman2.jpg', '2025-07-10'),
('Интерстеллар', 'Путешествие сквозь червоточину', 169, 'https://example.com/posters/interstellar.jpg', '2014-11-06'),
('Гладиатор 2', 'Легенда продолжается', 150, 'https://example.com/posters/gladiator2.jpg', '2025-11-20');

-- Halls
INSERT INTO halls (name, seats, description) VALUES
('Зал №1 (IMAX)', 250, 'Самый большой зал с IMAX-экраном'),
('Зал №2 (VIP)', 80, 'Комфортабельные кресла, обслуживание в зале'),
('Зал №3 (3D)', 180, 'Стереоскопический 3D-звук'),
('Зал №4 (Малый)', 60, 'Камерный зал для арт-хауса');

-- Sessions (date/time field may be 'date' or 'start_time' – adjust if needed)
INSERT INTO sessions (film_id, hall_id, date, price) VALUES
(1, 1, '2025-04-01 19:00:00', 450.00),
(1, 2, '2025-04-01 21:30:00', 600.00),
(2, 1, '2025-05-20 20:00:00', 500.00),
(2, 3, '2025-05-21 18:30:00', 400.00),
(3, 4, '2025-07-15 19:30:00', 350.00),
(4, 1, '2025-03-25 19:00:00', 450.00),
(4, 2, '2025-03-26 20:00:00', 550.00);

-- Bookings
INSERT INTO bookings (session_id, customer_name, customer_email, seats, booking_date) VALUES
(1, 'Иван Петров', 'ivan@example.com', 2, NOW()),
(1, 'Мария Сидорова', 'maria@example.com', 3, NOW()),
(2, 'Алексей Иванов', 'alex@example.com', 1, NOW()),
(3, 'Елена Смирнова', 'elena@example.com', 4, NOW()),
(5, 'Дмитрий Козлов', 'dmitry@example.com', 2, NOW());