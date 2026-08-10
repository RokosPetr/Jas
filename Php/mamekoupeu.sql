-- Adminer 5.4.2 MariaDB 11.4.12-MariaDB-ubu2204-log dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

CREATE TABLE `bath_bathrooms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(500) NOT NULL,
  `priority` tinyint(3) unsigned NOT NULL,
  `virtual_picture_focus` smallint(5) unsigned NOT NULL DEFAULT 180,
  `link_picture_position` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deleted_by` (`deleted_by`),
  CONSTRAINT `bath_bathrooms_ibfk_1` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `bath_bathroom_item_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bathroom` int(10) unsigned NOT NULL,
  `item` int(10) unsigned NOT NULL,
  `link` varchar(500) NOT NULL,
  `position_x` decimal(4,1) unsigned NOT NULL,
  `position_y` decimal(4,1) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bathroom` (`bathroom`),
  KEY `item` (`item`),
  CONSTRAINT `bath_bathroom_item_link_ibfk_1` FOREIGN KEY (`bathroom`) REFERENCES `bath_bathrooms` (`id`),
  CONSTRAINT `bath_bathroom_item_link_ibfk_2` FOREIGN KEY (`item`) REFERENCES `stock_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `bath_bathroom_options` (
  `bathroom` int(10) unsigned NOT NULL,
  `option` int(10) unsigned NOT NULL,
  PRIMARY KEY (`bathroom`,`option`),
  KEY `option` (`option`),
  CONSTRAINT `bath_bathroom_options_ibfk_1` FOREIGN KEY (`bathroom`) REFERENCES `bath_bathrooms` (`id`),
  CONSTRAINT `bath_bathroom_options_ibfk_2` FOREIGN KEY (`option`) REFERENCES `bath_parameter_options` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `bath_bathroom_ratings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bathroom` int(10) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bathroom` (`bathroom`),
  CONSTRAINT `bath_bathroom_ratings_ibfk_1` FOREIGN KEY (`bathroom`) REFERENCES `bath_bathrooms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `bath_parameters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `order` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `bath_parameter_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parameter` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `order` tinyint(3) unsigned NOT NULL,
  `picture` int(10) unsigned DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parameter` (`parameter`),
  KEY `picture` (`picture`),
  CONSTRAINT `bath_parameter_options_ibfk_1` FOREIGN KEY (`parameter`) REFERENCES `bath_parameters` (`id`),
  CONSTRAINT `bath_parameter_options_ibfk_2` FOREIGN KEY (`picture`) REFERENCES `sys_files` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `bath_pictures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bathroom` int(10) unsigned NOT NULL,
  `position` tinyint(3) unsigned NOT NULL,
  `picture` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bathroom` (`bathroom`),
  KEY `picture` (`picture`),
  CONSTRAINT `bath_pictures_ibfk_1` FOREIGN KEY (`bathroom`) REFERENCES `bath_bathrooms` (`id`),
  CONSTRAINT `bath_pictures_ibfk_2` FOREIGN KEY (`picture`) REFERENCES `sys_files` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `cache_takings_overview` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `store` tinyint(3) unsigned NOT NULL,
  `group` int(10) unsigned NOT NULL,
  `producer` int(10) DEFAULT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `value` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `cache_takings_overview_k2` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `store` tinyint(3) unsigned NOT NULL,
  `group` int(10) unsigned NOT NULL,
  `producer` int(10) DEFAULT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `value` int(11) NOT NULL,
  `k2_partner_id` int(10) unsigned DEFAULT NULL,
  `k2_partner_code` varchar(50) DEFAULT NULL,
  `ico` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_companies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ico` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `information` text DEFAULT NULL,
  `country_code` varchar(3) NOT NULL,
  `takings_ignore` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ico` (`ico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_company_depots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store` int(10) unsigned NOT NULL,
  `company` int(10) unsigned NOT NULL,
  `voj` varchar(4) NOT NULL,
  `title` varchar(250) NOT NULL,
  `city` varchar(100) NOT NULL,
  `group` int(10) unsigned DEFAULT NULL,
  `information` text DEFAULT NULL,
  `discount_group` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store` (`store`,`company`,`voj`),
  KEY `company` (`company`),
  KEY `group` (`group`),
  KEY `discount_group` (`discount_group`),
  CONSTRAINT `delivery_company_depots_ibfk_1` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`),
  CONSTRAINT `delivery_company_depots_ibfk_2` FOREIGN KEY (`company`) REFERENCES `delivery_companies` (`id`),
  CONSTRAINT `delivery_company_depots_ibfk_3` FOREIGN KEY (`group`) REFERENCES `delivery_company_groups` (`id`),
  CONSTRAINT `delivery_company_depots_ibfk_4` FOREIGN KEY (`discount_group`) REFERENCES `stock_discount_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_company_depots_dealers` (
  `depot_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`depot_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `delivery_company_depots_dealers_ibfk_1` FOREIGN KEY (`depot_id`) REFERENCES `delivery_company_depots` (`id`),
  CONSTRAINT `delivery_company_depots_dealers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_company_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` smallint(5) unsigned NOT NULL,
  `dealer` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dealer` (`dealer`),
  CONSTRAINT `delivery_company_groups_ibfk_1` FOREIGN KEY (`dealer`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_contacts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `depot` int(10) unsigned NOT NULL,
  `first_name` varchar(250) NOT NULL,
  `last_name` varchar(250) NOT NULL,
  `order` smallint(6) unsigned NOT NULL DEFAULT 0,
  `position` varchar(250) NOT NULL,
  `phone` varchar(250) NOT NULL,
  `email` varchar(250) DEFAULT NULL,
  `url` varchar(250) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `depot` (`depot`),
  KEY `deleted_by` (`deleted_by`),
  CONSTRAINT `delivery_contacts_ibfk_1` FOREIGN KEY (`depot`) REFERENCES `delivery_company_depots` (`id`),
  CONSTRAINT `delivery_contacts_ibfk_2` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_customer_complaints` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store` int(10) unsigned NOT NULL,
  `item` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `response` text DEFAULT NULL,
  `state` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store` (`store`),
  KEY `item` (`item`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `delivery_customer_complaints_ibfk_1` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`),
  CONSTRAINT `delivery_customer_complaints_ibfk_2` FOREIGN KEY (`item`) REFERENCES `stock_items` (`id`),
  CONSTRAINT `delivery_customer_complaints_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `delivery_customer_complaints_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_depot_address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `depot` int(10) unsigned NOT NULL,
  `street` varchar(250) NOT NULL,
  `number` varchar(250) NOT NULL,
  `city` varchar(250) NOT NULL,
  `zip` varchar(250) NOT NULL,
  `district` varchar(250) NOT NULL,
  `open_hours` varchar(250) DEFAULT NULL,
  `billing_email` varchar(250) DEFAULT NULL,
  `complain_email` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `depot` (`depot`),
  CONSTRAINT `delivery_depot_address_ibfk_1` FOREIGN KEY (`depot`) REFERENCES `delivery_company_depots` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store` int(10) unsigned NOT NULL,
  `number` int(10) unsigned NOT NULL,
  `issue_year` smallint(5) unsigned NOT NULL,
  `dispatch_start` date DEFAULT NULL,
  `dispatch_end` date DEFAULT NULL,
  `remark` varchar(2000) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store` (`store`,`number`,`issue_year`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `delivery_item_ibfk_1` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`),
  CONSTRAINT `delivery_item_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `delivery_item_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_missing_stands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stand_check` int(10) unsigned NOT NULL,
  `stand_note` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `stand_check` (`stand_check`),
  KEY `stand_note` (`stand_note`),
  CONSTRAINT `delivery_missing_stands_ibfk_1` FOREIGN KEY (`stand_check`) REFERENCES `delivery_stand_checks` (`id`),
  CONSTRAINT `delivery_missing_stands_ibfk_2` FOREIGN KEY (`stand_note`) REFERENCES `stock_stand_notes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store` int(10) unsigned NOT NULL,
  `number` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `description` varchar(100) NOT NULL,
  `season` int(10) unsigned DEFAULT NULL,
  `state_char` char(1) NOT NULL,
  `state` tinyint(3) unsigned DEFAULT NULL,
  `movement_number` smallint(5) unsigned NOT NULL,
  `movement_type` tinyint(3) unsigned NOT NULL,
  `depot` int(10) unsigned DEFAULT NULL,
  `bill` varchar(20) DEFAULT NULL,
  `depot_note` varchar(20) DEFAULT NULL,
  `cancel_note` int(10) unsigned DEFAULT NULL,
  `parent` bigint(20) unsigned DEFAULT NULL,
  `checked` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `net_sum` decimal(11,3) NOT NULL DEFAULT 0.000,
  `gross_sum` decimal(11,3) NOT NULL DEFAULT 0.000,
  `tax_sum` decimal(11,3) NOT NULL DEFAULT 0.000,
  `remark` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store` (`store`,`number`,`date`,`movement_number`),
  KEY `depot` (`depot`),
  KEY `parent` (`parent`),
  KEY `date` (`date`),
  CONSTRAINT `delivery_notes_ibfk_1` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`),
  CONSTRAINT `delivery_notes_ibfk_2` FOREIGN KEY (`depot`) REFERENCES `delivery_company_depots` (`id`),
  CONSTRAINT `delivery_notes_ibfk_3` FOREIGN KEY (`parent`) REFERENCES `delivery_notes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_notes_k2` (
  `store` int(10) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `year` int(10) unsigned NOT NULL,
  `month` int(10) unsigned NOT NULL,
  `real_sale` decimal(11,3) DEFAULT NULL,
  `real_profit` decimal(11,3) DEFAULT NULL,
  `sales_sale` decimal(11,3) DEFAULT NULL,
  `sales_profit` decimal(11,3) DEFAULT NULL,
  `release_sale` decimal(11,3) DEFAULT NULL,
  `release_profit` decimal(11,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_note_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `note` bigint(20) unsigned NOT NULL,
  `item` int(10) unsigned NOT NULL,
  `amount` decimal(9,3) NOT NULL,
  `sell_price` decimal(11,3) NOT NULL,
  `buy_price` decimal(11,3) NOT NULL,
  `discount` decimal(10,3) NOT NULL,
  `tax` tinyint(3) unsigned NOT NULL,
  `outlet_type` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `note` (`note`),
  KEY `item` (`item`),
  CONSTRAINT `delivery_note_items_ibfk_1` FOREIGN KEY (`note`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_note_items_ibfk_2` FOREIGN KEY (`item`) REFERENCES `stock_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_note_service` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `note` bigint(20) unsigned NOT NULL,
  `service` int(10) unsigned NOT NULL,
  `amount` decimal(9,3) NOT NULL,
  `sell_price` decimal(11,3) NOT NULL,
  `buy_price` decimal(11,3) NOT NULL,
  `discount` decimal(10,3) NOT NULL,
  `tax` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `note` (`note`),
  KEY `service` (`service`),
  CONSTRAINT `delivery_note_service_ibfk_1` FOREIGN KEY (`note`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_note_service_ibfk_2` FOREIGN KEY (`service`) REFERENCES `delivery_services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_sales_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `last_sale` int(11) NOT NULL,
  `sale_plan` int(11) NOT NULL,
  `real_sale` int(11) NOT NULL,
  `sale_plan_difference` int(11) NOT NULL,
  `last_sale_difference` int(11) NOT NULL,
  `last_profit` int(11) NOT NULL,
  `profit_plan` int(11) NOT NULL,
  `real_profit` int(11) NOT NULL,
  `profit_plan_difference` int(11) NOT NULL,
  `last_profit_difference` int(11) NOT NULL,
  `costs_plan` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store` (`store`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_sales_data_access` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned NOT NULL,
  `store` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`user`,`store`),
  CONSTRAINT `delivery_sales_data_access_ibfk_1` FOREIGN KEY (`user`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reg_number` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `group` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reg_number` (`reg_number`),
  KEY `group` (`group`),
  CONSTRAINT `delivery_services_ibfk_1` FOREIGN KEY (`group`) REFERENCES `delivery_service_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_service_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_stand_checks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `depot` int(10) unsigned NOT NULL,
  `remark` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `depot` (`depot`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `delivery_stand_checks_ibfk_1` FOREIGN KEY (`depot`) REFERENCES `delivery_company_depots` (`id`),
  CONSTRAINT `delivery_stand_checks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `delivery_stand_relocations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stand_note` int(10) unsigned NOT NULL,
  `target` int(10) unsigned DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stand_note` (`stand_note`),
  KEY `target` (`target`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `deleted_by` (`deleted_by`),
  CONSTRAINT `delivery_stand_relocations_ibfk_1` FOREIGN KEY (`stand_note`) REFERENCES `stock_stand_notes` (`id`),
  CONSTRAINT `delivery_stand_relocations_ibfk_2` FOREIGN KEY (`target`) REFERENCES `delivery_company_depots` (`id`),
  CONSTRAINT `delivery_stand_relocations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `delivery_stand_relocations_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `delivery_stand_relocations_ibfk_5` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `import_povsort` (
  `reg_number` varchar(10) NOT NULL,
  `quantity` decimal(9,3) NOT NULL,
  `min_order` decimal(9,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `mtz_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `order` smallint(5) unsigned NOT NULL,
  `tax` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  CONSTRAINT `mtz_groups_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `mtz_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `mtz_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reg_number` int(10) unsigned NOT NULL,
  `group` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) NOT NULL,
  `remark` text DEFAULT NULL,
  `package_size` smallint(5) unsigned DEFAULT NULL,
  `package_unit` tinyint(3) unsigned DEFAULT NULL,
  `order_unit` tinyint(3) unsigned DEFAULT NULL,
  `picture` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group` (`group`),
  KEY `picture` (`picture`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `deleted_by` (`deleted_by`),
  CONSTRAINT `mtz_items_ibfk_1` FOREIGN KEY (`group`) REFERENCES `mtz_groups` (`id`),
  CONSTRAINT `mtz_items_ibfk_2` FOREIGN KEY (`picture`) REFERENCES `sys_files` (`id`),
  CONSTRAINT `mtz_items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `mtz_items_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `mtz_items_ibfk_5` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `mtz_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `remark` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `mtz_orders_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `mtz_order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order` int(10) unsigned NOT NULL,
  `item` int(10) unsigned NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order` (`order`),
  KEY `item` (`item`),
  CONSTRAINT `mtz_order_items_ibfk_1` FOREIGN KEY (`order`) REFERENCES `mtz_orders` (`id`),
  CONSTRAINT `mtz_order_items_ibfk_2` FOREIGN KEY (`item`) REFERENCES `mtz_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_catalog_numbers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `item` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`item`),
  CONSTRAINT `stock_catalog_numbers_ibfk_1` FOREIGN KEY (`item`) REFERENCES `stock_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_cubicles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `depot` int(10) unsigned NOT NULL,
  `code_first_part` int(11) NOT NULL,
  `code_second_part` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `size` decimal(9,2) NOT NULL,
  `picture` int(10) unsigned DEFAULT NULL,
  `is_rival` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `remark` text DEFAULT NULL,
  `tag` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_first_part` (`code_first_part`,`code_second_part`),
  KEY `depot` (`depot`),
  KEY `picture` (`picture`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `deleted_by` (`deleted_by`),
  CONSTRAINT `stock_cubicles_ibfk_1` FOREIGN KEY (`depot`) REFERENCES `delivery_company_depots` (`id`),
  CONSTRAINT `stock_cubicles_ibfk_2` FOREIGN KEY (`picture`) REFERENCES `sys_files` (`id`),
  CONSTRAINT `stock_cubicles_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `stock_cubicles_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `stock_cubicles_ibfk_5` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_cubicle_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cubicle` int(10) unsigned NOT NULL,
  `item` int(10) unsigned NOT NULL,
  `quantity` decimal(9,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cubicle` (`cubicle`),
  KEY `item` (`item`),
  CONSTRAINT `stock_cubicle_items_ibfk_1` FOREIGN KEY (`cubicle`) REFERENCES `stock_cubicles` (`id`),
  CONSTRAINT `stock_cubicle_items_ibfk_2` FOREIGN KEY (`item`) REFERENCES `stock_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_custom_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `view_type` tinyint(3) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `stock_custom_groups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `stock_custom_groups_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_custom_groups_stock_groups` (
  `custom_group_id` int(10) unsigned NOT NULL,
  `stock_group_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`custom_group_id`,`stock_group_id`),
  KEY `stock_group_id` (`stock_group_id`),
  CONSTRAINT `stock_custom_groups_stock_groups_ibfk_1` FOREIGN KEY (`custom_group_id`) REFERENCES `stock_custom_groups` (`id`),
  CONSTRAINT `stock_custom_groups_stock_groups_ibfk_2` FOREIGN KEY (`stock_group_id`) REFERENCES `stock_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_discount_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` smallint(5) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_discount_stock_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stock_group` int(10) unsigned NOT NULL,
  `discount_group` int(10) unsigned NOT NULL,
  `value` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_group` (`stock_group`,`discount_group`),
  KEY `discount_group` (`discount_group`),
  CONSTRAINT `stock_discount_stock_groups_ibfk_1` FOREIGN KEY (`stock_group`) REFERENCES `stock_groups` (`id`),
  CONSTRAINT `stock_discount_stock_groups_ibfk_2` FOREIGN KEY (`discount_group`) REFERENCES `stock_discount_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_discount_stock_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stock_item` int(10) unsigned NOT NULL,
  `discount_group` int(10) unsigned NOT NULL,
  `value` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_item` (`stock_item`,`discount_group`),
  KEY `discount_group` (`discount_group`),
  CONSTRAINT `stock_discount_stock_items_ibfk_1` FOREIGN KEY (`stock_item`) REFERENCES `stock_items` (`id`),
  CONSTRAINT `stock_discount_stock_items_ibfk_2` FOREIGN KEY (`discount_group`) REFERENCES `stock_discount_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `producer` int(10) unsigned NOT NULL,
  `number` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `no_transfers` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `producer` (`producer`),
  CONSTRAINT `stock_groups_ibfk_1` FOREIGN KEY (`producer`) REFERENCES `stock_producers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reg_number` varchar(15) NOT NULL,
  `name` varchar(255) NOT NULL,
  `producer` int(10) unsigned DEFAULT NULL,
  `group` int(10) unsigned DEFAULT NULL,
  `unit` int(10) unsigned DEFAULT NULL,
  `package` decimal(9,3) unsigned DEFAULT NULL,
  `palette` decimal(9,3) unsigned DEFAULT NULL,
  `price` decimal(9,3) unsigned DEFAULT NULL,
  `min_order` smallint(5) unsigned DEFAULT NULL,
  `global_producer` tinyint(3) unsigned DEFAULT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `status_changed_at` datetime DEFAULT NULL,
  `status_changed_by` int(10) unsigned DEFAULT NULL,
  `inactive_from` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reg_number` (`reg_number`),
  KEY `producer` (`producer`),
  KEY `group` (`group`),
  KEY `unit` (`unit`),
  KEY `updated_by` (`status_changed_by`),
  CONSTRAINT `stock_items_ibfk_1` FOREIGN KEY (`producer`) REFERENCES `stock_producers` (`id`),
  CONSTRAINT `stock_items_ibfk_2` FOREIGN KEY (`group`) REFERENCES `stock_groups` (`id`),
  CONSTRAINT `stock_items_ibfk_3` FOREIGN KEY (`unit`) REFERENCES `stock_units` (`id`),
  CONSTRAINT `stock_items_ibfk_4` FOREIGN KEY (`status_changed_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_main_storage_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `number` tinyint(3) unsigned NOT NULL,
  `state` tinyint(3) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `year` (`year`,`month`,`number`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `stock_main_storage_orders_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `stock_main_storage_orders_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_main_storage_order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order` int(10) unsigned NOT NULL,
  `item` int(10) unsigned NOT NULL,
  `palette_count` smallint(5) unsigned NOT NULL,
  `quantity` decimal(10,3) unsigned NOT NULL,
  `stocked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order` (`order`),
  KEY `item` (`item`),
  CONSTRAINT `stock_main_storage_order_items_ibfk_1` FOREIGN KEY (`order`) REFERENCES `stock_main_storage_orders` (`id`),
  CONSTRAINT `stock_main_storage_order_items_ibfk_2` FOREIGN KEY (`item`) REFERENCES `stock_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_obligatory_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item` int(10) unsigned NOT NULL,
  `quantity` decimal(9,3) NOT NULL,
  `min_order` decimal(9,3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item` (`item`),
  CONSTRAINT `stock_obligatory_items_ibfk_1` FOREIGN KEY (`item`) REFERENCES `stock_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_obligatory_item_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `obligatory_item` int(10) unsigned NOT NULL,
  `store` int(10) unsigned NOT NULL,
  `order_sum` decimal(9,3) NOT NULL,
  `pre_order_quantity` decimal(9,3) NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `obligatory_item` (`obligatory_item`),
  KEY `store` (`store`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `stock_obligatory_item_orders_ibfk_1` FOREIGN KEY (`obligatory_item`) REFERENCES `stock_obligatory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_obligatory_item_orders_ibfk_2` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`),
  CONSTRAINT `stock_obligatory_item_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_producers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent` int(10) unsigned DEFAULT NULL,
  `company` int(10) unsigned DEFAULT NULL,
  `no_transfers` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `color` varchar(10) NOT NULL DEFAULT '#000000',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `company` (`company`),
  CONSTRAINT `stock_producers_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `stock_producers` (`id`),
  CONSTRAINT `stock_producers_ibfk_2` FOREIGN KEY (`company`) REFERENCES `delivery_companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_sectors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `store` (`store`),
  CONSTRAINT `stock_sectors_ibfk_1` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_series` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `key` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_series_items` (
  `series_id` int(10) unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`series_id`,`item_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `stock_series_items_ibfk_1` FOREIGN KEY (`series_id`) REFERENCES `stock_series` (`id`),
  CONSTRAINT `stock_series_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `stock_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_stands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `code_first_part` int(10) unsigned NOT NULL,
  `code_second_part` int(10) unsigned NOT NULL,
  `name` varchar(250) NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `producer` int(10) unsigned DEFAULT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `plate_order_type` tinyint(3) unsigned DEFAULT NULL,
  `width` decimal(9,2) NOT NULL,
  `depth` decimal(9,2) NOT NULL,
  `height` decimal(9,2) NOT NULL,
  `unit_count` smallint(5) unsigned NOT NULL,
  `picture` int(10) unsigned DEFAULT NULL,
  `second_picture` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `change_email` bit(1) NOT NULL,
  `piece_price_tag` bit(1) NOT NULL,
  `plate_price_tag` bit(1) NOT NULL,
  `b2b` bit(1) NOT NULL,
  `b2c` bit(1) NOT NULL,
  `qr` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `producer` (`producer`),
  KEY `picture` (`picture`),
  KEY `second_picture` (`second_picture`),
  KEY `deleted_by` (`deleted_by`),
  CONSTRAINT `stock_stands_ibfk_1` FOREIGN KEY (`producer`) REFERENCES `stock_producers` (`id`),
  CONSTRAINT `stock_stands_ibfk_2` FOREIGN KEY (`picture`) REFERENCES `sys_files` (`id`),
  CONSTRAINT `stock_stands_ibfk_3` FOREIGN KEY (`second_picture`) REFERENCES `sys_files` (`id`),
  CONSTRAINT `stock_stands_ibfk_4` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_stand_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `depot` int(10) unsigned NOT NULL,
  `stand` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `note` int(10) unsigned DEFAULT NULL,
  `note_date` date DEFAULT NULL,
  `invoice` int(10) unsigned DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `remove_date` date DEFAULT NULL,
  `remove_note` int(10) unsigned DEFAULT NULL,
  `remove_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `depot` (`depot`),
  KEY `stand` (`stand`),
  KEY `remove_by` (`remove_by`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `stock_stand_notes_ibfk_1` FOREIGN KEY (`depot`) REFERENCES `delivery_company_depots` (`id`),
  CONSTRAINT `stock_stand_notes_ibfk_2` FOREIGN KEY (`stand`) REFERENCES `stock_stands` (`id`),
  CONSTRAINT `stock_stand_notes_ibfk_3` FOREIGN KEY (`remove_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `stock_stand_notes_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `stock_stand_notes_ibfk_5` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_stand_plates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stand` int(10) unsigned NOT NULL,
  `order` smallint(6) NOT NULL,
  `description` varchar(50) NOT NULL DEFAULT '',
  `dimension` varchar(50) DEFAULT NULL,
  `picture` int(10) unsigned DEFAULT NULL,
  `qr` varchar(1000) DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `picture` (`picture`),
  KEY `deleted_by` (`deleted_by`),
  KEY `stand` (`stand`),
  CONSTRAINT `stock_stand_plates_ibfk_1` FOREIGN KEY (`stand`) REFERENCES `stock_stands` (`id`),
  CONSTRAINT `stock_stand_plates_ibfk_2` FOREIGN KEY (`picture`) REFERENCES `sys_files` (`id`),
  CONSTRAINT `stock_stand_plates_ibfk_3` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_stand_plate_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plate` int(10) unsigned NOT NULL,
  `item` int(10) unsigned NOT NULL,
  `order` smallint(6) NOT NULL,
  `photo_item` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `picture` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `series_item` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`item`),
  KEY `picture` (`picture`),
  KEY `deleted_by` (`deleted_by`),
  KEY `plate` (`plate`),
  CONSTRAINT `stock_stand_plate_items_ibfk_1` FOREIGN KEY (`plate`) REFERENCES `stock_stand_plates` (`id`),
  CONSTRAINT `stock_stand_plate_items_ibfk_2` FOREIGN KEY (`item`) REFERENCES `stock_items` (`id`),
  CONSTRAINT `stock_stand_plate_items_ibfk_3` FOREIGN KEY (`picture`) REFERENCES `sys_files` (`id`),
  CONSTRAINT `stock_stand_plate_items_ibfk_4` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_variants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item` int(10) unsigned NOT NULL,
  `supplement` char(1) NOT NULL,
  `remark` varchar(50) DEFAULT NULL,
  `catalog` int(10) unsigned DEFAULT NULL,
  `store` int(10) unsigned NOT NULL,
  `sector` int(10) unsigned DEFAULT NULL,
  `quantity` decimal(9,3) NOT NULL,
  `palette_quantity` decimal(9,3) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `sample` tinyint(4) NOT NULL DEFAULT 0,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `outlet_type` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`item`),
  KEY `catalog` (`catalog`),
  KEY `store` (`store`),
  KEY `sector` (`sector`),
  CONSTRAINT `stock_variants_ibfk_1` FOREIGN KEY (`item`) REFERENCES `stock_items` (`id`),
  CONSTRAINT `stock_variants_ibfk_2` FOREIGN KEY (`catalog`) REFERENCES `stock_catalog_numbers` (`id`),
  CONSTRAINT `stock_variants_ibfk_3` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`),
  CONSTRAINT `stock_variants_ibfk_4` FOREIGN KEY (`sector`) REFERENCES `stock_sectors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_warehouseman_hours` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `length` decimal(3,1) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `stock_warehouseman_hours_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `stock_warehouseman_hours_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_warehouseman_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `web_id` int(10) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `web_id` (`web_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `stock_warehousemen` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `web_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `deleted_by` (`deleted_by`),
  CONSTRAINT `stock_warehousemen_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `stock_warehousemen_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `stock_warehousemen_ibfk_3` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_external_logins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phpsessid` varchar(255) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `expiration` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `phpsessid` (`phpsessid`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sys_external_logins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `size` bigint(20) NOT NULL,
  `mime_type` varchar(50) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `web_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `sys_files_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_imports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_mails` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `sent_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  CONSTRAINT `sys_mails_ibfk_1` FOREIGN KEY (`user`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_menu_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `presenter` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `order` smallint(5) unsigned NOT NULL,
  `active` tinyint(4) NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module` (`module`,`presenter`,`action`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `sys_menu_items_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `sys_menu_items_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_phones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(20) NOT NULL,
  `description` varchar(250) NOT NULL,
  `user` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  CONSTRAINT `sys_phones_ibfk_1` FOREIGN KEY (`user`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_resources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `link` varchar(150) NOT NULL,
  `description` varchar(2000) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link` (`link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(2000) NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `sys_roles_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `sys_roles_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_roles_resources` (
  `role_id` int(10) unsigned NOT NULL,
  `resource_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`resource_id`),
  KEY `resource_id` (`resource_id`),
  CONSTRAINT `sys_roles_resources_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`),
  CONSTRAINT `sys_roles_resources_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `sys_resources` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phpsessid` varchar(255) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `expiration` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phpsessid` (`phpsessid`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sys_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_stores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `street` varchar(255) NOT NULL,
  `zipCode` varchar(10) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `manager` int(10) unsigned DEFAULT NULL,
  `color` varchar(10) NOT NULL DEFAULT '#000000',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `manager` (`manager`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `sys_stores_ibfk_1` FOREIGN KEY (`manager`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `sys_stores_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_store_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store` (`store`,`name`),
  CONSTRAINT `sys_store_settings_ibfk_1` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `internal_login` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `banned` tinyint(4) NOT NULL DEFAULT 0,
  `incorrect_logins` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `login_counter` smallint(5) unsigned NOT NULL DEFAULT 0,
  `token` varchar(255) DEFAULT NULL,
  `token_validity` datetime DEFAULT NULL,
  `store` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `store` (`store`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `deleted_by` (`deleted_by`),
  CONSTRAINT `sys_users_ibfk_1` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`),
  CONSTRAINT `sys_users_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `sys_users_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `sys_users_ibfk_4` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_users_roles` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `sys_users_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `sys_users_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_user_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned NOT NULL,
  `component` varchar(255) NOT NULL,
  `setting` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`user`,`component`),
  CONSTRAINT `sys_user_settings_ibfk_1` FOREIGN KEY (`user`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `sys_working_days` (
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `month` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `trans_cars_stores` (
  `car_id` int(10) unsigned NOT NULL,
  `store_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`car_id`,`store_id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `trans_cars_stores_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `trans_store_cars` (`id`),
  CONSTRAINT `trans_cars_stores_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `sys_stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `trans_store_cars` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `license_plate` varchar(50) NOT NULL,
  `weight_capacity` smallint(5) unsigned NOT NULL,
  `driver` int(10) unsigned DEFAULT NULL,
  `home_store` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `driver` (`driver`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `deleted_by` (`deleted_by`),
  KEY `home_store` (`home_store`),
  CONSTRAINT `trans_store_cars_ibfk_1` FOREIGN KEY (`driver`) REFERENCES `trans_store_drivers` (`id`),
  CONSTRAINT `trans_store_cars_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `trans_store_cars_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `trans_store_cars_ibfk_4` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `trans_store_cars_ibfk_5` FOREIGN KEY (`home_store`) REFERENCES `sys_stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `trans_store_drivers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `user` int(10) unsigned DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `deleted_by` (`deleted_by`),
  CONSTRAINT `trans_store_drivers_ibfk_1` FOREIGN KEY (`user`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `trans_store_drivers_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `trans_store_drivers_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `trans_store_drivers_ibfk_4` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `trans_store_transports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store` int(10) unsigned NOT NULL,
  `car` int(10) unsigned NOT NULL,
  `driver` int(10) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `time_from` decimal(4,2) NOT NULL,
  `time_till` decimal(4,2) NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `reason` tinyint(3) unsigned DEFAULT NULL,
  `reason_remark` text DEFAULT NULL,
  `drive_duration` smallint(5) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `locked_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store` (`store`),
  KEY `car` (`car`),
  KEY `driver` (`driver`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `deleted_by` (`deleted_by`),
  KEY `locked_by` (`locked_by`),
  CONSTRAINT `trans_store_transports_ibfk_1` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`),
  CONSTRAINT `trans_store_transports_ibfk_2` FOREIGN KEY (`car`) REFERENCES `trans_store_cars` (`id`),
  CONSTRAINT `trans_store_transports_ibfk_3` FOREIGN KEY (`driver`) REFERENCES `trans_store_drivers` (`id`),
  CONSTRAINT `trans_store_transports_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `trans_store_transports_ibfk_5` FOREIGN KEY (`updated_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `trans_store_transports_ibfk_6` FOREIGN KEY (`deleted_by`) REFERENCES `sys_users` (`id`),
  CONSTRAINT `trans_store_transports_ibfk_7` FOREIGN KEY (`locked_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `trans_store_transport_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `target` int(10) unsigned NOT NULL,
  `store` int(10) unsigned NOT NULL,
  `delivery_note` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `weight` smallint(5) unsigned NOT NULL,
  `delivered` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `set_delivered_by` int(10) unsigned DEFAULT NULL,
  `set_delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `target` (`target`),
  KEY `store` (`store`),
  KEY `set_delivered_by` (`set_delivered_by`),
  CONSTRAINT `trans_store_transport_items_ibfk_1` FOREIGN KEY (`target`) REFERENCES `trans_store_transport_targets` (`id`),
  CONSTRAINT `trans_store_transport_items_ibfk_2` FOREIGN KEY (`store`) REFERENCES `sys_stores` (`id`),
  CONSTRAINT `trans_store_transport_items_ibfk_3` FOREIGN KEY (`set_delivered_by`) REFERENCES `sys_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `trans_store_transport_item_parts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item` int(10) unsigned NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`item`),
  CONSTRAINT `trans_store_transport_item_parts_ibfk_1` FOREIGN KEY (`item`) REFERENCES `trans_store_transport_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `trans_store_transport_targets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transport` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `tariff` tinyint(3) unsigned DEFAULT NULL,
  `payment` tinyint(3) unsigned DEFAULT NULL,
  `remark` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transport` (`transport`),
  CONSTRAINT `trans_store_transport_targets_ibfk_1` FOREIGN KEY (`transport`) REFERENCES `trans_store_transports` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `wiki_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `module` tinyint(3) unsigned NOT NULL,
  `creatable` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `updatable` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `deletable` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `lockable` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `remark` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `wiki_params` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `order` tinyint(3) unsigned NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `virtual` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `remark` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`item`),
  CONSTRAINT `wiki_params_ibfk_1` FOREIGN KEY (`item`) REFERENCES `wiki_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


-- 2026-08-10 06:46:50 UTC
