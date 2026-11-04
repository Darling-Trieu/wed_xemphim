-- Giữ ghế tạm thời trong lúc khách xác nhận đặt vé.
CREATE TABLE IF NOT EXISTS seat_holds (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    showtime_id INT UNSIGNED NOT NULL,
    seat_id     INT UNSIGNED NOT NULL,
    session_id  VARCHAR(128) NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_active_seat_hold (showtime_id, seat_id),
    INDEX idx_seat_hold_expiry (expires_at),
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
) ENGINE=InnoDB;
