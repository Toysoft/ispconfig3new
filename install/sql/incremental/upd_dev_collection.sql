
-- we need those to fix some installations failing in 0089 and 0090
ALTER TABLE `web_domain` ROW_FORMAT=DYNAMIC;
ALTER IGNORE TABLE `web_domain` ADD COLUMN `proxy_protocol` ENUM('n','y') NOT NULL DEFAULT 'n' AFTER `log_retention`;
ALTER IGNORE TABLE `web_domain` ADD  `backup_format_web` VARCHAR( 255 ) NOT NULL default 'default' AFTER `backup_copies`;
ALTER IGNORE TABLE `web_domain` ADD  `backup_format_db` VARCHAR( 255 ) NOT NULL default 'gzip' AFTER `backup_format_web`;
ALTER IGNORE TABLE `web_domain` ADD  `backup_encrypt` enum('n','y') NOT NULL DEFAULT 'n' AFTER `backup_format_db`;
ALTER IGNORE TABLE `web_domain` ADD  `backup_password` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER `backup_encrypt`;
ALTER IGNORE TABLE `web_backup` ADD  `backup_format` VARCHAR( 64 ) NOT NULL DEFAULT '' AFTER `backup_mode`;
ALTER IGNORE TABLE `web_backup` ADD  `backup_password` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER `filesize`;
ALTER IGNORE TABLE `web_domain` ALTER pm SET DEFAULT 'ondemand';
ALTER IGNORE TABLE `web_domain` DROP COLUMN `enable_spdy`;
ALTER IGNORE TABLE `web_domain` ADD `folder_directive_snippets` TEXT NULL AFTER `https_port`;
ALTER IGNORE TABLE `web_domain` ADD `server_php_id` INT(11) UNSIGNED NOT NULL DEFAULT 0;
ALTER IGNORE TABLE `web_domain` CHANGE `apache_directives` `apache_directives` mediumtext NULL DEFAULT NULL;
ALTER IGNORE TABLE `web_domain` CHANGE `nginx_directives` `nginx_directives` mediumtext NULL DEFAULT NULL;
UPDATE `web_domain` as w LEFT JOIN sys_group as g ON (g.groupid = w.sys_groupid) INNER JOIN `server_php` as p ON (w.fastcgi_php_version = CONCAT(p.name, ':', p.php_fastcgi_binary, ':', p.php_fastcgi_ini_dir) AND p.server_id IN (0, w.server_id) AND p.client_id IN (0, g.client_id)) SET w.server_php_id = p.server_php_id, w.fastcgi_php_version = '' WHERE w.server_php_id = 0;
UPDATE `web_domain` as w LEFT JOIN sys_group as g ON (g.groupid = w.sys_groupid) INNER JOIN `server_php` as p ON (w.fastcgi_php_version = CONCAT(p.name, ':', p.php_fpm_init_script, ':', p.php_fpm_ini_dir, ':', p.php_fpm_pool_dir) AND p.server_id IN (0, w.server_id) AND p.client_id IN (0, g.client_id)) SET w.server_php_id = p.server_php_id, w.fastcgi_php_version = '' WHERE w.server_php_id = 0;
-- end of fixes

-- drop old php column because new installations don't have them (fails in multi-server)
ALTER TABLE `web_domain` DROP COLUMN `fastcgi_php_version`;
