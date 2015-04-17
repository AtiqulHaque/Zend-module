<?php
/**
 * Notice User Dao Model
 *
 * @category        Dao Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Dao;
use NBlog\Model\Dao\RelationBase;

class NoticeUser extends RelationBase
{
    protected $table = 'notices_users';
    protected $primaryKey = '';
}