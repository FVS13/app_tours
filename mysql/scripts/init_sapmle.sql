CREATE DATABASE IF NOT EXISTS `%%db_name%%`;
CREATE USER IF NOT EXISTS '%%user_name%%'@'localhost' IDENTIFIED BY '%%password%%';
SET PASSWORD FOR '%%user_name%%'@'localhost' = PASSWORD('%%password%%');
GRANT ALL PRIVILEGES ON %%db_name%%.* TO '%%user_name%%'@'localhost';
FLUSH PRIVILEGES;
