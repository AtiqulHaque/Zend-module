<?php
/**
 * Blog Dao Model
 *
 * @category        Dao Model
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Model\Dao;

use NBlog\Model\Category AS CategoryModel;
use NBlog\Model\Dao\Writing;
use NBlog\Model\ReportStatus;
use NBlog\Model\VoteConfig;
use NBlog\Model\WritingType;
use NBlog\Utility\PostLimit AS PostLimitModel;
use Zend\Db\Sql\Predicate\IsNotNull;
use Zend\Db\Sql\Predicate\Literal;
use Zend\Db\Sql\Predicate\NotIn;
use Zend\Db\Sql\Predicate\NotLike;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\Like;
use NBlog\Model\WritingStatus;
use NBlog\Model\PostType;

class Blog extends Writing
{
    protected $table = 'posts';
    protected $primaryKey = 'post_id';
    protected $writingType = WritingType::POST;

    public function getTopBloggers($limit = 10)
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT(created_by)"), 'post_created_by' => 'created_by', $this->primaryKey))
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.publicly_published_time >= ?" => date(DATE_W3C, strtotime('-15 days'))))
            ->group(array("{$this->table}.created_by"))
            ->order(array("no DESC"))
            ->limit($limit);

        return $this->returnResultSet($select);
    }

    public function getRecentUser(array $options = array())
    {
        $select = $this->select()
            ->columns(array('post_created_by' => 'created_by'))
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                new PredicateSet(array(
                    new NotLike("{$this->table}.publicly_published_time", '0000-00-00 00:00:00'),
                    new IsNotNull('publicly_published_time'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.publicly_published_time <= ?" => date(DATE_W3C, time())))
            ->group(array("{$this->table}.{$this->primaryKey}"))
            ->order(array("{$this->table}.publicly_published_time DESC"))
            ->offset($options['offset'])
            ->limit($options['limit']);

        empty($options['userId']) || $select->where(array("{$this->table}.created_by" => $options['userId']));
        return $this->returnResultSet($select);
    }

    public function getUsersOfQueuedPosts()
    {
        $select = $this->select()
            ->columns(array('post_created_by' => 'created_by', 'postId' => $this->primaryKey))
            ->where(array(
                "{$this->table}.status" => WritingStatus::QUEUE_POST,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR)))
            ->order("{$this->table}.published ASC")
            ->group("{$this->table}.created_by")
            ->limit(PostLimitModel::RECENT_POSTS);

        return $this->returnResultSet($select);
    }

    public function getRecentPosts(array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_created' => 'created', 'post_status' => 'status'))
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                new PredicateSet(array(
                    new NotLike("{$this->table}.publicly_published_time", '0000-00-00 00:00:00'),
                    new IsNotNull('publicly_published_time'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.publicly_published_time <= ?" => date(DATE_W3C, time())))
            ->group(array("{$this->table}.{$this->primaryKey}"))
            ->order(array("{$this->table}.publicly_published_time DESC"))
            ->offset($options['offset'])
            ->limit($options['limit']);

        empty($options['userId']) || $select->where(array("{$this->table}.created_by" => $options['userId']));
        $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function countRecentPosts(array $options = array())
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->primaryKey})")))
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                new PredicateSet(array(
                    new NotLike("{$this->table}.publicly_published_time", '0000-00-00 00:00:00'),
                    new IsNotNull('publicly_published_time'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.publicly_published_time <= ?" => date(DATE_W3C, time())))
            ->limit(1);

        empty($options['userId']) || $select->where(array("{$this->table}.created_by" => $options['userId']));
        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function getMostCommentedBlogPosts(array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_created' => 'created', 'post_status' => 'status'))
            ->join('comments', "{$this->table}.{$this->primaryKey} = comments.id_of_comment_for", array(), 'left')
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR),
                "comments.is_published" => 1,
                "{$this->table}.publicly_published_time >= ?" => date(DATE_W3C, strtotime('-15 days'))))
            ->group("{$this->table}.{$this->primaryKey}")
            ->order(array("{$this->table}.total_comments DESC"))
            ->limit(empty($options['limit']) ? 10 : (int)$options['limit']);

        $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function latestCategoryPosts(array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_created' => 'created', 'post_status' => 'status'))
            ->join('categories_posts', "categories_posts.{$this->primaryKey} = {$this->table}.{$this->primaryKey}", array())
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.publicly_published_time >= ?" => date(DATE_W3C, strtotime('-30 days')),
                "categories_posts.category_id" => $options['categoryId']))
            ->group("{$this->table}.{$this->primaryKey}")
            ->order(array("{$this->table}.total_comments DESC"))
            ->limit(empty($options['limit']) ? 10 : (int)$options['limit']);
        empty($options['blogPostIds']) || $select->where(array(new NotIn("{$this->table}.{$this->primaryKey}", $options['blogPostIds'])));
        $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function getMostFavoritedBlogPosts(array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'))
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                "{$this->table}.published != ?" => '0000-00-00 00:00:00',
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                "{$this->table}.type" => PostType::BLOG,
                "{$this->table}.total_viewed != ?" => 0,
                "{$this->table}.created >= ( CURDATE() - INTERVAL ? DAY )" => 25))
            ->order(array("{$this->table}.total_favorited DESC"))
            ->group("{$this->table}.{$this->primaryKey}")
            ->limit(empty($options['limit']) ? 10 : (int)$options['limit']);

        $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function getMostViewedBlogPosts(array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'))
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                "{$this->table}.type" => PostType::BLOG,
                "{$this->table}.total_viewed != ?" => 0,
                "{$this->table}.publicly_published_time >= ?" => date(DATE_W3C, strtotime('-15 days'))))
            ->order(array("{$this->table}.total_viewed DESC"))
            ->group("{$this->table}.{$this->primaryKey}")
            ->limit(empty($options['limit']) ? 10 : (int)$options['limit']);

        $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function getLatestModeratedPostsByCategory($id)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status'))
            ->join('categories_posts', "categories_posts.{$this->primaryKey} = {$this->table}.{$this->primaryKey}")
            ->join('categories', "categories.category_id = categories_posts.category_id", array('category' => 'name'))
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.type" => PostType::BLOG,
                "categories_posts.category_id" => $id))
            ->group("{$this->table}.{$this->primaryKey}")
            ->order(array("{$this->table}.moderated DESC"));

        return $this->returnResultSet($select);
    }

    public function getMostViewedPostsByCategory($id)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status'))
            ->join('categories_posts', "categories_posts.{$this->primaryKey} = {$this->table}.{$this->primaryKey}")
            ->join('categories', "categories.category_id = categories_posts.category_id", array('category' => 'name'))
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.type" => PostType::BLOG,
                "categories_posts.category_id" => $id))
            ->order(array("{$this->table}.total_viewed DESC"));

        return $this->returnResultSet($select);
    }

    public function getUsersPosts(array $options)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'));

        $select = $this->setBlogConditions($select, $options);
        $select->group("{$this->table}.{$this->primaryKey}");
        isset($options['sequenceType']) || $options['sequenceType'] = '';
        switch($options['sequenceType']){
            case PostType::SEQUENCE :
                $select->order(array("{$this->table}.{$this->primaryKey} DESC"));
                break;
            case PostType::MOST_VIEWED :
                $select->order('total_viewed DESC');
                break;
            case PostType::MOST_COMMENTED :
                $select->order('total_comments DESC');
                break;
            case PostType::MOST_FAVORITED :
                $select->order('total_favorited DESC');
                break;
            case PostType::LAST_UPDATED :
                $select->order('published DESC');
                break;
            default :
                $select->order(array("{$this->table}.{$this->primaryKey} DESC"));
        }

        $select->offset(empty($options['offset']) ? 0 : (int)$options['offset'])
            ->limit(empty($options['limit']) ? 15 : (int)$options['limit']);
        $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function getUserSelectedPosts(array $options)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'));

        $select = $this->setBlogConditions($select, $options)
            ->group("{$this->table}.{$this->primaryKey}");


        if (empty($options['exceptPostIds'])) {
            $select->order(array("{$this->table}.{$options['active']} DESC"));
        } else {
            $select->where(array(
                new NotIn("{$this->table}.{$this->primaryKey}", (array)$options['exceptPostIds']),
                "{$this->table}.publicly_published_time >= ?" => date(DATE_W3C, strtotime('-30 days'))
            ))->order(new Expression("RAND()"));
        }

        $select->offset(empty($options['offset']) ? 0 : (int)$options['offset'])
            ->limit($options['limit']);
        $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function getTopBlogPosts(array $options)
    {
        $select = $this->select()->columns(array(
            '*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created',
            'topPost' => new Expression("`total_viewed` * 0.01 + `total_favorited` * 0.1 + `total_comments` * 0.1")));

        $select = $this->setBlogConditions($select, $options);
        $select->where(array("{$this->table}.publicly_published_time >= ?" => date(DATE_W3C, strtotime('-1 days'))));
        $select->order(array('topPost DESC'));
        $select->offset(empty($options['offset']) ? 0 : (int)$options['offset'])
            ->limit(empty($options['limit']) ? 15 : (int)$options['limit']);

        $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function getRelatedPosts(array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'))
            ->join('categories_posts', "{$this->table}.{$this->primaryKey} = categories_posts.{$this->primaryKey}", array());

        $select = $this->setBlogConditions($select, $options);
        $sql = new Select('categories');
        $sql->columns(array('category_id'))->where(array('category_id' => $options['categoryId'], 'parent_id' => $options['categoryId']), PredicateSet::OP_OR);
        $select->where(array(new In('category_id', $sql)));
        $select->where(array(new NotIn("{$this->table}.{$this->primaryKey}", $options['postIds'])));
        $select->group("{$this->table}.{$this->primaryKey}");
        $select->order(new Expression("RAND()"));
        $select->limit(empty($options['limit']) ? 15 : (int)$options['limit']);
        return $this->returnResultSet($select);
    }

    public function getByPermalink($permalink, array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created', 'post_modified' => 'modified'))
            ->where(array(
                new PredicateSet(array(
                    new Like("{$this->table}.permalink", $permalink),
                    new Like("{$this->table}.alt_permalink", $permalink),
                ), PredicateSet::COMBINED_BY_OR)))
            ->group("{$this->table}.{$this->primaryKey}");
//            ->limit(1);

        !isset($options['status']) || $select->where(array("{$this->table}.status" => $options['status']));
        !isset($options['is_reported']) || $select->where(array("{$this->table}.is_reported" => (int)$options['is_reported']));
        $select = $this->setUserActivityStatus($select, $options);

        /* This is a special case, because of PDO problem. */
        return $this->returnResultByRawQuery($this->getSqlStringForSqlObject($select) . ' LIMIT 1', true);
    }

    public function getSinglePostByPermalink($permalink, array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created', 'post_modified' => 'modified', 'wall_content_published' => 'published', 'wall_content_created' => 'created', 'wall_content_modified' => 'modified','content_id'=> 'post_id'))
            ->where(array(
                new PredicateSet(array(
                    new Like("{$this->table}.permalink", $permalink),
                    new Like("{$this->table}.alt_permalink", $permalink),
                ), PredicateSet::COMBINED_BY_OR)))
            ->group("{$this->table}.{$this->primaryKey}");
//            ->limit(1);

        !isset($options['status']) || $select->where(array("{$this->table}.status" => $options['status']));
        !isset($options['is_reported']) || $select->where(array("{$this->table}.is_reported" => (int)$options['is_reported']));
        $select = $this->setUserActivityStatus($select, $options);

        /* This is a special case, because of PDO problem. */
        return $this->returnResultByRawQuery($this->getSqlStringForSqlObject($select) . ' LIMIT 1',true);
    }

    public function getByIds(array $postIds)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created', 'post_modified' => 'modified'))
            ->where(array(
                new In($this->primaryKey, $postIds),
                new In("type", array(PostType::BLOG, PostType::EPISODE))
            ));

        return $this->returnResultSet($select);
    }

    public function getPostsByUserIdAndPostId($user_id,$post_id)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created', 'post_modified' => 'modified'))
            ->where(array(
                "{$this->table}.created_by" => $user_id,
                "{$this->table}.post_id" => $post_id))
            ->group("{$this->table}.{$this->primaryKey}")
            ->limit(1);

        !isset($status) || $select->where(array("{$this->table}.status" => $status));
        !isset($options['is_reported']) || $select->where(array("{$this->table}.is_reported" => (int)$options['is_reported']));
        return $this->returnResultSet($select, true);
    }

    public function getByEpisodeId($episodeId, $status = null)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created', 'post_modified' => 'modified'))
            ->where(array("{$this->table}.episode_id" => $episodeId))
            ->limit(1);

        !isset($status) || $select->where(array("{$this->table}.status" => $status));
        return $this->returnResultSet($select, true);
    }

    public function countByEpisodeId($episodeId, $status = null)
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("COUNT({$this->primaryKey})")))
            ->where(array("{$this->table}.episode_id" => $episodeId))
            ->limit(1);

        !isset($status) || $select->where(array("{$this->table}.status" => $status));
        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function getRandomly(array $options)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'))
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.is_forbidden" => 0,
                "{$this->table}.publicly_published_time < ?" => date(DATE_W3C, strtotime('-30 days'))))
            ->group("{$this->table}.created_by")
            ->order(array(new Expression("RAND()")))
            ->limit($options['limit']);

        $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function getCategoricalOldPosts($categoryId, $limit = 1, $latestPostIds)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status'))
            ->join('categories_posts', "{$this->table}.{$this->primaryKey}=categories_posts.{$this->primaryKey}", array())
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR),
                "categories_posts.category_id" => $categoryId))
            ->group("{$this->table}.{$this->primaryKey}")
            ->order(array("{$this->table}.total_comments DESC"))
            ->limit($limit);
        empty($latestPostIds) || $select->where(array(new NotIn("{$this->table}.{$this->primaryKey}", $latestPostIds)));
        return $this->returnResultSet($select);
    }

    public function getByEpisode($episodeId)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'))
            ->where(array("{$this->table}.episode_id" => $episodeId));

        return $this->returnResultSet($select);
    }

    public function getOtherEpisodicPosts(array $post)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'))
            ->where(array(
                "{$this->table}.episode_id" => $post['episode_id'],
                "{$this->table}.created_by" => $post['post_created_by'],
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                "{$this->table}.{$this->primaryKey} != ?" => $post[$this->primaryKey],
            ));

        return $this->returnResultSet($select);
    }

    public function getMaxEpisodicSerial($episodeId)
    {
        $select = $this->select()
            ->columns(array('max_serial' => new Expression('max(episodic_serial)')))
            ->where(array("{$this->table}.episode_id" => $episodeId));

        return $this->returnResultSet($select, true);
    }

    public function countPostOfUsers(array $userIds)
    {
        $select = $this->select()
            ->columns(array('user_id' => 'created_by', 'post_count' => new Expression("COUNT({$this->primaryKey})")))
            ->where(array(
                new In($this->table . '.created_by', $userIds),
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR)))
            ->group("{$this->table}.created_by");

        $postCounts = $this->returnResultSet($select);
        $result = array();
        foreach ($postCounts AS $postCount) {
            $result[$postCount['user_id']] = $postCount['post_count'];
        }

        return $result;
    }

    public function searchBlog(array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_created' => 'created', 'post_status' => 'status'));

        $select = $this->setBlogConditions($select, $options)
            ->order(array("{$this->table}.{$this->primaryKey} DESC"))
            ->offset($options['offset'])
            ->limit($options['limit']);

        return $this->returnResultSet($select);
    }

    public function countSearchedBlog(array $options = array())
    {
        return $this->countPosts($options);
    }

    public function getCategoricalPosts(array $options)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_created' => 'created', 'post_status' => 'status'))
            ->join('categories_posts', "{$this->table}.{$this->primaryKey} = categories_posts.{$this->primaryKey}", array())
            ->join('categories', "categories.category_id = categories_posts.category_id", array())
            ->where(array(
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR)));

        if (empty($options['getDeletedCategoricalPosts'])) {
            $select->where(array("categories_posts.category_id" => $options['categoryId']));
        } else {
            $select->where(array('categories_posts.category_id' => $options['categoryId'], 'categories.is_deleted' => CategoryModel::PERMANENTLY_DELETED), PredicateSet::OP_OR);
        }
        $select->group("{$this->table}.{$this->primaryKey}")
            ->order("{$this->table}.published DESC")
            ->offset($options['offset'])
            ->limit($options['limit']);
        $this->setUserActivityStatus($select, $options);
        return $this->returnResultSet($select);
    }

    public function countCategoricalPosts(array $options = array())
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->table}.{$this->primaryKey})")))
            ->join('categories_posts', "{$this->table}.{$this->primaryKey} = categories_posts.{$this->primaryKey}", array())
            ->where(array(
                "{$this->table}.type" => PostType::BLOG,
                "{$this->table}.is_reported" => ReportStatus::NO_REPORT,
                "{$this->table}.status" => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR),
                "categories_posts.category_id" => $options['categoryId']))
            ->limit(1);

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function countPosts(array $options = array())
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->primaryKey})")));
        $select = $this->setBlogConditions($select, $options)->limit(1);
        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    public function countStatusWisePosts(array $options = array())
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->primaryKey})"), 'status'));
        $select = $this->setBlogConditions($select, $options);
        $select->group("{$this->table}.status");
        $countPosts = $this->returnResultSet($select);
        $result = array();
        foreach ($countPosts AS $count) {
            $result[$count['status']] = $count['no'];
        }

        return $result;
    }

    public function updateEpisodeTitle(array $data, $episodeId)
    {
        $update = $this->update()
            ->set($data)
            ->where(array('episode_id' => $episodeId));
        $result = $this->getResultAfterAlteration($update);
        return isset($result);
    }

    /**
     * This function set the conditions of search, summary of and count posts in the select query.
     *
     * @param Select $select
     * @param array $options
     *
     * @return Select
     */
    protected function setBlogConditions(Select $select, $options = array())
    {
        //empty($options['type']) || $select->where(array("{$this->table}.type" => $options['type']));
        empty($options['user_id']) || $select->where(array("{$this->table}.created_by" => $options['user_id']));
        empty($options['post_id']) || $select->where(array("{$this->table}.{$this->primaryKey} != ?" => $options['post_id']));
        empty($options['criteria']) || $select->where(array(new Like("{$this->table}.details", '%' . $options['criteria'] . '%')));
        empty($options['timeDuration']) ||  $select->where(array("{$this->table}.published >= ?" => date(DATE_W3C, strtotime('-'.$options['timeDuration'].'days'))));

        $select->where(array("{$this->table}.status != ?" => WritingStatus::ADMIN_TRASH));
        $select->where(array(new In("type", array(PostType::BLOG, PostType::EPISODE))));
        if (isset($options['status'])) {
            is_array($options['status']) || $options['status'] = (array)$options['status'];
            $select->where(array(new In($this->table . '.status', $options['status'])));
//            if (in_array(WritingStatus::PUBLISHED, $options['status'])) {
//                $select->where(array(new PredicateSet(array(
//                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
//                    new IsNotNull('published'),
//                ), PredicateSet::COMBINED_BY_OR)));
//            }
        }

        !isset($options['is_reported']) || $select->where(array("{$this->table}.is_reported" => (int)$options['is_reported']));
        !isset($options['isSelected']) || $select->where(array("{$this->table}.is_selected" => (int)$options['isSelected']));
        !isset($options['isSticky']) || $select->where(array("{$this->table}.is_sticky" => (int)$options['isSticky']));
        if (isset($options['active_slidable'])) {
            $select->where(array("{$this->table}.super_stickiness >= ?" => 1, "{$this->table}.super_stickiness <= ?" => 9));
        }
        return $select;
    }

    public function getMultipleUserPosts(array $arrOptions = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'))
            ->where(array("{$this->table}.type" => empty($arrOptions['type']) ? PostType::BLOG : $arrOptions['type']))
            ->where(array(new In($this->table . '.created_by', $arrOptions['user_id'])))
            ->where(array(new In($this->table . '.status', $arrOptions['status'])))
            ->group("{$this->table}.{$this->primaryKey}")
            ->order(array("{$this->table}.{$this->primaryKey} DESC"))
            ->offset(empty($arrOptions['offset']) ? 0 : (int)$arrOptions['offset'])
            ->limit(empty($arrOptions['limit']) ? 15 : (int)$arrOptions['limit']);

        return $this->returnResultSet($select);
    }

    public function countCommentsOnPosts($userId)
    {
        $select = $this->select()
            ->columns(array('comment_count' => new Expression("SUM(`total_comments`)")))
            ->where(array(
                'created_by' => $userId,
                "status" => WritingStatus::PUBLISHED,
                new PredicateSet(array(
                    new NotLike("{$this->table}.published", '0000-00-00 00:00:00'),
                    new IsNotNull('published'),
                ), PredicateSet::COMBINED_BY_OR)))
            ->limit(1);

        $commentCount = $this->returnResultSet($select, true);
        return empty($commentCount) ? 0 : $commentCount['comment_count'];
    }

    public function getFollowersPosts($iUserId, array $arrOptions = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'))
            ->join('subscribers', "{$this->table}.created_by = subscribers.subscribed_id", array('*'));

        $select = $this->_setConditionsForSubscriber($select, $iUserId, $arrOptions)
            ->group("{$this->table}.{$this->primaryKey}")
            ->order(array("{$this->table}.{$this->primaryKey} DESC"))
            ->offset((int)$arrOptions['offset'])
            ->limit((int)$arrOptions['limit']);
        return $this->returnResultSet($select);
    }

    public function getUserWallPost(array $arrOptions)
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'post_status' => 'status', 'post_created' => 'created'))
            ->where(array("{$this->table}.type" => empty($arrOptions['type']) ? PostType::BLOG : $arrOptions['type']))
            ->where(array(new In($this->table . '.created_by', $arrOptions['user_id'])))
            ->where(array(new In($this->table . '.status', $arrOptions['status'])))
            ->order(array("{$this->table}.created DESC"))
            ->offset(empty($arrOptions['offset']) ? 0 : (int)$arrOptions['offset'])
            ->limit(empty($arrOptions['limit']) ? 15 : (int)$arrOptions['limit']);
        return $this->returnResultSet($select);
    }

    public function getSelectedPostsForCompetition(array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'status-post' => 'status', 'post_created' => 'created', 'selected_post_id' => $this->primaryKey))
            ->join('post_for_voting', "{$this->table}.{$this->primaryKey}=post_for_voting.{$this->primaryKey}");

        $this->setVotePostConditions($select, $options)->order(array("{$this->table}.publicly_published_time DESC"));
        return $this->returnResultSet($select);
    }

    public function getSelectedPostsForIndependentCompetitionResults(array $options = array())
    {
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'status-post' => 'status', 'post_created' => 'created', 'selected_post_id' => $this->primaryKey))
            ->join('post_for_voting', "{$this->table}.{$this->primaryKey}=post_for_voting.{$this->primaryKey}", array('*', 'selectedCategory' => 'category_id'));

        $this->setVotePostConditions($select, $options)
            ->order(array("post_for_voting.count DESC", "{$this->table}.publicly_published_time ASC"));

        return $this->returnResultSet($select);
    }

    public function getCompetitionResult(array $options = array())
    {
        $categoryField = empty($options['top_five']) ? 'category_id' : new Expression('?', array(VoteConfig::TOP_CRITICS_FLAG));
        $select = $this->select()
            ->columns(array('*', 'post_created_by' => 'created_by', 'status-post' => 'status', 'post_created' => 'created', 'selected_post_id' => $this->primaryKey))
            ->join('post_for_voting', "{$this->table}.{$this->primaryKey}=post_for_voting.{$this->primaryKey}", array('*', 'selectedCategory' => $categoryField));

        unset($options['episode']);
        $select = $this->setVotePostConditions($select, $options)
            ->order(array("post_for_voting.count DESC", "{$this->table}.publicly_published_time ASC"));

        return $this->returnResultSet($select);
    }

    public function checkPermalinkExists($permalink)
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->table}.{$this->primaryKey})")))
            ->where(array(new Literal("{$this->table}.`permalink` REGEXP '^{$permalink}(-[[:digit:]]+)?$'")))->limit(1);

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }

    private function setVotePostConditions(Select $select, array $options = array())
    {
        $select = $this->setBlogConditions($select, $options)
            ->where(array(
                new PredicateSet(array(
                    new NotLike("{$this->table}.publicly_published_time", '0000-00-00 00:00:00'),
                    new IsNotNull('publicly_published_time'),
                ), PredicateSet::COMBINED_BY_OR),
                "{$this->table}.publicly_published_time <= ?" => date(DATE_W3C, time())
            ));

        empty($options['category']) || $select->where(array('post_for_voting.category_id' => $options['category']));
        empty($options['postIds']) || $select->where(array(new In('post_for_voting.'.$this->primaryKey, (array)$options['postIds'])));
        empty($options['episode']) || $select->where(array(
            "{$this->table}.publicly_published_time >= ?" => $options['episodeInfo']['start'],
            "{$this->table}.publicly_published_time <= ?" => $options['episodeInfo']['end'],
        ));

        return $select;
    }
}