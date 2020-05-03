<?php

class EmailExistsValidator extends Validator {

    /** @var Users */
    private $users;
    private $needToExists;

    public function __construct($needToExists=false) {
        parent::__construct();
        if ($needToExists) {
            $this->message = $this->translation->get('user', 'email_not_exists');
        } else {
            $this->message = $this->translation->get('user', 'email_exists');
        }
        $framework = Framework::instance();
        $this->users = $framework->get('users');
        $this->needToExists = $needToExists;
    }

    public function doValidate($value) {
        if ($this->users->findByEmail($value)) {
            return $this->needToExists;
        }
        return !$this->needToExists;
    }

}