<?php

class Roles {

    const ADMINISTRATOR_ID = 1;
    
    /** @var Framework */
    protected $framework;

    /** @var Database */
    protected $db;

    /** @var Translation */
    protected $translation;
    
    protected $dbInstanceName = 'database';
    protected $tableName = 'role';
    protected $recordClass = 'Role';

    public function __construct(Framework $framework) {
        $this->framework = $framework;
        $this->db = $framework->get($this->dbInstanceName);
        $this->translation = $framework->get('translation');
    }
    
    public function findAll() {
        $query = "SELECT r.id AS id, rt.name AS name FROM {$this->tableName} AS r";
        $query .= " JOIN {$this->tableName}_text AS rt ON r.id = rt.text_id AND rt.locale = :locale";
        return $this->db->fetchAll($this->recordClass, $query, [
            ':locale' => $this->translation->getLocale()
        ]);
    }
}
