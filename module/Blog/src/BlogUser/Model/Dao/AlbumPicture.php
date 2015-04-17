<?php
/**
 * AlbumPicture
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md.Atiqul Haque <md_atiqulhaque@yahoo.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */

namespace BlogUser\Model\Dao;
use Zend\Db\Sql\Predicate\Expression;
use \Zend\Db\Sql\Select AS Select;
use Zend\Db\Sql\Predicate\In;
use \NBlog\Model\Dao\RelationBase;

class AlbumPicture extends RelationBase
{
    protected $table = 'album_pictures';
    protected $primaryKey = 'album_picture_id';



    public function getAlbumPicByAlbumId($options)
    {
        $select = $this->select()
            ->columns(array('*'));
        $select = $this->setConditionForAlbumPic( $select,$options);
        $select = $select->order(array("{$this->table}.album_id  ASC"));
        return $this->returnResultSet($select);
    }

    public function getAlbumPic($options, $limit)
    {
        $options['album_id'] = (array)$options['album_id'];
        $select = $this->select()
            ->join('albums', "{$this->table}.album_id = albums.album_id", array('permalink' =>'permalink'))
            ->where(array(new In("{$this->table}.album_id", $options['album_id'])))
            ->order(array(new Expression("RAND()")));
            empty($limit) || $select->limit($limit);
        return $this->returnResultSet($select);
    }

    public function countAlbumImage($options)
    {
        $select = $this->select()
            ->columns(array('no'=> new Expression("count({$this->primaryKey})")))
            ->join('albums', "{$this->table}.album_id = albums.album_id", array())
            ->where(array("albums.user_id = ?" => $options['user_id']));

        $result = $this->returnResultSet($select, true);
        return empty($result) ? '0' : $result['no'];
    }

    public function countAllByAlbumId(array $options)
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->primaryKey})")))
            ->where(array(
            "{$this->table}.album_id = ?" => $options['album_id']));
        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    protected function setConditionForAlbumPic(Select  $select, array $options){
        if(is_array($options['album_id'])){
            return $select->where(array(new In('album_id', $options['album_id'])));
        }else{
            return $select->where(array("album_id"=>$options['album_id']));
        }
    }

}
