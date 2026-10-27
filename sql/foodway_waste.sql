-- Foodway Waste schema

CREATE TABLE `waste` (
  `entry_id`        INT NOT NULL AUTO_INCREMENT,
  `entry_date`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `scale_weight`    DECIMAL(8,2) NOT NULL,
  `cart_applied`    DECIMAL(8,2) NOT NULL DEFAULT 0,
  `total_weight`    DECIMAL(8,2) GENERATED ALWAYS AS (`scale_weight` - `cart_applied`) STORED,
  `waste_type`      VARCHAR(255) NOT NULL,
  `deleted_at`      DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  KEY `idx_waste_date` (`entry_date`),
  CONSTRAINT `chk_waste_scale_positive`    CHECK (`scale_weight` > 0),
  CONSTRAINT `chk_waste_cart_nonnegative`  CHECK (`cart_applied` >= 0),
  CONSTRAINT `chk_waste_no_negative_total` CHECK (`scale_weight` >= `cart_applied`),
  CONSTRAINT `chk_waste_type`              CHECK (`waste_type` IN ('pig_food', 'trash'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Reports and CSV export should read from this, so soft-deleted rows never count.
CREATE VIEW `waste_active` AS
SELECT `entry_id`, `entry_date`, `waste_type`, `scale_weight`, `cart_applied`,
       `total_weight`
FROM `waste`
WHERE `deleted_at` IS NULL;
