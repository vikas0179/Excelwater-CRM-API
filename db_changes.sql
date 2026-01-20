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

-- 17-06-2025
ALTER TABLE `invoice`
CHANGE `customer_id` `customer_id` int NULL AFTER `updated_at`,
CHANGE `invoice_no` `invoice_no` int NULL AFTER `ship_to`;

CREATE TABLE `transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `customer_id` int(11) NULL,
  `date` date NULL,
  `type` varchar(256) NULL,
  `desc` text NULL,
  `amount` float NULL,
  `status` tinyint(2) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
);

ALTER TABLE `invoice`
ADD `remaining_amount` float NULL AFTER `total_amount`,
ADD `transaction_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0=>Pending, 1=>Settle' AFTER `remaining_amount`;

-- 18-06-2025
ALTER TABLE `users`
ADD `bcc` text COLLATE 'utf8mb4_unicode_ci' NULL AFTER `shipping_zipcode`,
ADD `cc` text COLLATE 'utf8mb4_unicode_ci' NULL AFTER `bcc`;

-- 19-06-2025

ALTER TABLE `order` ADD `order_number` varchar(256) COLLATE 'utf8mb4_0900_ai_ci' NULL AFTER `order_id`;

-- 20-06-2025

CREATE TABLE `product_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` int(11) NULL,
  `product_code` varchar(256) NULL,
  `qty` int(11) NULL,
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0=> Not Use, 1=>Use',
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
);


ALTER TABLE `invoice_item` ADD `product_stock_id` int NULL AFTER `product_id`;

-- 09-07-2025

ALTER TABLE `order` ADD `delivery_date` date NULL AFTER `invoice_file`;

ALTER TABLE `product_master` ADD `min_alert_qty` int(11) NULL AFTER `price`;

-- 10-07-2025
ALTER TABLE `invoice_item` CHANGE `product_stock_id` `product_stock_id` varchar(256) NULL AFTER `product_id`;

ALTER TABLE `invoice` ADD `save_send` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0=>Send, 1=>Save Send, 2=>Save Print';

-- After 1 PM

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `module_id` int NULL,
  `title` varchar(256) NULL,
  `date` date NULL,
  `response` text NULL,
  `updater` varchar(256) NULL,
);

-- 11-07-2025

ALTER TABLE `activity_log`
ADD `type` varchar(256) COLLATE 'utf8mb4_0900_ai_ci' NULL,
ADD `desc` text COLLATE 'utf8mb4_0900_ai_ci' NULL AFTER `type`;

ALTER TABLE `activity_log` ADD `status` varchar(50) COLLATE 'utf8mb4_0900_ai_ci' NULL;

ALTER TABLE `transaction` ADD `invoice_id` int NULL AFTER `customer_id`;


-- 17-07-2025

ALTER TABLE `product_master` CHANGE `min_alert_qty` `min_alert_qty` decimal(8,0) NULL AFTER `price`;

-- 04-08-2025
ALTER TABLE `invoice` CHANGE `invoice_no` `invoice_no` varchar(256) NULL AFTER `ship_to`;

-- 23-12-2025
CREATE TABLE `banners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `path` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `mobile_path` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` int NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `call_to_actioin_link` varchar(500) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci

CREATE TABLE `addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `address` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `city` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `state` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `zip_code` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `country` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `is_default` int NOT NULL DEFAULT '0' COMMENT '1=default',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` int NOT NULL COMMENT '0=Draft, 1=Published',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `contact_us` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(250) COLLATE utf8mb3_unicode_ci NOT NULL,
  `reason` varchar(250) COLLATE utf8mb3_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE `country_checker_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `country_name` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `country_code` varchar(10) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE `discount_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `code` int NOT NULL,
  `order_id` int NOT NULL,
  `code_id` int NOT NULL,
  `type` int NOT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `user_id` int NOT NULL,
  `remarks` text COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE `gift_card` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `amount` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE `gift_card_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int NOT NULL,
  `user_email` varchar(250) COLLATE utf8mb3_unicode_ci NOT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `gift_card_number` varchar(30) COLLATE utf8mb3_unicode_ci NOT NULL,
  `to_name` varchar(60) COLLATE utf8mb3_unicode_ci NOT NULL,
  `to_email` varchar(60) COLLATE utf8mb3_unicode_ci NOT NULL,
  `to_phone` varchar(20) COLLATE utf8mb3_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb3_unicode_ci,
  `is_claimed` int NOT NULL DEFAULT '0' COMMENT '0=No, 1=Yes',
  `pay_status` int NOT NULL DEFAULT '0' COMMENT '0=unpaid,1=paid',
  `paid_by` int NOT NULL DEFAULT '0',
  `expiry_date` date DEFAULT NULL,
  `pending_amount` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `variations` varchar(350) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `sub_title` varchar(350) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `base_amount` double NOT NULL DEFAULT '0',
  `quantity` double NOT NULL DEFAULT '0',
  `tax_amount` double NOT NULL DEFAULT '0',
  `discount_amount` double NOT NULL DEFAULT '0',
  `total_amount` double NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_no` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `pay_status` int NOT NULL DEFAULT '0' COMMENT '0=unpaid,1=paid',
  `base_amount` double NOT NULL DEFAULT '0',
  `discount_amount` double NOT NULL DEFAULT '0',
  `shipping_amount` double NOT NULL DEFAULT '0',
  `tax_amount` double NOT NULL DEFAULT '0',
  `tax_percent` double NOT NULL DEFAULT '0',
  `total_amount` double NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '0' COMMENT '0=pending, 1=accepted, 2=out for delivery, -1=rejected	,3=delivered',
  `instructions` text COLLATE utf8mb4_general_ci,
  `order_hash` text COLLATE utf8mb4_general_ci NOT NULL,
  `payment_ref_id` int NOT NULL DEFAULT '-1',
  `billing_id` int NOT NULL DEFAULT '0',
  `shipping_id` int NOT NULL DEFAULT '0',
  `pay_remarks` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_by` int NOT NULL COMMENT '0=stripe, 1=razorpay',
  `discount_code` varchar(250) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `discount_code_id` int NOT NULL DEFAULT '-1',
  `discount_type` int NOT NULL DEFAULT '-1' COMMENT '0= gift card',
  `currency_symbol` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'CAD',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `payment_reference` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pay_id` varchar(50) NOT NULL,
  `cust_id` int NOT NULL,
  `card_details` text,
  `amount` double NOT NULL,
  `pay_by` varchar(100) NOT NULL DEFAULT '',
  `order_id` int NOT NULL,
  `status` int NOT NULL DEFAULT '-1' COMMENT '0=failed, 1=success',
  `json` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `product_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `type` int NOT NULL COMMENT '0=parent, 1=sub',
  `parent_id` int NOT NULL DEFAULT '0',
  `is_color` int NOT NULL DEFAULT '0' COMMENT '0=no,1=yes',
  `color_code` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `product_resource` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE `product_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `rating` int NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT '0=pending,1=approve,2=reject',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `product_selected_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attribute_id` int NOT NULL,
  `item_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL DEFAULT '0' COMMENT '0=parent,0>variation_product',
  `attribute_id` int NOT NULL DEFAULT '-1',
  `item_id` int NOT NULL DEFAULT '-1',
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `sku` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image_galley` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` int NOT NULL,
  `sub_category` int NOT NULL DEFAULT '0',
  `status` int NOT NULL,
  `type` int NOT NULL COMMENT '0=simple, 1=variations',
  `features` longtext COLLATE utf8mb4_general_ci,
  `specifications` longtext COLLATE utf8mb4_general_ci,
  `regular_price` double NOT NULL DEFAULT '0',
  `sale_price` double DEFAULT '0',
  `sale_price_usd` double NOT NULL DEFAULT '0',
  `regular_price_usd` double NOT NULL DEFAULT '0',
  `sale_price_inr` double NOT NULL DEFAULT '0',
  `regular_price_inr` double NOT NULL DEFAULT '0',
  `weight` varchar(250) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `attribute_ids` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `has_product_atributes` int NOT NULL DEFAULT '0' COMMENT '0=no, 1=yes',
  `product_atributes` text COLLATE utf8mb4_general_ci NOT NULL,
  `meta_title` varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `meta_description` varchar(300) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `meta_keywords` text COLLATE utf8mb4_general_ci NOT NULL,
  `has_stock` int NOT NULL DEFAULT '1' COMMENT '0=No, 1=Yes, Only for simple product',
  `reorder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `products_variations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `sale_price` double NOT NULL DEFAULT '0',
  `regular_price` double NOT NULL DEFAULT '0',
  `weight` varchar(250) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sale_price_usd` double NOT NULL DEFAULT '0',
  `regular_price_usd` double NOT NULL DEFAULT '0',
  `sale_price_inr` double NOT NULL DEFAULT '0',
  `regular_price_inr` double NOT NULL DEFAULT '0',
  `has_stock` int NOT NULL DEFAULT '1' COMMENT '0=No, 1=Yes',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE `products_variations_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `item_id` int NOT NULL,
  `pv_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` varchar(5) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE `sub_category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `c_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` int NOT NULL COMMENT '0=Draft, 1=Published	',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reorder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `webhook_calls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `exception` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `products`
CHANGE `title` `title` varchar(255) COLLATE 'utf8mb4_general_ci' NULL AFTER `item_id`,
CHANGE `slug` `slug` varchar(255) COLLATE 'utf8mb4_general_ci' NULL AFTER `title`,
CHANGE `description` `description` text COLLATE 'utf8mb4_general_ci' NULL AFTER `slug`,
CHANGE `sku` `sku` varchar(255) COLLATE 'utf8mb4_general_ci' NULL AFTER `description`,
CHANGE `category` `category` int NULL AFTER `image_galley`,
CHANGE `status` `status` int NULL AFTER `sub_category`,
CHANGE `product_atributes` `product_atributes` text COLLATE 'utf8mb4_general_ci' NULL AFTER `has_product_atributes`,
CHANGE `meta_keywords` `meta_keywords` text COLLATE 'utf8mb4_general_ci' NULL AFTER `meta_description`;

ALTER TABLE `products` ADD `product_master_id` int NULL AFTER `id`;


-- 30-12-2025

ALTER TABLE `users` ADD `last_name` varchar(256) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `name`;

ALTER TABLE `users` CHANGE `access_token` `access_token` longtext COLLATE 'utf8mb4_unicode_ci' NULL AFTER `device_token`;

-- 08-01-2026
CREATE TABLE `brand` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(256) NULL,
  `picture` text NULL,
  `status` tinyint NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `product_master` ADD `brand_id` int NULL AFTER `id`;

-- 12-01-2026
ALTER TABLE `users` ADD `role` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0=>Customer, 1=>Users, 2=>Dealer' AFTER `status`;

ALTER TABLE `users` ADD `discount_per` int NULL AFTER `role`;

-- 20-01-2026
ALTER TABLE `invoice` ADD `invoice_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '1=>Front, 0=>Backend';

ALTER TABLE `invoice` ADD `tax_amount` double NOT NULL DEFAULT '0';

ALTER TABLE `orders` ADD `dealer_discount` double NOT NULL DEFAULT '0' AFTER `total_amount`;