<?php
/**
 * Notification Dao Model
 *
 * @category        Dao Model
 * @package         BlogUser
 * @author          Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Dao;

use NBlog\Model\Dao\RelationBase;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate\NotIn;
use Zend\Db\Sql\Select;

class Notification extends RelationBase
{
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';

    public function removePreviousNotifications()
    {
        $sql = new Select('users_notifications');
        $sql->columns(array($this->primaryKey => new Expression("DISTINCT({$this->primaryKey})")));
        $delete = $this->delete()->where(array(new NotIn($this->primaryKey, $sql)));
        $result = $this->getResultAfterAlteration($delete);
        return isset($result);
    }
}