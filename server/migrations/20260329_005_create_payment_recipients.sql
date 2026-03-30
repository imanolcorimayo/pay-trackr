CREATE TABLE payment_recipients (
    payment_id VARCHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    cbu VARCHAR(30),
    alias VARCHAR(100),
    bank VARCHAR(100),
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);
