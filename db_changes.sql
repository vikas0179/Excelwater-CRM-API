ALTER TABLE `leads` CHANGE `phone` `phone` varchar(32) COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT '' AFTER `email`;

-- 15-03-2025
CREATE TABLE `invoice` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `invoice_no` int NOT NULL,
  `invoice_date` date DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `desc` text,
  `image` text,
  `sub_total` float DEFAULT NULL,
  `total_amount` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `invoice_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `invoice_id` int DEFAULT NULL,
  `item` varchar(255) DEFAULT NULL,
  `desc` text,
  `qty` int DEFAULT NULL,
  `rate` varchar(255) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `order` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `supplier_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `desc` text,
  `invoice_desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `invoice_file` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sub_total` float DEFAULT NULL,
  `total_amount` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `order_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `order_id` int DEFAULT NULL,
  `item` varchar(255) DEFAULT NULL,
  `desc` text,
  `qty` int DEFAULT NULL,
  `delivery_qty` int DEFAULT NULL,
  `status` tinyint NOT NULL COMMENT '0= pending & 1=complete',
  `rate` varchar(255) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `product_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `product_name` varchar(255) NOT NULL DEFAULT '',
  `product_code` varchar(255) NOT NULL DEFAULT '',
  `price` float NOT NULL DEFAULT '0',
  `desc` text NOT NULL,
  `image` text NOT NULL,
  `spare_parts` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `spare_parts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `part_name` varchar(255) NOT NULL DEFAULT '',
  `part_number` varchar(255) NOT NULL DEFAULT '',
  `price` float NOT NULL DEFAULT '0',
  `min_alert_qty` int DEFAULT NULL,
  `desc` text NOT NULL,
  `stock_qty` int DEFAULT NULL,
  `image` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `stocks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `order_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `spare_id` int DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `price` float DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `total_amount` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `supplier` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(32) NOT NULL DEFAULT '',
  `address` text NOT NULL,
  `tan_number` int DEFAULT NULL,
  `logo` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `use_parts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `invoice_id` int DEFAULT NULL,
  `part_id` int DEFAULT NULL,
  `qty` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2025-03-17
ALTER TABLE `invoice_item` ADD `product_id` int NULL AFTER `invoice_id`;

ALTER TABLE `invoice` DROP `product_id`;

ALTER TABLE `leads` ADD `is_old_leads` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0=no, 1=yes';
ALTER TABLE `leads` CHANGE `is_old_leads` `is_old_leads` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0=no, 1=yes' AFTER `is_duplicate`;


-- 2025-05-20
ALTER TABLE `invoice` ADD `ship_to` text NULL AFTER `customer_id`;

-- 2025-05-24
ALTER TABLE `users`
CHANGE `name` `name` varchar(256) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `id`,
CHANGE `email` `email` varchar(256) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `name`,
ADD `mobile` varchar(20) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `email`,
DROP `email_verified_at`,
CHANGE `password` `password` varchar(256) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `mobile`,
ADD `billing_address` text COLLATE 'utf8mb4_unicode_ci' NULL AFTER `password`,
ADD `billing_landmark` varchar(100) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `billing_address`,
ADD `billing_city` varchar(100) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `billing_landmark`,
ADD `billing_state` varchar(100) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `billing_city`,
ADD `billing_zipcode` varchar(20) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `billing_state`,
ADD `shipping_address` text COLLATE 'utf8mb4_unicode_ci' NULL AFTER `billing_zipcode`,
ADD `shipping_landmark` varchar(100) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `shipping_addr`;

ALTER TABLE `users` ADD `visible_pass` varchar(256) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `password`;

ALTER TABLE `users` ADD `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '0=>In Active, 1=>Active' AFTER `access_token`;

ALTER TABLE `spare_parts` ADD `opening_stock` int NULL AFTER `stock_qty`;

ALTER TABLE `invoice` ADD `bill_to` text NULL AFTER `customer_id`;

-- 04-06-2025

ALTER TABLE `supplier` ADD `spare_part_ids` text NULL AFTER `updated_at`;


-- 6-6-2025
ALTER TABLE `invoice` ADD `void_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0=>Off, 1=>On';