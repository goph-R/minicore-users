<?php

class UserSettingsController extends Controller {

    /** @var UserService */
    protected $userService;
    
    protected $saveMessages = [];

    public function __construct() {
        parent::__construct();
        $framework = Framework::instance();
        $this->userService = $framework->get('userService');
    }

    public function index() {
        $this->userService->requireLogin();
        $form = $this->userService->createSettingsForm($this->userService->getCurrentUser());
        $this->processForm($form);
        $form->setValue('old_password', '');
        $form->setValue('password', '');
        $form->setValue('password_again', '');
        $this->view->set(['userSession' => $this->userSession]);
        $this->render(':user/settings', [
            'form' => $form,
            'active' => 'general',
            'action' => route_url('settings')
        ]);
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
    
    protected function processForm(Form $form) {
        if (!$form->processInput()) {
            return;
        }
        $this->save($form);
        if ($this->saveMessages) {
            $this->userSession->setFlash('settings_messages', $this->saveMessages);
            $this->redirect('settings');
        }
    }   
    
    public function save(Form $form) {
        $this->saveMessages = [];
        $save = $this->saveFullName($form);
        $save |= $this->savePassword($form);
        $save |= $this->saveEmail($form);
        if ($save) {
            $user = $this->userService->getCurrentUser();
            $user->save();
        }
    }
    
    protected function saveFullName($form) {
        $user = $this->userService->getCurrentUser();
        if ($form->getValue('last_name') == $user->getLastName() && $form->getValue('first_name') == $user->getFirstName()) {
            return false;
        }
        $this->userService->changeFullName($user, $form->getValue('first_name'), $form->getValue('last_name'));
        $this->saveMessages[] = $this->getMessage('info', 'fullname_modify_success');
        return true;
    }

    protected function saveEmail($form) {
        $user = $this->userService->getCurrentUser();
        $email = $form->getValue('email');
        if ($email == $user->get('email')) {
            return false;
        }
        $hash = $this->userService->changeEmail($user, $email);
        if (!$this->userService->sendNewAddressEmail($email, $hash)) {
            $this->saveMessages[] = $this->getMessage('error', 'couldnt_send_email');
            return false;
        }
        $this->saveMessages[] = $this->getMessage('info', 'new_email_was_set');
        return true;
    }
    
    protected function savePassword($form) {
        $user = $this->userService->getCurrentUser();
        if (!$form->getValue('old_password') || !$form->getValue('password')) {
            return false;
        }
        $this->userService->changePassword($user, $form->getValue('password'));
        $this->saveMessages[] = $this->getMessage('info', 'password_changed');
        return true;
    }
    
    private function getMessage($type, $text) {
        return [
            'type' => $type,
            'text' => $this->translation->get('user', $text)
        ];
    }
    
}
