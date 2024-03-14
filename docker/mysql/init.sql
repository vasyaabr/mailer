CREATE DATABASE IF NOT EXISTS main;

USE main;

GRANT ALL PRIVILEGES ON main.* TO 'mainuser'@'%';

CREATE TABLE `users` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `username` varchar(256) NOT NULL,
    `email` varchar(256) DEFAULT NULL,
    `validts` timestamp,
    `confirmed` tinyint(1) NOT NULL DEFAULT 0,
    `checked` tinyint(1) NOT NULL DEFAULT 0,
    `valid` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `validts_confirmed_key` (`validts`, `confirmed`),
    KEY `valid_valid_ts_key` (`valid`, `validts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `mails` (
    `id` bigint unsigned NOT NULL,
    `validts` timestamp,
    `daysleft` int,
    PRIMARY KEY (`id`, `validts`, `daysleft`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
