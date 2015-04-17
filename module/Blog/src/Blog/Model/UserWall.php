<?php
namespace Blog\Model;

use NBlog\Model\ServiceLocatorBlogDB;
use NBlog\Model\WritingType;

/**
 * User Wall Model
 *
 * @category        Model
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Lab. http://www.nokkhotrolab.com
 */
class UserWall extends ServiceLocatorBlogDB
{
    /**
     * @var     \Blog\Model\Dao\UserWall
     */
    protected $dao = null;
    protected $commentModel;
    protected $friendModel;
    protected $subscribeModel;
    protected $userHelperModel;
    protected $postCategoryHelperModel;
    protected $discussionCategoryHelperModel;

    public function getAll($loggedInUser, $options = array())
    {
        if (empty($loggedInUser)) {
            return array();
        }

        if (empty($options['profileWall'])) {
            $friendsIds = $this->getFriendsAndSubscribers($loggedInUser);
        } else {
            $friendsIds = array($loggedInUser);
        }

        $options = array_merge(array('user_id' => $friendsIds, 'loggedInUser' => $loggedInUser), $options);
        $options = $this->setCountOffset($options);
        $allWallData = $this->dao->getAll($options);

        $allWallData = $this->getUserHelperModel()->getUsersDetail($allWallData, array(
            'withProfile' => true,
            'userKey' => 'created_by',
            'withPostsCommentsCount' => true,
            'withDiscussionsCommentsCount' => false,
            'withFriendsAndFollowersCount' => true,
        ));
        $posts = $discussions = $moods = $moodIds = $postIds =  $discussionIds = array();
        foreach ($allWallData AS $writing) {
            switch ($writing['writing_type']) {
                case WritingType::MOOD:
                    $moods[$writing['content_id']] = $writing;
                    $moodIds[] = $writing['content_id'];
                    break;

                case WritingType::DISCUSSION:
                    $discussions[$writing['content_id']] = $writing;
                    $discussionIds[] = $writing['content_id'];
                    break;

                case WritingType::POST:
                default:
                    $posts[$writing['content_id']] = $writing;
                    $postIds[] = $writing['content_id'];
            }
        }

        $options =  array(
            'loggedInUser' => $loggedInUser,
            'withUserReporting' => true,
            'withHidingStatus' => true,
            'withFavoriteStatus' => true
        );
        $commentModel = $this->getCommentModel();
        $moodComments = $commentModel->getByMoodIds($moodIds, $options);
        $postComments = $commentModel->getByBlogIds($postIds, $options);
        $discussionComments = $commentModel->getByDiscussionIds($discussionIds, $options);

        $posts = $this->getPostCategoryHelperModel()->getPostsCategories($posts, 'content_id');
        $discussions = $this->getDiscussionCategoryHelperModel()->getDiscussionsCategories($discussions, 'content_id');

        foreach ($allWallData AS $key => $writing) {
            switch ($writing['writing_type']) {
                case WritingType::MOOD:
                    $comments = empty($moodComments[$writing['content_id']]) ? array() : $moodComments[$writing['content_id']];
                    $allWallData[$key] = array_merge($allWallData[$key], $moods[$writing['content_id']], array('comments' => $comments));
                    break;

                case WritingType::DISCUSSION:
                    $comments = empty($discussionComments[$writing['content_id']]) ? array() : $discussionComments[$writing['content_id']];
                    $allWallData[$key] = array_merge($allWallData[$key], $discussions[$writing['content_id']], array('comments' => $comments));
                    break;

                case WritingType::POST:
                default:
                    $comments = empty($postComments[$writing['content_id']]) ? array() : $postComments[$writing['content_id']];
                    $allWallData[$key] = array_merge($allWallData[$key], $posts[$writing['content_id']], array('comments' => $comments));
            }
        }

        return $allWallData;
    }

    private function getFriendsAndSubscribers($userId)
    {
        $arrAllUserId = array($userId);
        $arrAllFriends = $this->getFriendModel()->getAllFriends($userId);
        if (!empty($arrAllFriends)) {
            foreach ($arrAllFriends AS $eachFriends) {
                $arrAllUserId[] = $eachFriends['friend_user_id'];
            }
        }

        $arrAllFollower = $this->getSubscribeModel()->getFavoriteWriters($userId);
        if (!empty($arrAllFollower)) {
            foreach ($arrAllFollower AS $eachFollower) {
                $arrAllUserId[] = $eachFollower['subscribed_id'];
            }
        }

        return $arrAllUserId;
    }

    /**
     * @return \Blog\Model\Comment
     */
    private function getCommentModel()
    {
        isset($this->commentModel) || $this->commentModel = $this->serviceManager->get('Blog\Model\Comment');
        return $this->commentModel;
    }

    /**
     * @return \BlogUser\Model\Friend
     */
    protected function getFriendModel()
    {
        isset($this->friendModel) || $this->friendModel = $this->serviceManager->get('BlogUser\Model\Friend');
        return $this->friendModel;
    }

    /**
     * @return  \BlogUser\Model\Subscribe
     */
    private function getSubscribeModel()
    {
        isset($this->subscribeModel) || $this->subscribeModel = $this->serviceManager->get('BlogUser\Model\Subscribe');
        return $this->subscribeModel;
    }

    /**
     * @return \NBlog\Model\Helper\User
     */
    private function getUserHelperModel()
    {
        isset($this->userHelperModel) || $this->userHelperModel = $this->serviceManager->get('NBlog\Model\Helper\User');
        return $this->userHelperModel;
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
     * @return \NBlog\Model\Helper\DiscussionCategory
     */
    private function getDiscussionCategoryHelperModel()
    {
        isset($this->discussionCategoryHelperModel) || $this->discussionCategoryHelperModel = $this->serviceManager->get('NBlog\Model\Helper\DiscussionCategory');
        return $this->discussionCategoryHelperModel;
    }
}
