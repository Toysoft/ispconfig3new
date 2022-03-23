ALTER TABLE `sys_user` ADD `otp_enabled` SET('n', 'y','v') NOT NULL DEFAULT 'n' COMMENT 'v=waiting for validation of the chosen otp method' AFTER `lost_password_reqtime`, ADD `otp_type` SET('email') NOT NULL DEFAULT 'email' AFTER `otp_enabled`, ADD `otp_data` VARCHAR(255) NULL AFTER `otp_type`, ADD `otp_recovery` VARCHAR(64) NULL AFTER `otp_data`, ADD `otp_attempts` TINYINT NOT NULL DEFAULT '0' AFTER `otp_recovery`;
