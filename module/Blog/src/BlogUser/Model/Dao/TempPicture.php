<?php
/**
 * TempPicture Dao Model
 * 
 * @category    Dao Model
 * @package     BlogUser
 * @author      Atik
 * @copyright   Copyright (c) 2013 Nokkhotro Blog (http://nokkhotroblog.com) 
 */
namespace BlogUser\Model\Dao;

use NBlog\Model\Dao\RelationBase;
use Zend\Db\Sql\Predicate\Expression;

class TempPicture extends RelationBase
{
    protected $table = 'picture_temps';
    protected $primaryKey = 'picture_temp_id';

    public function getAll($options)
    {
        $select = $this->select()
            ->columns(array('*'))
            ->where(array('user_id' => $options['user_id'], 'type' => $options['type']))
            ->order(array("{$this->table}.{$this->primaryKey} DESC"))
            ->limit(1);
        return $this->returnResultSet($select,true);
    }

    public function countAll(array $options)
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT(user_id)")))
            ->where(array(
                "{$this->table}.user_id = ?" => $options['user_id'],
                "{$this->table}.type = ?" => $options['type']))
            ->group(array("{$this->table}.user_id", "{$this->table}.type"));

        return $this->returnResultSet($select);
    }

    public function updateTemp(array $data, $user_id, $pic_type)
    {
        $data = $this->removeNonAttributes($data);
        $update = $this->update()
            ->set($data)
            ->where(array("user_id" => $user_id, "type" => $pic_type));
        $result = $this->getResultAfterAlteration($update);
        return isset($result);
    }

    public function removeTempFile($user_id, $pic_type)
    {
        $update = $this->delete()
            ->where(array("user_id" => $user_id, "type" => $pic_type));

        $result = $this->getResultAfterAlteration($update);
        return isset($result);
    }
}