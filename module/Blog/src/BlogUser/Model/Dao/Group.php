<?php

/**
 * Group Dao Model
 *
 * @category        Dao Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Dao;

use NBlog\Model\Dao\RelationBase;

class Group extends RelationBase
{
    protected $table = 'blog_groups';
    protected $primaryKey = 'blog_group_id';

    public function getAll()
    {
        $select = $this->select()
            ->join('blog_group_types', "blog_group_types.blog_group_type_id = {$this->table}.blog_group_type_id")
            ->where(array("{$this->table}.blog_group_is_published = ?" => "1"))
            ->order(array("{$this->primaryKey} DESC"));

        return $this->returnResultSet($select);
    }

    public function getGroupByUserName($userId)
    {
        $select = $this->select()
            ->join('blog_group_types', "blog_group_types.blog_group_type_id = {$this->table}.blog_group_type_id")
            ->where(array("{$this->table}.blog_group_is_published = ?" => "1", "{$this->table}.create_by = ?" => $userId))
            ->order(array("{$this->primaryKey} DESC"));

        return $this->returnResultSet($select);
    }
}