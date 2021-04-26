<?php

class UsersModule extends Module {

    protected $id = 'minicore-users';

    public function __construct() {
        Framework::instance()->add([
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

        $framework = Framework::instance();
        
        /** @var View $view */
        $view = $framework->get('view');
        $view->addFolder(':user', $this->getFolder().'templates');

        /** @var Translation $translation */
        $translation = $framework->get('translation');
        $translation->add('user', $this->getFolder().'translations');

        /** @var Router $router */
        $router = $framework->get('router');
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