-- Parental Control Database Schema Dump
-- Generated: 2026-06-29 11:36:07

SET FOREIGN_KEY_CHECKS=0;

-- Table structure for table `access_audit_log`
DROP TABLE IF EXISTS `access_audit_log`;
CREATE TABLE `access_audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `access_audit_log_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `access_audit_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `app_usage`
DROP TABLE IF EXISTS `app_usage`;
CREATE TABLE `app_usage` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `app_name` varchar(150) DEFAULT NULL,
  `usage_date` date NOT NULL,
  `foreground_sec` int(10) unsigned DEFAULT 0,
  `background_sec` int(10) unsigned DEFAULT 0,
  `launch_count` int(10) unsigned DEFAULT 0,
  `data_sent` bigint(20) unsigned DEFAULT 0,
  `data_received` bigint(20) unsigned DEFAULT 0,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_daily_usage` (`device_id`,`package_name`,`usage_date`),
  KEY `idx_device_date` (`device_id`,`usage_date`),
  KEY `idx_package` (`package_name`),
  CONSTRAINT `app_usage_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `audio_recordings`
DROP TABLE IF EXISTS `audio_recordings`;
CREATE TABLE `audio_recordings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `device_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `duration_sec` int(10) unsigned NOT NULL,
  `quality` enum('low','medium','high') DEFAULT 'medium',
  `trigger_type` enum('manual','scheduled','alert') NOT NULL,
  `trigger_reason` varchar(255) DEFAULT NULL,
  `status` enum('recording','uploaded','viewed','deleted') DEFAULT 'recording',
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `parent_viewed` tinyint(1) DEFAULT 0,
  `parent_viewed_at` timestamp NULL DEFAULT NULL,
  `parent_deleted` tinyint(1) DEFAULT 0,
  `parent_notes` text DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `requested_by` (`requested_by`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_uuid` (`uuid`),
  KEY `idx_status` (`status`),
  KEY `idx_started_at` (`started_at`),
  CONSTRAINT `audio_recordings_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `audio_recordings_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `blocked_apps`
DROP TABLE IF EXISTS `blocked_apps`;
CREATE TABLE `blocked_apps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `app_name` varchar(150) DEFAULT NULL,
  `block_type` enum('permanent','scheduled','time_limited') DEFAULT 'permanent',
  `reason` text DEFAULT NULL,
  `blocked_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_device_package` (`device_id`,`package_name`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `blocked_apps_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `blocked_numbers`
DROP TABLE IF EXISTS `blocked_numbers`;
CREATE TABLE `blocked_numbers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `block_calls` tinyint(1) DEFAULT 1,
  `block_sms` tinyint(1) DEFAULT 1,
  `reason` text DEFAULT NULL,
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_device_number` (`device_id`,`phone_number`),
  KEY `idx_device_id` (`device_id`),
  CONSTRAINT `blocked_numbers_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `blocked_websites`
DROP TABLE IF EXISTS `blocked_websites`;
CREATE TABLE `blocked_websites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `domain` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `block_type` enum('domain','keyword','category') DEFAULT 'domain',
  `is_active` tinyint(1) DEFAULT 1,
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_domain` (`domain`),
  KEY `idx_category` (`category`),
  CONSTRAINT `blocked_websites_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `browsing_history`
DROP TABLE IF EXISTS `browsing_history`;
CREATE TABLE `browsing_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `url` text NOT NULL,
  `title` varchar(500) DEFAULT NULL,
  `browser_name` varchar(100) DEFAULT NULL,
  `visit_count` int(10) unsigned DEFAULT 1,
  `visited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_visited_at` (`visited_at`),
  CONSTRAINT `browsing_history_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `call_logs`
DROP TABLE IF EXISTS `call_logs`;
CREATE TABLE `call_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `call_type` enum('incoming','outgoing','missed','rejected','blocked') NOT NULL,
  `duration_sec` int(10) unsigned DEFAULT 0,
  `is_unknown` tinyint(1) DEFAULT 0,
  `parent_read` tinyint(1) DEFAULT 0,
  `called_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_called_at` (`called_at`),
  KEY `idx_phone` (`phone_number`),
  KEY `idx_parent_read` (`parent_read`),
  CONSTRAINT `call_logs_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `camera_captures`
DROP TABLE IF EXISTS `camera_captures`;
CREATE TABLE `camera_captures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `device_id` bigint(20) unsigned NOT NULL,
  `capture_type` enum('photo','video') NOT NULL,
  `camera_facing` enum('front','back') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `duration_sec` int(10) unsigned DEFAULT NULL,
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `trigger_type` enum('manual','scheduled','alert') NOT NULL,
  `trigger_reason` varchar(255) DEFAULT NULL,
  `status` enum('pending','uploaded','viewed','deleted') DEFAULT 'pending',
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `parent_viewed` tinyint(1) DEFAULT 0,
  `parent_viewed_at` timestamp NULL DEFAULT NULL,
  `parent_deleted` tinyint(1) DEFAULT 0,
  `parent_notes` text DEFAULT NULL,
  `captured_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `requested_by` (`requested_by`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_uuid` (`uuid`),
  KEY `idx_capture_type` (`capture_type`),
  KEY `idx_status` (`status`),
  KEY `idx_captured_at` (`captured_at`),
  CONSTRAINT `camera_captures_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `camera_captures_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `child_consents`
DROP TABLE IF EXISTS `child_consents`;
CREATE TABLE `child_consents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `child_name` varchar(100) NOT NULL,
  `child_age` tinyint(3) unsigned NOT NULL,
  `policy_version` varchar(10) DEFAULT '2.0',
  `policy_text` text NOT NULL,
  `consent_status` enum('pending','accepted','revoked') DEFAULT 'pending',
  `consent_given_at` timestamp NULL DEFAULT NULL,
  `consent_ip` varchar(45) DEFAULT NULL,
  `consent_device` text DEFAULT NULL,
  `allow_camera` tinyint(1) DEFAULT 0,
  `allow_microphone` tinyint(1) DEFAULT 0,
  `allow_gallery` tinyint(1) DEFAULT 0,
  `allow_location` tinyint(1) DEFAULT 0,
  `allow_call_monitoring` tinyint(1) DEFAULT 0,
  `allow_sms_monitoring` tinyint(1) DEFAULT 0,
  `allow_app_monitoring` tinyint(1) DEFAULT 0,
  `allow_web_monitoring` tinyint(1) DEFAULT 0,
  `allow_screen_lock` tinyint(1) DEFAULT 0,
  `allow_contacts_sync` tinyint(1) DEFAULT 0,
  `show_permanent_notification` tinyint(1) DEFAULT 1,
  `show_monitoring_icon` tinyint(1) DEFAULT 1,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `revocation_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_consent_status` (`consent_status`),
  CONSTRAINT `child_consents_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `child_consents_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `contacts`
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_favorite` tinyint(1) DEFAULT 0,
  `photo_uri` text DEFAULT NULL,
  `times_contacted` int(10) unsigned DEFAULT 0,
  `last_contacted` timestamp NULL DEFAULT NULL,
  `synced_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_phone` (`phone_number`),
  CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `custom_alerts`
DROP TABLE IF EXISTS `custom_alerts`;
CREATE TABLE `custom_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `alert_name` varchar(100) NOT NULL,
  `alert_type` varchar(50) NOT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions`)),
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`actions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `trigger_count` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `custom_alerts_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `device_gallery`
DROP TABLE IF EXISTS `device_gallery`;
CREATE TABLE `device_gallery` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `device_id` bigint(20) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `media_type` enum('photo','video','audio','document') NOT NULL,
  `source_folder` varchar(255) DEFAULT NULL,
  `source_app` varchar(150) DEFAULT NULL,
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `duration_sec` int(10) unsigned DEFAULT NULL,
  `taken_at` timestamp NULL DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `parent_viewed` tinyint(1) DEFAULT 0,
  `parent_viewed_at` timestamp NULL DEFAULT NULL,
  `parent_flagged` tinyint(1) DEFAULT 0,
  `flag_reason` text DEFAULT NULL,
  `md5_hash` char(32) DEFAULT NULL,
  `sync_status` enum('pending','synced','failed') DEFAULT 'pending',
  `first_seen_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_uuid` (`uuid`),
  KEY `idx_media_type` (`media_type`),
  KEY `idx_source_app` (`source_app`),
  KEY `idx_taken_at` (`taken_at`),
  KEY `idx_md5_hash` (`md5_hash`),
  CONSTRAINT `device_gallery_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `device_locations`
DROP TABLE IF EXISTS `device_locations`;
CREATE TABLE `device_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `altitude` decimal(10,2) DEFAULT NULL,
  `accuracy` float DEFAULT NULL,
  `speed` float DEFAULT NULL,
  `bearing` float DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `provider` varchar(20) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_recorded_at` (`recorded_at`),
  CONSTRAINT `device_locations_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `device_settings`
DROP TABLE IF EXISTS `device_settings`;
CREATE TABLE `device_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_device_setting` (`device_id`,`setting_key`),
  CONSTRAINT `device_settings_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `devices`
DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `child_name` varchar(100) NOT NULL,
  `child_age` tinyint(3) unsigned NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_model` varchar(100) DEFAULT NULL,
  `device_brand` varchar(50) DEFAULT NULL,
  `android_version` varchar(20) DEFAULT NULL,
  `sdk_version` int(10) unsigned DEFAULT NULL,
  `device_id` varchar(255) NOT NULL,
  `imei` varchar(50) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `mac_address` varchar(50) DEFAULT NULL,
  `app_version` varchar(20) DEFAULT NULL,
  `battery_level` tinyint(3) unsigned DEFAULT 0,
  `is_charging` tinyint(1) DEFAULT 0,
  `is_screen_on` tinyint(1) DEFAULT 1,
  `current_wifi` varchar(100) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `last_location_lat` decimal(10,8) DEFAULT NULL,
  `last_location_lng` decimal(11,8) DEFAULT NULL,
  `last_location_at` timestamp NULL DEFAULT NULL,
  `perm_camera` tinyint(1) DEFAULT 0,
  `perm_microphone` tinyint(1) DEFAULT 0,
  `perm_storage` tinyint(1) DEFAULT 0,
  `perm_location` tinyint(1) DEFAULT 0,
  `perm_contacts` tinyint(1) DEFAULT 0,
  `perm_call_log` tinyint(1) DEFAULT 0,
  `perm_sms` tinyint(1) DEFAULT 0,
  `perm_overlay` tinyint(1) DEFAULT 0,
  `perm_usage_stats` tinyint(1) DEFAULT 0,
  `perm_accessibility` tinyint(1) DEFAULT 0,
  `perm_device_admin` tinyint(1) DEFAULT 0,
  `monitoring_enabled` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `is_locked_by_parent` tinyint(1) DEFAULT 0,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `device_id` (`device_id`),
  KEY `idx_uuid` (`uuid`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_is_online` (`is_online`),
  KEY `idx_last_seen` (`last_seen_at`),
  CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `installed_apps`
DROP TABLE IF EXISTS `installed_apps`;
CREATE TABLE `installed_apps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `app_name` varchar(150) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `version_name` varchar(50) DEFAULT NULL,
  `version_code` int(10) unsigned DEFAULT NULL,
  `app_size` bigint(20) unsigned DEFAULT NULL,
  `app_icon` text DEFAULT NULL,
  `is_system_app` tinyint(1) DEFAULT 0,
  `is_enabled` tinyint(1) DEFAULT 1,
  `install_date` timestamp NULL DEFAULT NULL,
  `update_date` timestamp NULL DEFAULT NULL,
  `first_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_device_package` (`device_id`,`package_name`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_package_name` (`package_name`),
  KEY `idx_is_system` (`is_system_app`),
  CONSTRAINT `installed_apps_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `notifications`
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `device_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `icon` varchar(50) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `device_id` (`device_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `personal_access_tokens`
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `remote_commands`
DROP TABLE IF EXISTS `remote_commands`;
CREATE TABLE `remote_commands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `device_id` bigint(20) unsigned NOT NULL,
  `command_category` enum('camera','microphone','screen','gallery','location','system','app','notification') NOT NULL,
  `command_type` varchar(50) NOT NULL,
  `command_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`command_data`)),
  `status` enum('pending','sent','executing','completed','failed','cancelled') DEFAULT 'pending',
  `result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`result`)),
  `error_message` text DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `retry_count` tinyint(3) unsigned DEFAULT 0,
  `max_retries` tinyint(3) unsigned DEFAULT 3,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `created_by` (`created_by`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_uuid` (`uuid`),
  KEY `idx_status` (`status`),
  KEY `idx_command_category` (`command_category`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `remote_commands_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `remote_commands_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `safe_zones`
DROP TABLE IF EXISTS `safe_zones`;
CREATE TABLE `safe_zones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radius` int(10) unsigned NOT NULL,
  `notify_on_enter` tinyint(1) DEFAULT 1,
  `notify_on_exit` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `safe_zones_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `scheduled_reports`
DROP TABLE IF EXISTS `scheduled_reports`;
CREATE TABLE `scheduled_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `device_id` bigint(20) unsigned DEFAULT NULL,
  `report_type` enum('daily','weekly','monthly') NOT NULL,
  `report_format` enum('email','pdf','both') DEFAULT 'email',
  `delivery_time` time DEFAULT NULL,
  `delivery_day` tinyint(3) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_sent_at` timestamp NULL DEFAULT NULL,
  `next_send_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `device_id` (`device_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_next_send` (`next_send_at`),
  CONSTRAINT `scheduled_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scheduled_reports_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `screen_locks`
DROP TABLE IF EXISTS `screen_locks`;
CREATE TABLE `screen_locks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `lock_type` enum('black_screen','custom_message','bedtime','study_time','punishment','emergency') NOT NULL,
  `message_title` varchar(255) DEFAULT NULL,
  `message_body` text DEFAULT NULL,
  `background_color` char(7) DEFAULT '#000000',
  `show_message` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `allow_emergency_calls` tinyint(1) DEFAULT 1,
  `allow_alarm` tinyint(1) DEFAULT 1,
  `allow_music` tinyint(1) DEFAULT 0,
  `whitelisted_numbers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`whitelisted_numbers`)),
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `unlocked_by` bigint(20) unsigned DEFAULT NULL,
  `locked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unlocked_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locked_by` (`locked_by`),
  KEY `unlocked_by` (`unlocked_by`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_lock_type` (`lock_type`),
  CONSTRAINT `screen_locks_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `screen_locks_ibfk_2` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `screen_locks_ibfk_3` FOREIGN KEY (`unlocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `screen_time`
DROP TABLE IF EXISTS `screen_time`;
CREATE TABLE `screen_time` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `total_sec` int(10) unsigned DEFAULT 0,
  `screen_on_sec` int(10) unsigned DEFAULT 0,
  `interactive_sec` int(10) unsigned DEFAULT 0,
  `unlock_count` int(10) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_device_date` (`device_id`,`date`),
  KEY `idx_device_date` (`device_id`,`date`),
  CONSTRAINT `screen_time_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `screenshots`
DROP TABLE IF EXISTS `screenshots`;
CREATE TABLE `screenshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `device_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `file_size` int(10) unsigned DEFAULT NULL,
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `trigger_type` enum('manual','auto','app_open','alert') DEFAULT 'manual',
  `trigger_app` varchar(255) DEFAULT NULL,
  `parent_viewed` tinyint(1) DEFAULT 0,
  `parent_viewed_at` timestamp NULL DEFAULT NULL,
  `captured_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_captured_at` (`captured_at`),
  CONSTRAINT `screenshots_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `sms_logs`
DROP TABLE IF EXISTS `sms_logs`;
CREATE TABLE `sms_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `message_type` enum('sent','received','draft') NOT NULL,
  `message_body` text DEFAULT NULL,
  `is_unknown` tinyint(1) DEFAULT 0,
  `parent_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_phone` (`phone_number`),
  KEY `idx_parent_read` (`parent_read`),
  FULLTEXT KEY `idx_body` (`message_body`),
  CONSTRAINT `sms_logs_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `system_settings`
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `time_limits`
DROP TABLE IF EXISTS `time_limits`;
CREATE TABLE `time_limits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned NOT NULL,
  `limit_name` varchar(100) DEFAULT NULL,
  `limit_type` enum('daily_total','app_specific','bedtime','study_time','custom') NOT NULL,
  `max_minutes_per_day` int(10) unsigned DEFAULT NULL,
  `package_name` varchar(255) DEFAULT NULL,
  `max_app_minutes` int(10) unsigned DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `active_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`active_days`)),
  `block_completely` tinyint(1) DEFAULT 0,
  `allow_emergency_calls` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_limit_type` (`limit_type`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `time_limits_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `user_sessions`
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `device_info` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_token` (`token_hash`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `subscription_plan` enum('free','premium','family') DEFAULT 'free',
  `subscription_expires_at` date DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `website_categories`
DROP TABLE IF EXISTS `website_categories`;
CREATE TABLE `website_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `domains` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`domains`)),
  `keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`keywords`)),
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
