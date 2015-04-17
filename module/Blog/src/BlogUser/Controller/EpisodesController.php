<?php
/**
 * Episodes Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;

use BlogUser\Form\Comment;
use BlogUser\Form\Episode;
use BlogUser\Form\Report;
use BlogUser\Model\EpisodeStyle;
use NBlog\Model\Entity\EpisodicPost AS EpisodicPostEntity;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class EpisodesController extends UserBaseController
{
    protected $blockedUserModel;
    protected $commentModel;
    protected $episodeModel;
    protected $episodicPostModel;
    protected $episodicSerialModel;
    protected $categoryModel;
    protected $reportModel;
    protected $writingStatusModel;
    protected $reportMessageModel;

    public function indexAction()
    {
        $options = array_merge($this->params()->fromRoute(), array('user_id' => $this->getSessionContainer()->offsetGet('user_id')));

        $episodeModel = $this->getEpisodeModel();
        $episodes = $episodeModel->getAll($options);
        $countEpisodes = $episodeModel->countAll($options);
        $viewModel = new ViewModel(array(
            'episodes' => $episodes,
            'statuses' => $this->getWritingStatusModel()->getAll(),
            'categories' => $this->getCategoryModel()->getAll()
        ));
        $this->setPagination($viewModel, $episodeModel, $episodes, $countEpisodes, array(
            'path' => '',
            'itemLink' => 'my-episodes'
        ));

        $this->initialize();
        return $viewModel;
    }

    public function showAction()
    {
        $permalink = $this->params()->fromRoute('permalink');
        if (empty($permalink)) {
            return $this->redirectForFailure('my-episodes', $this->translate('Episode data has not been found.'));
        }

        $episode = $this->getEpisodeModel()->getByPermalink($permalink);
        if (empty($episode)) {
            return $this->redirectForFailure('my-episodes', $this->translate('Episodes has been deleted.'));
        } elseif ($episode['user_id'] != $this->getSessionContainer()->offsetGet('user_id')) {
            return $this->redirectForFailure('my-episodes', $this->translate('Episode has not been found.'));
        }

        $this->initialize();
        return new ViewModel(array(
            'episode' => $episode,
            'episodicPosts' => $this->getBlogModel()->getByEpisode($episode['episode_id']),
            'categories' => $this->getCategoryModel()->getAll(),
            'statuses' => $this->getWritingStatusModel()->getAll()
        ));
    }

    public function addAction()
    {
        $blogCategoryModel = $this->getCategoryModel();
        /*        $episodeStyleModel = new EpisodeStyle($this);*/

        $categories = $blogCategoryModel->getAll();
        $episodeForm = new Episode(array(
            'categories' => $blogCategoryModel->getCategoryList($categories),
            'statuses' => $this->getWritingStatusModel()->getSelectedStatuses() /*,
            'styles' => $episodeStyleModel->getList()*/
        ));

        $viewModel = new ViewModel(array(
            'form' => $episodeForm,
            'categories' => $blogCategoryModel->getAllForNavigation($categories)
        ));

        $request = $this->getRequest();
        if ($request->isPost()) {
            $episodeEntity = new \BlogUser\Model\Entity\Episode($this->getServiceLocator());
            $episodeForm->setInputFilter($episodeEntity->getInputFilter());
            $episodeForm->setData($request->getPost());

            if ($episodeForm->isValid()) {
                $data = array_merge($episodeForm->getData(), array('user_id' => $this->getSessionContainer()->offsetGet('user_id')));
                $result = $this->getEpisodeModel()->save($data);
                if (empty($result)) {
                    if ($request->isXmlHttpRequest()) {
                        $viewModel->setVariable('errorMsg', $this->translate('Something went wrong. Please try again.'));
                        $responseData['status'] = 'error';
                    } else {
                        return $this->redirectForFailure('my-episodes', $this->translate('Something went wrong. Please try again.'));
                    }
                } else {
                    if ($request->isXmlHttpRequest()) {
                        $responseData['status'] = 'success';
                        $responseData['msg'] = $this->translate('Episode has been saved successfully.');
                    } else {
                        return $this->redirectForSuccess('my-episodes', $this->translate('Episode has been saved successfully.'));
                    }
                }
            } else {
                if ($request->isXmlHttpRequest()) {
                    $viewModel->setVariable('errorMsg', $this->translate('Please check the following errors.'));
                    $responseData['status'] = 'error';
                } else {
                    $this->setFailureMessage($this->translate('Please check the following errors.'));
                }
            }
        }

        if ($request->isXmlHttpRequest()) {
            $viewModel->setTemplate('blog-user/episodes/add')->setVariables(array('isAjax' => true));
            $responseData['html'] = $this->getServiceLocator()->get('viewRenderer')->render($viewModel);
            return $this->getResponse()->setContent(Json::encode($responseData));
        } else {
            $this->menuItem = 'new-post';
            $this->initialize();
            return $viewModel;
        }
    }

    public function editAction()
    {
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $blogCategoryModel = $this->getCategoryModel();
        /*        $episodeStyleModel = new EpisodeStyle($this);*/

        $categories = $blogCategoryModel->getAll();
        $episodeForm = new Episode(array(
            'categories' => $blogCategoryModel->getCategoryList($categories),
            'statuses' => $this->getWritingStatusModel()->getSelectedStatuses() /*,
            'styles' => $episodeStyleModel->getList()*/
        ));
        $episodeForm->get('submit')->setValue('Update');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $episodeEntity = new \BlogUser\Model\Entity\Episode($this->getServiceLocator());
            $episodeForm->setInputFilter($episodeEntity->getInputFilter());
            $episodeForm->setData($request->getPost());
            if ($episodeForm->isValid()) {
                $data = array_merge($episodeForm->getData(), array('user_id' => $currentUser));
                $episodeDetail = $this->getEpisodeModel()->getDetail($data['episode_id']);

                if ($episodeDetail['user_id'] != $currentUser) {
                    return $this->redirectForFailure('my-episodes', $this->translate('Something went wrong. Please try again.'));
                } else {
                    $result = $this->getEpisodeModel()->modify($data, $data['episode_id']);
                    if (empty($result)) {
                        return $this->redirectForFailure('my-episodes', $this->translate('Something went wrong. Please try again.'));
                    } else {
                        return $this->redirectForSuccess('my-episodes', $this->translate('Episodes has been updated successfully.'));
                    }
                }
            } else {
                $this->setFailureMessage($this->translate('Please check the following errors.'));
            }
        } else {
            $permalink = $this->params()->fromRoute('permalink', null);
            if (empty($permalink)) {
                return $this->redirectForFailure('my-episodes', $this->translate('Episode data has not given.'));
            } else {
                $episodeDetail = $this->getEpisodeModel()->getByPermalink($permalink);
                if (empty($episodeDetail)) {
                    return $this->redirectForFailure('my-episodes', $this->translate('Episode data has not found.'));
                } elseif ($episodeDetail['user_id'] != $currentUser) {
                    return $this->redirectForFailure('my-episodes', $this->translate('You are not permitted to edit this episode.'));
                } else {
                    $episodeForm->setData($episodeDetail);
                    $_POST = $episodeDetail;
                }
            }
        }

        $this->initialize();
        return new ViewModel(array(
            'form' => $episodeForm,
            'categories' => $blogCategoryModel->getAllForNavigation($categories)
        ));
    }

    public function deleteAction()
    {
        $episodePermalink = $this->params()->fromRoute('permalink');
        $episodeDetail = $this->getEpisodeModel()->getByPermalink($episodePermalink);
        if ($episodeDetail['user_id'] != $this->getSessionContainer()->offsetGet('user_id')) {
            return $this->redirectForFailure('my-episodes', $this->translate('You are not permitted to edit this episode.'));
        } else {
            $status = $this->getEpisodeModel()->remove($episodePermalink);
            if ($status) {
                return $this->redirectForSuccess('my-episodes', $this->translate('Episode has been deleted successfully.'));
            } else {
                return $this->redirectForFailure('my-episodes', $this->translate('Episodes went wrong. Please try again'));
            }
        }
    }

    public function blockCommenterAction()
    {
        $sessionContainer = $this->getSessionContainer();
        $username = $this->params()->fromRoute('username', null);
        $request = $this->getRequest();
        if ($username === 'me' || $username === $sessionContainer->offsetGet('username')) {
            $episodeDetail = $this->getEpisodeModel()->getByPermalink($this->params()->fromRoute('permalink', null));
            $userDetail = $this->getUserModel()->getDetailByUsername($this->params()->fromRoute('commenter', null));
            if (empty($episodeDetail) || empty($userDetail)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unknown'), true));
                } else {
                    return $this->redirectForSuccess('profile-home', $this->translate('Something went wrong. Please try again.'));
                }
            } else {
                $blockedUser = $this->getBlockedUserModel();
                $result = $blockedUser->save(array(
                    'writing_id' => $episodeDetail['episode_id'],
                    'blogger_id' => $userDetail['user_id'],
                    'blocked_for' => $blockedUser::FOR_EPISODE
                ));

                if ($request->isXmlHttpRequest()) {
                    if (empty($result)) {
                        $result = array('status' => 'error', 'data' => 'Unknown');
                    } else {
                        $result = array('status' => 'success', 'data' => 'Done');
                    }
                    return $this->getResponse()->setContent(Json::encode($result, true));
                } else {
                    if (empty($result)) {
                        return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
                    } else {
                        return $this->redirectForSuccess('profile-home', $this->translate('Comment has been blocked successfully.'));
                    }
                }
            }
        } else {

            if ($request->isXmlHttpRequest()) {
                return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unauthenticated'), true));
            } else {
                return $this->redirectForSuccess('profile-home', $this->translate('You are not authenticated to do this.'));
            }
        }
    }

    public function unblockCommenterAction()
    {
        $sessionContainer = $this->getSessionContainer();
        $username = $this->params()->fromRoute('username', null);
        $request = $this->getRequest();
        if ($username === 'me' || $username === $sessionContainer->offsetGet('username')) {
            $episodeDetail = $this->getEpisodeModel()->getByPermalink($this->params()->fromRoute('permalink', null));
            $userDetail = $this->getUserModel()->getDetailByUsername($this->params()->fromRoute('commenter', null));
            if (empty($episodeDetail) || empty($userDetail)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unknown'), true));
                } else {
                    return $this->redirectForSuccess('profile-home', $this->translate('Something went wrong. Please try again.'));
                }
            } else {
                $result = $this->getBlockedUserModel()->removeByEpisodeAndUser($episodeDetail['episode_id'], $userDetail['user_id']);

                if ($request->isXmlHttpRequest()) {
                    if (empty($result)) {
                        $result = array('status' => 'error', 'data' => 'Unknown');
                    } else {
                        $result = array('status' => 'success', 'data' => 'Done');
                    }
                    return $this->getResponse()->setContent(Json::encode($result, true));
                } else {
                    if (empty($result)) {
                        return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
                    } else {
                        return $this->redirectForSuccess('profile-home', $this->translate('Comment has been blocked successfully.'));
                    }
                }
            }
        } else {

            if ($request->isXmlHttpRequest()) {
                return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unauthenticated'), true));
            } else {
                return $this->redirectForSuccess('profile-home', $this->translate('You are not authenticated to do this.'));
            }
        }
    }

    public function showPostAction()
    {
        $episodePermalink = $this->params()->fromRoute('episodePermalink');
        $postPermalink = $this->params()->fromRoute('permalink');
        if (empty($episodePermalink) || !($episodeDetail = $this->getEpisodeModel()->getByPermalink($episodePermalink))) {
            return $this->redirectForFailure('my-episodes', $this->translate('Episode data has not found.'));
        } else if (empty($postPermalink) || !($episodicPost = $this->getEpisodicPostModel()->getByPermalink($episodeDetail['episode_id'], $postPermalink))) {
            return $this->redirectForFailure('show-my-episode', $this->translate('Post has not been found.'), array('permalink' => $episodePermalink));
        } elseif (($currentUser = $this->getSessionContainer()->offsetGet('user_id')) != $episodicPost['episode_created_by']) {
            return $this->redirectForFailure('show-my-episode', $this->translate('Post has not been found.'), array('permalink' => $episodePermalink));
        }

        $commentModel = $this->getCommentModel();
        $comments = $commentModel->getByBlogId($episodicPost['post_id']);
        $this->initialize();
        return new ViewModel(array(
            'episodicPost' => $episodicPost,
            'episode' => $episodeDetail,
            'categories' => $this->getCategoryModel()->getAll(),
            'comments' => $comments,
            'reportStatuses' => $this->getReportModel()->getStatusOfComments($currentUser, $commentModel->getCommentIds($comments)),
            'blockedBloggers' => $this->getBlockedUserModel()->getByPost($episodicPost['post_id']),
            'commentForm' => new Comment(array('translator' => $this->getTranslatorHelper())),
            'reportForm' => new Report(array(
                'messages' => $this->getReportMessageModel()->getAll()
            ))
        ));
    }

    public function addPostAction()
    {
        $permalink = $this->params()->fromRoute('episodePermalink');
        if (empty($permalink) || !($episodeDetail = $this->getEpisodeModel()->getByPermalink($permalink))) {
            return $this->redirectForFailure('my-episodes', $this->translate('Episode data has not found.'));
        }

        if ($episodeDetail['episodic_style_id'] != EpisodeStyle::CUSTOM) {
            $currentSerial = $this->getEpisodicSerialModel()->getDetail($episodeDetail['next_episodic_serial_id']);
            if (empty($currentSerial)) {
                return $this->redirectForFailure('show-my-episode', $this->translate('You can not create next episode. Please contact with the administration'), array('permalink' => $permalink));
            }
        }

        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $blogCategoryModel = $this->getCategoryModel();
        $categories = $blogCategoryModel->getAll();
        $episodicPostForm = new \BlogUser\Form\EpisodicPost(array(
            'translator' => $this->getTranslatorHelper(),
            'episode_id' => $episodeDetail['episode_id'],
            'categories' => $blogCategoryModel->getCategoryList($categories),
            'statuses' => $this->getWritingStatusModel()->getSelectedStatuses(),
            'episode_tag' => empty($currentSerial) ? '' : $currentSerial['serial'],
            'tag_readonly' => ($episodeDetail['episodic_style_id'] != EpisodeStyle::CUSTOM)
        ));

        $request = $this->getRequest();
        if ($request->isPost()) {
            $episodicPostEntity = new EpisodicPostEntity($this->getServiceLocator());
            $episodicPostForm->setInputFilter($episodicPostEntity->getInputFilter());
            $episodicPostForm->setData($request->getPost());

            if ($episodicPostForm->isValid()) {
                $data = array_merge($episodicPostForm->getData(), array('user_id' => $userId));
                $data['details'] = $this->ContentImageProcessor()->dealWithImages($this->getEvent(), $data['details'], null);
                $result = $this->getEpisodicPostModel()->save($data);
                if (empty($result)) {
                    $this->setFailureMessage($this->translate('Something went wrong. Please try again.'));
                } else {
                    if ($episodeDetail['episodic_style_id'] != EpisodeStyle::CUSTOM) {
                        $nextSerial = $this->getEpisodicSerialModel()->getNextSerial($episodeDetail['episodic_style_id'], $episodeDetail['next_episodic_serial_id']);
                        $this->getEpisodeModel()->updateNextSerial($nextSerial['episodic_serial_id'], $episodeDetail['episode_id']);
                    }
                    return $this->redirectForSuccess('show-my-episode', $this->translate('Episodic post has been saved successfully.'), array('permalink' => $permalink));
                }
            } else {
                $this->setFailureMessage($this->translate('Please check the following errors.'));
            }
        }

        $this->menuItem = 'new-episode';
        $this->initialize();
        return new ViewModel(array(
            'form' => $episodicPostForm,
            'episodePermalink' => $permalink,
            'categories' => $blogCategoryModel->getAllForNavigation($categories)
        ));
    }

    public function editPostAction()
    {
        $episodePermalink = $this->params()->fromRoute('episodePermalink');
        $postPermalink = $this->params()->fromRoute('permalink');
        if (empty($episodePermalink) || !($episodeDetail = $this->getEpisodeModel()->getByPermalink($episodePermalink))) {
            return $this->redirectForFailure('my-episodes', $this->translate('Episode data has not found.'));
        }

        $blogCategoryModel = $this->getCategoryModel();
        $categories = $blogCategoryModel->getAll();
        $episodicPostForm = new \BlogUser\Form\EpisodicPost(array(
            'translator' => $this->getTranslatorHelper(),
            'episode_id' => $episodeDetail['episode_id'],
            'categories' => $blogCategoryModel->getCategoryList($categories),
            'statuses' => $this->getWritingStatusModel()->getSelectedStatuses(),
            'episode_tag' => '',
            'tag_readonly' => ($episodeDetail['episodic_style_id'] != EpisodeStyle::CUSTOM)
        ));

        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $episodicPostEntity = new EpisodicPostEntity($this->getServiceLocator());
            $episodicPostForm->setInputFilter($episodicPostEntity->getInputFilter());
            $episodicPostForm->setData($request->getPost());

            if ($episodicPostForm->isValid()) {
                $data = array_merge($episodicPostForm->getData(), array('user_id' => $currentUser));
                $blogData = $this->getEpisodicPostModel()->getDetail($data['post_id']);
                if ($blogData['user_id'] != $currentUser) {
                    return $this->redirectForFailure('my-episodes', $this->translate('Something went wrong. Please try again.'));
                } else {
                    unset($data['user_id']);
                    if ($episodeDetail['episodic_style_id'] != EpisodeStyle::CUSTOM) {
                        unset($data['episode_tag']);
                    }
                    $imageProcessor = $this->ContentImageProcessor();
                    $data['details'] = $imageProcessor->dealWithImages($this->getEvent(), $data['details'], null, true);
                    $imageProcessor->removeImagesFromText($blogData['details'], $data['details']);
                    $result = $this->getEpisodicPostModel()->modify(array_merge($data, array('old_status' => $blogData['status'])), $data['post_id']);
                    if (empty($result)) {
                        $this->setFailureMessage($this->translate('Something went wrong. Please try again.'));
                    } else {
                        return $this->redirectForSuccess('show-my-episode', $this->translate('Episodic post has been updated successfully.'), array('permalink' => $episodePermalink));
                    }
                }
            } else {
                $this->setFailureMessage('Please check the following errors.');
            }
        } else if (empty($postPermalink) || !($episodicPost = $this->getEpisodicPostModel()->getByPermalink($episodeDetail['episode_id'], $postPermalink))) {
            return $this->redirectForFailure('show-my-episode', $this->translate('Post has not been found.'), array('permalink' => $episodePermalink));
        } elseif ($currentUser != $episodicPost['episode_created_by']) {
            return $this->redirectForFailure('show-my-episode', $this->translate('Post has not been found.'), array('permalink' => $episodePermalink));
        } else {
            $episodicPostForm->setData($episodicPost);
            $_POST = $episodicPost;
        }

        $this->menuItem = 'new-episode';
        $this->initialize();
        return new ViewModel(array(
            'form' => $episodicPostForm,
            'episodePermalink' => $episodePermalink,
            'postPermalink' => $postPermalink,
            'categories' => $blogCategoryModel->getAllForNavigation($categories)
        ));
    }

    public function trashPostAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            $episodePermalink = $this->params()->fromRoute('episodePermalink');
            $postPermalink = $this->params()->fromRoute('permalink');
            if (empty($episodePermalink) || !($episodeDetail = $this->getEpisodeModel()->getByPermalink($episodePermalink))) {
                $result = array('status' => 'error', 'data' => 'Episode data has not found.');
            } else if (empty($postPermalink) || !($episodicPost = $this->getEpisodicPostModel()->getByPermalink($episodeDetail['episode_id'], $postPermalink))) {
                $result = array('status' => 'error', 'data' => 'Episodic post data has not found.');
            } elseif ($this->getSessionContainer()->offsetGet('user_id') != $episodicPost['episode_created_by']) {
                $result = array('status' => 'error', 'data' => 'You are not authenticated to do this.');
            } elseif ($this->getEpisodicPostModel()->setTrashedStatus($episodicPost['post_id'])) {
                $this->setSuccessMessage($this->translate('Episodic post has been trashed successfully.'));
                $result = array('status' => 'success');
            } else {
                $result = array('status' => 'error', 'data' => 'Something went wrong. Please try again.');
            }
            return $this->getResponse()->setContent(Json::encode($result, true));
        } else {
            return $this->redirectForFailure('my-episodes', $this->translate('You are not authenticated.'));
        }
    }

    public function restorePostAction()
    {
        $episodePermalink = $this->params()->fromRoute('episodePermalink');
        $postPermalink = $this->params()->fromRoute('permalink');
        if (empty($episodePermalink) || !($episodeDetail = $this->getEpisodeModel()->getByPermalink($episodePermalink))) {
            return $this->redirectForFailure('my-episodes', $this->translate('Episode data has not found.'));
        } else if (empty($postPermalink) || !($episodicPost = $this->getEpisodicPostModel()->getByPermalink($episodeDetail['episode_id'], $postPermalink))) {
            return $this->redirectForFailure('show-my-episode', $this->translate('Episodic post has not been found.'), array('permalink' => $episodePermalink));
        } elseif (($currentUser = $this->getSessionContainer()->offsetGet('user_id')) != $episodicPost['episode_created_by']) {
            return $this->redirectForFailure('show-my-episodic-post', $this->translate('Post has not been found.'), array('episodePermalink' => $episodePermalink, 'permalink' => $postPermalink));
        } elseif ($this->getEpisodicPostModel()->setDraftStatus($episodicPost['post_id'])) {
            return $this->redirectForSuccess('show-my-episodic-post', $this->translate('Episodic post has been restored successfully.'), array('episodePermalink' => $episodePermalink, 'permalink' => $postPermalink));
        } else {
            return $this->redirectForFailure('show-my-episodic-post', $this->translate('Something went wrong. Please try again.'), array('episodePermalink' => $episodePermalink, 'permalink' => $postPermalink));
        }
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
     * @return  \BlogUser\Model\Episode
     */
    protected function getEpisodeModel()
    {
        isset($this->episodeModel) || $this->episodeModel = $this->getServiceLocator()->get('BlogUser\Model\Episode');
        return $this->episodeModel;
    }

    /**
     * @return \BlogUser\Model\EpisodicPost
     */
    protected function getEpisodicPostModel()
    {
        isset($this->episodicPostModel) || $this->episodicPostModel = $this->getServiceLocator()->get('BlogUser\Model\EpisodicPost');
        return $this->episodicPostModel;
    }

    /**
     * @return  \BlogUser\Model\EpisodeSerial
     */
    protected function getEpisodicSerialModel()
    {
        isset($this->episodicSerialModel) || $this->episodicSerialModel = $this->getServiceLocator()->get('BlogUser\Model\EpisodeSerial');
        return $this->episodicSerialModel;
    }

    /**
     * @return \BlogUser\Model\Report
     */
    private function getReportModel()
    {
        isset($this->reportModel) || $this->reportModel = $this->getServiceLocator()->get('BlogUser\Model\Report');
        return $this->reportModel;
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