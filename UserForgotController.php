<?php

class UserForgotController extends Controller {

    /** @var UserService */
    protected $userService;

    public function __construct(Framework $framework) {
        parent::__construct($framework);
        $this->userService = $framework->get('userService');
    }

    public function index() {
        if ($this->userSession->isLoggedIn()) {
            $this->redirect();
        }
        $form = $this->userService->createForgotForm();
        if ($form->processInput()) {
            if ($this->userService->sendForgotEmail($form->getValue('email'))) {
                $this->redirect('forgot/sent');
            } else {
                $form->addError($this->translation->get('user', 'couldnt_send_email'));
            }
        }
        $this->render(':user/forgot', ['form' => $form]);
    }

    public function sent() {
        return $this->message('info', 'password_changing', 'email_sent_with_instructions');
    }

    public function newPassword($hash) {
        if ($this->userSession->isLoggedIn()) {
              $this->redirect();
        }
        $user = $this->userService->findByForgotHash($hash);
        if (!$user) {
            return $this->message('error', 'password_changing', 'activation_not_found');
        }
        $form = $this->userService->createNewPasswordForm();
        if ($form->processInput()) {
            $this->userService->changeForgotPassword($user, $form->getValue('password'));
            $this->redirect('forgot/success');
        }
        $form->setValue('password', '');
        $form->setValue('password_again', '');
        $this->render(':user/forgot-new-password', [
            'hash' => $hash,
            'form' => $form
        ]);
    }

    public function success() {
        $this->message('info', 'password_changing', 'password_changed');
    }

    private function message($type, $title, $message) {
        $this->render(':user/message', [
            'title' => $this->translation->get('user', $title),
            'text' => $this->translation->get('user', $message),
            'type' => $type
        ]);
    }

}