<?php

class UsersModule extends Module {

    /** @var Framework */
    protected $framework;
    protected $id = 'minicore-users';

    public function __construct() {
        $this->framework = Framework::instance();
        $this->framework->add([
            'users'                  => 'Users',
            'roles'                  => 'Roles',
            'permissions'            => 'Permissions',
            'userService'            => 'UserService',
            'userRegisterController' => 'UserRegisterController',
            'userLoginController'    => 'UserLoginController',
            'userForgotController'   => 'UserForgotController',
            'userLogoutController'   => 'UserLogoutController',
            'userProfileController'  => 'UserProfileController',
            'userSettingsController' => 'UserSettingsController',
            'userAvatarController'   => 'UserAvatarController'
        ]);
    }

    public function init() {
        parent::init();

        /** @var View $view */
        $view = $this->framework->get('view');
        $view->addFolder(':user', $this->getFolder().'templates');

        /** @var Translation $translation */
        $translation = $this->framework->get('translation');
        $translation->add('user', $this->getFolder().'translations');

        /** @var Router $router */
        $router = $this->framework->get('router');
        $router->add([
            ['register', 'userRegisterController', 'index', ['GET', 'POST']],
            ['register/activation', 'userRegisterController', 'activation'],
            ['register/activate/?', 'userRegisterController', 'activate'],
            ['register/success', 'userRegisterController', 'success'],
            ['login', 'userLoginController', 'index', ['GET', 'POST']],
            ['forgot', 'userForgotController', 'index', ['GET', 'POST']],
            ['forgot/sent', 'userForgotController', 'sent'],
            ['forgot/new/?', 'userForgotController', 'newPassword', ['GET', 'POST']],
            ['forgot/success', 'userForgotController', 'success'],
            ['logout', 'userLogoutController', 'index'],
            ['profile/?', 'userProfileController', 'index'],
            ['settings', 'userSettingsController', 'index', ['GET', 'POST']],
            ['settings/activate/?', 'userSettingsController', 'activate'],
            ['settings/avatar', 'userAvatarController', 'index', ['GET', 'POST']],
            ['settings/remove_avatar', 'userAvatarController', 'remove'],
        ]);
    }

}