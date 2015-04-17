<?php
/**
 * Posts Controller
 *
 * @category        Controller
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Controller;

use BlogUser\Form\Comment;
use BlogUser\Form\Report;
use NBlog\Model\Category;
use NBlog\Utility\PostLimit;
use Zend\Json\Json;
use Zend\View\Model\ViewModel;
use NBlog\Model\WritingStatus;
use NBlog\Model\VoteConfig;

class PostsController extends BaseController
{
    protected $blockedUserModel;
    protected $blogModel;
    protected $commentModel;
    protected $hiddenModel;
    protected $postForVotingModel;
    protected $subscribeModel;
    protected $categoryModel;
    protected $professionModel;
    protected $writingStatusModel;
    protected $reportMessageModel;

    public function getByCategoryAction()
    {
        $permalink = $this->params()->fromRoute('permalink', null);
        $categoryModel = $this->getCategoryModel();
        $categoryDetail = $categoryModel->getByPermalink($permalink);

        if (empty($categoryDetail)) {
            return $this->redirectForFailure('blog', $this->translate('Category has not been found.'));
        }

        $blogModel = $this->getBlogModel();
        $viewModel = new ViewModel();

        $options = array_merge($this->params()->fromRoute(), array('categoryId' => $categoryDetail['category_id']));
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        ($categoryDetail['category_id'] != Category::DEFAULT_CATEGORY) || $options = array_merge($options, array('getDeletedCategoricalPosts' => true));

        $blogModel->dequePostIfExists();
        $blogPosts = $blogModel->getCategoricalPosts(array_merge($options, array('loggedInUser' => $currentUser, 'withHidingStatus' => true)));
        $countPosts = $blogModel->countCategoricalPosts($options);
        $blogPostsIds = $blogModel->getPostIds($blogPosts);
        $latestCategoryPosts = $blogModel->latestCategoryPosts($categoryDetail['category_id'], $currentUser, 15, $blogPostsIds);
        $viewModel->setVariables(array(
            'categories' => $categoryModel->getAll(),
            'categoryDetail' => $categoryDetail,
            'blogPosts' => $blogPosts,
            'latestCategoryPosts' => $latestCategoryPosts,
            'professions' => $this->getProfessionModel()->getAll()
        ));

        $this->setPagination($viewModel, $blogModel, $blogPosts, $countPosts, array(
            'path' => '',
            'itemLink' => 'category',
            'urlOptions' => array('permalink' => $categoryDetail['permalink'])
        ));
        $this->layout()->setVariables(array(
            'selectedCategory' => empty($categoryDetail['parent_id']) ? $categoryDetail['category_id'] : $categoryDetail['parent_id']
        ));

        $this->initializeLayout();
        return $viewModel;
    }

    public function getRandomPostAction()
    {
        $viewModel = new ViewModel(array(
            'randomPosts' => $this->getBlogModel()->getRandomly(),
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll()
        ));

        return $viewModel;
    }

    public function getSelectedPostsAction()
    {
        $blogModel = $this->getBlogModel();
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $options = array_merge($this->params()->fromRoute(), array('limit' => PostLimit::SELECTED_POSTS, 'loggedInUser' => $currentUser));
        $blogPosts = $blogModel->getSelectedBlogPosts($options);
        $countPosts = $blogModel->countSelectedBlogPosts($options);
        $viewModel = new ViewModel(array(
            'selectedBlogPosts' => $blogPosts,
            'oldSelectedPosts' => $blogModel->getOldSelectedBlogPosts(array('posts' => $blogPosts, 'loggedInUser' => $currentUser)),
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll()
        ));

        $this->setPagination($viewModel, $blogModel, $blogPosts, $countPosts, array(
            'path' => '',
            'itemLink' => 'get-selected-posts'
        ));

        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewModel->setTemplate('blog/posts/get-selected-posts')->setVariable('isAjax', true);
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel))
            ));
        } else {
            return $viewModel;
        }
    }

    public function getMostAnythingAboutPostsAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        }
        $blogModel = $this->getBlogModel();
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $viewModel = new ViewModel(array(
            'mostCommentedPosts' => $blogModel->getMostCommentedBlogPosts($currentUser, PostLimit::MOST_COMMENTED_POSTS),
            'mostFavoritedPosts' => $blogModel->getMostFavoritedBlogPosts($currentUser, PostLimit::MOST_FAVORITES_POSTS),
            'mostViewedPosts' => $blogModel->getMostViewedBlogPosts($currentUser, PostLimit::MOST_VIEWED_POSTS),
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll()
        ));

        $viewModel->setTemplate('blog/posts/get-most-anything-about-posts');
        return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel))
        ));
    }

    public function showAllPublishedPostsAction()
    {
        return $this->getStatusBasedPosts('published');
    }

    public function showAllSelectedPostsAction()
    {
        return $this->getStatusBasedPosts('selected');
    }

    public function showAllStickyPostsAction()
    {
        return $this->getStatusBasedPosts('sticky');
    }

    private function getStatusBasedPosts($status = '')
    {
        $blogModel = $this->getBlogModel();
        $options = $this->params()->fromRoute();
        $blogModel->dequePostIfExists();

        switch ($status) {

            case 'selected' :
                $blogPosts = $blogModel->getSelectedBlogPosts($options);
                $countPosts = $blogModel->countSelectedBlogPosts($options);
                $itemLink = 'view-all-selected-posts';
                $blogStatus = $this->translate('Selected');
                break;

            case 'sticky' :
                $blogPosts = $blogModel->getStickyPosts($options);
                $countPosts = $blogModel->countStickyPosts($options);
                $itemLink = 'view-all-sticky-posts';
                $blogStatus = $this->translate('Sticky');
                break;

            case 'published' :
            default :
                $blogPosts = $blogModel->getPublishedPosts(null, $options);
                $countPosts = $blogModel->countPublishedPosts(null, $options);
                $itemLink = 'view-all-published-posts';
                $blogStatus = $this->translate('Published');
        }

        $viewModel = new ViewModel(array(
            'blogPosts' => $blogPosts,
            'countPosts' => $countPosts,
            'statuses' => $this->getWritingStatusModel()->getAll(),
            'categories' => $this->getCategoryModel()->getAll(),
            'blogStatus' => $blogStatus,
            'professions' => $this->getProfessionModel()->getAll()
        ));

        $this->setPagination($viewModel, $blogModel, $blogPosts, $countPosts, array(
            'path' => '',
            'itemLink' => $itemLink
        ));

        $viewModel->setTemplate('blog/posts/status-based-posts');
        return $this->initialize($viewModel);
    }

    public function showAction()
    {
        $permalink = $this->params()->fromRoute('permalink');
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $blogModel = $this->getBlogModel();
        if (empty($permalink) || !($post = $blogModel->getByPermalink($permalink, array(
                'status' => WritingStatus::PUBLISHED,
                'is_reported' => 0,
                'loggedInUser' => $currentUser,
                'withUserReporting' => true,
                'withHidingStatus' => true,
                'withFavoriteStatus' => true,
                'withCommentBlocking' => true
            )))) {
            return $this->redirectForFailure('blog', $this->translate('Post has not been found !!!'));
        }
        $subscriberModel = $this->getSubscribeModel();
        $getEpisode = $blogModel->getOtherEpisodicPosts($post);
        $commentModel = $this->getCommentModel();
        $blogPosts = $blogModel->getOtherPosts($post['user_id'], $post['post_id'], array_merge($this->params()->fromRoute(), array(
            'loggedInUser' => $currentUser,
            'withHidingStatus' => true,
        )));
        $blogPostIds = $blogModel->getPostIds($blogPosts);
        $blogPostIds[] = $post['post_id'];

        $otherPostInfoByUser = array(
            'totalCommentsByUser' => $commentModel->getAllCommentsByUserId($post['post_created_by']),
            'totalLikesByUser' => $subscriberModel->getAllSubscriberByUserId($post['post_created_by']),
            'countBeingFavorite' => $subscriberModel->countBeingSubscribers($post['post_created_by']),
            'countWritingsBeingFavorite' => $subscriberModel->countWritingsOfUser($post['post_created_by']),
        );

        $comments = $commentModel->getByBlogId($post['post_id'], array(
            'loggedInUser' => $currentUser,
            'withUserReporting' => true,
            'withHidingStatus' => true,
            'withFavoriteStatus' => true
        ));

        $activeCompetition = VoteConfig::getActiveCompetition();
        $selectionInfoForCompetition = $this->getPostForVotingModel()->getSelectionStatus($post['post_id'], $activeCompetition);

        $viewModel = new ViewModel(array(
            'post' => $post,
            'comments' => $comments,
            'episode' => $getEpisode,
            'userDetails' => $this->getUserDetail(),
            'professions' => $this->getProfessionModel()->getAll(),
            'blogPost' => $blogPosts,
            'relatedPosts' => $blogModel->getRelatedPosts($post['category_id'], $blogPostIds),
            'otherPostInfoByUser' => $otherPostInfoByUser,
            'friendInfo' => $this->getFriendModel()->setFriendRequestText($currentUser, $post),
            'commentForm' => new Comment(array('translator' => $this->getTranslatorHelper())),
            'reportForm' => new Report(array(
                'messages' => $this->getReportMessageModel()->getAll()
            )),
            'isSelectedForVote' => !empty($selectionInfoForCompetition),
        ));

        if (!empty($selectionInfoForCompetition)) {
            $viewModel->setVariables(array(
                'voteCount' => empty($selectionInfoForCompetition['count']) ? 0 : $selectionInfoForCompetition['count'],
                'isVotingEnabled' => VoteConfig::isVotingRunning($activeCompetition, $selectionInfoForCompetition['episode'])
            ));
        }

        if ($currentUser) {
            $episode = empty($selectionInfoForCompetition['episode']) ? VoteConfig::EPISODE_1 : $selectionInfoForCompetition['episode'];
            $viewModel->setVariables(array(
                'isCheckVoteForUser' => $this->getPostForVotingModel()->checkUserVotePrivilege($post, $currentUser, $activeCompetition, $episode),
            ));

            if ($post['post_created_by'] == $currentUser) {
                $viewModel->setVariable('blockedBloggers', $this->getBlockedUserModel()->getByPost($post['post_id']));
            }
        }

        if ($this->checkBlogPostCookieExpired($post['post_id'])) {
            $blogModel->incrementViewing($post['post_id']);
            $this->setBlogPostCookie($post['post_id']);
        }

        $this->layout()->setVariable('metaInfo', $blogModel->retrieveInfoForSocialMedia($post));
        return $this->initialize($viewModel);
    }

    public function trashPostsAction()
    {
        $permalink = $this->params()->fromRoute('permalink', null);
        $blogDetail = $this->getBlogModel()->getByPermalink($permalink);

        $redirectOptions = array('permalink' => $permalink);
        if (empty($blogDetail)) {
            return $this->redirectForFailure('blog', $this->translate('Post has not been found.'), $redirectOptions);
        } elseif ($blogDetail['post_created_by'] != $this->getSessionContainer()->offsetGet('user_id')) {
            return $this->redirectForFailure('blog', $this->translate('Something went wrong. Please try again.'), $redirectOptions);
        } else {
            $result = $this->getBlogModel()->setTrashedStatus($blogDetail['post_id']);
            if (empty($result)) {
                return $this->redirectForFailure('blog', $this->translate('Something went wrong. Please try again.'), $redirectOptions);
            } else {
                return $this->redirectForSuccess('blog', $this->translate('Blog has been Deleted successfully.'), $redirectOptions);
            }
        }
    }

    public function dequePostAction()
    {
        $blogModel = $this->getBlogModel();
        $blogModel->dequePostIfExists();

        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $options = array_merge($this->params()->fromRoute(), array('loggedInUser' => $currentUser, 'withHidingStatus' => true));
        $latestPosts = $blogModel->getRecentPosts($options);
        $countPosts = $blogModel->countRecentPosts($options);
        $viewModel = new ViewModel(array(
            'recentPosts' => $latestPosts,
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll()
        ));
        $viewModel->setTemplate('blog/posts/queue-post');

        $this->setPagination($viewModel, $blogModel, $latestPosts, $countPosts, array(
            'path' => '',
            'itemLink' => 'blog'
        ));

        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->getResponse()->setContent(Json::encode(array(
                    'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel))
            ));
        } else {
            return $viewModel;
        }
    }

    /**
     * Set some layout values for the given view object.
     *
     * @param   ViewModel $viewModel
     * @return  ViewModel
     */
    protected function initialize(ViewModel $viewModel)
    {
        $blogModel = $this->getBlogModel();
        $commentModel = $this->getCommentModel();
        $viewModel->setVariables(array(
            'recentBlogPosts' => $blogModel->getRecentPosts(null, 5),
            'recentComments' => $commentModel->getRecentComments(null, 5),
            'topBloggers' => $blogModel->getTopBloggers(),
            'topCommentPosters' => $commentModel->getTopCommentPosters(),
            'newBloggers' => $this->getUserModel()->getNewBloggers(),
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll()
        ));

        $this->initializeLayout();
        return $viewModel;
    }

    /**
     * Set cookie for one day.
     *
     * @param $postId
     */
    private function setBlogPostCookie($postId)
    {
//        $serviceManager = $this->getServiceLocator();
//        $configuration = $serviceManager->get('Configuration');
//
//        $sessionConfig = new \Zend\Session\Config\SessionConfig();
//        $sessionConfig->setOptions($configuration['session']);
//        $sessionManager = new \Zend\Session\SessionManager($sessionConfig, null, null);
//        $this->getSessionContainer()->setDefaultManager($sessionManager);
        setcookie($postId . '_post_viewed', time(), time() + 7200);
    }

    /**
     *
     * Determine whether the cookie needs to be expired
     *
     * Returns true, if the cookie has not set or not expired, false otherwise.
     *
     * @param $postId
     * @return bool
     */
    private function checkBlogPostCookieExpired($postId)
    {
        return (empty($_COOKIE[$postId . '_post_viewed']) || ($_COOKIE[$postId . '_post_viewed'] > time()) ? true : false);
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
     * @return \NBlog\Model\PostForVoting
     */
    private function getPostForVotingModel()
    {
        isset($this->postForVotingModel) || $this->postForVotingModel = $this->getServiceLocator()->get('NBlog\Model\PostForVoting');
        return $this->postForVotingModel;
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

    /**
     * @return \BlogUser\Model\BlockedUser
     */
    private function getBlockedUserModel()
    {
        isset($this->blockedUserModel) || $this->blockedUserModel = $this->getServiceLocator()->get('BlogUser\Model\BlockedUser');
        return $this->blockedUserModel;
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
     * @return  \BlogUser\Model\Subscribe
     */
    private function getSubscribeModel()
    {
        isset($this->subscribeModel) || $this->subscribeModel = $this->getServiceLocator()->get('BlogUser\Model\Subscribe');
        return $this->subscribeModel;
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
     * @return \NBlog\Model\WritingStatus
     */
    private function getWritingStatusModel()
    {
        isset($this->writingStatusModel) || $this->writingStatusModel = $this->getServiceLocator()->get('NBlog\Model\WritingStatus');
        return $this->writingStatusModel;
    }

    /**
     * @return \NBlog\Model\ReportMessage
     */
    private function getReportMessageModel()
    {
        isset($this->reportMessageModel) || $this->reportMessageModel = $this->getServiceLocator()->get('NBlog\Model\ReportMessage');
        return $this->reportMessageModel;
    }
}
