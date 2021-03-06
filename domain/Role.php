<?php

class Role extends Record {
    
    protected $tableName = 'role';
    protected $localizedList = ['name'];
    protected $referenceList = ['permissions'];

    protected $id;
    protected $name;

    protected $permissions;
    
    public function __toString() {
        return $this->name;
    }
    
    public function findPermissions() {
        $query = 'SELECT p.id, pt.name FROM permission AS p';
        $query .= ' JOIN permission_text AS pt ON pt.text_id = p.id AND pt.locale = :locale';
        $query .= ' JOIN role_permission AS rp ON p.id = rp.permission_id';
        $query .= ' JOIN role AS r ON r.id = rp.role_id';
        $query .= ' WHERE r.id = :id';
        return $this->db->fetchAll('Permission', $query, [
            ':id' => $this->getId(),
            ':locale' => $this->translation->getLocale()
        ]);
    }
    
    public function getPermissions() {
        if (!$this->permissions) {
            $this->permissions = $this->findPermissions();
        }
        return $this->permissions;
    }    
    
}
