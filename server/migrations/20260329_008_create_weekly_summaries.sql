CREATE TABLE weekly_summaries (
    user_id VARCHAR(36) NOT NULL PRIMARY KEY,
    stats JSON NOT NULL,
    ai_insight TEXT,
    created_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
