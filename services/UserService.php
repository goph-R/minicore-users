<?php

class UserService {
    
    const CONFIG_USERS_REGISTER_DISABLED = 'users.register_disabled';
    const CONFIG_LOGGED_IN_URL = 'users.logged_in_url';
    const CONFIG_LOGGED_OUT_URL = 'users.logged_out_url';
    const CONFIG_SALT = 'users.salt';

    const CONFIG_AVATAR_SIZE = 'users.avatar_size';
    const DEFAULT_AVATAR_SIZE = 512;
    
    const CONFIG_AVATAR_MAX_FILE_SIZE = 'users.avatar_max_file_size';
    const DEFAULT_AVATAR_MAX_FILE_SIZE = 2*1024*1024; // 2MB
    
    const CONFIG_AVATAR_QUALITY = 'users.avatar_quality';
    const DEFAULT_AVATAR_QUALITY = 90;

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

    public function __construct() {
        $framework = Framework::instance();
        $this->config = $framework->get('config');
        $this->router = $framework->get('router');
        $this->userSession = $framework->get('userSession');
        $this->mailer = $framework->get('mailer');
        $this->translation = $framework->get('translation');
        $this->request = $framework->get('request');
        $this->response = $framework->get('response');
        $this->users = $framework->get('users');
        $this->anonymousUser = $framework->create('User');
        $this->anonymousUser->set('name', 'Anonymous');
        $this->rememberLogin();
    }
    
    public function getAvatarSize() {
        return $this->config->get(self::CONFIG_AVATAR_SIZE, self::DEFAULT_AVATAR_SIZE);
    }
    
    public function getAvatarMaxFileSize() {
        return $this->config->get(self::CONFIG_AVATAR_MAX_FILE_SIZE, self::DEFAULT_AVATAR_MAX_FILE_SIZE);
    }
    
    public function getAvatarQuality() {
        return $this->config->get(self::CONFIG_AVATAR_QUALITY, self::DEFAULT_AVATAR_QUALITY);
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
        return $this->users->findByHash('forgot', $hash);
    }

    public function rememberLogin() {
        $rememberHash = $this->request->getCookie('remember_hash');
        if ($this->userSession->isLoggedIn() || !$rememberHash) {
            return;
        }
        $user = $this->users->findActiveByHash('remember', $rememberHash);
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
        $framework = Framework::instance();
        $framework->redirect('login');
    }
    
    public function requirePermission($permission) {
        $this->requireLogin();
        $currentUser = $this->getCurrentUser();
        if (!$currentUser->hasPermission($permission)) {
            $framework = Framework::instance();
            $framework->error(403);
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
            $user->createHash('remember', $hash);
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
        $this->userSession->setId($user->getId());
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
        $this->currentUser = $this->users->findById($this->userSession->getId());
        return $this->currentUser;
    }

    public function logout() {
        $id = $this->userSession->getId();
        $user = $this->users->findById($id);
        if ($user) {
            $user->removeHashByName('remember');
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
        $user->save();
        $user->createHash('activation', $hash); // after save!
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
            text('user', 'registration'),
            ':user/register-email'
        );
    }

    public function activate($hash) {
        $user = $this->users->findByHash('activation', $hash);
        if (!$user) {
            return false;
        }
        $user->setActive(1);
        $user->save();
        $user->removeHashByName('activation');
        return true;
    }

    public function sendForgotEmail($email) {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return false;
        }
        $hash = $this->hash(time());
        $user->save();
        $user->createHash('forgot', $hash);
        $this->mailer->init();
        $this->mailer->addAddress($email);
        $this->mailer->set('hash', $hash);
        $result = $this->mailer->send(
            text('user', 'password_changing'),
            ':user/forgot-email'
        );
        return $result;
    }

    public function changeForgotPassword(User $user, $password) {
        $user->setPassword($this->hashPassword($password));
        $user->save();
        $user->removeHashByName('forgot');
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
        $user->createHash('new_email', $hash);
        return $hash;
    }

    public function sendNewAddressEmail($email, $hash) {
        $this->mailer->init();
        $this->mailer->addAddress($email);
        $this->mailer->set('hash', $hash);
        return $this->mailer->send(
            text('user', 'new_email_address'),
            ':user/new-address-email'
        );
    }

    public function activateNewEmail($id, $hash) {
        $user = $this->users->findActiveByHash('new_email', $hash);
        if (!$user) {
            return false;
        }
        $user->setEmail($user->getNewEmail());
        $user->setNewEmail(null);
        $user->save();
        $user->removeHashByName('new_email');
        return true;
    }

    /**
     * @return Form
     */
    public function createRegisterForm() {
        /** @var Form $form */
        $framework = Framework::instance();
        $form = $framework->create('Form', ['register']);
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
        $form->addInput('', ['SubmitInput', 'submit', text('user', 'registration')]);
        return $form;
    }
    
    /**
     * @return Form
     */
    public function createLoginForm() {
        $framework = Framework::instance();
        /** @var Form $form */
        $form = $framework->create('Form', ['login']);
        $emailInput = $form->addInput('Email', ['TextInput', 'email']);
        $emailInput->setAutocomplete(false);
        $form->addInput(['user', 'password'], ['PasswordInput', 'password']);
        $form->addInput('', ['CheckboxInput', 'remember', '1', ['user', 'remember_me']]);
        $form->addInput('', ['SubmitInput', 'submit', text('user', 'login')]);
        return $form;
    }

    /**
     * @return Form
     */
    public function createNewPasswordForm() {
        $framework = Framework::instance();
        /** @var Form $form */
        $form = $framework->create('Form', ['new_password']);
        $form->addInput(['user', 'password'], ['PasswordInput', 'password']);
        $form->addInput(['user', 'password_again'], ['PasswordInput', 'password_again']);
        $form->addValidator('password', 'PasswordValidator');
        $form->addValidator('password_again', ['SameValidator', $form, 'password']);
        $form->addInput('', ['SubmitInput', 'submit', text('user', 'password_changing')]);
        return $form;
    }

    /**
     * @return Form
     */
    public function createForgotForm() {
        $framework = Framework::instance();
        /** @var Form $form */
        $form = $framework->create('Form', ['forgot']);
        $emailInput = $form->addInput('Email', ['TextInput', 'email']);
        $emailInput->setAutocomplete(false);
        $form->addValidator('email', 'EmailValidator');
        $form->addValidator('email', ['EmailExistsValidator', true]);
        $form->addInput('', ['SubmitInput', 'submit', text('user', 'send')]);        
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
        $framework = Framework::instance();
        /** @var Form $form */
        $form = $framework->create('Form', ['settings']);
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
        $form->addInput('', ['SubmitInput', 'submit', text('user', 'save_settings')]);        
        return $form;
    }
    
    public function createAvatarForm(User $user) {
        $framework = Framework::instance();
        $form = $framework->create('Form', ['avatar']);
        $params = [
            'size' => $this->getAvatarSize(),
            'max' => round($this->getAvatarMaxFileSize() / 1024 / 1024)
        ];
        $description = text('user', 'avatar_upload_description', $params);
        $form->addInput(['user', 'avatar_upload'], ['FileInput', 'file'], $description);
        $currentAvatarInput = $form->addInput('', ['CurrentAvatarInput', 'current', $this->userSession->getId()]);
        $currentAvatarInput->setRequired(false);
        $form->addInput('', ['SubmitInput', 'submit', text('user', 'save_avatar')]);
        return $form;
    }
    
    public function changeAvatar(User $user, $srcPath) {
        $this->removeAvatar($user);
        do {
            $user->setAvatar(bin2hex(random_bytes(16)));            
        } while ($user->hasAvatar());
        $path = $user->getAvatarPath();
        $dir = dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $srcSize = getimagesize($srcPath);
        $src = imagecreatefromjpeg($srcPath);
        $srcX = 0;
        $srcY = 0;
        $srcW = $srcSize[0];
        $srcH = $srcSize[1];
        $destSize = $this->getAvatarSize();
        $dest = imagecreatetruecolor($destSize, $destSize);
        if ($srcW > $srcH) {
            $srcX = ($srcW - $srcH) / 2;
            $srcW = $srcH;
        } else {
            $srcY = ($srcH - $srcW) / 2;
            $srcH = $srcW;
        }
        imagecopyresampled($dest, $src, 0, 0, $srcX, $srcY, $destSize, $destSize, $srcW, $srcH);
        imagejpeg($dest, $path, $this->getAvatarQuality());
        imagedestroy($dest);
        imagedestroy($src);
    }
    
    public function removeAvatar(User $user) {
        if (!$user->hasAvatar()) {
            return;
        }
        unlink($user->getAvatarPath());
    }
    
    protected function getEmailDescription(User $user, $useEmailDescription) {
        if (!$useEmailDescription) {
            return '';
        }
        $newEmail = $user->getNewEmail();
        if (!$newEmail) {
            $result = text('user', 'email_change_description');
        } else {
            $result = text('user', 'waits_for_activation', ['email' => $newEmail]);
        }
        return $result;
    }
}