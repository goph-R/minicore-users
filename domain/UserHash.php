<?php

class UserHash extends Record {
    
    protected $tableName = 'user_hash';
    
    protected $id;
    protected $user_id;
    protected $name;
    protected $hash;
    
}
