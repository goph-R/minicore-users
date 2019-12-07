<?php

class UserLoginController extends Controller {

    /** @var UserService */
    protected $userService;

    public function __construct(Framework $framework) {
        parent::__construct($framework);
        $this->userService = $framework->get('userService');
    }

    public function index() {
        if ($this->userSession->isLoggedIn()) {
            $this->redirect('');
        }
        $form = $this->userService->createLoginForm();
        if ($form->processInput()) {
            $email = $form->getValue('email');
            $password = $form->getValue('password');
            $remember = $form->getValue('remember');
            if ($this->userService->login($email, $password, $remember)) {
                $loginRedirect = $this->userSession->get('login_redirect');
                $loggedInUrl = $this->userService->getLoggedInUrl();
                $this->redirect($loginRedirect ? $loginRedirect : $loggedInUrl);
            } else {
                $form->addError($this->translation->get('user', 'email_password_not_found'));
            }
        }
        $form->setValue('password', '');
        $this->render(':user/login', [
            'request' => $this->request,
            'form' => $form
        ]);
    }

}