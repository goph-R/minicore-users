<?php

class User extends Record {

    protected $tableName = 'user';
    protected $referenceList = ['roles', 'permissions'];
    protected $roles = [];
    protected $permissions = [];
    
    protected $id = 0;
    protected $name;
    protected $first_name;
    protected $last_name;
    protected $email;
    protected $password;
    protected $active = 0;
    protected $last_login = 0;
    protected $activation_hash = '';
    protected $forgot_hash = '';
    protected $remember_hash = '';
    protected $new_email = '';
    protected $new_email_hash = '';
    
    public function findRoles() {
        $query = 'SELECT r.id, rt.name FROM role AS r';
        $query .= ' JOIN role_text AS rt ON rt.text_id = r.id AND rt.locale = :locale';
        $query .= ' JOIN user_role AS ur ON ur.role_id = r.id';
        $query .= ' JOIN user AS u ON u.id = ur.user_id AND u.id = :id';
        return $this->db->fetchAll('Role', $query, [
            ':id' => $this->get('id'),
            ':locale' => $this->translation->getLocale()
        ]);        
    }
    
    public function findPermissions() {
        $query = 'SELECT p.id, pt.name FROM permission AS p';
        $query .= ' JOIN permission_text AS pt ON pt.text_id = p.id AND pt.locale = :locale';
        $query .= ' JOIN role_permission AS rp ON p.id = rp.permission_id';
        $query .= ' JOIN role AS r ON r.id = rp.role_id';
        $query .= ' JOIN user_role AS ur ON ur.role_id = r.id';
        $query .= ' JOIN user AS u ON u.id = ur.user_id AND u.id = :id';
        return $this->db->fetchAll('Permission', $query, [
            ':id' => $this->get('id'),
            ':locale' => $this->translation->getLocale()
        ]);
    }
    
    public function getRoles() {
        if (!$this->roles) {
            $this->roles = $this->findRoles();
        }
        return $this->roles;
    }

    public function getPermissions() {
        if (!$this->permissions) {
            $this->permissions = $this->findPermissions();
        }
        return $this->permissions;
    }
    
    public function hasPermission($permissionIds) {
        if (!is_array($permissionIds)) {
            $permissionIds = [$permissionIds];
        }
        if (in_array(null, $permissionIds)) {
            return true;
        }
        $permissions = $this->getPermissions();
        foreach ($permissions as $permission) {
            if (in_array($permission->getId(), $permissionIds)) {
                return true;
            }
        }
        return false;
    }

}