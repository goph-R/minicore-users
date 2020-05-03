<?php

class Permissions {

    /** @var Database */
    protected $db;

    /** @var Translation */
    protected $translation;
    
    protected $dbInstanceName = 'database';
    protected $tableName = 'permission';
    protected $recordClass = 'Permission';

    public function __construct() {
        $framework = Framework::instance();
        $this->db = $framework->get($this->dbInstanceName);
        $this->translation = $framework->get('translation');
    }
    
    public function findAll() {
        $query = "SELECT p.id AS id, pt.name AS name FROM {$this->tableName} AS p";
        $query .= " JOIN {$this->tableName}_text AS pt ON p.id = pt.text_id AND pt.locale = :locale";
        return $this->db->fetchAll($this->recordClass, $query, [
            ':locale' => $this->translation->getLocale()
        ]);
    }

}
