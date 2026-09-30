-- Foodway Food Out schema

CREATE TABLE `goods_out` (
  `entry_id`        INT NOT NULL AUTO_INCREMENT,
  `entry_date`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id`     INT NOT NULL,
  `scale_weight`    DECIMAL(8,2) NOT NULL,
  `cart_applied`    DECIMAL(8,2) NOT NULL DEFAULT 0,
  `total_weight`    DECIMAL(8,2) GENERATED ALWAYS AS (`scale_weight` - `cart_applied`) STORED,
  `household_count` INT NOT NULL DEFAULT 1,
  `deleted_at`      DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  KEY `idx_goods_out_date` (`entry_date`),
  KEY `idx_goods_out_customer` (`customer_id`),
  CONSTRAINT `chk_scale_positive`    CHECK (`scale_weight` > 0),
  CONSTRAINT `chk_cart_nonnegative`  CHECK (`cart_applied` >= 0),
  CONSTRAINT `chk_no_negative_total` CHECK (`scale_weight` >= `cart_applied`),
  CONSTRAINT `chk_household_min`     CHECK (`household_count` >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Reports and CSV export should read from this, so soft-deleted rows never count.
CREATE VIEW `goods_out_active` AS
SELECT `entry_id`, `entry_date`, `customer_id`, `scale_weight`, `cart_applied`,
       `total_weight`, `household_count`
FROM `goods_out`
WHERE `deleted_at` IS NULL;
