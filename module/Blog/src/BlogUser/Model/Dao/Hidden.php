<?php
/**
 * Hidden Dao Model
 *
 * @category        Dao Model
 * @package         BlogUser
 * @author          Md. Nuruzzaman Bappi <bappi.cse562@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Dao;

use NBlog\Model\Dao\RelationBase;
use \Zend\Db\Sql\Predicate\In;

class Hidden extends RelationBase
{
    protected $table = 'hidings';
    protected $primaryKey = 'hidden_id';

    public function getHiddenStatus(array $idsHiddenOf, $loggedInUserId, $hiddenFor, $status = '1')
    {
        $select = $this->select()
            ->columns(array('content_id', 'status'))
            ->where(array(
                'content_type' => $hiddenFor,
                'hidden_by' => $loggedInUserId,
                'status' => $status,
                new In('content_id', $idsHiddenOf)
            ));

        return $this->returnResultSet($select);
    }

    public function getHiddenCommentsOfUser($loggedInUser, $id, $hiddenStatus)
    {
        $select = $this->select()
            ->columns(array($this->primaryKey, 'content_id' => 'content_id'))
            ->where(array(
                'content_type' => $hiddenStatus,
                'hidden_by' => $loggedInUser,
                'status' => 1,
                'id_of_hidden_for' => $id
            ));

        return $this->returnResultSet($select);
    }

    public function getStatusOfHidden($loggedInUser, $postId, $hiddenType)
    {
        $select = $this->select()
            ->columns(array($this->primaryKey, 'post_id' => 'content_id'))
            ->where(array(
                'content_id' => $postId,
                'content_type' => $hiddenType,
                'hidden_by' => $loggedInUser,
                'status' => 1
            ));

        return $this->returnResultSet($select, true);
    }

    public function getDetails($on, $content_id, $status)
    {
        $select = $this->select()
            ->where(array("{$this->table}.content_id = ?" => $content_id, 'content_type' => $on, 'status' => $status))
            ->limit(1);

        return $this->returnResultSet($select, true);
    }
}