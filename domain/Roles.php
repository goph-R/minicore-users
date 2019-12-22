<?php

class Roles {

    /** @var Framework */
    protected $framework;

    /** @var Database */
    protected $db;

    protected $dbInstanceName = 'database';
    protected $tableName = 'role';
    protected $recordClass = 'Role';

    public function __construct(Framework $framework) {
        $this->framework = $framework;
        $this->db = $framework->get($this->dbInstanceName);
    }

}
