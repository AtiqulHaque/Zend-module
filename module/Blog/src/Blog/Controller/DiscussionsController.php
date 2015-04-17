<?php
/**
 * Discussions Controller
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
use NBlog\Model\ReportStatus;
use Zend\View\Model\ViewModel;
use NBlog\Model\WritingStatus;

class DiscussionsController extends BaseController
{
    protected $blogModel;
    protected $blockedUserModel;
    protected $categoryModel;
    protected $commentModel;
    protected $discussionModel;
    protected $hiddenModel;
    protected $imageModel;
    protected $professionModel;
    protected $reportMessageModel;

    public function indexAction()
    {
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $options = array_merge($this->params()->fromRoute(), array('loggedInUser' => $currentUser, 'withHidingStatus' => true));
        $latestDiscussion = $this->getDiscussionModel()->getLatestDiscussion($options);
        $discussionIds = $this->getDiscussionModel()->getDiscussionIds($latestDiscussion);

        $viewModel = new viewModel(array(
            'discussion' => $this->getDiscussionModel()->getAllDiscussion($options),
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll(),
            'latestDiscussion' => $latestDiscussion,
            'oldDiscussion' => $this->getDiscussionModel()->getOldDiscussions($options, $discussionIds),
        ));
        return $this->initialize($viewModel);
    }

    public function showAction()
    {
        $permalink = $this->params()->fromRoute('permalink');
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        if (empty($permalink) || !($discussion = $this->getDiscussionModel()->getByPermalink($permalink, array(
                'status' => WritingStatus::PUBLISHED,
                'is_reported' => ReportStatus::NO_REPORT,
                'loggedInUser' => $currentUser,
                'withUserReporting' => true,
                'withHidingStatus' => true,
                'withFavoriteStatus' => true,
                'withCommentBlocking' => true,
                'withCategories' => true,
                'withUserDetail' => true
            )))) {
            return $this->redirectForFailure('blog', $this->translate('Discussion data has not been found.'));
        }
        $comments = $this->getCommentModel()->getByDiscussionId($discussion['discussion_id'], array(
            'loggedInUser' => $currentUser,
            'withUserReporting' => true,
            'withHidingStatus' => true,
            'withFavoriteStatus' => true
        ));

        $discussions = $this->getDiscussionModel()->getOtherDiscussions($discussion['user_id'], $discussion['discussion_id'], array_merge(
            $this->params()->fromRoute(), array('loggedInUser' => $currentUser, 'withHidingStatus' => true)
        ));
        $discussionIds = $this->getDiscussionModel()->getDiscussionIds($discussions);
        $discussionIds[] = $discussion['discussion_id'];

        $viewModel = new ViewModel(array(
            'discussion' => $discussion,
            'comments' => $comments,
            'professions' => $this->getProfessionModel()->getAll(),
            'otherDiscussions' => $discussions,
            'categories' => $this->getCategoryModel()->getAll(),
            'relatedPosts' => $this->getDiscussionModel()->getRelatedDiscussions($discussion['category_id'], $discussionIds, array_merge(
                $this->params()->fromRoute(), array('loggedInUser' => $currentUser, 'withHidingStatus' => true)
            )),
            'friendInfo' => $this->getFriendModel()->setFriendRequestText($currentUser, $discussion),
            'commentForm' => new Comment(array('translator' => $this->getTranslatorHelper())),
            'reportForm' => new Report(array(
                'messages' => $this->getReportMessageModel()->getAll()
            ))
        ));

        if ($currentUser) {
            if ($discussion['discussion_created_by'] == $currentUser) {
                $viewModel->setVariable('blockedBloggers', $this->getBlockedUserModel()->getByPost($discussion['discussion_id']));
            }
        }
        $this->layout()->setVariables(array(
            'metaInfo' => array(
                'title' => $discussion['title'],
                'description' => $this->getServiceLocator()->get('viewHelperManager')->get('Text')->word_limiter(strip_tags($discussion['details']), 100),
                'author' => $discussion['nickname']
            )
        ));

        if ($this->checkDiscussionCookieExpired($discussion['discussion_id'])) {
            $this->getDiscussionModel()->incrementViewing($discussion['discussion_id']);
            $this->setDiscussionCookie($discussion['discussion_id']);
        }
        return $this->initialize($viewModel);
    }

    public function deleteAction()
    {
        $permalink = $this->params()->fromRoute('permalink');
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $redirectOptions = array('permalink' => $permalink);
        if (empty($permalink) || !($discussionDetail = $this->getDiscussionModel()->getByPermalink($permalink))) {
            return $this->redirectForFailure('blog', $this->translate('Discussion has not been found.'), $redirectOptions);
        } elseif ($discussionDetail['discussion_created_by'] != $this->getSessionContainer()->offsetGet('user_id')) {
            return $this->redirectForFailure('blog', $this->translate('Something went wrong. Please try again.'), $redirectOptions);
        } else {
            $result = $this->getDiscussionModel()->remove($discussionDetail['discussion_id']);
            if (empty($result)) {
                return $this->redirectForFailure('blog', $this->translate('Discussions went wrong. Please try again'));
            } else {
                $imageProcessor = $this->ContentImageProcessor();
                $removeFiles = $imageProcessor->extractAllImages($discussionDetail['details']);
                if(!empty($removeFiles)){
                    $removeFiles = $this->getImageModel()->deleteImage(array_merge($removeFiles,array('user_id'=>$userId,'id_of_image_for'=>$discussionDetail['discussion_id'])));
                    $imageProcessor->removeImages($userId,$removeFiles);
                }
                return $this->redirectForSuccess('blog', $this->translate('Discussion has been deleted successfully.'));
            }
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
     * @param $discussionId
     */
    private function setDiscussionCookie($discussionId)
    {
//        $serviceManager = $this->getServiceLocator();
//        $configuration = $serviceManager->get('Configuration');
//
//        $sessionConfig = new \Zend\Session\Config\SessionConfig();
//        $sessionConfig->setOptions($configuration['session']);
//        $sessionManager = new \Zend\Session\SessionManager($sessionConfig, null, null);
//        $this->getSessionContainer()->setDefaultManager($sessionManager);
        setcookie($discussionId . '_discussion_viewed', time(), time() + 7200);
    }

    /**
     *
     * Determine whether the cookie needs to be expired
     *
     * Returns true, if the cookie has not set or not expired, false otherwise.
     *
     * @param $discussionId
     * @return bool
     */
    private function checkDiscussionCookieExpired($discussionId)
    {
        return (empty($_COOKIE[$discussionId . '_discussion_viewed']) || ($_COOKIE[$discussionId . '_discussion_viewed'] > time()) ? true : false);
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
     * @return \BlogUser\Model\BlockedUser
     */
    private function getBlockedUserModel()
    {
        isset($this->blockedUserModel) || $this->blockedUserModel = $this->getServiceLocator()->get('BlogUser\Model\BlockedUser');
        return $this->blockedUserModel;
    }

    /**
     * @return \BlogUser\Model\Discussion
     */
    protected function getDiscussionModel()
    {
        isset($this->discussionModel) || $this->discussionModel = $this->getServiceLocator()->get('BlogUser\Model\Discussion');
        return $this->discussionModel;
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
     * @return \NBlog\Model\Image
     */
    private function getImageModel()
    {
        isset($this->imageModel) || $this->imageModel = $this->getServiceLocator()->get('NBlog\Model\Image');
        return $this->imageModel;
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
     * @return \NBlog\Model\ReportMessage
     */
    private function getReportMessageModel()
    {
        isset($this->reportMessageModel) || $this->reportMessageModel = $this->getServiceLocator()->get('NBlog\Model\ReportMessage');
        return $this->reportMessageModel;
    }
}
