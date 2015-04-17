<?php
/**
 * Friends Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md.Atiqul Haque <mailtoatiqul@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Lab. http://www.nokkhotrolab.com
 */
namespace BlogUser\Controller;

use NBlog\Utility\PostLimit;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use NBlog\Model\FriendsAndFollowersConfig;

class FriendsController extends UserBaseController
{
    protected $menuItem = 'friends';

    public function indexAction()
    {
        if ($this->params()->fromRoute('username', null) === 'me' && !$this->getSessionContainer()->offsetGet('user_id')) {
            return $this->redirectNow('login', array('next' => urlencode($_SERVER['REQUEST_URI'])));
        }
        $userDetail = $this->getUserDetail();

        $objSubscribe = $this->getSubscribeModel();
        $this->initialize(NULL, $userDetail);
        $this->enableLayoutBanner();
        $viewModel = new ViewModel(array(
            'totalFriends' => $this->getFriendModel()->countAllFriends($userDetail['user_id']),
            'totalFollowing' => $objSubscribe->countFavoriteWriters($userDetail['user_id']),
            'totalFollower' => $objSubscribe->countBeingSubscribers($userDetail['user_id']),
            'totalRequests' => $this->layout()->getVariable('countFriendRequests'),
            'userDetail' => $userDetail
        ));
        return $viewModel;
    }

    public function getFriendsAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToPreviousUrlForFailure($this->translate('Direct Access is Denied.'));
        }

        $userDetail = $this->getUserDetail();
        $objSubscribe = $this->getSubscribeModel();

        switch($request->getPost()->get('type')) {
            case FriendsAndFollowersConfig::FOLLOWERS :
                $data = $objSubscribe->getBeingSubscribers($userDetail['user_id'], true);
                break;

            case FriendsAndFollowersConfig::SUBSCRIBERS :
                $data = $objSubscribe->getFavoriteWriters($userDetail['user_id'], true);
                break;

            case FriendsAndFollowersConfig::REQUESTS :
                $data = $this->getFriendModel()->getAllFriendsRequest($userDetail['user_id']);
                break;

            default :
                $data = $this->getFriendModel()->getAllFriends($userDetail['user_id']);
        }

        return $this->getResponse()->setContent(Json::encode(array(
            'status' => 'success',
            'data' => $data
        )));
    }

    public function getFriendSuggestionListAction()
    {
        $userDetail = $this->getUserDetail();
        $viewModel = new ViewModel(array(
            'friendSuggestions' => $this->getFriendModel()->getAllFriendsForSuggestions(array(
                'user_id' => $userDetail['user_id'],
                'limit' => PostLimit::FRIEND_SUGGESTION
            )),
        ));

        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewModel->setTemplate('blog-user/friends/get-friend-suggestion-list');
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel)
            )));
        } elseif ($this->params()->fromRoute('isCalled')) {
            return $viewModel;
        } else {
            return $this->redirectToPreviousUrlForFailure($this->translate('Direct Access is Denied.'));
        }
    }

    public function getFriendRequestListAction()
    {
        $viewModel = new ViewModel(array(
            'friendRequests' => $this->getFriendModel()->getAllFriendsRequest(
                $this->getSessionContainer()->offsetGet('user_id'),
                array('offset' => $this->params()->fromRoute('page'))
            )
        ));

        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewModel->setTemplate('blog-user/friends/get-friend-request-list');
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel)
            )));
        } elseif ($this->params()->fromRoute('isCalled')) {
            return $viewModel;
        } else {
            return $this->redirectToPreviousUrlForFailure($this->translate('Direct Access is Denied.'));
        }
    }

    public function sendFriendRequestAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            $data = $request->getPost()->toArray();
            if (!$this->isUserIdInteger($data['user_id'])) {
                $result = array('status' => 'error', 'html' => $this->translate('Data is not given properly.'));
            } elseif ($userId == $data['user_id']) {
                $result = array('status' => 'error', 'html' => $this->translate('This action is not possible.'));
            } else {
                $objSubscribe = $this->getSubscribeModel();
                if ($objSubscribe->checkFavoriteWriterOfLoggedInUser($data['user_id'], $userId)) {
                    $result = array('status' => 'error', 'html' => $this->translate('You are already a follower of this user.'));
                } elseif ($objSubscribe->checkFavoriteWriterOfLoggedInUser($userId, $data['user_id'])) {
                    $result = array('status' => 'error', 'html' => $this->translate('This user is already a subscriber of you.'));
                } else {
                    $objFriendModel = $this->getFriendModel();
                    if ($objFriendModel->isRequestAlreadySent(array('user_id' => $data['user_id'], 'friend_user_id' => $userId))) {
                        $result = array('status' => 'error', 'html' => $this->translate('Friend request has been already sent.'));
                    } elseif ($objFriendModel->isRequestAlreadySent(array('user_id' => $userId, 'friend_user_id' => $data['user_id']))) {
                        $result = array('status' => 'error', 'html' => $this->translate('Friend request has been already sent.'));
                    } else {
                        $result = $objFriendModel->setFriendRequest(array('user_id' => $data['user_id'], 'friend_user_id' => $userId));
                        if (empty($result)) {
                            $result = array('status' => 'error', 'html' => $this->translate('Something went wrong. Please try again.'));
                        } else {
                            $result = array('status' => 'success');
                        }
                    }
                }
            }

            if ('success' == $result['status']) {
                if (isset($data['withHtml'])) {
                    $viewModel = new ViewModel(array(
                        'status' => 'request_not_accept',
                        'userId' => $data['user_id']
                    ));
                    $viewModel->setTemplate('layout/partials/friend-request-options');
                    $result['html'] = $this->getServiceLocator()->get('viewRenderer')->render($viewModel);
                } else {
                    $result['html'] = $this->translate('Friend request has been successfully sent.');
                }
            }
            return $this->getResponse()->setContent(Json::encode($result, true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function friendRequestAcceptAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = $request->getPost()->toArray();
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            if (!$this->isUserIdInteger($params['friend_user_id'])) {
                return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'html' => $this->translate('Data is not given properly.')), true));
            } elseif ($params['friend_user_id'] == $userId) {
                return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'html' => $this->translate('This action is not possible.')), true));
            }

            $objFriendModel = $this->getFriendModel();
            switch ($params['action']) {
                case 1:
                    /**
                     * Request Accept
                     */
                    $isRequestAccept = $objFriendModel->handleFriendRequest(array(
                        'is_request_accept' => $objFriendModel::IS_REQUEST_ACCEPT,
                        'is_active_friend' => $objFriendModel::IS_ACTIVE_FRIEND
                    ), array(
                        'user_id' => $userId,
                        'friend_user_id' => $params['friend_user_id']
                    ));
                    if (empty($isRequestAccept)) {
                        $result = array('status' => 'error', 'html' => $this->translate('Something went wrong. Please try again.'));
                    } else {
                        $result = array('status' => 'success', 'html' => $this->translate('Friend request is accepted.'));
                    }
                    break;

                case 2:
                    /**
                     * Add as a Subscriber
                     */
                    $bIsSubscriberAdded = $this->getSubscribeModel()->makeFollowingWriter($userId, $params['friend_user_id']);
                    $objFriendModel->cancelFriendRequest(array('user_id' => $userId, 'friend_user_id' => $params['friend_user_id']));
                    if (empty($bIsSubscriberAdded)) {
                        $result = array('status' => 'error', 'html' => $this->translate('Something went wrong. Please try again.'));
                    } else {
                        $result = array('status' => 'success', 'html' => $this->translate('Friend request is accepted as making subscriber.'));
                    }
                    break;

                case 3:
                    /**
                     * Cancel Friend Request
                     */
                    $isRequestAccept = $objFriendModel->cancelFriendRequest(array(
                        'user_id' => $userId,
                        'friend_user_id' => $params['friend_user_id']
                    ));
                    if (empty($isRequestAccept)) {
                        $result = array('status' => 'error', 'html' => $this->translate('Something went wrong. Please try again.'));
                    } else {
                        $result = array('status' => 'success', 'html' => $this->translate('Friend request is cancelled.'));
                    }
                    break;

                case 4:
                    /**
                     * Remove Friend
                     */
                    $bIsRemoveOneSide = $objFriendModel->removeFriend(array(
                        'user_id' => $userId,
                        'friend_user_id' => $params['friend_user_id']
                    ));
                    $bIsRemoveOtherSide = $objFriendModel->removeFriend(array(
                        'user_id' => $params['friend_user_id'],
                        'friend_user_id' => $userId
                    ));
                    if (!empty($bIsRemoveOneSide) && !empty($bIsRemoveOtherSide)) {
                        $result = array('status' => 'success', 'html' => $this->translate('This friend is removed from the friend list.'));
                    } else {
                        $result = array('status' => 'error', 'html' => $this->translate('Something went wrong. Please try again.'));
                    }
                    break;

                case 5 :
                    /**
                     * Un subscribe when current user is following some one
                     * and he wants to cancel the subscription or following
                     */
                    $bIsUnSubscribe = $this->getSubscribeModel()->cancelFavoriteWriter($userId, $params['friend_user_id']);
                    if (!empty($bIsUnSubscribe)) {
                        $result = array('status' => 'success', 'html' => $this->translate('The subscription is cancelled.'));
                    } else {
                        $result = array('status' => 'error', 'html' => $this->translate('Something went wrong. Please try again.'));
                    }
                    break;

                case 6 :
                    /**
                     * Cancel subscription when logged in user cancel someone to follow him.
                     */
                    $bIsUnSubscribe = $this->getSubscribeModel()->cancelFavoriteWriter($params['friend_user_id'], $userId);
                    if (!empty($bIsUnSubscribe)) {
                        $result = array('status' => 'success', 'html' => $this->translate('The subscription is cancelled.'));
                    } else {
                        $result = array('status' => 'error', 'html' => $this->translate('Something went wrong. Please try again.'));
                    }
                    break;

                case 7 :
                    /**
                     * Cancel friend request sent by logged-in user.
                     */
                    $result = $objFriendModel->cancelFriendRequest(array(
                        'user_id' => $params['friend_user_id'],
                        'friend_user_id' => $userId
                    ));
                    if (empty($result)) {
                        $result = array('status' => 'error', 'html' => $this->translate('Something went wrong. Please try again.'));
                    } else {
                        $result = array('status' => 'success', 'html' => $this->translate('Friend request is cancelled.'));
                    }
                    break;

                default:
                    $result = null;
            }

            if (isset($params['withHtml']) && isset($result['status']) && 'success' == $result['status']) {
                $viewModel = new ViewModel(array('userId' => $params['friend_user_id']));
                switch ($params['action']) {
                    case 1: // Request Accept
                        $status = 'request_accept';
                        break;

                    case 2: // Add as a Subscriber
                        $status = 'follower';
                        break;

                    case 3: // Cancel Friend Request
                    case 4: // Remove Friend
                    case 5 :
                        /**
                         * Un subscribe when current user is following someone
                         * and he wants to cancel the subscription or following
                         */
                    case 6 : // Cancel subscription when logged in user cancel someone to follow him.
                    case 7 : // Cancel friend request sent by logged-in user.
                        $status = 'request_not_send';
                        break;

                    default:
                        $status = null;
                }
                empty($status) || $viewModel->setVariable('status', $status);
                $viewModel->setTemplate('layout/partials/friend-request-options');
                $result['html'] = $this->getServiceLocator()->get('viewRenderer')->render($viewModel);
            }
            return $this->getResponse()->setContent(Json::encode($result, true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function viewAllRequestsAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('Unauthorized access. Please login to access.'));
        }

        $this->initialize(null, $userDetail);
        return new ViewModel(array(
            'friendRequest' => $this->getFriendModel()->getAllFriendsRequest($userDetail['user_id'])
        ));
    }

    private function isUserIdInteger($iUserId)
    {
        if (preg_match('/^[0-9]+$/', $iUserId)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * @return  \BlogUser\Model\User
     */
    protected function getUserModel()
    {
        isset($this->userModel) || $this->userModel = $this->getServiceLocator()->get('BlogUser\Model\User');
        return $this->userModel;
    }
}