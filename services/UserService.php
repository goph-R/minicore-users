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
    
    /** @var Response */
    protected $response;

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
        $this->response = $framework->get('response');
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
    }
    
    public function requireLogin() {
        if ($this->userSession->isLoggedIn()) {
            return;
        }
        $url = $this->router->getBaseUrl().$this->request->getUri();
        $this->setLoginRedirectUrl($url);
        $this->framework->redirect('login');
    }
    
    public function requirePermission($permission) {
        $this->requireLogin();
        $currentUser = $this->getCurrentUser();
        if (!$currentUser->hasPermission($permission)) {
            $this->framework->error(403);
        }
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
            $user->setRememberHash($hash);
            $this->response->setCookie('remember_hash', $hash);
        }
        $this->doLogin($user);
        return true;
    }
    
    public function getLoginRedirectUrl() {
        return $this->userSession->get('login_redirect_url');
    }
    
    public function setLoginRedirectUrl($value) {
        $this->userSession->set('login_redirect_url', $value);
    }
    
    protected function doLogin(User $user) {
        $user->setLastLogin(time());
        $user->save();
        $this->userSession->setLoggedIn(true);
        $this->userSession->set('id', $user->getId());
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
            $user->setRememberHash(null);
            $user->save();
        }
        $this->request->setCookie('remember_hash', null);
        $this->userSession->destroy();
    }
    
    public function hashPassword($password) {
        return $this->hash($password);
    }

    public function register($values) {
        $fields = ['email', 'first_name', 'last_name', 'name'];
        $hash = $this->hash(time());
        $user = $this->users->create();
        $user->setArray($values, $fields);
        $user->setPassword($this->hashPassword($values['password']));
        $user->setActivationHash($hash);
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
        $user->setActivationHash(null);
        $user->setActive(1);
        $user->save();
        return true;
    }

    public function sendForgotEmail($email) {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return false;
        }
        $hash = $this->hash(time());
        $user->setForgotHash($hash);
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
        $user->setForgotHash(null);
        $user->setPassword($this->hashPassword($password));
        $user->save();
    }

    public function changePassword(User $user, $password) {
        $user->setPassword($this->hashPassword($password));
    }
    
    public function changeFullName(User $user, $firstName, $lastName) {
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
    }

    public function changeEmail(User $user, $email) {
        $hash = $this->hash($email);
        $user->setNewEmail($email);
        $user->setNewEmailHash($hash);
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
        if (!$user || $user->getNewEmailHash() != $hash) {
            return false;
        }
        $email = $user->getNewEmail();
        $user->setNewEmail(null);
        $user->setNewEmailHash(null);
        $user->setEmail($email);
        $user->save();
        return true;
    }

    /**
     * @return Form
     */
    public function createRegisterForm() {
        /** @var Form $form */
        $form = $this->framework->create('Form', ['register']);
        $form->addInput(['user', 'name'], ['TextInput', 'name'], ['user', 'will_be_used_as_public']);
        $form->addValidator('name', 'NameExistsValidator');
        $form->addInput(['user', 'first_name'], ['TextInput', 'first_name']);
        $form->addInput(['user', 'last_name'], ['TextInput', 'last_name']);
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
        $nameInput = $form->addInput(['user', 'name'], ['TextInput', 'name', $user->getName()], ['user', 'cant_modify']);
        $nameInput->setReadOnly(true);
        $form->setRequired('name', false);
        $form->addInput(['user', 'first_name'], ['TextInput', 'first_name', $user->getFirstName()]);
        $form->addInput(['user', 'last_name'], ['TextInput', 'last_name', $user->getLastName()]);
        $emailInput = $form->addInput('Email', ['TextInput', 'email', $user->getEmail()], $emailDescription);
        $emailInput->setAutocomplete(false);
        $form->addValidator('email', 'EmailValidator');
        $form->addValidator('email', ['EmailExistsExceptValidator', $user->getId()]);
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
        $newEmail = $user->getNewEmail();
        if (!$newEmail) {
            $result = $this->translation->get('user', 'email_change_description');
        } else {
            $result = $this->translation->get('user', 'waits_for_activation', ['email' => $newEmail]);
        }
        return $result;
    }
}