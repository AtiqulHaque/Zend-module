<?php
/**
 * Blog Model
 *
 * @category        Model
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Model;

use NBlog\Model\VoteConfig;
use NBlog\Model\Writing;
use NBlog\Model\WritingStatus;
use NBlog\Model\PostType;
use NBlog\Utility\PostLimit;
use NBlog\Model\ReportStatus;

class Blog extends Writing
{
    /**
     * @var     \Blog\Model\Dao\Blog
     */
    protected $dao = null;
    protected $episodeModel;
    protected $postForVotingModel;
    protected $writingStatusModel;
    protected $categoryPostModel;
    protected $postCategoryHelperModel;

    public function save(array $data)
    {
        if ($data['type'] == PostType::EPISODE) {
            if (empty($data['episode_id'])) {
                $episodeId = $this->getEpisodeModel()->save(array_merge($data, array('episodic_style_id' => 0, 'next_episodic_serial_id' => 0)));
            } else {
                $data['episodic_serial'] = $this->getMaxEpisodicSerial($data['episode_id']) + 1;
                $episodeId = $data['episode_id'];
            }
        }

        $data['permalink'] = $this->getPermalink($data['title']);
        if (strlen($data['permalink']) > 250) {
            $data['permalink'] = substr($data['permalink'], 0, strrpos($data['permalink'], '-'));
        }

        $count = $this->dao->checkPermalinkExists(str_replace('*', '\\\\*', $data['permalink']));
        empty($count) || $data['permalink'] .= '-' . ($count + 1);

        $data['alt_permalink'] = $data['permalink'];
        $data['created_by'] = $data['user_id'];
        $data['created'] = date(DATE_W3C);
        $data['modified'] = date(DATE_W3C);
        $data['episode_id'] = (empty($episodeId) ? null : $episodeId);

        if ($data['status'] == WritingStatus::PUBLISHED) {
            if (empty($data['isInHomePage'])) {
                $data['publicly_published_time'] = date(DATE_W3C);
            } else {
                $data['status'] = WritingStatus::QUEUE_POST;
                $data['publicly_published_time'] = null;
            }
            $data['published'] = date(DATE_W3C);
        }
        $data['is_draft'] = ($data['status'] === WritingStatus::DRAFT) ? 1 : 0;

        $postId = $this->dao->save($data);
        if (empty($postId)) {
            return false;
        }
        $data['post_id'] = $postId;
        $postCategoryModel = $this->getCategoryPostModel();
        foreach ($data['category_id'] AS $category) {
            $postCategoryModel->save(array(
                'category_id' => $category,
                'post_id' => $postId
            ));
        }

        return $data;
    }

    public function getMaxEpisodicSerial($episodeId)
    {
        if (empty($episodeId)) {
            return false;
        }
        $result = $this->dao->getMaxEpisodicSerial($episodeId);
        return $result['max_serial'];
    }

    public function modify(array $data, $blogId)
    {
        if (PostType::EPISODE == $data['type']) {
            $this->dealWithPreviousEpisode($data);
            if (empty($data['episode_id'])) {
                $data['episode_id'] = $this->getEpisodeModel()->save(array_merge($data, array('episodic_style_id' => 0, 'next_episodic_serial_id' => 0)));
            } else {
                $data['episodic_serial'] = $this->getMaxEpisodicSerial($data['episode_id']) + 1;
            }
        } elseif (PostType::BLOG == $data['type']) {
            $data['episode_id'] = $data['episode_tag'] = null;
            $data['episodic_serial'] = 0;
            $this->dealWithPreviousEpisode($data);
        }

        if ($data['status'] == WritingStatus::PUBLISHED) {
            if ($data['old_status'] != WritingStatus::PUBLISHED) {
                if (empty($data['isInHomePage'])) {
                    $data['publicly_published_time'] = date(DATE_W3C);
                } else {
                    $data['status'] = WritingStatus::QUEUE_POST;
                    $data['publicly_published_time'] = null;
                }
                $data['published'] = date(DATE_W3C);
            }
        } else {
            $data['published'] = null;
            $data['publicly_published_time'] = null;
        }

        $data['is_draft'] = ($data['status'] === WritingStatus::DRAFT) ? 1 : 0;
        $data['modified'] = date(DATE_W3C);

        $result = $this->dao->modify($data, $blogId);
        if (empty($result)) {
            return false;
        }
        $this->updateEpisodeTitle($data['title'], $data['episode_id']);
        $postCategoryModel = $this->getCategoryPostModel();
        $postCategoryModel->remove($blogId);
        foreach ($data['category_id'] AS $category) {
            $postCategoryModel->save(array(
                'category_id' => $category,
                'post_id' => $blogId
            ));
        }

        return $blogId;
    }

    public function updateEpisodeTitle($title, $episodeId)
    {
        if (empty($title) || empty($episodeId)) {
            return false;
        }
        return $this->dao->updateEpisodeTitle(array('title' => $title, 'modified' => date(DATE_W3C)), $episodeId);
    }

    public function delete($postId)
    {
        if (empty($postId)) {
            return false;
        }

        $this->getCategoryPostModel()->remove($postId);
        $this->getCommentModel()->removeByPostId($postId);
        return $this->dao->remove($postId);
    }

    public function getByPermalink($permalink, array $options = array())
    {
        if (empty($permalink)) {
            return false;
        }

        $result = $this->dao->getByPermalink($permalink, $options);
        if (empty($result)) {
            return $result;
        }
        return $this->getUsersDetail(current($this->getPostsCategories(array($result))), true);
    }

    public function getSinglePostByPermalink($permalink, array $options = array())
    {
        if (empty($permalink)) {
            return false;
        }

        $result = $this->dao->getSinglePostByPermalink($permalink, $options);
        if (empty($result)) {
            return $result;
        }
        return $this->getUsersDetail(current($this->getPostsCategories(array($result))), true);
    }

    public function getByIds(array $postIds, $withCategory = false)
    {
        if (empty($postIds)) {
            return false;
        }

        $result = $this->dao->getByIds($postIds);
        if (!empty($result)) {
            $posts = array();
            foreach($result AS $post) {
                $posts[$post['post_id']] = $post;
            }
            $result = empty($withCategory) ? $posts : $this->getPostsCategories($posts);
        }
        return $result;
    }

    private function getPostsCategories(array $posts, $index = 'post_id')
    {
        if (empty($posts)) {
            return $posts;
        }

        return $this->getPostCategoryHelperModel()->getPostsCategories($posts, $index);
    }

    public function getByEpisodeId($episodeId, $status = null)
    {
        if (empty($episodeId)) {
            return false;
        }

        $result = $this->dao->getByEpisodeId($episodeId, $status);
        if (empty($result)) {
            return $result;
        }
        return $this->getUsersDetail(current($this->getPostsCategories(array($result))), true);
    }

    public function getRandomly($limit = 5, $loggedInUser = null)
    {
        $options = $this->setCountOffset(array(
            'loggedInUser' => $loggedInUser,
            'withHidingStatus' => true,
            'limit' => $limit
        ));
        $result = $this->dao->getRandomly($options);
        if (empty($result)) {
            return array();
        }

        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function getCategoricalOldPosts($categoryId, $limit = 1, $latestPostIds)
    {
        $result = $this->dao->getCategoricalOldPosts($categoryId, $limit, $latestPostIds);
        if (empty($result)) {
            return array();
        }

        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function getByEpisode($episodeId)
    {
        if (empty($episodeId)) {
            return false;
        }

        return $this->getPostsCategories($this->dao->getByEpisode($episodeId));
    }

    public function getOtherEpisodicPosts($post)
    {
        if (empty($post['episode_id'])) {
            return array();
        }

        return $this->dao->getOtherEpisodicPosts($post);
    }

    public function getAllPosts($userId, $options = array())
    {
        if (empty($userId)) {
            return array();
        }

        if (empty($options['isUserAnonymous'])) {
            $statuses = array(
                WritingStatus::PENDING,
                WritingStatus::BOUNCE,
                WritingStatus::PUBLISHED,
                WritingStatus::USER_WALL,
                WritingStatus::FRIENDS_FOLLOWERS,
                WritingStatus::ONLY_FRIENDS,
                WritingStatus::QUEUE_POST,
            );
        } else {
            $statuses = array(
                WritingStatus::PUBLISHED,
                WritingStatus::QUEUE_POST,
            );
        }
        return $this->getPosts(array_merge(array(
            'user_id' => $userId,
            'status' => $statuses
        ), $options));
    }

    public function getPostsByUserIdAndPostId($userId, $post_id)
    {
        if (empty($userId) || empty($post_id)) {
            return array();
        }
        $result = $this->dao->getPostsByUserIdAndPostId($userId,$post_id);
        if(empty($result)){
            return array();
        }
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    private function getPosts(array $options)
    {
        isset($options['active_slidable']) || $options = $this->setCountOffset($options);
        $result = $this->dao->getUsersPosts($options);
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function countAllPosts($userId, $options = array())
    {
        if (empty($userId)) {
            return 0;
        }

        return $this->countPosts(array_merge(array('user_id' => $userId), $options));
    }

    private function countPosts(array $options)
    {
        return $this->dao->countPosts($options);
    }

    public function getPublishedPosts($userId, $options = array())
    {
        return $this->getPosts(array_merge(array(
            'user_id' => $userId,
            'is_reported' => ReportStatus::NO_REPORT,
            'status' => WritingStatus::PUBLISHED
        ), $options));
    }

    public function countPublishedPosts($userId, $options = array())
    {
        return $this->countPosts(array_merge(array(
            'user_id' => $userId,
            'is_reported' => ReportStatus::NO_REPORT,
            'status' => WritingStatus::PUBLISHED
        ), $options));
    }

    public function getOtherPosts($userId, $postId, $options = array())
    {
        return $this->getPosts(array_merge(array(
            'user_id' => $userId,
            'post_id' => $postId,
            'status' => WritingStatus::PUBLISHED
        ), $options));
    }

    public function getPostIds(array $posts)
    {
        $postIds = array();
        foreach ($posts AS $post) {
            $postIds[] = $post['post_id'];
        }
        return $postIds;
    }

    public function getRelatedPosts($categoryId, array $blogPostIds)
    {
        $result = $this->dao->getRelatedPosts(array(
            'categoryId' => $categoryId,
            'status' => WritingStatus::PUBLISHED,
            'type' => PostType::BLOG,
            'postIds' => $blogPostIds,
            'limit' => 10
        ));

        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function getDraftPosts($userId, $options = array())
    {
        if (empty($userId)) {
            return array();
        }

        return $this->getPosts(array_merge(array(
            'user_id' => $userId,
            'status' => WritingStatus::DRAFT
        ), $options));
    }

    public function getTrashedPosts($userId, $options = array())
    {
        if (empty($userId)) {
            return array();
        }

        return $this->getPosts(array_merge(array(
            'user_id' => $userId,
            'status' => WritingStatus::TRASH
        ), $options));
    }

    public function getPendingPosts($userId, $options = array())
    {
        if (empty($userId)) {
            return array();
        }

        return $this->getPosts(array_merge(array(
            'user_id' => $userId,
            'status' => WritingStatus::PENDING
        ), $options));
    }

    public function getBouncedPosts($userId, $options = array())
    {
        if (empty($userId)) {
            return array();
        }

        return $this->getPosts(array_merge(array(
            'user_id' => $userId,
            'status' => WritingStatus::BOUNCE
        ), $options));
    }

    public function countBouncedPosts($userId, $options = array())
    {
        if (empty($userId)) {
            return 0;
        }

        return $this->countPosts(array_merge(array(
            'user_id' => $userId,
            'status' => WritingStatus::BOUNCE
        ), $options));
    }

    public function getQueuedPosts($userId, $options = array())
    {
        if (empty($userId)) {
            return array();
        }

        return $this->getPosts(array_merge(array(
            'user_id' => $userId,
            'status' => WritingStatus::QUEUE_POST
        ), $options));
    }

    public function countStatusWisePosts($userId)
    {
        if (empty($userId)) {
            return array();
        }

        $counters = $this->dao->countStatusWisePosts(array('user_id' => $userId));
        $statuses = $this->getWritingStatusModel()->getAll();
        foreach ($statuses AS $key => $status) {
            $counters[$key] = empty($counters[$key]) ? 0 : (int)$counters[$key];
        }

        return $counters;
    }

    public function searchBlog($options)
    {
        if (empty($options)) {
            return false;
        }

        $options = array_merge($this->setCountOffset((array)$options), array('status' => WritingStatus::PUBLISHED));
        $result = $this->dao->searchBlog($options);
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function countSearchedBlog($options)
    {
        if (empty($options)) {
            return 0;
        }

        $options = array_merge((array)$options, array('status' => WritingStatus::PUBLISHED));
        return $this->dao->countSearchedBlog($options);
    }

    public function getSelectedBlogPosts(array $options = array())
    {
        $options = array_merge(array(
            'isSelected' => true,
            'status' => WritingStatus::PUBLISHED,
            'is_reported' => ReportStatus::NO_REPORT,
            'active' => 'active_selected', // used for record ordering
            'withHidingStatus' => true,
            'limit' => PostLimit::SELECTED_POSTS
        ), $options);
        $options = $this->setCountOffset($options);
        $result = $this->dao->getUserSelectedPosts($options);
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function getOldSelectedBlogPosts(array $options = array())
    {
        empty($options['posts']) ? $options['posts'] = array() : null;
        $result = $this->dao->getUserSelectedPosts(array(
            'isSelected' => true,
            'status' => WritingStatus::PUBLISHED,
            'is_reported' => ReportStatus::NO_REPORT,
            'active' => 'active_selected', // used for record ordering
            'loggedInUser' => $options['loggedInUser'],
            'withHidingStatus' => true,
            'limit' => PostLimit::SELECTED_POSTS,
            'exceptPostIds' => $this->getPostIds($options['posts'])
        ));
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function countSelectedBlogPosts($options = array())
    {
        $options = array_merge(array(
            'isSelected' => true,
            'is_reported' => ReportStatus::NO_REPORT,
            'status' => WritingStatus::PUBLISHED
        ), $options);

        return $this->dao->countPosts($options);
    }

    public function getTopBlogPosts($limit = 10, $loggedInUser = null)
    {
        $options = $this->setCountOffset(array(
            'type' => PostType::BLOG,
            'status' => WritingStatus::PUBLISHED,
            'is_reported' => ReportStatus::NO_REPORT,
            'loggedInUser' => $loggedInUser,
            'withHidingStatus' => true,
            'limit' => $limit
        ));
        $result = $this->dao->getTopBlogPosts($options);
        if (empty($result)) {
            return array();
        }
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function sortByTopPost($item1, $item2)
    {
        if ($item1['topPost'] == $item2['topPost']) return 0;
        return ($item1['topPost'] < $item2['topPost']) ? 1 : -1;
    }

    public function getRecentPosts($options = array())
    {
        $options = array_merge((array)$options, array('rowPerPage' => PostLimit::RECENT_POSTS));
        $options = $this->setCountOffset($options);
        $result = $this->dao->getRecentPosts($options);
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function countRecentPosts($options = array())
    {
        return $this->dao->countRecentPosts($options);
    }

    public function getLatestPostUserIdsOfHomePage($options = array())
    {
        $queuePostLimit = PostLimit::RECENT_POSTS - 1;
        $options = array_merge((array)$options, array('rowPerPage' => $queuePostLimit));
        $options = $this->setCountOffset($options);
        $recentBloggers = $this->dao->getRecentUser($options);

        $userIds = array();
        foreach ($recentBloggers AS $userIdsOfRecentPosts) {
            $userIds[] = $userIdsOfRecentPosts['post_created_by'];
        }
        return $userIds;
    }

    public function checkUserPostExistsInHomePage($userId)
    {
        $usersInHomePage = $this->getLatestPostUserIdsOfHomePage();
        return in_array($userId, $usersInHomePage);
    }

    public function getTopBloggers($limit = 10)
    {
        return $this->getUsersDetail($this->dao->getTopBloggers($limit), true);
    }

    public function getUsersOfQueuedPosts()
    {
        $result = $this->dao->getUsersOfQueuedPosts();
        if (empty($result)) {
            return array();
        }

        $users = array();
        foreach ($result AS $userPost) {
            $users[$userPost['post_created_by']] = $userPost['postId'];
        }
        return $users;
    }

    public function dequePostIfExists()
    {
        $queuedPostsUsers = $this->getUsersOfQueuedPosts();

        if (!empty($queuedPostsUsers)) {
            $recentBloggers = $this->getLatestPostUserIdsOfHomePage();
            $additionalSeconds = 0;
            foreach ($queuedPostsUsers AS $user => $post) {
                if (!in_array($user, $recentBloggers)) {
                    $this->dao->modify(array(
                        'status' => WritingStatus::PUBLISHED,
                        'publicly_published_time' => date(DATE_W3C, time() + $additionalSeconds)
                    ), $post);
                    $additionalSeconds += PostLimit::PENDING_TIME;
                }
            }
        }
    }

    public function getMostCommentedBlogPosts($userId = null, $limit = 5)
    {
        $result = $this->dao->getMostCommentedBlogPosts(array(
            'loggedInUser' => $userId, 'withHidingStatus' => true, 'limit' => $limit
        ));
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function latestCategoryPosts($categoryId, $userId = null, $limit = 5, $blogPostsIds)
    {
        if (empty($categoryId)) {
            return false;
        }
        $result = $this->dao->latestCategoryPosts(array(
            'categoryId' => $categoryId,
            'loggedInUser' => $userId,
            'limit' => $limit,
            'blogPostIds' => $blogPostsIds,
            'withHidingStatus' => true,
        ));
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function getMostViewedBlogPosts($userId = null, $limit = 15)
    {
        $result = $this->dao->getMostViewedBlogPosts(array(
            'loggedInUser' => $userId, 'withHidingStatus' => true, 'limit' => $limit
        ));
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function getMostFavoritedBlogPosts($userId = null, $limit = 15)
    {
        $result = $this->dao->getMostFavoritedBlogPosts(array(
            'loggedInUser' => $userId, 'withHidingStatus' => true, 'limit' => $limit
        ));
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function getStickyPosts($limit = 1, $loggedInUser = null)
    {
        $options = array_merge(array(
            'isSticky' => true,
            'is_reported' => ReportStatus::NO_REPORT,
            'status' => WritingStatus::PUBLISHED,
            'active' => 'active_sticky', // used for record ordering
            'loggedInUser' => $loggedInUser,
            'withHidingStatus' => true,
            'limit' => $limit
        ));

        return $this->getPosts($options);
    }

    public function getActiveSlidablePosts($limit = 9, $loggedInUser = null)
    {
        $options = array_merge(array(
            'active_slidable' => true,
            'is_reported' => ReportStatus::NO_REPORT,
            'status' => WritingStatus::PUBLISHED,
            'loggedInUser' => $loggedInUser,
            'withHidingStatus' => true,
            'limit' => $limit
        ));

        $posts = $this->getPosts($options);
        usort($posts, array($this, 'sortBySuperStickyPost'));
        return $posts;
    }

    public function sortBySuperStickyPost($item1, $item2)
    {
        if ($item1['super_stickiness'] == $item2['super_stickiness']) {
            return 0;
        }
        return ($item1['super_stickiness'] < $item2['super_stickiness']) ? -1 : 1;

    }

    public function countStickyPosts($options = array())
    {
        return $this->countPosts(array_merge(array(
            'isSticky' => true,
            'is_reported' => ReportStatus::NO_REPORT,
            'status' => WritingStatus::PUBLISHED
        ), $options));
    }

    public function getCategoricalPosts($options = array())
    {
        $options = $this->setCountOffset($options);
        $result = $this->dao->getCategoricalPosts($options);
        return $this->getUsersDetail($this->getPostsCategories($result), true);
    }

    public function countCategoricalPosts($options = array())
    {
        return $this->dao->countCategoricalPosts($options);
    }

    public function getLatestModeratedPostsByCategory($id)
    {
        if (empty($id)) {
            return false;
        }

        $result = $this->dao->getLatestModeratedPostsByCategory($id);
        return $this->getUsersDetail($this->getPostsCategories($result));
    }

    public function getMostViewedPostsByCategory($id)
    {
        if (empty($id)) {
            return false;
        }

        $result = $this->dao->getMostViewedPostsByCategory($id);
        return $this->getUsersDetail($this->getPostsCategories($result));
    }

    public function countPostOfUsers(array $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        return $this->dao->countPostOfUsers($userIds);
    }

    public function getMultipleUserPost(array $arrAllUser = array(), array $arrOptions = array(), $strIndex = 'friend_user_id')
    {
        if (empty($arrAllUser)) {
            return array();
        }

        $arrUsers = array();
        foreach ($arrAllUser as $arrEachRow) {
            $arrUsers[] = $arrEachRow[$strIndex];
        }
        $arrOptions['user_id'] = $arrUsers;
        $arrOptions = $this->setCountOffset($arrOptions);
        $arrOptions = array_merge($arrOptions, array('type' => PostType::BLOG));
        $arrOptions = array_merge(array('status' => $this->getWritingStatusModel()->getForUserWall()), $arrOptions);
        $arrResult = $this->dao->getMultipleUserPosts($arrOptions);
        return $this->getUsersDetail($this->getPostsCategories($arrResult), true);
    }

    public function countCommentsOnPosts($userId)
    {
        if (empty($userId)) {
            return 0;
        }

        return $this->dao->countCommentsOnPosts($userId);
    }

    public function getFollowersPosts($iUserId)
    {
        if (empty($iUserId)) {
            return array();
        }
        $arrOptions = array();
        $arrOptions = $this->setCountOffset($arrOptions);
        $arrOptions = array_merge(array('subscribed_for' => 2, 'type' => PostType::BLOG), $arrOptions);
        $arrOptions = array_merge(array('status' => $this->getWritingStatusModel()->getForUserWall()), $arrOptions);
        $arrResult = $this->dao->getFollowersPosts($iUserId, $arrOptions);
        return $this->getUsersDetail($this->getPostsCategories($arrResult), true);
    }

    public function makePostPublished($postId, $isUserPostInHomePage)
    {
        if (empty($postId)) {
            return false;
        }

        if (empty($isUserPostInHomePage)) {
            $data['status'] = WritingStatus::PUBLISHED;
            $data['publicly_published_time'] = date(DATE_W3C);
        } else {
            $data['status'] = WritingStatus::QUEUE_POST;
            $data['publicly_published_time'] = null;
        }
        $data['published'] = date(DATE_W3C);

        return $this->dao->modify($data, $postId);
    }

    public function getSelectedPostsForCompetition(array $options)
    {
        if (empty($options['contest'])) {
            return array();
        } elseif (empty($options['episode'])) {
            $options['episode'] = VoteConfig::EPISODE_1;
        }

        $options = array_merge($options, $this->getConditionOfPostsForCompetition());
        $options['episodeInfo'] = VoteConfig::getCompetitionTimingConfig($options['contest'], $options['episode']);
        $result = $this->dao->getSelectedPostsForCompetition($options);
        return $this->getUsersDetail($this->getPostCategoryHelperModel()->getPostsCategories($result), true);
    }

    public function getSelectedPostsForIndependentCompetitionResults(array $options)
    {
        if (empty($options['episodeInfo'])) {
            return array();
        }

        $options = array_merge($options, $this->getConditionOfPostsForCompetition());
        $result = $this->dao->getSelectedPostsForIndependentCompetitionResults($options);
        return $this->getUsersDetail($this->getPostCategoryHelperModel()->getPostsCategories($result), true);
    }

    public function getCompetitionResult($competition)
    {
        $episodesPassed = VoteConfig::getPassedEpisodes($competition);
        if (empty($episodesPassed)) {
            return array();
        }

        switch($competition) {
            case VoteConfig::BOOK_FAIR_2014:
            case VoteConfig::BOOK_FAIR_2015:
                $mostVotedPosts = $this->getPostForVotingModel()->getMostVotedPosts($competition, $episodesPassed, VoteConfig::RESULTED_PERSON);
                break;

            case VoteConfig::VoteForIndependent:
                $mostVotedPosts = array(
                    3901, 4124, 4532, 3820, 4545,
                    4020, 4049, 3956, 3944, 4536,
                    4411, 4513, 4517, 4518, 4546
                );
                break;

            default:
                return array();
        }

        $options = array_merge(array('postIds' => $mostVotedPosts), $this->getConditionOfPostsForCompetition());
        $result = $this->dao->getCompetitionResult($options);
        if (VoteConfig::VoteForIndependent == $competition) {
            $options['postIds'] = array(3831, 4535, 4038, 4537, 4095);
            $options['top_five'] = true;
            $result = array_merge_recursive($result, $this->dao->getCompetitionResult($options));
        }
        $result = $this->getUsersDetail($result, true);

        if (!empty($result)) {
            $array = array();
            $result = $this->getPostsCategories($result);
            foreach($result AS $row) {
                $array[$row['episode']][$row['selectedCategory']][] = $row;
            }

            ksort($array);
            $result = $array;
        }
        return $result;
    }

    public function getIndependentCompetitionResult()
    {
        if (VoteConfig::checkResultToBePublished(VoteConfig::VoteForIndependent)) {
            $episodesPassed = array(VoteConfig::EPISODE_1);
        }

        if (empty($episodesPassed)) {
            return array();
        }

        $mostVotedPosts = $this->getPostForVotingModel()->getMostVotedIndependentPosts($episodesPassed, VoteConfig::RESULTED_PERSON_FOR_INDEPENDENCE);
        $result = $this->getUsersDetail($this->getPostCategoryHelperModel()->getPostsCategories($mostVotedPosts), true);

        if (!empty($result)) {
            $array = array();
            foreach($result AS $row) {
                $array[$row['voted_category']][$row['voting_count']][] = $row;
            }
            $result = $array;
        }

        return $result;
    }

    public function retrieveInfoForSocialMedia(array $post)
    {
        $viewHelperManager = $this->serviceManager->get('ViewHelperManager');
        $socialMediaInfo = array(
            'title' => $post['title'],
            'description' => $viewHelperManager->get('Text')->word_limiter(strip_tags($post['details']), 100),
            'author' => $post['nickname']
        );

        $images = $viewHelperManager->get('Image')->extractImages($post['details']);
        $images[] = $viewHelperManager->get('Profile')->getImage($post, 'image_source', 'profile');
        $socialMediaInfo['image'] = $images;

        return $socialMediaInfo;
    }

    private function getConditionOfPostsForCompetition()
    {
        return array(
            'is_reported' => 0,
            'status' => WritingStatus::PUBLISHED
        );
    }

    private function dealWithPreviousEpisode(array $data)
    {
        if ($data['episode_id'] == $data['old_episode_id'] && !empty($data['old_episode_id'])) {
            if ($data['old_title'] != $data['title']) {
                $this->getEpisodeModel()->modify(array_merge($data, array('episodic_style_id' => 0, 'next_episodic_serial_id' => 0)), $data['episode_id']);
            }
        } else if (!empty($data['old_episode_id'])) {
            $episodeModel = $this->getEpisodeModel();
            $count = $this->dao->countByEpisodeId($data['old_episode_id']);
            ($count > 1) || $episodeModel->remove($data['old_episode_id']);
        }
    }

    /**
     * @return  \BlogUser\Model\Episode
     */
    private function getEpisodeModel()
    {
        isset($this->episodeModel) || $this->episodeModel = $this->serviceManager->get('BlogUser\Model\Episode');
        return $this->episodeModel;
    }

    /**
     * @return \NBlog\Model\PostForVoting
     */
    private function getPostForVotingModel()
    {
        isset($this->postForVotingModel) || $this->postForVotingModel = $this->serviceManager->get('NBlog\Model\PostForVoting');
        return $this->postForVotingModel;
    }

    /**
     * @return  \NBlog\Model\CategoryPost
     */
    private function getCategoryPostModel()
    {
        isset($this->categoryPostModel) || $this->categoryPostModel = $this->serviceManager->get('NBlog\Model\CategoryPost');
        return $this->categoryPostModel;
    }

    /**
     * @return \NBlog\Model\Helper\PostCategory
     */
    private function getPostCategoryHelperModel()
    {
        isset($this->postCategoryHelperModel) || $this->postCategoryHelperModel = $this->serviceManager->get('NBlog\Model\Helper\PostCategory');
        return $this->postCategoryHelperModel;
    }

    /**
     * @return \NBlog\Model\WritingStatus
     */
    private function getWritingStatusModel()
    {
        isset($this->writingStatusModel) || $this->writingStatusModel = $this->serviceManager->get('NBlog\Model\WritingStatus');
        return $this->writingStatusModel;
    }
}
