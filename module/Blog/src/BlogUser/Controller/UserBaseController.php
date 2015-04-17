<?php
/**
 * Blog User Base Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;

use NBlog\Controller\AbstractController;
use Zend\EventManager\EventManagerInterface;
use Zend\Json\Json;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

abstract class UserBaseController extends AbstractController
{
    protected $blogModel;
    protected $subscribeModel;
    protected $tempPictureModel;
    protected $userBannerModel;

    /**
     * @var         string
     */
    protected $menuItem = '';

    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);
        $controller = $this;
        $events->attach('dispatch', function (MvcEvent $e) use ($controller) {
            $route = $e->getRouteMatch()->getMatchedRouteName();
            $routesOfNotLoggingCheck = array(
                'public-profile',
                'get-profile-wall-data',
                'friends-subscribers-followers',
                'get-all-friends',
                'get-user-friends',
                'get-friend-suggestion-list',
                'show-user-pictures',
                'show-pic-album',
                'show-user-posts',
                'get-post-status',
                'view-my-post',
                'show-single-post',
                'all-like-single-posts',
                'change-keyboardlayout'
            );
            if (in_array($route, $routesOfNotLoggingCheck) || $controller->validateUser()) {
                $routesToBeSmsVerified = array(
                    'write-about-mood',
                    'edit-my-mood',
                    'add-my-post',
                    'edit-my-post',
                    'save-comment',
                    'save-comment-for-userwall',
                    'edit-comment',
                    'edit-userwall-comment',
                    'report'
                );
                if (!in_array($route, $routesToBeSmsVerified) || $controller->getSessionContainer()->offsetGet('isSmsVerified')) {
                    return $controller;
                } else {
                    return $controller->getResponse()->setContent(Json::encode(array(
                        'status' => 'not-sms-verified',
                        'html' => $controller->translate('This user is not sms verified')
                    )), true);
                }
            } else {
                $request = $controller->getRequest();
                if ($request->isXmlHttpRequest()) {
                    $moodViewModel = $controller->forward()->dispatch('User\Controller\Index', array(
                        'action'   => 'loginByModal',
                        'isCalled' => true
                    ));
                    return $controller->getResponse()->setContent(Json::encode(array(
                        'status' => 'not-logged-in',
                        'html' => $controller->getServiceLocator()->get('viewRenderer')->render($moodViewModel)
                    )), true);
                } else {
                    return $controller->redirectNow('login', array('next' => urlencode($_SERVER['REQUEST_URI'])));
                }
            }
        }, 100);
    }

    protected function initializeLayout($pageTitle = '')
    {
        parent::initializeLayout($pageTitle);
        $this->layout()->setVariables(array(
            'disableBanner' => true,
            'disableFooterCategory' => true,
            'isChatEnable' => true,
            'menuItem' => $this->menuItem,
        ));
    }

    protected function initialize(ViewModel $viewModel = null, $userDetail = array(), $layout = '')
    {
        (!empty($viewModel)) || $viewModel = $this->layout();
        if (empty($userDetail)) {
            $userDetail = $this->getUserModel()->getDetailHavingProfile($this->getSessionContainer()->offsetGet('user_id'), true);
        }

        $viewModel->setVariables(array(
            'userDetail' => $userDetail,
        ));

        $viewModel->setTemplate(empty($layout) ? 'profile/layout' : $layout);
        $this->initializeLayout();
    }

    protected function enableLayoutBanner()
    {
        $this->layout()->setVariable('disableBanner', false);
    }

    /**
     * @return \Blog\Model\Blog
     */
    protected function getBlogModel()
    {
        isset($this->blogModel) || $this->blogModel = $this->getServiceLocator()->get('Blog\Model\Blog');
        return $this->blogModel;
    }

    /**
     * @return  \BlogUser\Model\Subscribe
     */
    protected function getSubscribeModel()
    {
        isset($this->subscribeModel) || $this->subscribeModel = $this->getServiceLocator()->get('BlogUser\Model\Subscribe');
        return $this->subscribeModel;
    }

    protected function getUserDetail()
    {
        $sessionContainer = $this->getSessionContainer();
        $currentUsername = $sessionContainer->offsetGet('username');
        $username = $this->params()->fromRoute('username', null);
        if (!($username) || $username === 'me' || $username === $currentUsername) {
            $userDetail = $this->getUserModel()->getDetailHavingProfile($sessionContainer->offsetGet('user_id'), true);
        } else {
            $userDetail = $this->getUserModel()->getDetailByUsername($username, true);
        }

        return $userDetail;
    }

    protected function isLoggedInUser($userId)
    {
        return ($userId === $this->getSessionContainer()->offsetGet('user_id'));
    }

    public function checkUserSmsVerified()
    {
        if ($this->getSessionContainer()->offsetGet('isSmsVerified')) {
            return true;
        }

        $this->setFailureMessage(empty($message) ? $this->translate('Please login first to view.') : $message);
        return false;
    }

    /**
     * @return  \BlogUser\Model\UserBanner
     */
    protected function getUserBannerModel()
    {
        isset($this->userBannerModel) || $this->userBannerModel = $this->getServiceLocator()->get('BlogUser\Model\UserBanner');
        return $this->userBannerModel;
    }

    /**
     * @return  \BlogUser\Model\TempPicture
     */
    protected function getTempPictureModel()
    {
        isset($this->tempPictureModel) || $this->tempPictureModel = $this->getServiceLocator()->get('BlogUser\Model\TempPicture');
        return $this->tempPictureModel;
    }
}