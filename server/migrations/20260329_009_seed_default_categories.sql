CREATE TABLE default_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) NOT NULL
);

INSERT INTO default_categories (name, color) VALUES
    ('Vivienda y Alquiler', '#4682B4'),
    ('Servicios', '#0072DF'),
    ('Supermercado', '#1D9A38'),
    ('Salidas', '#FF6347'),
    ('Transporte', '#E6AE2C'),
    ('Entretenimiento', '#6158FF'),
    ('Salud', '#E84A8A'),
    ('Fitness y Deportes', '#FF4500'),
    ('Cuidado Personal', '#DDA0DD'),
    ('Mascotas', '#3CAEA3'),
    ('Ropa', '#800020'),
    ('Viajes', '#FF8C00'),
    ('Educación', '#9370DB'),
    ('Suscripciones', '#20B2AA'),
    ('Regalos', '#FF1493'),
    ('Impuestos y Gobierno', '#8B4513'),
    ('Otros', '#808080');
