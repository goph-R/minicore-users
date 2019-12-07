<?php

class UsersModule extends Module {

    protected $id = 'minicore-users';

    public function __construct(Framework $framework) {
        parent::__construct($framework);
        $framework->add([
            'users'                  => 'Users',
            'userService'            => 'UserService',
            'userRegisterController' => 'UserRegisterController',
            'userLoginController'    => 'UserLoginController',
            'userForgotController'   => 'UserForgotController',
            'userLogoutController'   => 'UserLogoutController',
            'userProfileController'  => 'UserProfileController',
            'userSettingsController' => 'UserSettingsController'
        ]);
    }

    public function init() {
        /** @var View $view */
        $view = $this->framework->get('view');
        $view->addFolder(':user', 'modules/minicore-users/templates');

        /** @var Translation $translation */
        $translation = $this->framework->get('translation');
        $translation->add('user', 'modules/minicore-users/translations');

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
            ['settings/activate/?', 'userSettingsController', 'activate']
        ]);
    }

}