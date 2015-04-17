<?php
/**
 * Friends Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;

use NBlog\Model\ServiceLocatorBlogDB;

class Friend extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\Friend
     */
    protected $dao = null;
    protected $blogModel = null;
    protected $userHelperModel = null;
    protected $subscribeModel = null;

    const IS_ACTIVE_FRIEND = 1;
    const IS_REQUEST_FRIEND = 0;
    const IS_REQUEST_ACCEPT = 1;
    const IS_ACTIVE_SUBSCRIBER = 1;

    public function getAll($userId)
    {
        $result = $this->dao->getAll($userId);
        return $this->getUserHelperModel()->getShortProfilesOfUsers($result, array('withProfile' => true, 'userKey' => 'friend_user_id'));
    }

    public function setFriendRequest(array $data)
    {
        if (empty($data)) {
            return false;
        }

        return $this->dao->save($data);
    }

    public function isRequestAlreadySent(array $data)
    {
        $result = $this->dao->getAllFriends($data);
        return !empty($result);
    }

    public function handleFriendRequest(array $data, array $options = array(), $actionType = 0)
    {
        $result = $this->dao->modifyFriendStatus($data, $options);
        if (!empty($result)) {
            if ($actionType) {
                $isSuccess = true;
            } else {
                $isSuccess = $this->setFriendRequest(array(
                    'user_id' => $options['friend_user_id'],
                    'friend_user_id' => $options['user_id'],
                    'is_request_accept' => self::IS_REQUEST_ACCEPT,
                    'is_active_friend' => self::IS_ACTIVE_FRIEND,
                ));
            }
        }

        return (empty($isSuccess)) ? false : true;
    }

    public function getAllFriendsRequest($userId, array $options = array())
    {
        !isset($options['offset']) || $options['offset'] = $options['offset'] * 10;
        $result = $this->dao->getAllFriendsRequest($userId, $options);
        return $this->getUserHelperModel()->getShortProfilesOfUsers($result, array('userKey' => 'friend_user_id', 'withProfile' => true));
    }

    public function countAllRequests($userId)
    {
        if (empty($userId)) {
            return 0;
        }

        return $this->dao->countAllRequests($userId);
    }

    public function removeFriend(array $options)
    {
        if (empty($options)) {
            return false;
        }

        return $this->dao->removeFriend($options);
    }

    public function getUserFriendInfo(array $options)
    {
        $result = $this->dao->getAllFriends($options, true);
        if (empty($result)) {
            return array();
        }

        return $this->getUserHelperModel()->getShortProfilesOfUsers($result, array('userKey' => 'friend_user_id', 'withProfile' => true));
    }

    public function getFriendsIds($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $result = $this->dao->getFriendsIds($userId);
        $ids = array();
        if (!empty($result)) {
            foreach ($result AS $user) {
                $ids[$user['friend_user_id']] = $user['friend_user_id'];
            }
        }

        return $ids;
    }

    public function getFriendsCheckForId($userId, $friendId)
    {
        if (empty($userId)) {
            return false;
        }
        $result = $this->dao->getFriendsId($userId, $friendId);
        return (!empty($result));
    }

    public function getAllFriends($loggedInUser)
    {
        if (empty($loggedInUser)) {
            return array();
        }
        $result = $this->dao->getAllFriends(array('user_id' => $loggedInUser, 'is_active_friend' => self::IS_ACTIVE_FRIEND));
        if (empty($result)) {
            return array();
        }
        foreach ($result AS $key => $row) {
            $result[$key]['commonFriends'] = $this->getUserHelperModel()->getShortProfilesOfUsers($this->dao->getCommonFriends($loggedInUser, array($row['friend_user_id'])), array('userKey' => 'friend_user_id', 'withProfile' => true), true);
            $result[$key]['countCommonFriends'] = $this->dao->countCommonFriends($loggedInUser, array($row['friend_user_id']));
            $result[$key]['enableShowingMutualFriends'] = true;
        }

        return $this->getUserHelperModel()->getShortProfilesOfUsers($result, array('userKey' => 'friend_user_id', 'withProfile' => true), true);
    }

    public function countCommonFriends($loggedInUser, $friendId)
    {
        if (empty($loggedInUser) || ($loggedInUser == $friendId)) {
            return false;
        }

        return $this->dao->countCommonFriends($loggedInUser, array($friendId));
    }

    public function getFriendsForProfile($userId)
    {
        if (empty($userId)) {
            return array();
        }
        $result = $this->dao->getFriendsForProfile(array('user_id' => $userId, 'is_active_friend' => self::IS_ACTIVE_FRIEND));
        if (empty($result)) {
            return array();
        }

        return $this->getUserHelperModel()->getShortProfilesOfUsers($result, array('userKey' => 'friend_user_id', 'withProfile' => true));
    }

    public function countAllFriends($userId)
    {
        if (empty($userId)) {
            return 0;
        }

        return $this->dao->countAllFriends(array('user_id' => $userId, 'is_active_friend' => self::IS_ACTIVE_FRIEND));
    }

    public function getAllFriendsForSuggestions($options = array())
    {
        if (empty($options['user_id'])) {
            return false;
        }
        $friendListForSuggestion = $this->dao->getAllFriendsForSuggestions($options);
        if (empty($friendListForSuggestion)) {
            return array();
        }

        shuffle($friendListForSuggestion);
        isset($options['limit']) || $options['limit'] = 20;
        $keys = array_rand($friendListForSuggestion, $options['limit']);
        $result = array();
        foreach ($keys AS $index => $friendKey) {
            $result[$index] = array_merge($friendListForSuggestion[$friendKey], array(
                'commonFriendsList' => $this->getUserHelperModel()->getShortProfilesOfUsers($this->dao->getCommonFriends($options['user_id'], (array)$friendListForSuggestion[$friendKey]), array('userKey' => 'friend_user_id', 'withProfile' => true), true)
            ));
        }

        return $this->getUserHelperModel()->getUsersDetail($result, array('userKey' => 'friend_user_id', 'withProfile' => true));
    }

    public function countFriendsOfUsers(array $userIds = array())
    {
        if (empty($userIds)) {
            return array();
        }

        return $this->dao->countFriendsOfUsers($userIds);
    }

    public function getAllSubscribers($userId)
    {
        if (empty($userId)) {
            return array();
        }
        $options = array('user_id' => $userId, 'is_active_friend' => !self::IS_ACTIVE_FRIEND, 'is_request_accept' => !self::IS_REQUEST_ACCEPT, 'is_active_subscriber' => self::IS_ACTIVE_SUBSCRIBER);
        $result = $this->dao->getAllFriends($options, true);
        if (empty($result)) {
            return array();
        }

        return $this->getUserHelperModel()->getShortProfilesOfUsers($result, array('userKey' => 'friend_user_id', 'withProfile' => true));
    }

    public function getAllFriendPost($iUserId)
    {
        $arAllFriendInfo = $this->dao->getAllFriends(array(
            'user_id' => $iUserId,
            'is_request_accept' => self::IS_REQUEST_ACCEPT,
            'is_active_friend' => self::IS_ACTIVE_FRIEND
        ));

        if (empty($arAllFriendInfo)) {
            return array();
        }

        return $this->getBlogModel()->getMultipleUserPost($arAllFriendInfo);
    }

    public function getAllSubscriberPost($iUserId)
    {
        $arAllFriendInfo = $this->dao->getAllFriends(array(
            'user_id' => $iUserId,
            'is_request_accept' => !self::IS_REQUEST_ACCEPT,
            'is_active_friend' => !self::IS_ACTIVE_FRIEND,
            'is_active_subscriber' => self::IS_ACTIVE_SUBSCRIBER
        ));

        if (empty($arAllFriendInfo)) {
            return array();
        }

        return $this->getBlogModel()->getMultipleUserPost($arAllFriendInfo);
    }

    public function countAllFollower($iUserId)
    {
        return $this->dao->countAllFollower($iUserId);
    }

    public function countAllFollowing($iUserId)
    {
        return $this->dao->countAllFollowing($iUserId);
    }

    public function getAllFollowing($userId)
    {
        if (empty($userId)) {
            return array();
        }

        $result = $this->dao->getAllFriends(array(
            'friend_user_id' => $userId,
            'is_active_friend' => !self::IS_ACTIVE_FRIEND,
            'is_request_accept' => !self::IS_REQUEST_ACCEPT,
            'is_active_subscriber' => self::IS_ACTIVE_SUBSCRIBER
        ));

        if (empty($result)) {
            return array();
        }

        return $this->getUserHelperModel()->getShortProfilesOfUsers($result, array('userKey' => 'user_id', 'withProfile' => true));
    }

    public function setFriendRequestText($loggedInUserId, array $arrUserDetails)
    {
        if (empty($loggedInUserId)) {
            $arFriendReqInfo = array('stRequestStatus' => 'user_not_logged_in');
        } elseif ($loggedInUserId == $arrUserDetails['user_id']) {
            $arFriendReqInfo = array('stRequestStatus' => 'same_user');
        } else {
            $arrFriendInfo = $this->getUserFriendStatus(array('user_id' => $loggedInUserId, 'friend_user_id' => (int)$arrUserDetails['user_id']));
            if (!empty($arrFriendInfo)) {
                if ($arrFriendInfo['is_request_accept'] == 0) {
                    $arFriendReqInfo = array('stRequestStatus' => 'show_accept_deny_link');
                } else {
                    $arFriendReqInfo = array('stRequestStatus' => 'request_accept');
                }
            } else {
                $arrFriendInfo = $this->getUserFriendStatus(array('user_id' => (int)$arrUserDetails['user_id'], 'friend_user_id' => $loggedInUserId));
                if (empty($arrFriendInfo)) {
                    $objSubscriber = $this->getSubscribeModel();
                    $arSubscriberInfo = $objSubscriber->checkFavoriteWriterOfLoggedInUser($arrUserDetails['user_id'], $loggedInUserId);
                    if (empty($arSubscriberInfo)) {
                        $arFollowerInfo = $objSubscriber->checkFavoriteWriterOfLoggedInUser($loggedInUserId, $arrUserDetails['user_id']);
                        if (empty($arFollowerInfo)) {
                            $arFriendReqInfo = array('stRequestStatus' => 'request_not_send');
                        } else {
                            $arFriendReqInfo = array('stRequestStatus' => 'follower');
                        }
                    } else {
                        $arFriendReqInfo = array('stRequestStatus' => 'following');
                    }
                } else {
                    if ($arrFriendInfo['is_request_accept'] == 0) {
                        $arFriendReqInfo = array('stRequestStatus' => 'request_not_accept');
                    } else
                        $arFriendReqInfo = array('stRequestStatus' => 'request_accept');
                }
            }
        }

        return $arFriendReqInfo;
    }

    public function getUserFriendStatus(array $arrOptions)
    {
        return $this->dao->getAllFriends(array(
            'user_id' => $arrOptions['user_id'],
            'friend_user_id' => $arrOptions['friend_user_id']
        ), true);
    }

    public function cancelFriendRequest(array $options)
    {
        if (empty($options)) {
            return false;
        }

        return $this->dao->cancelFriendRequest($options);
    }

    public function friendsRequestCount($userId)
    {
        if (empty($userId)) {
            return false;
        }

        return $this->dao->friendsRequestCount($userId);
    }

    public function getFriendsRequest($userId)
    {
        if (empty($userId)) {
            return array();
        }

        $friendsRequest = $this->getUserHelperModel()->getShortProfilesOfUsers($this->dao->getFriendsRequest($userId), array(
            'withProfile' => true
        ));

        return $friendsRequest;
    }

    public function checkFriendOrNot($bloggerId, $loggedInUser)
    {
        if (empty($bloggerId) || empty($loggedInUser)) {
            return false;
        }

        return $this->dao->checkFriendOrNot($bloggerId, $loggedInUser);
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
}
