<?php
/**
 * Index Controller
 *
 * This is the controller which has home of the site.
 *
 * @category        Controller
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Controller;

use Zend\Json\Json;
use Zend\View\Model\ViewModel;
use NBlog\Model\Setting;
//use BlogUser\Model\Discussion;
use NBlog\Utility\PostLimit;

class IndexController extends BaseController
{
    protected $blogModel;
    protected $commentModel;
    protected $hiddenModel;
    protected $categoryModel;
    protected $noticeModel;
    protected $professionModel;
    protected $userSettingModel;
    protected $divisionModel;
    protected $districtModel;
    protected $stationModel;
    protected $postOfficeModel;

    public function indexAction()
    {
        $blogModel = $this->getBlogModel();
        $commentModel = $this->getCommentModel();
        $recentComments = $commentModel->getRecentComments('', PostLimit::RECENT_COMMENTS);
        $sessionContainer = $this->getSessionContainer();
        $currentUser = $sessionContainer->offsetGet('user_id');

        $viewModel = new ViewModel(array(
            'notices' => $this->getNoticeModel()->getLatestActiveNotice($currentUser),
            'stickyPosts' => $blogModel->getStickyPosts(PostLimit::STICKY_POSTS, $currentUser),
            'superStickyPosts' => $this->processSuperSticky($blogModel->getActiveSlidablePosts(PostLimit::SUPER_STICKY_POSTS, $currentUser)),
            'topBlogPosts' => $blogModel->getTopBlogPosts(PostLimit::TOP_BLOG_POSTS, $currentUser),
            'recentComments' => $recentComments,
            'topBloggers' => $blogModel->getTopBloggers(PostLimit::TOP_POSTERS),
            'topCommentPosters' => $commentModel->getTopCommentPosters(PostLimit::TOP_COMMENTERS),
            'oldPosts' => $blogModel->getRandomly(PostLimit::OLD_POSTS, $currentUser),
            'newBloggers' => $this->getUserModel()->getNewBloggers(PostLimit::NEW_BLOGGERS),
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll(),
            'liveFeedEnable' => PostLimit::LIVE_FEED_ENABLE
        ));

        $latestPostsViewModel = $this->forward()->dispatch('Blog\Controller\Posts', array(
            'action'     => 'deque-post',
            'page'       => $this->params()->fromRoute('page', 1)
        ));
        $viewModel->addChild($latestPostsViewModel, 'recentBlogPosts');

        if ($currentUser) {
            $settings = $this->getUserSettingModel()->getAll($currentUser);
            $sessionContainer->offsetSet('render_mode', empty($settings['render_mode']) ? '' : $settings['render_mode']);
        } else {
            $sessionContainer->offsetSet('render_mode', Setting::MODERN_VIEW);
        }
        $this->layout()->setVariable('disableBanner', false);
        $this->initializeLayout();
        return $viewModel;
    }

    public function userShortProfileWithPopoverAction()
    {
        $request = $this->getRequest();
        $options = $request->getPost()->toArray();
        $userId = $this->getSessionContainer()->offsetGet('user_id');

        $userDetails = $this->getUserModel()->getUsersDetailsById($options['permalink'], true);
        $friendModel = $this->getFriendModel();
        $ViewModel = new ViewModel(array(
            'userDetails' => $userDetails,
            'professions' => $this->getProfessionModel()->getAll(),
            'mutualFriends' => $friendModel->countCommonFriends($userId, $options['permalink']),
            'isFriends' => $friendModel->getFriendsCheckForId($userId, $options['permalink']),
        ));
        $ViewModel->setTemplate('blog/index/user-short-profile-with-popover');
        return $this->getResponse()->setContent(Json::encode(array(
            'status' => 'success',
            'userDetails'=> $this->getServiceLocator()->get('viewRenderer')->render($ViewModel),
        )));
    }

    public function searchAction()
    {
        $request = $this->getRequest();
        $options = ($request->isPost()) ? (array)$request->getPost() : $this->params()->fromRoute();

        $users = $posts = array();
        $options['status'] = empty($options['status']) ? '' : $options['status'];
        $options['criteria'] = empty($options['criteria']) ? '' : strip_tags($options['criteria']);
        $viewModel = new ViewModel();

        switch ($options['status']) {
            case 'user':
                $userModel = $this->getUserModel();
                $users = $userModel->getSearchUser($options);
                $rowCount = $userModel->countSearchedUser($options);
                $this->setPagination($viewModel, $userModel, $users, $rowCount);
                break;

            case 'post' :
                $blogModel = $this->getBlogModel();
                $posts = $blogModel->searchBlog($options);
                $rowCount = $blogModel->countSearchedBlog($options);
                $this->setPagination($viewModel, $blogModel, $posts, $rowCount);
                break;

            default :
                $searchOptions = array_merge($options, array('limit' => 5));
                $options['paginateFor'] = empty($options['paginateFor']) ? '' : $options['paginateFor'];

                $searchOptions = array_merge($searchOptions, array('page' => ($options['paginateFor'] != 'post' ? 1 : $options['page'])));
                $blogModel = $this->getBlogModel();
                $posts = $blogModel->searchBlog($searchOptions);
                $rowCount = $blogModel->countSearchedBlog($searchOptions);
                $paginator = $blogModel->getPaginator($posts, $rowCount);
                $viewModel->setVariables(array(
                    'paginatorForPost' => $paginator,
                    'paginatorOptionsForPost' => $this->getPaginationOptions()
                ));

                $searchOptions = array_merge($searchOptions, array('page' => ($options['paginateFor'] != 'user' ? 1 : $options['page'])));
                $userModel = $this->getUserModel();
                $users = $userModel->getSearchUser($searchOptions);
                $rowCount = $userModel->countSearchedUser($searchOptions);
                $paginator = $userModel->getPaginator($users, $rowCount);
                $viewModel->setVariables(array(
                    'paginatorForUser' => $paginator,
                    'paginatorOptionsForUser' => $this->getPaginationOptions()
                ));
        }

        $viewModel->setVariables(array(
            'posts' => $posts,
            'users' => $users,
            'page' => empty($options['page']) ? 1 : $options['page'],
            'status' => $options['status'],
            'categories' => $this->getCategoryModel()->getAll(),
            'criteria' => empty($options['criteria']) ? '' : $options['criteria']
        ));

        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewModel->setTemplate('blog/index/search');
            return $this->getResponse()->setContent(Json::encode(array(
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel))
            ));
        } else {
            $this->initializeLayout();
        }

        return $viewModel;
    }

    public function setUserAvailableAction()
    {
        return $this->getResponse()->setContent(Json::encode(array('success' => 1), true));
    }

    protected function getPaginationOptions()
    {
        return array(
            'path' => '',
            'itemLink' => 'search'
        );
    }

    private function processSuperSticky(array $posts = array())
    {
        if (empty($posts)) {
            return array();
        }

        $imageProcessor = $this->ContentImageProcessor();
        $postHolder = array();
        foreach ($posts AS $post) {
            $postHolder[] = array_merge($post, array('details' => $imageProcessor->removeMultipleImagesFromText($post['details'])));
        }
        return $postHolder;
    }

    public function getCloseButtonAction()
    {

    }

    /*------------Geo Ajax Load -------------*/

    public function ajaxCountryAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = $request->getPost()->toArray();
            $divisionList = $this->getDivisionModel()->getDivisionList($params, true);
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => empty($divisionList) ? 'error' : 'success',
                'divisionList' => empty($divisionList) ? array() : $divisionList
            )));
        }

        exit('Direct access is denied');
    }

    public function ajaxDistrictAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = $request->getPost()->toArray();
            $districtList = $this->getDistrictModel()->getDistrictListByDivisionId($params, true);
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => empty($districtList) ? 'error' : 'success',
                'districtList' => empty($districtList) ? array() : $districtList
            )));
        }

        exit('Direct access is denied');
    }

    public function ajaxStationAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = $request->getPost()->toArray();
            $stationList = $this->getPoliceStationModel()->getStationByDistrictId($params, true);
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'stationList' => empty($stationList) ? array() : $stationList
            )));
        }

        exit('Direct access is denied');
    }

    public function ajaxOfficeAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = $request->getPost()->toArray();
            $officeList = $this->getPostOfficeModel()->getOfficesByStationId($params, true);
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'officeList' => empty($officeList) ? array() : $officeList
            )));
        }

        exit('Direct access is denied');
    }

    /**
     * @return \Blog\Model\Blog
     */
    private function getBlogModel()
    {
        isset($this->blogModel) || $this->blogModel = $this->getServiceLocator()->get('Blog\Model\Blog');
        return $this->blogModel;
    }

    /**
     * @return \Blog\Model\Comment
     */
    private function getCommentModel()
    {
        isset($this->commentModel) || $this->commentModel = $this->getServiceLocator()->get('Blog\Model\Comment');
        return $this->commentModel;
    }

    /**
     * @return \Blog\Model\Notice
     */
    private function getNoticeModel()
    {
        isset($this->noticeModel) || $this->noticeModel = $this->getServiceLocator()->get('Blog\Model\Notice');
        return $this->noticeModel;
    }

    /**
     * @return \BlogUser\Model\Hidden
     */
    protected function getHiddenModel()
    {
        isset($this->hiddenModel) || $this->hiddenModel = $this->getServiceLocator()->get('BlogUser\Model\Hidden');
        return $this->hiddenModel;
    }

    /**
     * @return \NBlog\Model\Category
     */
    private function getCategoryModel()
    {
        isset($this->categoryModel) || $this->categoryModel = $this->getServiceLocator()->get('NBlog\Model\Category');
        return $this->categoryModel;
    }

    /**
     * @return \NBlog\Model\Profession
     */
    private function getProfessionModel()
    {
        isset($this->professionModel) || $this->professionModel = $this->getServiceLocator()->get('NBlog\Model\Profession');
        return $this->professionModel;
    }

    /**
     * @return \NBlog\Model\UserSetting
     */
    private function getUserSettingModel()
    {
        isset($this->userSettingModel) || $this->userSettingModel = $this->getServiceLocator()->get('NBlog\Model\UserSetting');
        return $this->userSettingModel;
    }

    /*------------Geo Model-------------*/

    /**
     * @return \Geo\Model\Division
     */
    private function getDivisionModel()
    {
        isset($this->divisionModel) || $this->divisionModel = $this->getServiceLocator()->get('Geo\Model\Division');
        return $this->divisionModel;
    }

    /**
     * @return \Geo\Model\District
     */
    private function getDistrictModel()
    {
        isset($this->districtModel) || $this->districtModel = $this->getServiceLocator()->get('Geo\Model\District');
        return $this->districtModel;
    }

    /**
     * @return \Geo\Model\PoliceStation
     */
    private function getPoliceStationModel()
    {
        isset($this->stationModel) || $this->stationModel = $this->getServiceLocator()->get('Geo\Model\PoliceStation');
        return $this->stationModel;
    }

    /**
     * @return \Geo\Model\PostOffice
     */
    private function getPostOfficeModel()
    {
        isset($this->postOfficeModel) || $this->postOfficeModel = $this->getServiceLocator()->get('Geo\Model\PostOffice');
        return $this->postOfficeModel;
    }
}
