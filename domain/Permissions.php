<?php

class Permissions {

    /** @var Framework */
    protected $framework;

    /** @var Database */
    protected $db;

    protected $dbInstanceName = 'database';
    protected $tableName = 'permission';
    protected $recordClass = 'Permission';

    public function __construct(Framework $framework) {
        $this->framework = $framework;
        $this->db = $framework->get($this->dbInstanceName);
    }

}
