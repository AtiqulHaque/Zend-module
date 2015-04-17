<?php
/**
 * Album Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md.Atiqul Haque <md_atiqulhaque@yahoo.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Dao;

use NBlog\Model\Dao\RelationBase;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;

class Album extends RelationBase
{
    protected $table = 'albums';
    protected $primaryKey = 'album_id';

    public function getById($options)
    {
        $select = $this->select()
            ->columns(array('*'))
            ->where(array('user_id' => $options['user_id'],"{$this->table}.{$this->primaryKey}" => $options['album_id']))
            ->limit(1);
        return $this->returnResultSet($select,true);
    }

    public function getAlbumByPermalink($options)
    {
        $select = $this->select()
            ->columns(array('*'))
            ->where(array('user_id' => $options['user_id'],"permalink = ?"=>$options['permalink']))
            ->limit(1);
        return $this->returnResultSet($select,true);
    }

    public function getUserAllAlbum(array $options = array())
    {
        $sql = new Select('image_usages');
        $sql = $sql->columns(array('image_id', 'album_id'))->order(array(new Expression('RAND()')));
        $sql2 = new Select('images');
        $sql2 = $sql2->columns(array('image_id', 'image_url'));

        $select = $this->select()
            ->join(array('image_usages' => $sql), "image_usages.{$this->primaryKey} = {$this->table}.{$this->primaryKey}", Select::SQL_STAR, Select::JOIN_LEFT)
            ->join(array('images' => $sql2), "images.image_id = image_usages.image_id", Select::SQL_STAR, Select::JOIN_LEFT)
            ->where(array(
                "{$this->table}.user_id" => $options['user_id']))
            ->group($this->table.'.'.$this->primaryKey);
        return $this->returnResultSet($select);
    }

    public function countAll(array $options)
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT(user_id)")))
            ->where(array(
            "{$this->table}.user_id = ?" => $options['user_id'],
            "{$this->table}.{$this->primaryKey} = ?" => $options['album_id']));

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function updateTotalPic(array $data, $album_id)
    {
        $data = $this->removeNonAttributes($data);
        $update = $this->update()
            ->set($data)
            ->where(array("album_id" => $album_id));
        $result = $this->getResultAfterAlteration($update);
        return isset($result);
    }

    public function countAllByAlbumName(array $options)
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->primaryKey})")))
            ->where(array(
                "{$this->table}.album_name = ?" => $options['album_name'],"{$this->table}.user_id = ?"=>$options['user_id']));
        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

}