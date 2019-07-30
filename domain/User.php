<?php

class User extends Record {

/*

CREATE TABLE `user` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`first_name` VARCHAR(255) NOT NULL COLLATE 'utf8_hungarian_ci',
	`last_name` VARCHAR(255) NOT NULL COLLATE 'utf8_hungarian_ci',
	`name` VARCHAR(64) NOT NULL COLLATE 'utf8_hungarian_ci',
	`email` VARCHAR(255) NOT NULL COLLATE 'utf8_hungarian_ci',
	`password` CHAR(32) NOT NULL COLLATE 'utf8_hungarian_ci',
	`active` TINYINT(1) NOT NULL DEFAULT '0',
	`last_login` INT(11) NOT NULL DEFAULT '0',
	`activation_hash` CHAR(32) NOT NULL DEFAULT '' COLLATE 'utf8_hungarian_ci',
	`forgot_hash` CHAR(32) NOT NULL DEFAULT '' COLLATE 'utf8_hungarian_ci',
	`remember_hash` CHAR(32) NOT NULL DEFAULT '' COLLATE 'utf8_hungarian_ci',
	`new_email` CHAR(32) NOT NULL DEFAULT '' COLLATE 'utf8_hungarian_ci',
	`new_email_hash` CHAR(32) NOT NULL DEFAULT '' COLLATE 'utf8_hungarian_ci',
	PRIMARY KEY (`id`)
)
COLLATE='utf8_hungarian_ci'
ENGINE=InnoDB
;

 */

    protected $tableName = 'user';

    protected $id;
    protected $name;
    protected $first_name;
    protected $last_name;
    protected $email;
    protected $password;
    protected $active = 0;
    protected $last_login = 0;
    protected $activation_hash = '';
    protected $forgot_hash = '';
    protected $remember_hash = '';
    protected $new_email = '';
    protected $new_email_hash = '';

    public function getFormattedLastLogin() {
        return date('Y-m-d H:i', $this->get('last_login'));
    }

}