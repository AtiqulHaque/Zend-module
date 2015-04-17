<?php

/**
 * Comment Dao Model
 *
 * @category        Dao Model
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Model\Dao;

use NBlog\Model\Dao\Writing;
use NBlog\Model\ReportStatus;
use NBlog\Model\WritingType;
use Zend\Db\Sql\Predicate\NotIn;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate\IsNotNull;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\NotLike;
use Zend\Db\Sql\Predicate\PredicateSet;
use NBlog\Model\WritingStatus;

class Comment extends Writing
{
    protected $table = 'comments';
    protected $primaryKey = 'comment_id';
    protected $writingType = WritingType::COMMENT;

    public function getAll()
    {
        $select = $this->select()->columns(array('*', 'total_comment_favorited' => 'total_favorited'));
        return $this->returnResultSet($select);
    }

    public function getRecentCommentsOnPost($options = array())
    {
        $postTable = 'posts';
        $commentTable = 'comments';
        $this->table = $postTable;
        $select = $this->select()->columns(array('post_permalink' => 'permalink', 'post_title' => 'title', 'post_created_by' => 'created_by'));
        $sql = new Select($commentTable);
        $sql = $sql
            ->columns(array('*', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited'))
            ->where(array('comment_for' => WritingType::POST, 'is_published' => 1, 'is_reported' => 0))
            ->order(array($this->primaryKey . ' DESC'));
        !isset($options['is_reported']) || $sql->where(array("is_reported" => (int)$options['is_reported']));

        $select->join(array($commentTable => $sql), "{$commentTable}.id_of_comment_for={$postTable}.post_id");
        $select->where(array(
            "{$postTable}.status" => WritingStatus::PUBLISHED,
            "{$postTable}.is_reported" => ReportStatus::NO_REPORT,
            new PredicateSet(array(
                new NotLike("{$postTable}.published", '0000-00-00 00:00:00'),
                new IsNotNull("{$postTable}.published"),
            ), PredicateSet::COMBINED_BY_OR)));
        $select->group(array($postTable . '.post_id', $commentTable . '.user_id'));
        $select->order(array("{$commentTable}.{$this->primaryKey} DESC"));
        empty($options['userId']) || $select->where(array("{$postTable}.created_by" => $options['userId']));
        empty($options['offset']) || $select->offset($options['offset']);
        empty($options['limit']) || $select->limit((int)$options['limit']);
        $this->table = $commentTable;
        return $this->returnResultSet($select);
    }

    public function countCommentsByPostIds($options = array())
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT(DISTINCT(comments.user_id))")))
            ->join('posts', "{$this->table}.id_of_comment_for=posts.post_id", array())
            ->where(array(
                "{$this->table}.is_published" => 1,
                'comment_for' => WritingType::POST,
                "posts.status" => WritingStatus::PUBLISHED,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                new PredicateSet(array(
                    new NotLike("posts.published", '0000-00-00 00:00:00'),
                    new IsNotNull('posts.published'),
                ), PredicateSet::COMBINED_BY_OR)));

        empty($options['postIds']) || $select->where(array("{$this->table}.id_of_comment_for" => $options['postIds']));
        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function getRecentCommentsOnPostOfUserId($options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited'))
            ->join('posts', "{$this->table}.id_of_comment_for = posts.post_id", array('permalink', 'post_title' => 'title'))
            ->where(array(
                "{$this->table}.is_published" => 1,
                'comment_for' => WritingType::POST,
                "posts.created_by" => $options['userId'],
                'posts.status' => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("posts..published", '0000-00-00 00:00:00'),
                    new IsNotNull('posts.published'),
                ), PredicateSet::COMBINED_BY_OR)))
            ->order(array("{$this->table}.{$this->primaryKey} DESC"))
            ->offset($options['offset'])
            ->limit($options['limit']);

        return $this->returnResultSet($select);
    }

    public function countRecentCommentsOnPostOfUserId($options = array())
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT({$this->primaryKey})")))
            ->join('posts', "{$this->table}.id_of_comment_for=posts.post_id", array())
            ->where(array(
                "{$this->table}.is_published" => 1,
                'comment_for' => WritingType::POST,
                "posts.created_by" => $options['userId'],
                'posts.status' => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("posts.published", '0000-00-00 00:00:00'),
                    new IsNotNull('posts.published'),
                ), PredicateSet::COMBINED_BY_OR)))
            ->limit(1);

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function getUserCommentsOnPosts($options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited'))
            ->join('posts', "{$this->table}.id_of_comment_for = posts.post_id", array('permalink', 'post_title' => 'title'))
            ->where(array(
                "{$this->table}.comment_for" => WritingType::POST,
                "{$this->table}.user_id" => $options['userId'],
                "{$this->table}.type" => \Blog\Model\Comment::TYPE_NEW,
                "{$this->table}.is_published" => 1))
            ->order(array("{$this->table}.{$this->primaryKey} DESC"))
            ->offset($options['offset'])
            ->limit($options['limit']);

        return $this->returnResultSet($select);
    }

    public function getOtherCommentersByWritingIds($userId, $writingId)
    {
        $select = $this->select()
            ->columns(array('user_id', 'writing_id' => 'id_of_comment_for'))
            ->where(array($this->table . '.id_of_comment_for' => $writingId));
        empty($userId) || $select->where(array($this->table . '.user_id != ?' => $userId));
        return $this->returnResultSet($select);
    }

    public function getAllCommenters($writingId, $commentFor)
    {
        $select = $this->select()->columns(array('user_id' => new Expression("DISTINCT(`user_id`)")))
            ->where(array('id_of_comment_for' => $writingId, 'comment_for' => $commentFor));

        return $this->returnResultSet($select);
    }

    public function countUserCommentsOnPosts($options = array())
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT({$this->primaryKey})")))
            ->join('posts', "{$this->table}.id_of_comment_for=posts.post_id", array())
            ->where(array(
                "{$this->table}.comment_for" => WritingType::POST,
                "{$this->table}.user_id" => $options['userId'],
                "{$this->table}.type" => \Blog\Model\Comment::TYPE_NEW,
                "{$this->table}.is_published" => 1))
            ->limit(1);

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function getRepliesOfUserCommentsOnPost($options = array())
    {
        $select = $this->select()
            ->columns(array($this->primaryKey, 'reply' => 'details', 'reply_created' => 'created', 'reply_created_by' => 'user_id'))
            ->join(array('parent' => $this->table), "parent.{$this->primaryKey} = {$this->table}.parent_id",
                array('comment' => 'details'))
            ->join('posts', "parent.id_of_comment_for = posts.post_id", array('permalink', 'post_title' => 'title'));

        $select = $this->setConditionsForRepliesOfUserCommentsWithFor($select, WritingType::POST, $options)
            ->order(array("{$this->table}.{$this->primaryKey} DESC"))
            ->offset($options['offset'])
            ->limit($options['limit']);

        return $this->returnResultSet($select);
    }

    public function countRepliesOfUserCommentsOnPost($options = array())
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT({$this->table}.{$this->primaryKey})")))
            ->join(array('parent' => $this->table), "parent.{$this->primaryKey} = {$this->table}.parent_id", array());

        $select = $this->setConditionsForRepliesOfUserCommentsWithFor($select, WritingType::POST, $options);
        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    private function setConditionsForRepliesOfUserCommentsWithFor(Select $select, $for, $options = array())
    {
        $select->where(array(
            "parent.comment_for" => $for,
            "parent.user_id" => $options['userId'],
            "{$this->table}.type" => \Blog\Model\Comment::TYPE_REPLY,
            "{$this->table}.is_published" => 1,
            "parent.is_published" => 1));

        return $select;
    }

    public function getMyRepliesOfComments($options = array())
    {
        $select = $this->select()
            ->columns(array($this->primaryKey, 'reply' => 'details', 'reply_created' => 'created', 'reply_created_by' => 'user_id'))
            ->join(array('parent' => $this->table), "parent.{$this->primaryKey} = {$this->table}.parent_id",
                array('comment' => 'details', 'commenter' => 'user_id', 'comment_created' => 'created'))
            ->join('posts', "parent.id_of_comment_for = posts.post_id", array('permalink', 'post_title' => 'title'));

        $select = $this->setConditionsForMyRepliesOfCommentsWithFor($select, WritingType::POST, $options)
            ->order(array("{$this->table}.{$this->primaryKey} DESC"))
            ->offset($options['offset'])
            ->limit($options['limit']);

        return $this->returnResultSet($select);
    }

    public function countMyRepliesOfComments($options = array())
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT({$this->table}.{$this->primaryKey})")))
            ->join(array('parent' => $this->table), "parent.{$this->primaryKey} = {$this->table}.parent_id", array())
            ->limit(1);

        $select = $this->setConditionsForMyRepliesOfCommentsWithFor($select, WritingType::POST, $options);
        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    private function setConditionsForMyRepliesOfCommentsWithFor(Select $select, $for, $options = array())
    {
        $select->where(array(
            "parent.comment_for" => $for,
            "{$this->table}.user_id" => $options['userId'],
            "{$this->table}.type" => \Blog\Model\Comment::TYPE_REPLY,
            "{$this->table}.is_published" => 1,
            "parent.is_published" => 1));

        return $select;
    }

    public function getTopCommentPosters($limit = 10)
    {
        $select = $this->select()
            ->columns(array($this->primaryKey, 'no' => new Expression("COUNT(user_id)"), 'comment_created' => 'created', 'comment_created_by' => 'user_id'))
            ->join('posts', "posts.post_id = {$this->table}.id_of_comment_for", array())
            ->where(array(
                "{$this->table}.comment_for" => WritingType::POST,
                "{$this->table}.is_published" => 1,
                "posts.status" => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("posts.published", '0000-00-00 00:00:00'),
                    new IsNotNull('posts.published'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.created >= ?" => date(DATE_W3C, strtotime('-7 days'))))
            ->group(array("{$this->table}.user_id"))
            ->order(array("no DESC"))
            ->limit($limit);

        return $this->returnResultSet($select);
    }

    public function getDetailByUserId($loggedInUser, $commentId)
    {
        $select = $this->select()
            ->where(array($this->primaryKey => $commentId, 'user_id' => $loggedInUser))
            ->limit(1);

        return $this->returnResultSet($select, true);
    }

    public function getTraceabilityOfWriting($commentId)
    {
        $select = $this->select()
            ->columns(array('*', 'comments' => 'details', 'comment_created_by' => 'user_id'))
            ->where(array(
                "{$this->table}.{$this->primaryKey}" => $commentId,
                "{$this->table}.is_published" => 1))
            ->limit(1);

        $select->join('posts', new Expression("posts.post_id = {$this->table}.id_of_comment_for AND comment_for=?", WritingType::POST), array('*', 'post_permalink' => 'permalink'), Select::JOIN_LEFT);
        $select->join('discussions', new Expression("discussions.discussion_id = {$this->table}.id_of_comment_for AND comment_for=?", WritingType::DISCUSSION), array('*', 'discussion_permalink' => 'permalink'), Select::JOIN_LEFT);
        $select->join('moods', new Expression("moods.mood_id = {$this->table}.id_of_comment_for AND comment_for=?", WritingType::MOOD), array('*', 'mood_permalink' => 'permalink'), Select::JOIN_LEFT);

        return $this->returnResultSet($select, true);
    }

    public function getAllCommentsByUserId($userId)
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT(comment_id)")))
            ->where(array(
                "{$this->table}.user_id" => $userId,
                "{$this->table}.is_published" => 1));

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function getByMoodIds(array $moodIds, array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'comments' => 'details', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited'))
            ->join('moods', "moods.mood_id = {$this->table}.id_of_comment_for", array('creatorCommented' => 'user_id'))
            ->where(array(
                "moods.status" => WritingStatus::PUBLISHED,
                new In("{$this->table}.id_of_comment_for", $moodIds),
                "{$this->table}.comment_for" => WritingType::MOOD,
                "{$this->table}.is_published" => 1))
            ->order(array("{$this->primaryKey} DESC"));

        $select = $this->setUserActivityStatus($select, $options);
        return $this->returnResultByRawQuery($this->getSqlStringForSqlObject($select));
    }

    public function getByPostIds($postIds, array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'comments' => 'details', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited'))
            ->join('posts', "posts.post_id = {$this->table}.id_of_comment_for", array('creatorCommented' => 'created_by'))
            ->where(array(
                "posts.status" => WritingStatus::PUBLISHED,
                new In("{$this->table}.id_of_comment_for", $postIds),
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                "{$this->table}.comment_for" => WritingType::POST,
                "{$this->table}.is_published" => 1))
            ->order("{$this->table}.{$this->primaryKey} ASC");

        $select = $this->setUserActivityStatus($select, $options);
        return $this->returnResultByRawQuery($this->getSqlStringForSqlObject($select));
    }

    public function getAllCommentsForMyPosts($userId, array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'comments' => 'details', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited'))
            ->join('posts', "posts.post_id = {$this->table}.id_of_comment_for", array('*'))
            ->where(array(
                "posts.status" => WritingStatus::PUBLISHED,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                "{$this->table}.comment_for" => WritingType::POST,
                "{$this->table}.user_id" => $userId,
                "{$this->table}.is_published" => 1));
           empty($options['timeDuration']) ||  $select->where(array("{$this->table}.created >= ?" => date(DATE_W3C, strtotime('-'.$options['timeDuration'].'days'))));

           $select->order("{$this->table}.{$this->primaryKey} DESC")
            ->offset(empty($options['offset']) ? 0 : (int)$options['offset'])
            ->limit(empty($options['limit']) ? 15 : (int)$options['limit']);

        return $this->returnResultSet($select);
    }

    public function getAllCommentsForMyPostsThread($userId, array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'comments' => 'details', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited', 'otherCommentCount' => new Expression("COUNT({$this->table}.id_of_comment_for)")))
            ->join('posts', "posts.post_id = {$this->table}.id_of_comment_for", array('*'))
            ->where(array(
                "posts.status" => WritingStatus::PUBLISHED,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                "{$this->table}.comment_for" => WritingType::POST,
                "{$this->table}.user_id" => $userId,
                "{$this->table}.is_published" => 1));
        empty($options['timeDuration']) ||  $select->where(array("{$this->table}.created >= ?" => date(DATE_W3C, strtotime('-'.$options['timeDuration'].'days'))));
        $select->group("{$this->table}.id_of_comment_for");
        $select->order("{$this->table}.created DESC")
            ->offset(empty($options['offset']) ? 0 : (int)$options['offset'])
            ->limit(empty($options['limit']) ? 15 : (int)$options['limit']);

        return $this->returnResultSet($select);
    }

    public function getAllOthersComments($userId , $commentInfo)
    {
        $select = $this->select()
            ->columns(array('*', 'comments' => 'details', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited'))
            ->where(array(new In("{$this->table}.id_of_comment_for", $commentInfo['postIds']),
                "{$this->table}.is_published" => 1,
                "{$this->table}.comment_for" => WritingType::POST,
                "{$this->table}.user_id" => $userId,
                new NotIn($this->table.'.'.$this->primaryKey, (array)$commentInfo['latestCommentIds'])
            ));

        return $this->returnResultSet($select);
    }

    public function getByDiscussionIds(array $discussionIds, array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'comments' => 'details', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited'))
            ->join('discussions', "discussions.discussion_id = {$this->table}.id_of_comment_for", array('creatorCommented' => 'user_id'))
            ->where(array(
                "discussions.status" => WritingStatus::PUBLISHED,
                new In("{$this->table}.id_of_comment_for", $discussionIds),
                "{$this->table}.comment_for" => WritingType::DISCUSSION,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                "{$this->table}.is_published" => 1))
            ->order(array("{$this->primaryKey} DESC"));

        $select = $this->setUserActivityStatus($select, $options);
        return $this->returnResultByRawQuery($this->getSqlStringForSqlObject($select));
    }

    public function getByNoticeId($noticeId, array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'comments' => 'details', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited'))
            ->where(array(
                "{$this->table}.id_of_comment_for" => $noticeId,
                "{$this->table}.comment_for" => WritingType::NOTICE,
                "{$this->table}.is_published" => 1))
            ->order(array("{$this->primaryKey} DESC"));

        $select = $this->setUserActivityStatus($select, $options);
        return $this->returnResultByRawQuery($this->getSqlStringForSqlObject($select));
    }

    public function getCommentCountByDiscussionId($discussionId)
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT(comment_id)")))
            ->join('discussions', "discussions.discussion_id = {$this->table}.id_of_comment_for", array('creatorCommented' => 'user_id'))
            ->where(array(
                "discussions.status" => WritingStatus::PUBLISHED,
                "{$this->table}.id_of_comment_for" => $discussionId,
                "{$this->table}.comment_for" => WritingType::DISCUSSION,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                "{$this->table}.is_published" => 1))
            ->order(array("{$this->primaryKey} DESC"));

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function getPersonCountByDiscussionId($discussionId)
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT(DISTINCT({$this->table}.user_id))")))
            ->join('discussions', "discussions.discussion_id = {$this->table}.id_of_comment_for", array('creatorCommented' => 'user_id'))
            ->where(array(
                "discussions.status" => WritingStatus::PUBLISHED,
                "{$this->table}.id_of_comment_for" => $discussionId,
                "{$this->table}.comment_for" => WritingType::DISCUSSION,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                "{$this->table}.is_published" => 1))
            ->order(array("{$this->primaryKey} DESC"));

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function countCommentOnPostOfUsers(array $userIds)
    {
        $select = $this->select()
            ->columns(array('comment_count' => new Expression("COUNT({$this->primaryKey})"), 'comment_created' => 'created', 'comment_created_by' => 'user_id'))
            ->join('posts', "{$this->table}.id_of_comment_for=posts.post_id", array())
            ->where(array(
                new In($this->table . '.user_id', $userIds),
                "{$this->table}.is_published" => 1,
                'comment_for' => WritingType::POST,
                "posts.status" => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("posts.published", '0000-00-00 00:00:00'),
                    new IsNotNull('posts.published'),
                ), PredicateSet::COMBINED_BY_OR)))
            ->group("{$this->table}.user_id");

        $commentsCounts = $this->returnResultSet($select);

        $result = array();
        foreach ($commentsCounts AS $commentsCount) {
            $result[$commentsCount['comment_created_by']] = $commentsCount['comment_count'];
        }

        return $result;
    }

    public function countCommentOnDiscussionOfUsers(array $userIds)
    {
        $select = $this->select()
            ->columns(array('comment_count' => new Expression("COUNT({$this->primaryKey})"), 'comment_created' => 'created', 'comment_created_by' => 'user_id'))
            ->join('discussions', "{$this->table}.id_of_comment_for=discussions.discussion_id", array())
            ->where(array(
                new In($this->table . '.user_id', $userIds),
                "{$this->table}.is_published" => 1,
                'comment_for' => WritingType::DISCUSSION,
                "discussions.status" => WritingStatus::PUBLISHED))
            ->group("{$this->table}.user_id");

        $commentsCounts = $this->returnResultSet($select);

        $result = array();
        foreach ($commentsCounts AS $commentsCount) {
            $result[$commentsCount['comment_created_by']] = $commentsCount['comment_count'];
        }

        return $result;
    }

    public function countUserRepliesOfComments($userId)
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT({$this->table}.{$this->primaryKey})")))
            ->join(array('parent' => $this->table), "parent.{$this->primaryKey} = {$this->table}.parent_id", array())
            ->where(array("parent.user_id" => $userId, "{$this->table}.is_published" => 1))
            ->limit(1);

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function countRepliesById($commentId)
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT({$this->table}.{$this->primaryKey})")))
            ->where(array('parent_id' => $commentId, 'type' => \Blog\Model\Comment::TYPE_REPLY))
            ->limit(1);

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function countByIdWithFor($idOfCommentFor, $for)
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT({$this->table}.{$this->primaryKey})")))
            ->where(array(
                'id_of_comment_for' => $idOfCommentFor,
                'comment_for' => $for))
            ->limit(1);

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function removeByIdWithFor($idOfCommentFor, $for)
    {
        $remove = $this->delete()
            ->where(array('id_of_comment_for' => $idOfCommentFor, 'comment_for' => $for));

        $result = $this->getResultAfterAlteration($remove);
        return isset($result);
    }

    public function removeRepliesById($commentId)
    {
        $remove = $this->delete()
            ->where(array('parent_id' => $commentId, "{$this->table}.type" => \Blog\Model\Comment::TYPE_REPLY));

        $result = $this->getResultAfterAlteration($remove);
        return isset($result);
    }

    public function getByWallPostId($postId, array $options)
    {
        $select = $this->select()
            ->columns(array('*', 'comments' => 'details', 'comment_created' => 'created', 'comment_created_by' => 'user_id', 'total_comment_favorited' => 'total_favorited'))
            ->join('posts', "posts.post_id = {$this->table}.id_of_comment_for", array('creatorCommented' => 'created_by'))
            ->where(array(new In('posts.status', $options['statuses']),
                "{$this->table}.id_of_comment_for" => $postId,
                "{$this->table}.comment_for" => WritingType::POST,
                "{$this->table}.is_published" => 1))
            ->order(array("{$this->primaryKey} DESC"));

        $select = $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function updateByCommentId(array $data, $commentId)
    {
        $update = $this->update()
            ->set($data)
            ->where(array(
                    "{$this->primaryKey}" => $commentId,
                    $this->table . '.user_id' => $data['user_id'])
            );
        $result = $this->getResultAfterAlteration($update);
        return isset($result);
    }

    protected function setUserActivityStatus(Select $select, array $options = array())
    {
        unset($options['withCommentBlocking']);
        return parent::setUserActivityStatus($select, $options);
    }
}