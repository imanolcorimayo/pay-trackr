-- Income categories: separate table from expense_category so we can keep
-- the two taxonomies clean (Salario / Freelance / Reembolso vs the existing
-- Vivienda / Servicios / etc). Both surfaced together on /categorias via a
-- ?kind= filter on /api/categories.

CREATE TABLE default_income_category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) NOT NULL
);

INSERT INTO default_income_category (name, color) VALUES
    ('Salario',         '#1D9A38'),
    ('Freelance',       '#0072DF'),
    ('Reembolso',       '#20B2AA'),
    ('Inversión',       '#6158FF'),
    ('Venta',           '#E6AE2C'),
    ('Regalo',          '#FF1493'),
    ('Otros ingresos',  '#808080');

CREATE TABLE income_category (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) NOT NULL,
    deleted_ts TIMESTAMP NULL DEFAULT NULL,
    created_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
);

-- Backfill existing users with the default income categories. New users get
-- them via seed_income_categories_for_user() in middleware/auth.php.
INSERT INTO income_category (id, user_id, name, color)
SELECT
    LOWER(HEX(RANDOM_BYTES(14))),
    u.id,
    d.name,
    d.color
FROM `user` u
CROSS JOIN default_income_category d;
