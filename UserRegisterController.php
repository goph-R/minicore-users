<?php

class UserRegisterController extends Controller {

    /** @var UserService */
    protected $userService;

    public function __construct() {
        parent::__construct();
        $framework = Framework::instance();
        $this->userService = $framework->get('userService');
        if ($this->userService->isRegisterDisabled()) {
            $this->redirect();
        }
    }

    public function index() {
        if ($this->userSession->isLoggedIn()) {
            $this->redirect('');
        }
        $form = $this->userService->createRegisterForm();
        if ($form->processInput()) {
            $values = $form->getValues();
            $user = $this->userService->register($values);
            if ($this->userService->sendRegisterEmail($values, $user->get('activation_hash'))) {
                $this->redirect('register/activation');
            }
            $form->addError($this->translation->get('user', 'couldnt_send_email'));
        }
        $form->setValue('password', '');
        $form->setValue('password_again', '');
        $this->render(':user/register', ['form' => $form]);
    }

    public function activation() {
        $this->message('info', 'activation', 'activation_sent');
    }

    public function activate($hash) {
        if ($this->userService->activate($hash)) {
            $this->redirect('register/success');
        }
        $this->message('error', 'activation', 'activation_unsuccessful');
    }

    public function success() {
        $this->message('info', 'registration', 'registration_successful');
    }

    private function message($type, $title, $message) {
        $this->render(':user/message', [
            'title' => $this->translation->get('user', $title),
            'text' => $this->translation->get('user', $message),
            'type' => $type
        ]);
    }

}