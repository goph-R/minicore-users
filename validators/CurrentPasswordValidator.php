<?php


class CurrentPasswordValidator extends Validator {

    /** @var UserSession */
    private $userSession;
    
    /** @var UserService */
    private $userService;
    
    public function __construct() {
        parent::__construct();
        $framework = Framework::instance();
        $this->userSession = $framework->get('userSession');
        $this->userService = $framework->get('userService');
        $this->message = $this->translation->get('user', 'current_password_mismatch');
    }

    public function doValidate($value) {
        if ($value && $this->userService->hash($value) != $this->userService->getCurrentUser()->get('password')) {
            return false;
        }
        return true;
    }

}
