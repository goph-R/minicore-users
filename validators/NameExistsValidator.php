<?php

class NameExistsValidator extends Validator {

    /** @var Users */
    private $users;

    public function __construct() {
        parent::__construct();
        $framework = Framework::instance();
        $this->message = $this->translation->get('user', 'name_exists');
        $this->users = $framework->get('users');
    }

    public function doValidate($value) {
        if ($this->users->findByName($value)) {
            return false;
        }
        return true;
    }

}
