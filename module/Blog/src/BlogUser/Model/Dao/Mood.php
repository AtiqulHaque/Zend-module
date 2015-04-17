<?php
/**
 * Mood Dao Model
 *
 * @category        Dao Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Dao;

use NBlog\Model\Dao\Writing;
use NBlog\Model\WritingStatus;
use NBlog\Model\WritingType;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\Like;
use Zend\Db\Sql\Select;

class Mood extends Writing
{
    protected $table = 'moods';
    protected $primaryKey = 'mood_id';
    protected $writingType = WritingType::MOOD;

    public function getAll($options)
    {
        $select = $this->select()
            ->columns(array('*', 'mood_created_by' => 'user_id', 'mood_created' => 'created', 'mood_status' => 'status'));

        $select = $this->setMoodConditions($select, $options)
            ->order(array("{$this->table}.{$this->primaryKey} DESC"))
            ->offset($options['offset'])
            ->limit($options['limit']);

        return $this->returnResultSet($select);
    }

    public function countAll($options = array())
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->primaryKey})")));
        $select = $this->setMoodConditions($select, $options);
        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function getDetail($id, array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'mood_created_by' => 'user_id', 'mood_status' => 'status', 'mood_updated' => 'modified', 'created_by' => 'user_id'))
            ->where(array("{$this->primaryKey} = ?" => $id))
            ->limit(1);

        return $this->returnResultSet($select, true);
    }

    public function getDetailByUserId($moodIds,$userId)
    {
        $select = $this->select()
            ->columns(array('*', 'mood_created_by' => 'user_id', 'mood_status' => 'status', 'mood_updated' => 'modified', 'created_by' => 'user_id'))
            ->where(array(
                "{$this->primaryKey} = ?" => $moodIds,
                "user_id = ?" => $userId
            ))
            ->limit(1);

        return $this->returnResultSet($select, true);
    }

    public function getAllStatusByImage()
    {
        $select = $this->select()
            ->columns(array('details'=>'title','post_id'=>'mood_id','created_by'=>'user_id','permalink','title'))
            ->where(array(new Like('title', "%<img%")));

        return $this->returnResultSet($select);
    }

    public function getByPermalink($permalink, array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'mood_created_by' => 'user_id', 'mood_status' => 'status', 'mood_updated' => 'modified', 'created_by' => 'user_id'))
            ->where(array("{$this->table}.permalink = ?" => $permalink))
            ->group("{$this->table}.{$this->primaryKey}");
//            ->limit(1);

        !isset($options['status']) || $select->where(array("{$this->table}.status = ?" => $options['status']));
        !isset($options['is_reported']) || $select->where(array("{$this->table}.is_reported = ?" => (int)$options['is_reported']));
        $select = $this->setUserActivityStatus($select, $options);

        /* This is a special case, because of PDO problem. */
        return $this->returnResultByRawQuery($this->getSqlStringForSqlObject($select) . ' LIMIT 1', true);
    }

    public function getByIds(array $moodIds)
    {
        $select = $this->select()
            ->columns(array('*', 'mood_created_by' => 'user_id', 'mood_status' => 'status', 'mood_updated' => 'modified', 'created_by' => 'user_id'))
            ->where(array(new In($this->primaryKey, $moodIds)));

        return $this->returnResultSet($select);
    }

    /**
     * This function set the conditions of search, summary of and count moods in the select query.
     *
     * @param       Select $select
     * @param       array $options
     *
     * @return Select
     */
    protected function setMoodConditions(Select $select, $options = array())
    {
        empty($options['user_id']) || $select->where(array("{$this->table}.user_id = ?" => $options['user_id']));

        $select->where(array("{$this->table}.status != ?" => WritingStatus::ADMIN_TRASH));
        if (isset($options['status'])) {
            is_array($options['status']) || $options['status'] = (array)$options['status'];
            $select->where(array(new In($this->table . '.status', $options['status'])));
        }

        return $select;
    }
}