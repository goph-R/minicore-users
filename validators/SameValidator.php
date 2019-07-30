<?php

class SameValidator extends Validator {

    private $form;
    private $otherInputName;

    public function __construct(Framework $framework, Form $form, $otherInputName) {
        parent::__construct($framework);
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