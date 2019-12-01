<?php

class UserSettingsController extends Controller {

    /** @var UserService */
    protected $userService;

    public function __construct(Framework $framework) {
        parent::__construct($framework);
        $this->userService = $framework->get('userService');
    }

    public function index() {
        if (!$this->userSession->isLoggedIn()) {
            $this->redirect();
        }
        $form = $this->userService->createSettingsForm($this->userService->getCurrentUser());
        if ($form->processInput()) {
            $messages = $this->save($form);
            if ($messages) {
                $this->userSession->setFlash('settings_messages', $messages);
                $this->redirect('settings');
            }
        }
        $form->setValue('old_password', '');
        $form->setValue('password', '');
        $form->setValue('password_again', '');
        $this->render(':user/settings', [
            'form' => $form,
            'userSession' => $this->userSession
        ]);
    }
    
    protected function save(Form $form) {
        $user = $this->userService->getCurrentUser();
        $messages = [];
        if ($form->getValue('old_password') && $form->getValue('password')) {
            $this->userService->changePassword($user->get('id'), $form->getValue('password'));
            $messages[] = $this->getMessage('info', 'password_changed');
        }
        $email = $form->getValue('email');
        if ($email != $user->get('email')) {
            $hash = $this->userService->setNewEmail($user, $email);
            if ($this->userService->sendNewAddressEmail($email, $hash)) {
                $user->save();
                $messages[] = $this->getMessage('info', 'new_email_was_set');
            } else {
                $messages[] = $this->getMessage('error', 'couldnt_send_email');
            }  
        }
        return $messages;
    }
    
    public function activate($hash) {
        if (!$this->userSession->isLoggedIn()) {
            $this->redirect('');
        }
        $user = $this->userService->getCurrentUser();
        if ($this->userService->activateNewEmail($user->get('id'), $hash)) {
            $message = $this->getMessage('info', 'email_activation_successful');
        } else {
            $message = $this->getMessage('error', 'email_activation_not_found');
        }
        $message['title'] = $this->translation->get('user', 'new_email_address');
        $this->render(':user/message', $message);
    }
    
    private function getMessage($type, $text) {
        return [
            'type' => $type,
            'text' => $this->translation->get('user', $text)
        ];
    }
    
}
