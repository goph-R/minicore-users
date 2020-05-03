<?php

class UserAvatarController extends Controller {
    
    /** @var UserService */
    protected $userService;
    
    public function __construct() {
        parent::__construct();
        $framework = Framework::instance();
        $this->userService = $framework->get('userService');
    }    

    public function index() {
        $this->userService->requireLogin();
        $form = $this->userService->createAvatarForm($this->userService->getCurrentUser());
        $this->saveAvatar($form);
        $this->view->set(['userSession' => $this->userSession]);
        $this->render(':user/settings', [
            'form' => $form,            
            'active' => 'avatar',
            'action' => route_url('settings/avatar')
        ]);        
    }
    
    public function remove() {
        $this->userService->requireLogin();
        $this->userService->removeAvatar($this->userService->getCurrentUser());
        $this->redirect('settings/avatar');
    }
    
    public function saveAvatar(Form $form) {
        if (!$form->processInput()) {
            return;
        }
                
        // TODO: put this part in a Validator
        /** @var UploadedFile $file */
        $file = $form->getValue('file');
        $error = null;
        if (!$file->isUploaded()) {
            $error = $this->getMessage('error', 'upload_was_unsuccessful');
        } else if ($file->getSize() > $this->userService->getAvatarMaxFileSize() || in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) {
            $error = $this->getMessage('error', 'file_too_big');
        } else if ($file->getError() != UPLOAD_ERR_OK) {
            $error = $this->getMessage('error', 'upload_was_unsuccessful');
        } else if ($file->getType() != 'image/jpeg' || !getimagesize($file->getTempPath())) {
            $error = $this->getMessage('error', 'uploaded_file_not_image');
        }
        if ($error) {
            $this->userSession->setFlash('settings_messages', [$error]);
            return;
        }
        //
        
        $user = $this->userService->getCurrentUser();
        $this->userService->changeAvatar($user, $file->getTempPath());
        $user->save();
    }
    
    private function getMessage($type, $text) {
        return [
            'type' => $type,
            'text' => text('user', $text)
        ];
    }    
    
}