CREATE TABLE recurrents (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description VARCHAR(500) DEFAULT '',
    amount DECIMAL(12,2) NOT NULL,
    start_date DATE,
    due_date_day TINYINT UNSIGNED NOT NULL,
    end_date DATE,
    time_period VARCHAR(20) DEFAULT 'monthly',
    category_id VARCHAR(36),
    is_credit_card TINYINT(1) NOT NULL DEFAULT 0,
    credit_card_id VARCHAR(36),
    created_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL
);
