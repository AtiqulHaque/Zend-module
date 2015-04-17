<?php
/**
 * Other Settings Dao Model
 *
 * @category        Dao Model
 * @package         BlogUser
 * @author          Md. Nuruzzaman Bappi<bappi.cse562@gmail.com>
 * @copyright       Copyright (c) 2014 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Dao;
use NBlog\Model\Dao\RelationBase;
use Zend\Db\Sql\Select;

class OtherSetting extends RelationBase
{
    protected $table = 'other_settings';
    protected $primaryKey = 'settings_id';

    public function getUserSettings($userId)
    {
        $select = $this->select()
            ->columns(array('*'));
        $select->where(array('user_id' => $userId));

        return $this->returnResultSet($select, true);
    }

   protected function setSettingsConditions(Select $select, $options = array())
   {
      return $select;
   }

}