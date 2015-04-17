<?php
/**
 * Chat Dao Model
 *
 * @category        Dao Model
 * @package         BlogUser
 * @author          Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Dao;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\Like;
use Zend\Db\Sql\Predicate\PredicateSet;
use NBlog\Model\Dao\RelationBase;
use Zend\Db\Sql\Select;

class Chat extends RelationBase
{
    protected $table = 'chat';
    protected $primaryKey = 'id';
    protected $sleepTime = 1;
    protected $timeoutTime = 15;

    public function setTimeout($timeout)
    {
        $this->timeoutTime = $timeout;
    }

    public function setSleep($sleep)
    {
        $this->sleepTime = $sleep;
    }

    public function loadChatBox($userId)
    {
        $select = $this->select()
            ->where(array("{$this->table}.read = ?" => '0', "{$this->table}.to = ?" => $userId))
            ->group("{$this->table}.from");

        $result = $this->returnResultSet($select);
        return (empty($result)) ? false : $result;
    }

    public function getChatterFriends($userId)
    {
        $select = $this->select()
            ->where(array("{$this->table}.read = ?" => '0', "{$this->table}.to = ?" => $userId))
            ->group("{$this->table}.from");

        $result = $this->returnResultSet($select);
        return (empty($result)) ? false : $result;
    }

    public function getChatterFriendsHistory($userId)
    {
        $select = $this->select()->columns(array(
            '*',
            'unread'=> new Expression('GROUP_CONCAT( `read` ORDER BY `read` ASC  )')
            ))
            ->where(array(new PredicateSet(array(
                new Like("from", $userId),
                new Like("to", $userId)
            ), PredicateSet::COMBINED_BY_OR)))
            ->group("{$this->table}.from")
            ->order("{$this->table}.time DESC");
        $result = $this->returnResultSet($select);
        return (empty($result)) ? false : $result;
    }

    public function newMessage($from, $to, $message)
    {
        $data = array('from' => $from, 'to' => $to, 'message' => $message, 'time' => time());
        return $this->save($data);
    }

    public function loadData($from, $to)
    {
        $select = $this->select()
            ->where(array(
                "{$this->table}.read = ?" => '0',
                "{$this->table}.to = ?" => $to,
                "{$this->table}.from = ?" => $from));

        $result = $this->returnResultSet($select);
        if (empty($result)) {
            return false;
        }

        $this->setMessageRead($result);
        return $result;
    }

    public function getChatHistory($userId)
    {
        $select = $this->select()->columns(array('id', 'message', 'partner_id' => 'to', 'message_time'=>'time', 'from'))
            ->where(array('from' => $userId))
            ->where(array('to' => $userId), PredicateSet::OP_OR)
            ->order('time ASC');
        return $this->returnResultSet($select);
    }

    public function getUnreadMessagePerson($userId,array $options = array(),$isOnlyRead = true)
    {
        $options['offset'] = empty($options['offset']) ? 0 : $options['offset'];
        $options['limit']  = empty($options['limit']) ? 10 : $options['limit'];
        $select = $this->select()->columns(array('id',
            'partner_id' => 'to',
            'message_time'=>'time', 'from','read',
            'message' => new Expression((($isOnlyRead)) ? "group_concat(message ORDER BY id DESC)" : "group_concat(message,'(!-!)',`read` ORDER BY id DESC)" )
        ))
            ->where(array('to'=>$userId))
            ->group("{$this->table}.from")
            ->order('time DESC')
            ->offset($options['offset'])
            ->limit($options['limit']);
        empty($isOnlyRead) || $select->where(array('read' => 0));
        return $this->returnResultSet($select);
    }
    public function countUnreadMessagePerson($userId)
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->primaryKey})")))
            ->where(array('read' => 0, 'to'=>$userId))
            ->group("{$this->table}.from")
            ->order('time ASC');
       return $this->returnResultSet($select);
    }

    public function getChatAllMessage(array $options)
    {
        $select = $this->select()
            ->columns(array('message', 'partner_id' => 'to', 'message_time'=>'time', 'from'))
            ->where(array('from' => $options, 'to' =>$options))
            ->order('time ASC');
        return $this->returnResultSet($select);
    }

    public function getMessageBtId($userId)
    {
        $select = $this->select()->columns(array('message', 'partner_id' => 'to', 'message_time'=>'time', 'from'))
            ->where(array($this->primaryKey => $userId));
        return $this->returnResultSet($select, true);
    }

    public function setChatMessageRead($to, $from)
    {
        $update = $this->update()
            ->set(array('read' => '1'))
            ->where(array('to'=>$to,'from'=>$from));
        return $this->getResultAfterAlteration($update);
    }

    private function setMessageRead(array $result = array())
    {
        if (!empty($result)) {
            $rowIds = array();
            foreach ($result AS $row) {
                $rowIds[] = $row[$this->primaryKey];
            }

            if (!empty($rowIds)) {
                $update = $this->update()
                    ->set(array('read' => '1'))
                    ->where(array(new In($this->primaryKey, $rowIds)));
                $this->getResultAfterAlteration($update);
            }
        }
    }
}