<?php

class Permission extends Record {
    
    protected $tableName = 'permission';

    protected $id;
    protected $name;    
    
    public function __toString() {
        return $this->name;
    }    
}

