CREATE TABLE `user` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`first_name` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_general_ci',
	`last_name` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_general_ci',
	`name` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_general_ci',
	`email` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_general_ci',
	`password` CHAR(32) NOT NULL COLLATE 'utf8mb4_general_ci',
	`active` TINYINT(1) NOT NULL DEFAULT '0',
	`last_login` INT(11) NOT NULL DEFAULT '0',
	`activation_hash` CHAR(32) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
	`forgot_hash` CHAR(32) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
	`remember_hash` CHAR(32) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
	`new_email` CHAR(32) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
	`new_email_hash` CHAR(32) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
	PRIMARY KEY (`id`)
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB;

CREATE TABLE `role` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	PRIMARY KEY (`id`)
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB;

CREATE TABLE `role_text` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
    `text_id` INT(11) NOT NULL,
    `locale` CHAR(5) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
	PRIMARY KEY (`id`),
    FOREIGN KEY (`text_id`) REFERENCES `role`(`id`) ON DELETE CASCADE,
    UNIQUE (`text_id`, `locale`)
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB;

CREATE TABLE `permission` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	PRIMARY KEY (`id`)
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB;

CREATE TABLE `permission_text` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
    `text_id` INT(11) NOT NULL,
    `locale` CHAR(5) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
	PRIMARY KEY (`id`),
    FOREIGN KEY (`text_id`) REFERENCES `permission`(`id`) ON DELETE CASCADE,
    UNIQUE (`text_id`, `locale`)
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB;

CREATE TABLE `user_role` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`user_id` INT(11) NOT NULL,
	`role_id` INT(11) NOT NULL,
	PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `user`(id) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `role`(id) ON DELETE CASCADE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB;

CREATE TABLE `role_permission` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`role_id` INT(11) NOT NULL,
	`permission_id` INT(11) NOT NULL,
	PRIMARY KEY (`id`),
    FOREIGN KEY (`role_id`) REFERENCES `role`(id) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permission`(id) ON DELETE CASCADE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB;

INSERT INTO `role`(id) VALUES (1);
INSERT INTO `role_text`(id, text_id, locale, `name`) VALUES (NULL, 1, 'en', 'Administrator');
INSERT INTO `role_text`(id, text_id, locale, `name`) VALUES (NULL, 1, 'hu', 'Adminisztrátor');
INSERT INTO `permission`(id) VALUES (1);
INSERT INTO `permission_text`(id, text_id, locale, `name`) VALUES (NULL, 1, 'en', 'Administration');
INSERT INTO `permission_text`(id, text_id, locale, `name`) VALUES (NULL, 1, 'hu', 'Adminisztráció');
INSERT INTO `role_permission`(id, role_id, permission_id) VALUES (1, 1, 1);

/*

DROP TABLE user_role;
DROP TABLE role_permission;
DROP TABLE permission_text;
DROP TABLE permission;
DROP TABLE role_text;
DROP TABLE role;

*/