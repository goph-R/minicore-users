<?php

class Users {

    /** @var Framework */
    protected $framework;

    /** @var Database */
    protected $db;

    /** @var Config */
    protected $config;

    protected $dbInstanceName = 'database';
    protected $tableName = 'user';
    protected $recordClass = 'User';

    public function __construct(Framework $framework) {
        $this->framework = $framework;
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
    public function findById($id) {
        return $this->db->fetch(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE id = :id LIMIT 1",
            [':id' => $id]
        );
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
    public function findByActivationHash($hash) {
        return $this->db->fetch(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE activation_hash = :hash LIMIT 1",
            [':hash' => $hash]
        );
    }

    /**
     * @param $hash
     * @return User
     */
    public function findByForgotHash($hash) {
        return $this->db->fetch(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE forgot_hash = :hash LIMIT 1",
            [':hash' => $hash]
        );
    }

    /**
     * @param $hash
     * @return User
     */
    public function findActiveByRememberHash($hash) {
        return $this->db->fetch(
            $this->recordClass,
            "SELECT * FROM {$this->tableName} WHERE remember_hash = :hash LIMIT 1",
            [':hash' => $hash]
        );
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
        /** @var User $user */
        $user = $this->framework->create($this->recordClass);
        return $user;
    }

    public function hash($value) {
        return md5($this->config->get('user.salt').$value);
    }
}