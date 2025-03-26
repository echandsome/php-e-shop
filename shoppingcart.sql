CREATE DATABASE IF NOT EXISTS `shoppingcart_advanced` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shoppingcart_advanced`;

CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Member','Admin') NOT NULL DEFAULT 'Member',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `address_street` varchar(255) NOT NULL,
  `address_city` varchar(100) NOT NULL,
  `address_state` varchar(100) NOT NULL,
  `address_zip` varchar(50) NOT NULL,
  `address_country` varchar(100) NOT NULL,
  `registered` datetime NOT NULL DEFAULT current_timestamp(),
  `reset_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `accounts` (`id`, `email`, `password`, `role`, `first_name`, `last_name`, `address_street`, `address_city`, `address_state`, `address_zip`, `address_country`, `registered`, `reset_code`) VALUES
(1, 'admin@example.com', '$2y$10$pEHRAE4Ia0mE9BdLmbS.ueQsv/.WlTUSW7/cqF/T36iW.zDzSkx4y', 'Admin', 'John', 'Doe', '98 High Street', 'New York', 'NY', '10001', 'United States', '2025-01-01 00:00:00', NULL);

CREATE TABLE IF NOT EXISTS `discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_ids` varchar(50) NOT NULL,
  `product_ids` varchar(50) NOT NULL,
  `discount_code` varchar(50) NOT NULL,
  `discount_type` enum('Percentage','Fixed') NOT NULL,
  `discount_value` decimal(7,2) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `discounts` (`id`, `category_ids`, `product_ids`, `discount_code`, `discount_type`, `discount_value`, `start_date`, `end_date`) VALUES
(1, '', '', 'YEAR2025', 'Percentage', '5.00', '2025-01-01 00:00:00', '2025-12-31 00:00:00'),
(2, '', '', '5OFF', 'Fixed', '5.00', '2025-01-01 00:00:00', '2035-01-01 00:00:00');

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` mediumtext NOT NULL,
  `sku` varchar(255) NOT NULL DEFAULT '',
  `price` decimal(7,2) NOT NULL,
  `rrp` decimal(7,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `weight` decimal(7,2) NOT NULL DEFAULT 0.00,
  `url_slug` varchar(255) NOT NULL DEFAULT '',
  `product_status` tinyint(1) NOT NULL DEFAULT 1,
  `subscription` tinyint(1) NOT NULL DEFAULT 0,
  `subscription_period` int(11) NOT NULL DEFAULT 0,
  `subscription_period_type` varchar(50) NOT NULL DEFAULT 'day',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `title`, `description`, `sku`, `price`, `rrp`, `quantity`, `created`, `weight`, `url_slug`, `product_status`, `subscription`, `subscription_period`, `subscription_period_type`) VALUES
(1, 'Watch', '<p>Meet our special watch! It\'s made of strong metal and is perfect for anyone who loves watches that can do cool things. It\'s not just a regular watch.</p>\r\n<h3>What makes it great?</h3>\r\n<ul>\r\n<li>It works with Android and has apps already in it.</li>\r\n<li>You can adjust it to fit your wrist just right.</li>\r\n<li>The battery lasts a long time – wear it for 2 days without needing to charge.</li>\r\n<li>It\'s light and comfy to wear all day.</li>\r\n</ul>', 'watch', '29.99', '0.00', -1, '2025-01-01 00:00:00', '0.00', 'smart-watch', 1, 0, 0, 'day'),
(2, 'Wallet', '<p>Discover our sleek black wallet, a must-have accessory that combines simplicity with practicality. It\'s ideal for anyone looking for a reliable yet stylish way to carry their essentials.</p>\r\n<h3>Why you\'ll love it:</h3>\r\n<ul>\r\n<li>Made from durable materials to keep your items safe.</li>\r\n<li>Slim design that fits comfortably in your pocket or purse.</li>\r\n<li>Multiple compartments for cash, cards, and IDs.</li>\r\n<li>Classic black color that matches everything.</li>\r\n</ul>', 'wallet', '14.99', '19.99', -1, '2025-01-01 00:00:00', '0.00', '', 1, 0, 0, 'day'),
(3, 'Headphones', '<p>Experience the freedom of sound with our compact wireless headphones, perfect for those on the move or who love uncluttered simplicity.</p>\r\n<h3>Highlights:</h3>\r\n<ul>\r\n<li>Wireless technology for ultimate mobility and ease.</li>\r\n<li>Compact size for easy storage and portability.</li>\r\n<li>Long-lasting battery for extended listening sessions.</li>\r\n<li>High-quality audio that brings your music to life.</li>\r\n</ul>', 'headphones', '19.99', '0.00', -1, '2025-01-01 00:00:00', '0.00', '', 1, 0, 0, 'day'),
(4, 'Digital Camera', '<p>Discover the world through a lens with our digital camera, designed for both beginners and photography enthusiasts.</p>\r\n<h3>Key Features:</h3>\r\n<ul>\r\n<li>High-resolution imaging for stunning picture quality.</li>\r\n<li>User-friendly interface for easy operation.</li>\r\n<li>Compact and durable design, ready for any adventure.</li>\r\n<li>Powerful zoom to capture distant subjects with clarity.</li>\r\n</ul>', 'digital-camera', '269.99', '0.00', 0, '2025-01-01 00:00:00', '0.00', '', 1, 0, 0, 'day'),
(5, 'Subscription Item 1', '', 'sub-item-1', '15.00', '30.00', -1, '2025-01-01 00:00:00', '0.00', '', 1, 1, 1, 'month');

CREATE TABLE IF NOT EXISTS `product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_categories` (`id`, `title`, `parent_id`) VALUES
(1, 'Sale', 0),
(2, 'Watches', 0);

CREATE TABLE IF NOT EXISTS `product_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`,`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_category` (`id`, `product_id`, `category_id`) VALUES
(1, 1, 2),
(2, 2, 1),
(3, 5, 1);

CREATE TABLE IF NOT EXISTS `product_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`,`file_path`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `caption` varchar(255) NOT NULL,
  `date_uploaded` datetime NOT NULL DEFAULT current_timestamp(),
  `full_path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_media` (`id`, `title`, `caption`, `date_uploaded`, `full_path`) VALUES 
(1, 'Watch Front', '', '2025-01-01 00:00:00', 'uploads/watch.jpg'),
(2, 'Watch Side', '', '2025-01-01 00:00:00', 'uploads/watch-2.jpg'),
(3, 'Watch Back', '', '2025-01-01 00:00:00', 'uploads/watch-3.jpg'),
(4, 'Wallet', '', '2025-01-01 00:00:00', 'uploads/wallet.jpg'),
(5, 'Camera', '', '2025-01-01 00:00:00', 'uploads/camera.jpg'),
(6, 'Headphones', '', '2025-01-01 00:00:00', 'uploads/headphones.jpg'),
(7, 'Subscription Placeholder', '', '2025-01-01 00:00:00', 'uploads/subscription.png');

CREATE TABLE IF NOT EXISTS `product_media_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `media_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_media_map` (`id`, `product_id`, `media_id`, `position`) VALUES
(1, 1, 1, 1),
(2, 1, 2, 2),
(3, 1, 3, 3),
(4, 2, 4, 1),
(5, 3, 6, 1),
(6, 4, 5, 1),
(7, 5, 7, 1);

CREATE TABLE IF NOT EXISTS `product_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `option_name` varchar(255) NOT NULL,
  `option_value` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(7,2) NOT NULL,
  `price_modifier` enum('add','subtract') NOT NULL,
  `weight` decimal(7,2) NOT NULL,
  `weight_modifier` enum('add','subtract') NOT NULL,
  `option_type` enum('select','radio','checkbox','text','datetime') NOT NULL,
  `required` tinyint(1) NOT NULL,
  `position` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`,`option_name`,`option_value`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_options` (`id`, `option_name`, `option_value`, `quantity`, `price`, `price_modifier`, `weight`, `weight_modifier`, `option_type`, `required`, `position`, `product_id`) VALUES
(1, 'Size', 'Small', -1, '9.99', 'add', '9.99', 'add', 'select', 1, 1, 1),
(2, 'Size', 'Large', -1, '8.99', 'add', '8.99', 'add', 'select', 1, 1, 1),
(3, 'Type', 'Standard', -1, '0.00', 'add', '0.00', 'add', 'radio', 1, 2, 1),
(4, 'Type', 'Deluxe', -1, '10.00', 'add', '0.00', 'add', 'radio', 1, 2, 1),
(5, 'Color', 'Red', -1, '1.00', 'add', '10.00', 'add', 'checkbox', 0, 3, 1),
(6, 'Color', 'Yellow', -1, '2.00', 'add', '10.00', 'add', 'checkbox', 0, 3, 1),
(7, 'Color', 'Blue', -1, '3.00', 'add', '10.00', 'add', 'checkbox', 0, 3, 1),
(8, 'Color', 'Purple', 0, '4.00', 'add', '10.00', 'add', 'checkbox', 0, 3, 1),
(9, 'Color', 'Brown', 0, '5.00', 'add', '10.00', 'add', 'checkbox', 0, 3, 1),
(10, 'Color', 'Pink', 0, '6.00', 'add', '10.00', 'add', 'checkbox', 0, 3, 1),
(11, 'Color', 'Orange', -1, '8.00', 'add', '11.00', 'add', 'checkbox', 0, 3, 1),
(12, 'Delivery Date', '', -1, '5.00', 'add', '0.00', 'add', 'datetime', 0, 4, 1),
(13, 'Type', 'Standard', -1, '0.00', 'add', '0.00', 'add', 'radio', 1, 1, 5),
(14, 'Type', 'Premium', -1, '10.00', 'add', '0.00', 'add', 'radio', 1, 1, 5);

CREATE TABLE IF NOT EXISTS `shipping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `shipping_type` enum('Single Product','Entire Order') NOT NULL DEFAULT 'Single Product',
  `countries` varchar(255) NOT NULL DEFAULT '',
  `price_from` decimal(7,2) NOT NULL,
  `price_to` decimal(7,2) NOT NULL,
  `price` decimal(7,2) NOT NULL,
  `weight_from` decimal(7,2) NOT NULL DEFAULT 0.00,
  `weight_to` decimal(7,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shipping` (`id`, `title`, `shipping_type`, `countries`, `price_from`, `price_to`, `price`, `weight_from`, `weight_to`) VALUES
(1, 'Standard', 'Entire Order', '', '0.00', '99999.00', '3.99', '0.00', '99999.00'),
(2, 'Express', 'Entire Order', '', '0.00', '99999.00', '7.99', '0.00', '99999.00');

CREATE TABLE IF NOT EXISTS `taxes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country` varchar(255) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `rate_type` varchar(50) NOT NULL DEFAULT 'percentage',
  `rules` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `taxes` (`id`, `country`, `rate`, `rate_type`, `rules`) VALUES
(1, 'United Kingdom', '20.00', 'percentage', NULL);

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txn_id` varchar(255) NOT NULL,
  `payment_amount` decimal(7,2) NOT NULL,
  `payment_status` varchar(30) NOT NULL,
  `created` datetime NOT NULL,
  `payer_email` varchar(255) NOT NULL DEFAULT '',
  `first_name` varchar(100) NOT NULL DEFAULT '',
  `last_name` varchar(100) NOT NULL DEFAULT '',
  `address_street` varchar(255) NOT NULL DEFAULT '',
  `address_city` varchar(100) NOT NULL DEFAULT '',
  `address_state` varchar(100) NOT NULL DEFAULT '',
  `address_zip` varchar(50) NOT NULL DEFAULT '',
  `address_country` varchar(100) NOT NULL DEFAULT '',
  `account_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'website',
  `shipping_method` varchar(255) NOT NULL DEFAULT '',
  `shipping_amount` decimal(7,2) NOT NULL DEFAULT 0.00,
  `discount_code` varchar(50) NOT NULL DEFAULT '',
  `tax_amount` decimal(7,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `txn_id` (`txn_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transaction_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txn_id` varchar(255) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_price` decimal(7,2) NOT NULL,
  `item_quantity` int(11) NOT NULL,
  `item_options` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;