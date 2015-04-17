<?php
/**
 * Group Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;

use NBlog\Model\ServiceLocatorBlogDB;

class Group extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\Group
     */
    protected $dao = null;

    public function getAll($options = array())
    {
        return $this->dao->getAll($options);
    }

    public function getGroupByUserName($userId)
    {
        return $this->dao->getGroupByUserName($userId);
    }
}