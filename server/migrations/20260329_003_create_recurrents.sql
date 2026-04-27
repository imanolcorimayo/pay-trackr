CREATE TABLE recurrent (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description VARCHAR(500) DEFAULT '',
    amount DECIMAL(12,2) NOT NULL,
    start_date DATE,
    due_date_day TINYINT UNSIGNED NOT NULL,
    end_date DATE,
    time_period VARCHAR(20) DEFAULT 'monthly',
    expense_category_id VARCHAR(36),
    card_id VARCHAR(36),
    created_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE,
    FOREIGN KEY (expense_category_id) REFERENCES expense_category(id) ON DELETE SET NULL
);
