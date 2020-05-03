<?php

class UserLoginController extends Controller {

    /** @var UserService */
    protected $userService;

    public function __construct() {
        parent::__construct();
        $framework = Framework::instance();
        $this->userService = $framework->get('userService');
    }

    public function index() {
        if ($this->userSession->isLoggedIn()) {
            $this->redirect($this->userService->getLoggedInUrl());
        }
        $form = $this->userService->createLoginForm();
        if ($form->processInput()) {
            $email = $form->getValue('email');
            $password = $form->getValue('password');
            $remember = $form->getValue('remember');
            if ($this->userService->login($email, $password, $remember)) {
                $redirectUrl = $this->userService->getLoginRedirectUrl();
                $this->userService->setLoginRedirectUrl(null);
                $this->framework->redirect($redirectUrl ? $redirectUrl : $this->userService->getLoggedInUrl());        
            } else {
                $form->addError($this->translation->get('user', 'email_password_not_found'));
            }
        }
        $form->setValue('password', '');
        $this->render(':user/login', ['form' => $form]);
    }

}