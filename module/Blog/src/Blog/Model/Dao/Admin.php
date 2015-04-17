<?php

/**
 * Admin Dao Model
 *
 * @category        Dao Model
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Model\Dao;
use NBlog\Model\Dao\RelationBase;
use NBlog\Model\Role;
use Zend\Db\Sql\Predicate\In;

class Admin extends RelationBase
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public function getAdminUsersDetails(array $adminIds)
    {
        $select = $this->select()->columns(array($this->primaryKey, 'username', 'email', 'admit_date' => 'created', 'last_login'))
            ->join('roles_users', "{$this->table}.{$this->primaryKey}=roles_users.{$this->primaryKey}", array())
            ->join('profiles', "{$this->table}.{$this->primaryKey} = profiles.{$this->primaryKey}", array('first_name','last_name','middle_name', 'nickname','gender', 'image_id', 'website','permanent_address', 'profession'))
            ->where(array(
                    new In($this->table.'.'.$this->primaryKey, $adminIds),
                    "roles_users.role_id = ?" => Role::ADMIN));

        return $this->returnResultSet($select);
    }
}
