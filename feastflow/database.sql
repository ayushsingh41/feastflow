-- ============================================================
-- FEASTFLOW - Food Ordering System Database Schema
-- ApexPlanet Task 4 | Real-World Full Stack Project
-- Normalized to 3NF | Days 37-48
-- ============================================================

CREATE DATABASE IF NOT EXISTS `feastflow_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `feastflow_db`;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`            VARCHAR(100)    NOT NULL,
    `email`           VARCHAR(150)    NOT NULL UNIQUE,
    `password_hash`   VARCHAR(255)    NOT NULL,
    `role`            ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    `phone`           VARCHAR(20)     NULL,
    `address`         TEXT            NULL,
    `avatar`          VARCHAR(255)    NULL,
    `status`          ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
    `login_attempts`  TINYINT         NOT NULL DEFAULT 0,
    `locked_until`    TIMESTAMP       NULL,
    `last_login`      TIMESTAMP       NULL,
    `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role`  (`role`),
    INDEX `idx_status`(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT         NULL,
    `icon`        VARCHAR(100) NULL DEFAULT 'ri-restaurant-line',
    `color`       VARCHAR(20)  NULL DEFAULT '#f59e0b',
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order`  INT          NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: products
-- ============================================================
CREATE TABLE IF NOT EXISTS `products` (
    `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED   NOT NULL,
    `name`        VARCHAR(200)   NOT NULL,
    `description` TEXT           NULL,
    `price`       DECIMAL(10,2)  NOT NULL,
    `image`       VARCHAR(255)   NULL,
    `stock`       INT            NOT NULL DEFAULT 100,
    `is_featured` TINYINT(1)     NOT NULL DEFAULT 0,
    `is_veg`      TINYINT(1)     NOT NULL DEFAULT 0,
    `rating`      DECIMAL(3,2)   NOT NULL DEFAULT 0.00,
    `total_orders`INT            NOT NULL DEFAULT 0,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_status`   (`status`),
    INDEX `idx_featured` (`is_featured`),
    CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: orders
-- ============================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `user_id`          INT UNSIGNED   NOT NULL,
    `order_number`     VARCHAR(20)    NOT NULL UNIQUE,
    `subtotal`         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `delivery_fee`     DECIMAL(10,2)  NOT NULL DEFAULT 40.00,
    `discount`         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `total`            DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `status`           ENUM('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `payment_method`   ENUM('cod','upi','card') NOT NULL DEFAULT 'cod',
    `payment_status`   ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
    `delivery_address` TEXT           NOT NULL,
    `notes`            TEXT           NULL,
    `estimated_time`   INT            NULL COMMENT 'Minutes',
    `delivered_at`     TIMESTAMP      NULL,
    `created_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user`   (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_order_number` (`order_number`),
    CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `order_id`   INT UNSIGNED  NOT NULL,
    `product_id` INT UNSIGNED  NOT NULL,
    `name`       VARCHAR(200)  NOT NULL,
    `price`      DECIMAL(10,2) NOT NULL,
    `quantity`   INT           NOT NULL DEFAULT 1,
    `subtotal`   DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_order`   (`order_id`),
    INDEX `idx_product` (`product_id`),
    CONSTRAINT `fk_item_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: cart (session-based fallback)
-- ============================================================
CREATE TABLE IF NOT EXISTS `cart` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED  NOT NULL,
    `product_id` INT UNSIGNED  NOT NULL,
    `quantity`   INT           NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_cart_item` (`user_id`, `product_id`),
    CONSTRAINT `fk_cart_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: reviews
-- ============================================================
CREATE TABLE IF NOT EXISTS `reviews` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `order_id`   INT UNSIGNED NULL,
    `rating`     TINYINT      NOT NULL DEFAULT 5,
    `comment`    TEXT         NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_review` (`product_id`, `user_id`),
    CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_review_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: activity_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED    NULL,
    `action`      VARCHAR(100)    NOT NULL,
    `description` TEXT            NULL,
    `ip_address`  VARCHAR(45)     NULL,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user`   (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created`(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: coupons
-- ============================================================
CREATE TABLE IF NOT EXISTS `coupons` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `code`         VARCHAR(20)   NOT NULL UNIQUE,
    `discount_type`ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    `discount`     DECIMAL(10,2) NOT NULL,
    `min_order`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `max_uses`     INT           NOT NULL DEFAULT 100,
    `used_count`   INT           NOT NULL DEFAULT 0,
    `expires_at`   DATE          NULL,
    `status`       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin user (password: Admin@123)
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `phone`, `status`) VALUES
('Super Admin', 'admin@feastflow.com', '$2y$10$Ffry8eABv59UWFx9cFtYieEVIQb/uqewaf9WiaGsdp6QH894Qd1Ay', 'admin', '9999999999', 'active'),
('Rahul Sharma', 'rahul@example.com', '$2y$10$Ffry8eABv59UWFx9cFtYieEVIQb/uqewaf9WiaGsdp6QH894Qd1Ay', 'customer', '9876543210', 'active'),
('Priya Singh', 'priya@example.com', '$2y$10$Ffry8eABv59UWFx9cFtYieEVIQb/uqewaf9WiaGsdp6QH894Qd1Ay', 'customer', '9876543211', 'active'),
('Amit Kumar', 'amit@example.com', '$2y$10$Ffry8eABv59UWFx9cFtYieEVIQb/uqewaf9WiaGsdp6QH894Qd1Ay', 'customer', '9876543212', 'active');

-- Categories
INSERT INTO `categories` (`name`, `description`, `icon`, `color`, `sort_order`) VALUES
('Burgers', 'Juicy handcrafted burgers', 'ri-goblet-line', '#ef4444', 1),
('Pizzas', 'Wood-fired artisan pizzas', 'ri-cake-3-line', '#f59e0b', 2),
('Pasta', 'Authentic Italian pasta dishes', 'ri-restaurant-2-line', '#8b5cf6', 3),
('Salads', 'Fresh & healthy salads', 'ri-leaf-line', '#10b981', 4),
('Beverages', 'Refreshing drinks & shakes', 'ri-cup-line', '#3b82f6', 5),
('Desserts', 'Sweet treats & desserts', 'ri-cake-2-line', '#ec4899', 6);

-- Products
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `is_featured`, `is_veg`, `rating`, `total_orders`, `status`) VALUES
(1, 'Classic Smash Burger', 'Double smash patty, cheddar cheese, lettuce, tomato, special sauce', 249.00, 1, 0, 4.8, 342, 'active'),
(1, 'BBQ Bacon Burger', 'Crispy bacon, BBQ sauce, caramelized onions, pickles', 299.00, 1, 0, 4.7, 218, 'active'),
(1, 'Veggie Delight Burger', 'Crispy veggie patty, fresh veggies, mint chutney', 199.00, 0, 1, 4.5, 156, 'active'),
(1, 'Spicy Chicken Burger', 'Crispy fried chicken, jalapeños, sriracha mayo', 279.00, 0, 0, 4.6, 189, 'active'),
(2, 'Margherita Pizza', 'San Marzano tomatoes, fresh mozzarella, basil', 349.00, 1, 1, 4.9, 421, 'active'),
(2, 'Pepperoni Feast', 'Double pepperoni, mozzarella, oregano', 399.00, 1, 0, 4.8, 387, 'active'),
(2, 'BBQ Chicken Pizza', 'Grilled chicken, BBQ sauce, red onions, cilantro', 379.00, 0, 0, 4.7, 265, 'active'),
(2, 'Farm Fresh Veggie', 'Seasonal vegetables, pesto, goat cheese', 329.00, 0, 1, 4.5, 142, 'active'),
(3, 'Spaghetti Carbonara', 'Guanciale, eggs, pecorino romano, black pepper', 299.00, 1, 0, 4.8, 198, 'active'),
(3, 'Penne Arrabbiata', 'Spicy tomato sauce, garlic, parsley', 249.00, 0, 1, 4.6, 167, 'active'),
(3, 'Fettuccine Alfredo', 'Butter, parmesan cream sauce, herbs', 279.00, 0, 1, 4.7, 143, 'active'),
(4, 'Caesar Salad', 'Romaine, croutons, parmesan, caesar dressing', 199.00, 0, 1, 4.5, 89, 'active'),
(4, 'Greek Salad', 'Feta, olives, cucumber, cherry tomatoes, oregano', 219.00, 0, 1, 4.6, 76, 'active'),
(5, 'Mango Shake', 'Fresh Alphonso mangoes, milk, ice cream', 149.00, 1, 1, 4.9, 312, 'active'),
(5, 'Cold Coffee', 'Espresso, milk, ice cream, chocolate drizzle', 129.00, 0, 1, 4.7, 245, 'active'),
(5, 'Fresh Lime Soda', 'Freshly squeezed lime, soda, mint', 79.00, 0, 1, 4.5, 198, 'active'),
(6, 'Chocolate Lava Cake', 'Warm chocolate cake, molten center, vanilla ice cream', 179.00, 1, 1, 4.9, 287, 'active'),
(6, 'Cheesecake', 'New York style, strawberry compote', 159.00, 0, 1, 4.8, 213, 'active');

-- Sample orders
INSERT INTO `orders` (`user_id`, `order_number`, `subtotal`, `delivery_fee`, `total`, `status`, `payment_method`, `payment_status`, `delivery_address`, `created_at`) VALUES
(2, 'FF-2026-001', 648.00, 40.00, 688.00, 'delivered', 'cod', 'paid', '123 MG Road, Delhi', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 'FF-2026-002', 399.00, 40.00, 439.00, 'delivered', 'upi', 'paid', '456 Park Street, Mumbai', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(4, 'FF-2026-003', 527.00, 40.00, 567.00, 'out_for_delivery', 'cod', 'pending', '789 Anna Salai, Chennai', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'FF-2026-004', 298.00, 40.00, 338.00, 'preparing', 'upi', 'paid', '123 MG Road, Delhi', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 'FF-2026-005', 746.00, 0.00, 746.00, 'pending', 'card', 'pending', '456 Park Street, Mumbai', NOW());

-- Order items
INSERT INTO `order_items` (`order_id`, `product_id`, `name`, `price`, `quantity`, `subtotal`) VALUES
(1, 1, 'Classic Smash Burger', 249.00, 2, 498.00),
(1, 14, 'Mango Shake', 149.00, 1, 149.00),
(2, 6, 'Pepperoni Feast', 399.00, 1, 399.00),
(3, 5, 'Margherita Pizza', 349.00, 1, 349.00),
(3, 9, 'Spaghetti Carbonara', 299.00, 1, 299.00),
(4, 3, 'Veggie Delight Burger', 199.00, 1, 199.00),
(4, 15, 'Cold Coffee', 129.00, 1, 129.00),
(5, 7, 'BBQ Chicken Pizza', 379.00, 1, 379.00),
(5, 17, 'Chocolate Lava Cake', 179.00, 2, 358.00);

-- Coupons
INSERT INTO `coupons` (`code`, `discount_type`, `discount`, `min_order`, `max_uses`, `expires_at`) VALUES
('FEAST20', 'percent', 20.00, 200.00, 500, '2026-12-31'),
('NEWUSER50', 'fixed', 50.00, 100.00, 1000, '2026-12-31'),
('SAVE100', 'fixed', 100.00, 500.00, 200, '2026-12-31');
