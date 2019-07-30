<?php

class UserProfileController extends Controller {

    /** @var UserService */
    protected $userService;

    public function __construct(Framework $framework) {
        parent::__construct($framework);
        $this->userService = $framework->get('userService');
    }

    public function index($id) {
        $user = $this->userService->findById($id);
        if (!$user || !$user->get('active')) {
            $this->framework->error(404);
        }
        $this->render(':user/profile', ['user' => $user]);
    }

}