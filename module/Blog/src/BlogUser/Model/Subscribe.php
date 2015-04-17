<?php
namespace BlogUser\Model;

use NBlog\Model\ServiceLocatorBlogDB;

/**
 * Subscribe Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Lab. http://www.nokkhotrolab.com
 */
class Subscribe extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\Subscribe
     */
    protected $dao = null;
    protected $blogModel;
    protected $commentModel;
    protected $discussionModel;
    protected $moodModel;
    protected $noticeModel;
    protected $notificationUserModel;
    protected $userModel;
    protected $userHelperModel;
    protected $postCategoryHelperModel;
    const CONTENT_NOT_FOUND = 'not-found';

    public function getFavoritePosts($userId, $options = array(), $withProfile = false)
    {
        if (empty($userId)) {
            return false;
        }

        isset($options['status']) || $options['status'] = '1';
        $options = $this->setCountOffset($options);
        $result = $this->dao->getPosts($userId, $options);
        return $this->getUserHelperModel()->getUsersDetail($this->getPostsCategories($result), array(
            'withProfile' => $withProfile,
            'userKey' => 'subscribed_user_id',
            'withPostsCommentsCount' => true,
            'withDiscussionsCommentsCount' => true
        ));
    }

    public function countPosts($userId, $status = '1')
    {
        if (empty($userId)) {
            return false;
        }

        return $this->dao->countPosts($userId, array('status' => $status));
    }

    public function getFavoriteWriters($userId, $withProfile = false, $status = '1', $limit = 10)
    {
        if (empty($userId)) {
            return false;
        }

        $options = $this->setCountOffset(array('status' => $status, 'limit' => $limit));
        return $this->getUserHelperModel()->getUsersDetail($this->dao->getWriters($userId, $options), array(
            'withProfile' => $withProfile,
            'userKey' => 'subscribed_user_id',
            'withPostsCommentsCount' => true,
            'withDiscussionsCommentsCount' => false
        ));
    }

    public function countFavoriteWriters($userId, $status = '1')
    {
        if (empty($userId)) {
            return false;
        }

        return $this->dao->countWriters($userId, array('status' => $status));
    }

    public function checkFavoriteOrNot($idFavoriteOf, $loggedInUserId, $favoriteFor)
    {
        if (empty($idFavoriteOf) || empty($loggedInUserId)) {
            return false;
        }

        return $this->dao->checkFavorite($idFavoriteOf, $loggedInUserId, $favoriteFor);
    }

    public function checkFavoriteWriterOfLoggedInUser($userId, $loggedInUserId)
    {
        if (empty($userId) || empty($loggedInUserId)) {
            return false;
        }

        return $this->dao->checkFavorite($userId, $loggedInUserId, FavoriteType::WRITER);
    }

    public  function checkMultipleFavorite(array $idsFavoriteOf, $loggedInUserId, $favoriteFor)
    {
        if (empty($idsFavoriteOf)) {
            return array();
        }
        $result = $this->dao->checkMultipleFavorite($idsFavoriteOf, $loggedInUserId, $favoriteFor);
        if (empty($result)) {
            return array();
        }
        $temp = array();
        foreach($result AS $row) {
            $temp[$row['subscribed_id']] = $row['is_active'];
        }
        return $temp;
    }

    public function getBeingSubscribers($userId, $withProfile = false, $status = '1', $limit = 10)
    {
        if (empty($userId)) {
            return false;
        }

        $options = $this->setCountOffset(array('status' => $status, 'limit' => $limit));
        return $this->getUserHelperModel()->getUsersDetail($this->dao->getBeingSubscribers($userId, $options), array(
            'withProfile' => $withProfile,
            'userKey' => 'user_id_who_subscribes',
            'withPostsCommentsCount' => true,
            'withDiscussionsCommentsCount' => false
        ));
    }

    public function countBeingSubscribers($userId)
    {
        if (empty($userId)) {
            return false;
        }

        return $this->dao->countBeingSubscribers($userId);
    }

    public function getAllSubscriberByUserId($userId)
    {
        if (empty($userId)) {
            return false;
        }

        return $this->dao->getAllSubscriberByUserId($userId);
    }

    /**
     * @param   array $data
     * @param   bool $doFavorite
     * @return  mixed
     */
    public function dealWithFavorite(array $data, $doFavorite = true)
    {
        if (empty($data)) {
            return false;
        }

        $data['subscribed_for'] = $data['for'];
        switch ($data['subscribed_for']) {
            case FavoriteType::POST:
                $blogModel = $this->getBlogModel();
                $blogDetail = $blogModel->getByPermalink($data['permalink']);
                if (empty($blogDetail)) {
                    return false;
                } else {
                    $data['subscribed_id'] = $blogDetail['post_id'];
                    if ($doFavorite) {
                        $data['subscription_id'] = $this->dao->makeFavorite($data);
                    } else {
                        $data['subscription_id'] = $this->dao->cancelFavorite($data);
                    }
                    if ($data['subscription_id']) {
                        $data['writer_id'] = $blogDetail['post_created_by'];
                        if ($doFavorite) {
                            $blogModel->incrementSubscriberCounting($data['subscribed_id']);
                        } else {
                            $blogModel->decrementSubscriberCounting($data['subscribed_id']);
                        }
                    } else {
                        return false;
                    }
                }
                break;

            case FavoriteType::NOTICE:
                $noticeModel = $this->getNoticeModel();
                $noticeDetail = $noticeModel->getByPermalink($data['permalink']);
                if (empty($noticeDetail)) {
                    return false;
                } else {
                    $data['subscribed_id'] = $noticeDetail['notice_id'];
                    if ($doFavorite) {
                        $data['subscription_id'] = $this->dao->makeFavorite($data);
                    } else {
                        $data['subscription_id'] = $this->dao->cancelFavorite($data);
                    }
                    if ($data['subscription_id']) {
                        $data['writer_id'] = $noticeDetail['notice_created_by'];
                        if ($doFavorite) {
                            $noticeModel->incrementCommentCounting($data['subscribed_id']);
                        } else {
                            $noticeModel->decrementCommentCounting($data['subscribed_id']);
                        }
                    } else {
                        return false;
                    }
                }
                break;

            case FavoriteType::DISCUSSION:
                $discussionModel = $this->getDiscussionModel();
                $discussionDetail = $discussionModel->getByPermalink($data['permalink']);
                if (empty($discussionDetail)) {
                    return false;
                } else {
                    $data['subscribed_id'] = $discussionDetail['discussion_id'];
                    if ($doFavorite) {
                        $data['subscription_id'] = $this->dao->makeFavorite($data);
                    } else {
                        $data['subscription_id'] = $this->dao->cancelFavorite($data);
                    }
                    if ($data['subscription_id']) {
                        $data['writer_id'] = $discussionDetail['discussion_created_by'];
                        if ($doFavorite) {
                            $discussionModel->incrementSubscriberCounting($data['subscribed_id']);
                        } else {
                            $discussionModel->decrementSubscriberCounting($data['subscribed_id']);
                        }
                    } else {
                        return false;
                    }
                }
                break;

            case FavoriteType::MOOD:
                $moodModel = $this->getMoodModel();
                $moodDetail = $moodModel->getByPermalink($data['permalink']);
                if (empty($moodDetail)) {
                    return false;
                } else {
                    $data['subscribed_id'] = $moodDetail['mood_id'];
                    if ($doFavorite) {
                        $data['subscription_id'] = $this->dao->makeFavorite($data);
                    } else {
                        $data['subscription_id'] = $this->dao->cancelFavorite($data);
                    }
                    if ($data['subscription_id']) {
                        $data['writer_id'] = $moodDetail['mood_created_by'];
                        if ($doFavorite) {
                            $moodModel->incrementSubscriberCounting($data['subscribed_id']);
                        } else {
                            $moodModel->decrementSubscriberCounting($data['subscribed_id']);
                        }
                    } else {
                        return false;
                    }
                }
                break;

            case FavoriteType::COMMENT:
                $commentModel = $this->getCommentModel();
                $commentDetail = $commentModel->getDetailWithWriting($data['permalink']);
                if (empty($commentDetail)) {
                    return false;
                } else {
                    $data['subscribed_id'] = $commentDetail['comment_id'];
                    $data['comment_for'] = $commentDetail['comment_for'];
                    $data['writing_id'] = $commentDetail['writing_id'];
                    if ($doFavorite) {
                        $data['subscription_id'] = $this->dao->makeFavorite($data);
                    } else {
                        $data['subscription_id'] = $this->dao->cancelFavorite($data);
                    }
                    if ($data['subscription_id']) {
                        $data['writer_id'] = $commentDetail['comment_created_by'];
                        if ($doFavorite) {
                            $commentModel->incrementSubscriberCounting($data['subscribed_id']);
                        } else {
                            $commentModel->decrementSubscriberCounting($data['subscribed_id']);
                        }
                    } else {
                        return false;
                    }
                }
                break;

            case FavoriteType::WRITER:
                $userDetail = $this->getUserModel()->getUsernameByUserId($data['permalink']);
                if (empty($userDetail)) {
                    return false;
                } else {
                    $data['subscribed_id'] = $data['permalink'];
                    if ($doFavorite) {
                        $data['subscription_id'] = $this->dao->makeFavorite($data);
                    } else {
                        $data['subscription_id'] = $this->dao->cancelFavorite($data);
                    }
                    if (!$data['subscription_id']) {
                        return false;
                    }
                }
                break;

            default:
                return false;
        }

        empty($doFavorite) || $this->getNotificationUserModel()->saveForMakingFavorite($data);
        return $data;
    }

    public function makeFollowingWriter($subscribedId, $userId)
    {
        if (empty($subscribedId) || empty($userId)) {
            return false;
        }

        return $this->dao->makeFavorite(array(
            'user_id' => $userId,
            'subscribed_id' => $subscribedId,
            'subscribed_for' => FavoriteType::WRITER
        ));
    }

    public function cancelFavoriteWriter($subscribedId, $userId)
    {
        if (empty($subscribedId) || empty($userId)) {
            return false;
        }

        return $this->dao->cancelFavorite(array(
            'user_id' => $userId,
            'subscribed_id' => $subscribedId,
            'subscribed_for' => FavoriteType::WRITER
        ));
    }

    public function cancelSubscribersBulky($userId, $options)
    {
        if (empty($userId) || empty($options['type'])) {
            return false;
        } else if (empty($options['bloggerIds']) && empty($options['postIds'])) {
            return false;
        }

        $subscribers = empty($options['postIds']) ? $options['bloggerIds'] : $options['postIds'];
        if ($options['type'] == FavoriteType::POST) {
            $this->getBlogModel()->decrementSubscribersCountingBulky($subscribers);
        }
        return $this->dao->cancelFavoriteBulky($subscribers, $userId, $options['type']);
    }

    public function getSubscriberIdsOfUser($userId)
    {
        return $this->getAllLikers($userId, FavoriteType::WRITER);
    }

    public function getAllLikers($idSubscribedFor, $subscribedFor)
    {
        if (empty($idSubscribedFor)) {
            return array();
        }
        $users = $this->dao->getAllSubscribers($idSubscribedFor, $subscribedFor);
        $result = array();
        if (!empty($users)) {
            foreach ($users AS $user) {
                $result[$user['user_id']] = $user['user_id'];
            }
        }
        return $result;
    }

    public function getAllLikes($options)
    {
        if (empty($options)) {
            return array();
        }
        $result = $this->dao->getAllLikes($options);

        return $this->getUserHelperModel()->getShortProfilesOfUsers($result, array(
                                                        'withProfile' => true,
                                                         'userKey' => 'user_id'
                                                    ));
    }

    public function countFollowersOfUsers(array $userIds = array())
    {
        if (empty($userIds)) {
            return array();
        }

        return $this->dao->countFollowersOfUsers($userIds);
    }

    public function getWritingsOfUser($userId, $withProfile = false, $status = '1', $limit = 10)
    {
        if (empty($userId)) {
            return false;
        }

        $options = $this->setCountOffset(array('status' => $status, 'limit' => $limit));

        return $this->getUserHelperModel()->getUsersDetail($this->dao->getWritingsOfUser($userId, $options), array(
            'withProfile' => $withProfile,
            'userKey' => 'subscribed_user_id',
            'withPostsCommentsCount' => false,
            'withDiscussionsCommentsCount' => false
        ));
    }

    public function countWritingsOfUser($userId)
    {
        if (empty($userId)) {
            return false;
        }

        return $this->dao->countWritingsOfUser($userId);
    }

    public function getAllSubscriberPost($iUserId)
    {
        $arAllSubscriberInfo = $this->getFavoriteWriters($iUserId);

        if (empty($arAllSubscriberInfo)) {
            return array();
        }
        $arrResult = $this->getBlogModel()->getMultipleUserPost($arAllSubscriberInfo);
        return (empty($arrResult) ? array() : $arrResult);
    }

    public function checkFollowerOrNot($bloggerId, $loggedInUser)
    {
        if (empty($bloggerId) || empty($loggedInUser)) {
            return false;
        }

        return $this->dao->checkFollowerOrNot($bloggerId, $loggedInUser);
    }

    private function getPostsCategories(array $posts, $index = 'post_id')
    {
        if (empty($posts)) {
            return $posts;
        }

        return $this->getPostCategoryHelperModel()->getPostsCategories($posts, $index);
    }

    /**
     * @return \Blog\Model\Blog
     */
    private function getBlogModel()
    {
        isset($this->blogModel) || $this->blogModel = $this->serviceManager->get('Blog\Model\Blog');
        return $this->blogModel;
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
     * @return \Blog\Model\Notice
     */
    private function getNoticeModel()
    {
        isset($this->noticeModel) || $this->noticeModel = $this->serviceManager->get('Blog\Model\Notice');
        return $this->noticeModel;
    }

    /**
     * @return \BlogUser\Model\Discussion
     */
    private function getDiscussionModel()
    {
        isset($this->discussionModel) || $this->discussionModel = $this->serviceManager->get('BlogUser\Model\Discussion');
        return $this->discussionModel;
    }

    /**
     * @return \BlogUser\Model\Mood
     */
    private function getMoodModel()
    {
        isset($this->moodModel) || $this->moodModel = $this->serviceManager->get('BlogUser\Model\Mood');
        return $this->moodModel;
    }

    /**
     * @return \BlogUser\Model\NotificationUser
     */
    private function getNotificationUserModel()
    {
        isset($this->notificationUserModel) || $this->notificationUserModel = $this->serviceManager->get('BlogUser\Model\NotificationUser');
        return $this->notificationUserModel;
    }

    /**
     * @return  \NBlog\Model\User
     */
    private function getUserModel()
    {
        isset($this->userModel) || $this->userModel = $this->serviceManager->get('NBlog\Model\User');
        return $this->userModel;
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
}