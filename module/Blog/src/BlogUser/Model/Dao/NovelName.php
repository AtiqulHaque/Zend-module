<?php

/**
 * Novel Name Dao Model
 *
 * @category        Dao Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Dao;

use NBlog\Model\Dao\RelationBase;

class NovelName extends RelationBase
{
    protected $table = 'novel_name';
    protected $primaryKey = 'novel_name_id';

    public function getByUser($userId)
    {
        $select = $this->select()
            ->join('users', "{$this->table}.create_by = users.user_id")
            ->where(array("{$this->table}.create_by = ?" => $userId, "{$this->table}.novel_name_is_published = ?" => 1))
            ->order(array("{$this->primaryKey} DESC"));

        return $this->returnResultSet($select);
    }

    public function getByPermalink($permalink)
    {
        $select = $this->select()
            ->where(array("{$this->table}.novel_name_permalink = ?" => $permalink));

        return $this->returnResultSet($select, true);
    }
}