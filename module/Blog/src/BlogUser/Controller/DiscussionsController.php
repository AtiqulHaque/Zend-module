<?php
/**
 * Discussions Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;

use BlogUser\Form\Comment;
use BlogUser\Form\Discussion;
use BlogUser\Form\Report;
use NBlog\Model\ImageConfig;
use NBlog\Model\ImageUsagesType;
use BlogUser\Model\Notification;
use Zend\Json\Json;
use Zend\View\Model\ViewModel;
use NBlog\Model\WritingStatus;

class DiscussionsController extends UserBaseController
{
    protected $blockedUserModel;
    protected $categoryModel;
    protected $commentModel;
    protected $discussionModel;
    protected $imageModel;
    protected $writingStatusModel;
    protected $reportMessageModel;
    protected $menuItem = 'discussion';

    public function indexAction()
    {
        $options = array_merge($this->params()->fromRoute(), array('user_id' => $this->getSessionContainer()->offsetGet('user_id')));

        $discussionModel = $this->getDiscussionModel();
        $discussions = $discussionModel->getAll($options);
        $countDiscussions = $discussionModel->countAll($options);
        $viewModel = new ViewModel(array(
            'discussions' => $discussions,
            'statuses' => $this->getWritingStatusModel()->getAll(),
            'categories' => $this->getCategoryModel()->getAll()
        ));
        $this->setPagination($viewModel, $discussionModel, $discussions, $countDiscussions, array(
            'path' => '',
            'itemLink' => 'my-discussions'
        ));

        $this->initialize();
        return $viewModel;
    }

    public function showAction()
    {
        $permalink = $this->params()->fromRoute('permalink');
        if (empty($permalink)) {
            return $this->redirectForFailure('my-discussions', $this->translate('Discussion data has not been found.'));
        }

        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        if (empty($permalink) || !($discussion = $this->getDiscussionModel()->getByPermalink($permalink, array(
                'loggedInUser' => $currentUser,
                'withUserReporting' => true,
                'withHidingStatus' => true,
                'withFavoriteStatus' => true,
                'withCommentBlocking' => true,
                'withCategories' => true,
                'withUserDetail' => true
            )))) {
            return $this->redirectForFailure('my-discussions', $this->translate('Discussion has not been found.'));
        }

        $comments = $this->getCommentModel()->getByDiscussionId($discussion['discussion_id'], array(
            'loggedInUser' => $currentUser,
            'withUserReporting' => true,
            'withHidingStatus' => true,
            'withFavoriteStatus' => true
        ));
        $this->initialize();
        $viewModel = new ViewModel(array(
            'discussion' => $discussion,
            'categories' => $this->getCategoryModel()->getAll(),
            'comments' => $comments,
            'commentForm' => new Comment(array('translator' => $this->getTranslatorHelper())),
            'reportForm' => new Report(array(
                'messages' => $this->getReportMessageModel()->getAll()
            ))
        ));

        if ($currentUser == $discussion['discussion_created_by']) {
            $viewModel->setVariable('blockedBloggers', $this->getBlockedUserModel()->getByDiscussionId($discussion['discussion_id']));
        }

        return $viewModel;
    }

    public function addAction()
    {
        $blogCategoryModel = $this->getCategoryModel();
        $categories = $blogCategoryModel->getAll();
        $discussionForm = new Discussion(array(
            'translator' => $this->getTranslatorHelper(),
            'categories' => $blogCategoryModel->getCategoryList($categories),
            'statuses' => $this->getWritingStatusModel()->getSelectedStatuses()
        ));

        $viewModel = new ViewModel(array(
            'form' => $discussionForm,
            'categories' => $blogCategoryModel->getAllForNavigation($categories)
        ));

        $request = $this->getRequest();
        if ($request->isPost()) {
            $discussionEntity = new \BlogUser\Model\Entity\Discussion($this->getServiceLocator());
            $discussionForm->setInputFilter($discussionEntity->getInputFilter());
            $discussionForm->setData($request->getPost());

            if ($discussionForm->isValid()) {
                $data = array_merge($discussionForm->getData(), array('user_id' => $this->getSessionContainer()->offsetGet('user_id')));
                $imgInfo        = $this->ContentImageProcessor()->dealWithImages($this->getEvent(), $data['details'], ImageConfig::DISCUSSION);
                $data           = array_merge($data,array('details'=>$imgInfo['details']));
                $discussionId = $this->getDiscussionModel()->save($data);
                if (empty($discussionId)) {
                    if ($request->isXmlHttpRequest()) {
                        $viewModel->setVariable('errorMsg', $this->translate('Something went wrong. Please try again.'));
                        $responseData['status'] = 'error';
                    } else {
                        return $this->redirectForFailure('my-discussions', $this->translate('Something went wrong. Please try again.'));
                    }
                } else {
                    if(!empty($imgInfo['images'])){
                        $imgInfo = array_merge($imgInfo,array(
                            'id_of_image_for'   =>  $discussionId,
                            'user_id'           =>  $data['user_id'],
                            "usages_type"       =>  ImageUsagesType::DISCUSSION));

                        $this->getImageModel()->saveContentImage($imgInfo);
                    }

                    if ($data['status'] != WritingStatus::DRAFT) {
                        $data['writing_id'] = $discussionId;
                        $this->getNotificationUserModel()->saveForWritingPublishing($data, Notification::DISCUSSION_PUBLISH);
                    }
                    if ($request->isXmlHttpRequest()) {
                        $responseData['status'] = 'success';
                        $responseData['msg'] = $this->translate('Discussion has been saved successfully.');
                    } else {
                        return $this->redirectForSuccess('my-discussions', $this->translate('Discussion has been saved successfully.'));
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
            $viewModel->setTemplate('blog-user/discussions/add')->setVariables(array('isAjax' => true));
            $responseData['html'] = $this->getServiceLocator()->get('viewRenderer')->render($viewModel);
            return $this->getResponse()->setContent(Json::encode($responseData));
        } else {
            $this->menuItem = 'new-discussion';
            $this->initialize();
            return $viewModel;
        }
    }

    public function editAction()
    {
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $blogCategoryModel = $this->getCategoryModel();
        $categories = $blogCategoryModel->getAll();
        $discussionForm = new Discussion(array(
            'translator' => $this->getTranslatorHelper(),
            'categories' => $blogCategoryModel->getCategoryList($categories),
            'statuses' => $this->getWritingStatusModel()->getSelectedStatuses()
        ));
        $discussionForm->get('submit')->setValue('Update');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $imageModel = $this->getImageModel();
            $discussionEntity = new \BlogUser\Model\Entity\Discussion($this->getServiceLocator());
            $discussionForm->setInputFilter($discussionEntity->getInputFilter());
            $discussionForm->setData($request->getPost());
            if ($discussionForm->isValid()) {
                $data = array_merge($discussionForm->getData(), array('user_id' => $userId));
                $discussionDetail = $this->getDiscussionModel()->getDetail($data['discussion_id']);
                if ($discussionDetail['user_id'] != $userId) {
                    return $this->redirectForFailure('my-discussions', $this->translate('Something went wrong. Please try again.'));
                } else {
                    $imageProcessor = $this->ContentImageProcessor();
                    $imgInfo = $imageProcessor->dealWithImages($this->getEvent(), $data['details'], ImageConfig::DISCUSSION, true);
                    if(!empty($imgInfo['images'])){
                        $imgInfo = array_merge($imgInfo,array("usages_type" =>  ImageUsagesType::DISCUSSION,'id_of_image_for'=>$data['discussion_id'],'user_id'=>$data['user_id']));
                        $imageModel->saveContentImage($imgInfo);
                    }
                    $removeFiles = $imageProcessor->removeImagesFromText($discussionDetail['details'], $data['details']);
                    if(!empty($removeFiles)){
                        $removeFiles = $imageModel->deleteImage(array_merge($removeFiles,array('user_id'=>$userId,'id_of_image_for'=>$data['discussion_id'])));
                        $imageProcessor->removeImages($userId,$removeFiles);
                    }
                    $result = $this->getDiscussionModel()->modify(array_merge($data, array('old_status' => $discussionDetail['status'],'details'=>$imgInfo['details'])), $data['discussion_id']);
                    if (empty($result)) {
                        return $this->redirectForFailure('my-discussions', $this->translate('Something went wrong. Please try again.'));
                    } else {
                        return $this->redirectForSuccess('my-discussions', $this->translate('Discussions has been updated successfully.'));
                    }
                }
            } else {
                $this->setFailureMessage($this->translate('Please check the following errors.'));
            }
        } else {
            $permalink = $this->params()->fromRoute('permalink', null);
            if (empty($permalink)) {
                return $this->redirectForFailure('my-discussions', $this->translate('Discussion data has not given.'));
            } else {
                $discussionDetail = $this->getDiscussionModel()->getByPermalink($permalink, array('withCategories' => true));
                if (empty($discussionDetail)) {
                    return $this->redirectForFailure('my-discussions', $this->translate('Discussion data has not found.'));
                } elseif ($discussionDetail['discussion_created_by'] != $userId) {
                    return $this->redirectForFailure('my-discussions', $this->translate('You are not permitted to edit this discussion.'));
                } else {
                    $discussionForm->setData($discussionDetail);
                    $_POST = $discussionDetail;
                }
            }
        }

        $this->initialize();
        return new ViewModel(array(
            'form' => $discussionForm,
            'categories' => $blogCategoryModel->getAllForNavigation($categories)
        ));
    }

    public function deleteAction()
    {
        $permalink = $this->params()->fromRoute('permalink');
        $discussionDetail = $this->getDiscussionModel()->getByPermalink($permalink);
        $userId = $this->getSessionContainer()->offsetGet('user_id');

        $redirectOptions = array('permalink' => $permalink);
        if (empty($discussionDetail)) {
            return $this->redirectForFailure('view-my-post', $this->translate('Post has not been found.'), $redirectOptions);
        } elseif ($discussionDetail['post_created_by'] != $this->getSessionContainer()->offsetGet('user_id')) {
            return $this->redirectForFailure('view-my-post', $this->translate('Something went wrong. Please try again.'), $redirectOptions);
        } else {
            $result = $this->getDiscussionModel()->remove($discussionDetail['discussion_id']);
            if (empty($result)) {
                return $this->redirectForFailure('my-discussions', $this->translate('Discussions went wrong. Please try again'));
            } else {
                $imageProcessor = $this->ContentImageProcessor();
                $removeFiles = $imageProcessor->extractAllImages($discussionDetail['details']);
                if(!empty($removeFiles)){
                    $removeFiles = $this->getImageModel()->deleteImage(array_merge($removeFiles,array('user_id'=>$userId,'id_of_image_for'=>$discussionDetail['discussion_id'])));
                    $imageProcessor->removeImages($userId,$removeFiles);
                }
                return $this->redirectForSuccess('my-discussions', $this->translate('Discussion has been deleted successfully.'));
            }
        }
    }

    public function blockCommenterAction()
    {
        $sessionContainer = $this->getSessionContainer();
        $username = $this->params()->fromRoute('username', null);
        $request = $this->getRequest();
        if ($username === 'me' || $username === $sessionContainer->offsetGet('username')) {
            $discussionDetail = $this->getDiscussionModel()->getByPermalink($this->params()->fromRoute('permalink', null));
            $userDetail = $this->getUserModel()->getDetailByUsername($this->params()->fromRoute('commenter', null));
            if (empty($discussionDetail) || empty($userDetail)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unknown'), true));
                } else {
                    return $this->redirectForSuccess('profile-home', $this->translate('Something went wrong. Please try again.'));
                }
            } else {
                $blockedUserModel = $this->getBlockedUserModel();
                $result = $blockedUserModel->save(array(
                    'writing_id' => $discussionDetail['discussion_id'],
                    'blogger_id' => $userDetail['user_id'],
                    'blocked_for' => $blockedUserModel::FOR_DISCUSSION
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
            $discussionDetail = $this->getDiscussionModel()->getByPermalink($this->params()->fromRoute('permalink', null));
            $userDetail = $this->getUserModel()->getDetailByUsername($this->params()->fromRoute('commenter', null));
            if (empty($discussionDetail) || empty($userDetail)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unknown'), true));
                } else {
                    return $this->redirectForSuccess('profile-home', $this->translate('Something went wrong. Please try again.'));
                }
            } else {
                $result = $this->getBlockedUserModel()->removeByDiscussionAndUser($discussionDetail['discussion_id'], $userDetail['user_id']);

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

    /**
     * @return \BlogUser\Model\BlockedUser
     */
    private function getBlockedUserModel()
    {
        isset($this->blockedUserModel) || $this->blockedUserModel = $this->getServiceLocator()->get('BlogUser\Model\BlockedUser');
        return $this->blockedUserModel;
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
     * @return \BlogUser\Model\Discussion
     */
    protected function getDiscussionModel()
    {
        isset($this->discussionModel) || $this->discussionModel = $this->getServiceLocator()->get('BlogUser\Model\Discussion');
        return $this->discussionModel;
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