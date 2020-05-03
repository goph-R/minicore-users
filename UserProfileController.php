<?php

class UserProfileController extends Controller {

    /** @var UserService */
    protected $userService;

    public function __construct() {
        parent::__construct();
        $framework = Framework::instance();
        $this->userService = $framework->get('userService');
    }

    public function index($id) {
        $user = $this->userService->findById($id);
        if (!$user || !$user->get('active')) {
            $this->error(404);
        }
        $this->render(':user/profile', ['user' => $user]);
    }

}