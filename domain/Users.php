<?php

class Users {

    const CONFIG_SALT = 'users.salt';

    /** @var Database */
    protected $db;

    /** @var Config */
    protected $config;

    protected $dbInstanceName = 'database';
    protected $tableName = 'user';
    protected $recordClass = 'User';
    
    protected $cache = [];

    public function __construct() {
        $framework = Framework::instance();
        $this->config = $framework->get('config');
        $this->db = $framework->get($this->dbInstanceName);
    }

    /**
     * @param $email
     * @param $password
     * @return User
     */
    public function findActiveByEmailAndPassword($email, $password) {
        $record = $this->db->fetch(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE email = :email AND password = :password AND active = 1 LIMIT 1",
            [':email' => $email, ':password' => $this->hash($password)]
        );
        return $record;
    }

    /**
     * @param $id
     * @return User
     */
    public function findById($id, $useCache=true) {
        $id = (int)$id;
        if ($useCache && isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        $result = $this->db->fetch(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE id = :id LIMIT 1",
            [':id' => $id]
        );
        if ($useCache) {
            $this->cache[$id] = $result;
        }
        return $result;
    }

    /**
     * @param $id
     * @return User
     */
    public function findActiveById($id) {
        return $this->db->fetch(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE id = :id AND active = 1 LIMIT 1",
            [':id' => $id]
        );
    }
    
    public function findByIds($ids) {
        $in = $this->db->getInConditionAndParams($ids);
        return $this->db->fetchAll(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE id IN (".$in['condition'].") LIMIT ".count($ids),
            $in['params']
        );
    }

    /**
     * @param $email
     * @return User
     */
    public function findByEmail($email) {
        return $this->db->fetch(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE email = :email LIMIT 1",
            [':email' => $email]
        );
    }

    /**
     * @param $name
     * @return User
     */
    public function findByName($name) {
        return $this->db->fetch(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE name = :name LIMIT 1",
            [':name' => $name]
        );
    }
    
    /**
     * @param $hash
     * @return User
     */
    public function findByHash($name, $hash) {
        $query = "SELECT user_id FROM user_hash WHERE name = :name AND hash = :hash LIMIT 1";
        $id = $this->db->fetchColumn($query, [
            ':name' => $name,
            ':hash' => $hash
        ]);
        if (!$id) {
            return null;
        }
        return $this->findById($id);
    }
    
    /**
     * @param $hash
     * @return User
     */
    public function findActiveByHash($name, $hash) {
        $user = $this->findByHash($name, $hash);
        if (!$user || !$user->getActive()) {
            return null;
        }
        return $user;
    }
    

    public function findByEmailExceptId($email, $exceptId) {
        return $this->db->fetch(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE email = :email AND id <> :id LIMIT 1",
            [':email' => $email, ':id' => $exceptId]
        );
    }


    /**
     * @return User
     */
    public function create() {
        $framework = Framework::instance();
        /** @var User $user */
        $user = $framework->create($this->recordClass);
        return $user;
    }

    public function hash($value) {
        $salt = $this->config->get(self::CONFIG_SALT);
        if (!$salt) {
            throw new RuntimeException("'".self::CONFIG_SALT."' has no value in configuration.");
        }
        return md5($salt.$value);
    }
}