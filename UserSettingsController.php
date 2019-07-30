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
            $messages[] = $this->translation->get('user', 'password_changed');
        }
        $email = $form->getValue('email');
        if ($email != $user->get('email')) {
            $hash = $this->userService->saveNewEmail($user->get('id'), $email);
            if ($hash && $this->userService->sendNewAddressEmail($email, $hash)) {
                $messages[] = $this->translation->get('user', 'new_email_was_set');
            }            
        }
        return $messages;
    }
    
    public function activate($hash) {
        if (!$this->userSession->isLoggedIn()) {
            $this->redirect('');
        }
        $user = $this->userService->getCurrentUser();
        $data = ['title' => $this->translation->get('user', 'new_email_address')];
        if ($this->userService->activateNewEmail($user->get('id'), $hash)) {
            $data['messageType'] = 'info';
            $data['message'] = $this->translation->get('user', 'email_activation_successful');
        } else {
            $data['messageType'] = 'error';
            $data['message'] = $this->translation->get('user', 'email_activation_not_found');
        }
        $this->render(':user/message', $data);
    }
    
}
