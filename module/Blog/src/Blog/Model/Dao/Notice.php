<?php
/**
 * Notice Dao Model
 *
 * @category        Dao Model
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Model\Dao;
use Admin\Model\Notice AS NoticeModel;
use NBlog\Model\Dao\RelationBase;
use NBlog\Model\NoticeStatus;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\NotIn;
use Zend\Db\Sql\Predicate\Expression;
use Admin\Model\NoticeService;

class Notice extends RelationBase
{
    protected $table = 'notices';
    protected $primaryKey = 'notice_id';

    public function getAll($options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'notice_created_by' => 'created_by', 'notice_created' => 'created', 'notice_updated' => 'updated'));

        $select = $this->setConditions($select, $options)
            ->order(array("{$this->table}.{$this->primaryKey} DESC"));

        empty($options['offset']) || $select->offset($options['offset']);
        $select->limit($options['limit']);
        return $this->returnResultSet($select);
    }

    public function countAll()
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->primaryKey})")));
        $select = $this->setConditions($select);
        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    protected function setConditions(Select $select, $options = array())
    {
        empty($options['is_active']) || $select->where(array("is_active = ?" => $options['is_active']));
        $subQuery = new Select('notices_services');
        $subQuery->columns(array($this->primaryKey))->where(array('service_id' => NoticeService::BLOG));
        if (empty($options['user_logged_in'])) {
            $subQuery->where(array('visibility' => NoticeStatus::VISIBLE_FOR_ALL));
        } else {
            $subQuery2 = new Select('notices_users');
            $subQuery2->columns(array($this->primaryKey))->where(array('user_id' => $options['user_logged_in']));
            $subQuery->where(array(new In('visibility', array(NoticeStatus::VISIBLE_FOR_USERS,NoticeStatus::VISIBLE_FOR_ALL)), new NotIn($this->primaryKey, $subQuery2)));
        }
        $select->where(array(new In($this->primaryKey, $subQuery), 'type' => NoticeModel::WEB_TYPE, 'status'=> NoticeStatus::PRESENT));
        return $select;
    }

    public function getByPermalink($permalink)
    {
        $select = $this->select()
            ->columns(array('*', 'notice_created_by' => 'created_by', 'notice_created' => 'created', 'notice_updated' => 'updated'))
            ->where(array("permalink = ?" => $permalink))
            ->limit(1);

        return $this->returnResultSet($select, true);
    }

    public function incrementCommentCounting($noticeId)
    {
        return $this->modify(array('total_comments' => new Expression("`total_comments` + 1")), $noticeId);
    }

    public function decrementCommentCounting($noticeId, $decrementValue = 1)
    {
        return $this->modify(array('total_comments' => new Expression("`total_comments` - {$decrementValue}")), $noticeId);
    }

    public function getByIds(array $noticeIds)
    {
        $select = $this->select()
            ->columns(array('*', 'notice_created_by' => 'created_by', 'notice_status' => 'status', 'notice_created' => 'created', 'notice_updated' => 'updated'))
            ->where(array(new In($this->primaryKey, $noticeIds)));

        return $this->returnResultSet($select);
    }
}