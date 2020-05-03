<?php

class UserLogoutController extends Controller {

    /** @var UserService */
    protected $userService;

    public function __construct() {
        parent::__construct();
        $framework = Framework::instance();
        $this->userService = $framework->get('userService');
    }

    public function index() {
        $this->userService->logout();
        $this->redirect($this->userService->getLoggedOutUrl());
    }

}