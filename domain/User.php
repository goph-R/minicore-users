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
    protected $new_email = '';
    protected $avatar = '';
    
    public function setActive($value) {
        $this->active = $value ? 1 : 0;
    }
    
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
    
    public function hasPermission($permissionId) {
        if ($permissionId === null) {
            return false;
        }
        if ($permissionId === 0) {
            return true;
        }
        $permissionIds = [$permissionId, AdminPermissions::ADMINISTRATION];
        foreach ($this->getPermissions() as $permission) {
            if (in_array($permission->getId(), $permissionIds)) {
                return true;
            }
        }
        return false;
    }
        
    public function getAvatarPath() {
        $avatar = $this->getAvatar();
        $prefix = '';
        if ($avatar) {
            $prefix = $avatar[0].$avatar[1].'/'.$avatar[2].$avatar[3].'/';
        }
        return 'upload/avatar/'.$prefix.$avatar.'.jpg';
    }
    
    public function hasAvatar() {
        return file_exists($this->getAvatarPath());
    }
    
    public function getAvatarUrl() {
        if (!$this->hasAvatar()) {
            return 'modules/minicore-users/static/default-avatar.png';
        }
        return $this->getAvatarPath();
    }
    
    public function createHash($name, $hash) {
        $userHash = new UserHash();
        $userHash->setUserId($this->getId());
        $userHash->setName($name);
        $userHash->setHash($hash);
        $userHash->save();
        return $userHash;
    }
    
    public function removeHashByName($name) {
        $query = "DELETE FROM user_hash WHERE user_id = :user_id AND name = :name";
        $this->db->query($query, [
            ':user_id' => $this->getId(),
            ':name' => $name
        ]);
    }
    
}