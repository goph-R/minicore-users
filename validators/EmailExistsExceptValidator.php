<?php

class EmailExistsExceptValidator extends Validator {

    /** @var Users */
    private $users;
    
    /** @var Record */
    private $exceptId;

    public function __construct($exceptId=null) {
        parent::__construct();
        $framework = Framework::instance();
        $this->users = $framework->get('users');
        $this->message = $this->translation->get('user', 'email_exists');
        $this->exceptId = $exceptId;
    }

    public function doValidate($value) {
        $user = $this->users->findByEmailExceptId($value, $this->exceptId);
        return $user ? false : true;
    }

}