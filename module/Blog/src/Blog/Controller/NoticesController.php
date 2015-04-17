<?php
/**
 * Notices Controller
 *
 * @category        Controller
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Controller;

use BlogUser\Form\Comment;
use Zend\View\Model\ViewModel;
use BlogUser\Form\Report;

class NoticesController extends BaseController
{
    protected $blogModel;
    protected $commentModel;
    protected $noticeModel;
    protected $professionModel;
    protected $reportMessageModel;

    public function indexAction()
    {
        $options = array_merge($this->params()->fromRoute(), array('user_logged_in' => $this->getSessionContainer()->offsetGet('user_id')));
        $noticeModel = $this->getNoticeModel();
        $notices = $noticeModel->getAll($options);
        $rowCount = $noticeModel->countAll($options);

        $viewModel = new ViewModel(array(
            'notices' => $notices
        ));

        $this->setPagination($viewModel, $noticeModel, $notices, $rowCount);
        $this->initialize($viewModel);
        return $viewModel;
    }

    public function showAction()
    {
        $permalink = $this->params()->fromRoute('permalink');
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        if (empty($permalink) || !($notice = $this->getNoticeModel()->getByPermalink($permalink, array(
                'loggedInUser' => $currentUser,
                'withHidingStatus' => true,
                'withFavoriteStatus' => true,
                'withCommentBlocking' => true
            )))) {
            return $this->redirectForFailure('blog', $this->translate('Notice has not been found.'));
        }

        $comments = $this->getCommentModel()->getByNoticeId($notice['notice_id'], array(
            'loggedInUser' => $currentUser,
            'withUserReporting' => true,
            'withHidingStatus' => true,
            'withFavoriteStatus' => true
        ));
        $viewModel = new ViewModel(array(
            'notice' => $notice,
            'comments' => $comments,
            'professions' => $this->getProfessionModel()->getAll(),
            'commentForm' => new Comment(array('translator' => $this->getTranslatorHelper())),
            'reportForm' => new Report(array(
                'messages' => $this->getReportMessageModel()->getAll()
            ))
        ));

        $this->initialize($viewModel);
        return $viewModel;
    }

    /**
     * Set some layout values for the given view object.
     *
     * @param   ViewModel $viewModel
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
            'professions' => $this->getProfessionModel()->getAll()
        ));

        $this->initializeLayout();
    }

    protected function getPaginationOptions()
    {
        return array(
            'path' => '',
            'itemLink' => 'all-active-notices'
        );
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
