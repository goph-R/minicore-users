<?php

class UserService {
    
    const CONFIG_USERS_REGISTER_DISABLED = 'users.register_disabled';
    const CONFIG_LOGGED_IN_URL = 'users.logged_in_url';
    const CONFIG_LOGGED_OUT_URL = 'users.logged_out_url';

    /** @var Config */
    protected $config;

    /** @var UserSession */
    protected $userSession;

    /** @var Mailer */
    protected $mailer;

    /** @var Translation */
    protected $translation;

    /** @var Request */
    protected $request;

    /** @var Framework */
    protected $framework;
    
    /** @var Router */
    protected $router;

    /** @var Users */
    protected $users;

    /** @var User */
    protected $anonymousUser;

    /** @var User */
    protected $currentUser;

    public function __construct(Framework $framework) {
        $this->framework = $framework;
        $this->config = $framework->get('config');
        $this->router = $framework->get('router');
        $this->userSession = $framework->get('userSession');
        $this->mailer = $framework->get('mailer');
        $this->translation = $framework->get('translation');
        $this->request = $framework->get('request');
        $this->users = $framework->get('users');
        $this->anonymousUser = $framework->create('User');
        $this->rememberLogin();
    }
    
    public function isRegisterDisabled() {
        return $this->config->get(self::CONFIG_USERS_REGISTER_DISABLED);
    }

    public function hash($value) {
        return $this->users->hash($value);
    }

    public function findById($id) {
        return $this->users->findById($id);
    }

    public function findByForgotHash($hash) {
        return $this->users->findByForgotHash($hash);
    }

    public function rememberLogin() {
        $rememberHash = $this->request->getCookie('remember_hash');
        if ($this->userSession->isLoggedIn() || !$rememberHash) {
            return;
        }
        $user = $this->users->findActiveByRememberHash($rememberHash);
        if (!$user) {
            return;
        }
        $this->doLogin($user);
        $this->redirectAfterLogin();
    }
    
    public function requireLogin() {
        if ($this->userSession->isLoggedIn()) {
            return;
        }
        $url = $this->router->getBaseUrl().$this->request->getUri();
        $this->userSession->set('login_redirect_url', $url);
        $this->framework->redirect('login');
    }
    
    public function getLoggedInUrl() {
        return $this->config->get(self::CONFIG_LOGGED_IN_URL);
    }
    
    public function getLoggedOutUrl() {
        return $this->config->get(self::CONFIG_LOGGED_OUT_URL);
    }

    public function login($email, $password, $remember) {
        $user = $this->users->findActiveByEmailAndPassword($email, $password);
        if (!$user) {
            return false;
        }
        if ($remember) {
            $hash = $this->hash(time());
            $user->set('remember_hash', $hash);
            $this->request->setCookie('remember_hash', $hash);
        }
        $this->doLogin($user);
        return true;
    }
    
    public function redirectAfterLogin() {
        $redirectUrl = $this->userSession->get('login_redirect_url');
        $this->userSession->set('login_redirect_url', null);
        $this->framework->redirect($redirectUrl ? $redirectUrl : $this->getLoggedInUrl());        
    }

    protected function doLogin(User $user) {
        $user->set('last_login', time());
        $user->save();
        $this->userSession->setLoggedIn(true);
        $this->userSession->set('id', $user->get('id'));
    }

    /**
     * @return User
     */
    public function getCurrentUser() {
        if (!$this->userSession->isLoggedIn()) {
            return $this->anonymousUser;
        }
        if ($this->currentUser) {
            return $this->currentUser;
        }
        $this->currentUser = $this->users->findById($this->userSession->get('id'));
        return $this->currentUser;
    }

    public function logout() {
        $id = $this->userSession->get('id');
        $user = $this->users->findById($id);
        if ($user) {
            $user->set('remember_hash', null);
            $user->save();
        }
        $this->request->setCookie('remember_hash', null);
        $this->userSession->destroy();
    }

    public function register($values) {
        $fields = ['email', 'first_name', 'last_name', 'name'];
        $hash = $this->hash(time());
        $user = $this->users->create();
        $user->setArray($values, $fields);
        $user->set('password', $this->hash($values['password']));
        $user->set('activation_hash', $hash);
        $user->save();
        return $user;
    }

    public function sendRegisterEmail($values, $hash) {
        $this->mailer->init();
        $this->mailer->addAddress($values['email']);
        foreach ($values as $name => $value) {
            $this->mailer->set($name, $value);
        }
        $this->mailer->set('hash', $hash);
        return $this->mailer->send(
            $this->translation->get('user', 'registration'),
            ':user/register-email'
        );
    }

    public function activate($hash) {
        $user = $this->users->findByActivationHash($hash);
        if (!$user) {
            return false;
        }
        $user->set('activation_hash', null);
        $user->set('active', 1);
        $user->save();
        return true;
    }

    public function sendForgotEmail($email) {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return false;
        }
        $hash = $this->hash(time());
        $user->set('forgot_hash', $hash);
        $user->save();
        $this->mailer->init();
        $this->mailer->addAddress($email);
        $this->mailer->set('hash', $hash);
        $result = $this->mailer->send(
            $this->translation->get('user', 'password_changing'),
            ':user/forgot-email'
        );
        return $result;
    }

    public function changeForgotPassword(User $user, $password) {
        $user->set('forgot_hash', '');
        $user->set('password', $this->hash($password));
        $user->save();
    }

    public function changePassword($id, $password) {
        $user = $this->users->findById($id);
        if (!$user) {
            return false;
        }
        $user->set('password', $this->hash($password));
        $user->save();
        return true;
    }

    public function setNewEmail(User $user, $email) {
        $hash = $this->hash($email);
        $user->set('new_email', $email);
        $user->set('new_email_hash', $hash);
        return $hash;
    }

    public function sendNewAddressEmail($email, $hash) {
        $this->mailer->init();
        $this->mailer->addAddress($email);
        $this->mailer->set('hash', $hash);
        return $this->mailer->send(
            $this->translation->get('user', 'new_email_address'),
            ':user/new-address-email'
        );
    }

    public function activateNewEmail($id, $hash) {
        $user = $this->users->findById($id);
        if (!$user || $user->get('new_email_hash') != $hash) {
            return false;
        }
        $email = $user->get('new_email');
        $user->set('new_email', null);
        $user->set('new_email_hash', null);
        $user->set('email', $email);
        $user->save();
        return true;
    }

    /**
     * @return Form
     */
    public function createRegisterForm() {
        /** @var Form $form */
        $form = $this->framework->create('Form', ['register']);
        $form->addInput(['user', 'first_name'], ['TextInput', 'first_name']);
        $form->addInput(['user', 'last_name'], ['TextInput', 'last_name']);
        $form->addInput(['user', 'name'], ['TextInput', 'name'], ['user', 'will_be_used_as_public']);
        $form->addInput('Email', ['TextInput', 'email'], ['user', 'we_will_send_an_activation']);
        $form->addValidator('email', 'EmailValidator');
        $form->addValidator('email', 'EmailExistsValidator');
        $form->addInput(['user', 'password'], ['PasswordInput', 'password']);
        $form->addValidator('password', 'PasswordValidator');
        $form->addInput(['user', 'password_again'], ['PasswordInput', 'password_again']);
        $form->addValidator('password_again', ['SameValidator', $form, 'password']);
        $form->addInput('', ['SubmitInput', 'submit', $this->translation->get('user', 'registration')]);
        return $form;
    }
    
    /**
     * @return Form
     */
    public function createLoginForm() {
        /** @var Form $form */
        $form = $this->framework->create('Form', ['login']);
        $emailInput = $form->addInput('Email', ['TextInput', 'email']);
        $emailInput->setAutocomplete(false);
        $form->addInput(['user', 'password'], ['PasswordInput', 'password']);
        $form->addInput('', ['CheckboxInput', 'remember', '1', ['user', 'remember_me']]);
        $form->addInput('', ['SubmitInput', 'submit', $this->translation->get('user', 'login')]);
        return $form;
    }

    /**
     * @return Form
     */
    public function createNewPasswordForm() {
        /** @var Form $form */
        $form = $this->framework->create('Form', ['new_password']);
        $form->addInput(['user', 'password'], ['PasswordInput', 'password']);
        $form->addInput(['user', 'password_again'], ['PasswordInput', 'password_again']);
        $form->addValidator('password', 'PasswordValidator');
        $form->addValidator('password_again', ['SameValidator', $form, 'password']);
        $form->addInput('', ['SubmitInput', 'submit', $this->translation->get('user', 'password_changing')]);
        return $form;
    }

    /**
     * @return Form
     */
    public function createForgotForm() {
        /** @var Form $form */
        $form = $this->framework->create('Form', ['forgot']);
        $emailInput = $form->addInput('Email', ['TextInput', 'email']);
        $emailInput->setAutocomplete(false);
        $form->addValidator('email', 'EmailValidator');
        $form->addValidator('email', ['EmailExistsValidator', true]);
        $form->addInput('', ['SubmitInput', 'submit', $this->translation->get('user', 'send')]);        
        return $form;
    }

    /**
     * @param User
     * @param boolean
     * @return Form
     */
    public function createSettingsForm(User $user, $useEmailDescription=true) {
        // TODO: a post validator for the passwords
        $emailDescription = $this->getEmailDescription($user, $useEmailDescription);
        /** @var Form $form */
        $form = $this->framework->create('Form', ['settings']);
        $emailInput = $form->addInput('Email', ['TextInput', 'email', $user->get('email')], $emailDescription);
        $emailInput->setAutocomplete(false);
        $form->addValidator('email', 'EmailValidator');
        $form->addValidator('email', ['EmailExistsExceptValidator', $user->get('id')]);
        $form->addInput(['user', 'old_password'], ['PasswordInput', 'old_password'], ['user', 'set_if_change_password']);
        $form->addValidator('old_password', 'CurrentPasswordValidator');
        $form->setRequired('old_password', false);
        $form->addInput(['user', 'new_password'], ['PasswordInput', 'password']);
        $form->addValidator('password', ['PasswordValidator']);
        $form->addValidator('password', ['SameValidator', $form, 'password_again']);
        $form->setRequired('password', false);
        $form->addInput(['user', 'new_password_again'], ['PasswordInput', 'password_again']);
        $form->addValidator('password_again', ['SameValidator', $form, 'password']);
        $form->setRequired('password_again', false);
        $form->addInput('', ['SubmitInput', 'submit', $this->translation->get('user', 'save_settings')]);        
        return $form;
    }
    
    protected function getEmailDescription(User $user, $useEmailDescription) {
        if (!$useEmailDescription) {
            return '';
        }
        $newEmail = $user->get('new_email');
        if (!$newEmail) {
            $result = $this->translation->get('user', 'email_change_description');
        } else {
            $result = $this->translation->get('user', 'waits_for_activation', ['email' => $newEmail]);
        }
        return $result;
    }
}