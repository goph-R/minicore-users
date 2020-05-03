<?php

class SameValidator extends Validator {

    private $form;
    private $otherInputName;

    public function __construct(Form $form, $otherInputName) {
        parent::__construct();
        $this->message = $this->translation->get('user', 'didnt_match');
        $this->otherInputName = $otherInputName;
        $this->form = $form;
    }

    public function doValidate($value) {
        if ($this->form->getValue($this->otherInputName) != $value) {
            return false;
        }
        return true;
    }

}