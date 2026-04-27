CREATE TABLE payment_recipient (
    payment_id VARCHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    cbu VARCHAR(30),
    alias VARCHAR(100),
    bank VARCHAR(100),
    FOREIGN KEY (payment_id) REFERENCES payment(id) ON DELETE CASCADE
);
