<?php
/**
 * Comments Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;

use BlogUser\Form\Comment;
use NBlog\Model\ImageConfig;
use NBlog\Model\ImageUsagesType;
use Zend\Json\Json;
use Zend\View\Model\ViewModel;

class CommentsController extends UserBaseController
{
    protected $commentModel;
    protected $categoryModel;
    protected $imageModel;
    protected $professionModel;
    protected $reportModel;
    protected $reportMessageModel;
    protected $userHelperModel;
    protected $menuItem = 'comment';

    public function indexAction()
    {
        return $this->redirectNow('all-comments-in-my-posts');
    }

    public function allCommentsInMyPostsAction()
    {
        return $this->handlePostsComments('all-comments-in-my-posts');
    }

    public function myCommentsInPostsAction()
    {
        return $this->handlePostsComments('my-comments-in-posts');
    }

    private function handlePostsComments($action)
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('User has not been found.'));
        }

        $commentModel = $this->getCommentModel();
        $viewModel = new ViewModel();

        if ($action == 'all-comments-in-my-posts') {
            $comments = $commentModel->getRecentCommentsOnPostOfUserId($userDetail['user_id'], $this->params()->fromRoute());
            $countComments = $commentModel->countRecentCommentsOnPostOfUserId($userDetail['user_id']);
            $this->setPagination($viewModel, $commentModel, $comments, $countComments, array(
                'path' => '',
                'itemLink' => 'all-comments-in-my-posts'
            ));
            $viewModel->setVariable('pageTitle', $this->translate('Comments in my posts'));

        } else if ($action == 'my-comments-in-posts') {
            $comments = $commentModel->getUserCommentsOnPosts($userDetail['user_id'], $this->params()->fromRoute());
            $countComments = $commentModel->countUserCommentsOnPosts($userDetail['user_id']);
            $this->setPagination($viewModel, $commentModel, $comments, $countComments, array(
                'path' => '',
                'itemLink' => 'my-comments-in-posts'
            ));
            $viewModel->setVariable('pageTitle', $this->translate('My comments in posts'));

        } else {
            return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
        }

        $viewModel->setVariables(array(
            'comments' => $comments,
            'reportStatuses' => $this->getReportModel()->getStatusOfComments($userDetail['user_id'], $commentModel->getCommentIds($comments)),
        ));
        $this->initialize(null, $userDetail);
        $viewModel->setTemplate('blog-user/comments/handle-posts-comments');
        return $viewModel;
    }

    public function repliesOfMyCommentsAction()
    {
        $userDetail = $this->getUserDetail();

        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('User has not been found.'));
        }

        $commentModel = $this->getCommentModel();
        $viewModel = new ViewModel();
        $comments = $commentModel->getRepliesOfUserComments($userDetail['user_id'], $this->params()->fromRoute());
        $countComments = $commentModel->countRepliesOfUserComments($userDetail['user_id']);
        $this->setPagination($viewModel, $commentModel, $comments, $countComments, array(
            'path' => '',
            'itemLink' => 'replies-of-my-comments'
        ));

        $viewModel->setVariable('comments', $comments);
        $this->initialize(null, $userDetail);
        return $viewModel;
    }

    public function myRepliesOfCommentsAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('User has not been found.'));
        }

        $commentModel = $this->getCommentModel();
        $viewModel = new ViewModel();
        $comments = $commentModel->getMyRepliesOfComments($userDetail['user_id'], $this->params()->fromRoute());
        $countComments = $commentModel->countMyRepliesOfComments($userDetail['user_id']);
        $this->setPagination($viewModel, $commentModel, $comments, $countComments, array(
            'path' => '',
            'itemLink' => 'my-replies-of-comments'
        ));

        $viewModel->setVariable('comments', $comments);
        $this->initialize(null, $userDetail);
        return $viewModel;
    }

    public function addCommentAction()
    {
        $permalink = $this->params()->fromRoute('permalink', null);
        $blogDetail = $this->getBlogModel()->getByPermalink($permalink);
        if (empty($blogDetail)) {
            return $this->redirectForFailure('profile-home', $this->translate('Post has not been found.'));
        }

        $sessionContainer = $this->getSessionContainer();
        $commentForm = new Comment(array('translator' => $this->getTranslatorHelper()));
        $request = $this->getRequest();

        if ($request->isPost()) {
            $commentEntity = new \BlogUser\Model\Entity\Comment($this->getServiceLocator());
            $commentForm->setInputFilter($commentEntity->getInputFilter());
            $commentForm->setData($request->getPost());

            if ($commentForm->isValid()) {
                $data = array_merge($commentForm->getData(), array(
                    'user_id' => $sessionContainer->offsetGet('user_id'),
                    'post_id' => $blogDetail['post_id']
                ));

                $result = $this->getCommentModel()->save($data);
                if (empty($result)) {
                    $this->setFailureMessage($this->translate('Something went wrong. Please try again.'));
                } else {
                    $this->setSuccessMessage($this->translate('Comment has been saved successfully.'));
                }

                return $this->redirectNow('view-my-post', array('permalink' => $permalink));
            } else {
                $this->setFailureMessage($this->translate('Please check the following error'));
            }
        }

        $userDetail = $this->getUserModel()->getDetailHavingProfile($sessionContainer->offsetGet('user_id'), true);

        $this->initialize();
        return new ViewModel(array(
            'userDetail' => $userDetail,
            'blog' => $blogDetail,
            'categories' => $this->getCategoryModel()->getAll(),
            'form' => $commentForm
        ));
    }

    public function saveAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('blog', $this->translate('Something went wrong. Please try again.'));
        } else if (!$this->validateUser()) {
            return $this->getResponse()->setContent(Json::encode(array('status' => 'not-logged-in'), true));
        }

        $commentForm = new Comment(array('translator' => $this->getTranslatorHelper()));
        $commentEntity = new \BlogUser\Model\Entity\Comment($this->getServiceLocator());
        $commentForm->setInputFilter($commentEntity->getInputFilter());
        $commentForm->setData($request->getPost());

        if ($commentForm->isValid()) {
            $categories = $this->getCategoryModel()->getAll();
            $professions = $this->getProfessionModel()->getAll();

            $currentUser = $this->getSessionContainer()->offsetGet('user_id');
            $data = array_merge($commentForm->getData(), array(
                'user_id' => $currentUser,
                'permalink' => $this->params()->fromRoute('permalink', null)
            ));
            $imgInfo = $this->ContentImageProcessor()->dealWithImages($this->getEvent(), $data['comment'], ImageConfig::COMMENT);
            $data           = array_merge($data,array('comment'=>$imgInfo['details']));
            $result = $this->getCommentModel()->save($data);
            if (!empty($result)) {
                if (!empty($imgInfo['images'])) {
                    $imgInfo = array_merge($imgInfo, array(
                        'id_of_image_for'   =>  $result['id_of_comment_for'],
                        'user_id'           =>  $data['user_id'],
                        'usages_type'       =>  ImageUsagesType::COMMENT));
                    $this->getImageModel()->saveContentImage($imgInfo);
                }

                $singleCommentViewModel = new ViewModel(array(
                    'categories' => $categories,
                    'professions' => $professions,
                    'currentUser' => $currentUser
                ));
                $userDetails = $this->getUserHelperModel()->getUsersDetail(array('user_id' => $data['user_id']), array(
                    'withProfile' => true,
                    'withFriendsAndFollowersCount' => true,
                    'withPostsCommentsCount' => true
                ));

                $commentData = array_merge($userDetails,$result,array(
                    'comment_created' => $result['created'],
                    'total_comment_favorited' => 0
                ));
                if (!empty($data['type']) && $data['type'] == \Blog\Model\Comment::TYPE_REPLY){
                    $singleCommentViewModel->setTemplate('blog/partials/single-comment-reply-view')->setVariables(array(
                        'reply'=> $commentData
                    ));
                } else {
                    $singleCommentViewModel->setTemplate('blog/partials/single-comment-view')->setVariables(array(
                        'comment'=> $commentData
                    ));
                }

                if (!empty($result)) {
                    $this->getNotificationUserModel()->saveForCommentPublishing($result);
                    $this->setSuccessMessage($this->translate('Comment has been saved successfully.'));
                }

                $result = array(
                    'status' => empty($result) ? 'not-saved' : 'success',
                    'data'=>$this->getServiceLocator()->get('viewRenderer')->render($singleCommentViewModel)
                );
            } else {
                $result = array(
                    'status' => 'error',
                    'msg' => $this->translate('Something went wrong. Please try again.')
                );
            }

        } else {
            $result = array(
                'status' => 'error',
                'msg' => $this->translate('Something went wrong. Please try again.')
            );
        }
        return $this->getResponse()->setContent(Json::encode($result, true));
    }

    public function saveForUserWallAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('blog', $this->translate('Something went wrong. Please try again.'));
        } else if (!$this->validateUser()) {
            return $this->getResponse()->setContent(Json::encode(array('status' => 'not-logged-in'), true));
        }

        $commentForm = new Comment(array('translator' => $this->getTranslatorHelper()));
        $commentEntity = new \BlogUser\Model\Entity\Comment($this->getServiceLocator());
        $commentForm->setInputFilter($commentEntity->getInputFilter());
        $commentForm->setData($request->getPost());

        if ($commentForm->isValid()) {
            $categories = $this->getCategoryModel()->getAll();
            $professions = $this->getProfessionModel()->getAll();

            $currentUser = $this->getSessionContainer()->offsetGet('user_id');
            $data = array_merge($commentForm->getData(), array(
                'user_id' => $currentUser,
                'permalink' => $this->params()->fromRoute('permalink', null)
            ));
            $imgInfo = $this->ContentImageProcessor()->dealWithImages($this->getEvent(), $data['comment'], ImageConfig::COMMENT);
            $data = array_merge($data,array('comment' => $imgInfo['details']));
            $result = $this->getCommentModel()->save($data);
            if (!empty($result)) {
                if (!empty($imgInfo['images'])) {
                    $imgInfo = array_merge($imgInfo, array(
                        'id_of_image_for'   =>  $result['id_of_comment_for'],
                        'user_id'           =>  $data['user_id'],
                        'usages_type'       =>  ImageUsagesType::COMMENT));
                    $this->getImageModel()->saveContentImage($imgInfo);
                }

                $this->getNotificationUserModel()->saveForCommentPublishing($result);
                $userDetails = $this->getUserHelperModel()->getUsersDetail(array('user_id' => $data['user_id']), array(
                    'withProfile' => true,
                    'withFriendsAndFollowersCount' => true,
                    'withPostsCommentsCount' => true
                ));

                $commentData = array_merge($userDetails,$result,array(
                    'comment_created' => $result['created'],
                    'total_comment_favorited' => 0
                ));
                $commentData = array_merge($commentData,array('comments'=>$commentData['details']));

                $singleCommentViewModel = new ViewModel(array(
                    'categories' => $categories,
                    'professions' => $professions,
                    'currentUser' => $currentUser,
                    'userDetails' =>  $userDetails,
                    'commentOn'         =>  $commentData,
                    'commentFor'        =>  $commentData['comment_for'],
                ));

                if (!empty($data['type']) && $data['type'] == \Blog\Model\Comment::TYPE_REPLY){
                    $singleCommentViewModel->setTemplate('blog-user/index/partials/user_wall_single_reply')->setVariables(array(
                        'eachReplyData'     =>  $commentData,
                        'isCommentFormEnable'   => false
                    ));
                } else {
                    $singleCommentViewModel->setTemplate('blog-user/index/partials/user_wall_single_comment')->setVariables(array(
                        'eachCommentsData'  =>  $commentData,
                        'commentForm'       =>  new Comment(array('translator' => $this->getTranslatorHelper())),
                        'isCommentFormEnable'  => true,
                        'formForReply' => true
                    ));
                }

                $result = array(
                    'status' => 'success',
                    'data'=>$this->getServiceLocator()->get('viewRenderer')->render($singleCommentViewModel)
                );
            } else {
                $result = array(
                    'status' => 'error',
                    'html' => $this->translate('Comment not saved. Something went wrong. Please try again.')
                );
            }

        } else {
            $result = array(
                'status' => 'error',
                'html' => $this->translate('Invalid input. Please try again.')
            );
        }
        return $this->getResponse()->setContent(Json::encode($result, true));
    }

    public function editAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('blog', $this->translate('You are not authenticated.'));
        }

        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $commentId = $this->params()->fromRoute('id', null);
        $comment = $this->getCommentModel()->getDetailByUserId($currentUser, $commentId);
        if (empty($comment)) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'invalid',
                'html' => 'Invalid input'
            ), true));
        }

        $commentForm = new Comment(array('translator' => $this->getTranslatorHelper()));
        $viewModel = new ViewModel(array(
            'commentForm' => $commentForm,
            'commentOn' => $comment
        ));
        $viewModel->setTemplate('blog/partials/single-comment-edit-form')->setTerminal(true);

        if ($request->isPost()) {
            $commentEntity = new \BlogUser\Model\Entity\Comment($this->getServiceLocator());
            $commentForm->setInputFilter($commentEntity->getInputFilter());
            $commentForm->setData($request->getPost());
            if ($commentForm->isValid()) {
                $imageModel = $this->getImageModel();
                $data = array_merge($commentForm->getData(), array(
                    'user_id' => $currentUser
                ));
                $imageProcessor = $this->ContentImageProcessor();
                $imgInfo = $imageProcessor->dealWithImages($this->getEvent(), $data['comment'], ImageConfig::COMMENT, true);
                if (!empty($imgInfo['images'])){
                    $imgInfo = array_merge($imgInfo,array(
                        'id_of_image_for'   =>  $commentId,
                        'user_id'           =>  $data['user_id'],
                        'usages_type'       =>  ImageUsagesType::COMMENT));
                    $imageModel->saveContentImage($imgInfo);
                }
                $removeFiles = $imageProcessor->removeImagesFromText($comment['details'], $imgInfo['details']);
                if (!empty($removeFiles)){
                    $removeFiles = $imageModel->deleteImage(array_merge($removeFiles,array('user_id'=>$data['user_id'],'id_of_image_for'=>$commentId)));
                    $imageProcessor->removeImages($data['user_id'],$removeFiles);
                }
                $data = array_merge($data,array('comment'=>$imgInfo['details']));
                $result = $this->getCommentModel()->updateByCommentId($data, $commentId);
                if (empty($result)) {
                    $viewModel->setVariable('errorMsg', $this->translate('Something went wrong. Please try again.'));
                    $msg = $this->translate('Something went wrong. Please try again.');
                } else {
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'success',
                        'comment' => $data['comment'],
                        'msg' => $this->translate('Comment has been successfully updated.'),
                        'type' => ($data['type'] == \Blog\Model\Comment::TYPE_NEW) ? 'comment' : 'reply'
                    )));
                }
            } else {
                $commentForm->setData($comment);
                $commentForm->setData(array('comment' => $comment['details']));
                $msg = $this->translate('Something went wrong. Please try again.');
            }

            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'invalid',
                'html' => $msg
            )));

        } elseif ($request->isGet()) {
            $commentForm->setData($comment);
            $commentForm->setData(array('comment' => $comment['details']));
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel)
            )));
        } else {
            return $this->getResponse()->setContent(Json::encode(array('status' => 'invalid', 'html' => 'unauthorized')));
        }
    }

    public function userWallEditAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('blog', $this->translate('You are not authenticated.'));
        }

        if (!$this->validateUser()) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'not-logged-in',
                'html' => $this->translate('Please log in first.')
            ), true));
        }

        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $commentId = $this->params()->fromRoute('id', null);
        $comment = $this->getCommentModel()->getDetailByUserId($currentUser, $commentId);
        if (empty($comment)) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'invalid',
                'html' => 'Invalid input'
            ), true));
        }

        $commentForm = new Comment(array('translator' => $this->getTranslatorHelper()));
        $viewModel = new ViewModel(array(
            'commentForm' => $commentForm,
            'commentOn' => $comment
        ));
        $viewModel->setTemplate('blog/partials/single-comment-edit-form');

        if ($request->isPost()) {
            $commentEntity = new \BlogUser\Model\Entity\Comment($this->getServiceLocator());
            $commentForm->setInputFilter($commentEntity->getInputFilter());
            $commentForm->setData($request->getPost());
            if ($commentForm->isValid()) {
                $imageModel = $this->getImageModel();
                $data = array_merge($commentForm->getData(), array(
                    'user_id' => $currentUser
                ));
                $imageProcessor = $this->ContentImageProcessor();
                $imgInfo = $imageProcessor->dealWithImages($this->getEvent(), $data['comment'], ImageConfig::COMMENT, true);
                if (!empty($imgInfo['images'])){
                    $imgInfo = array_merge($imgInfo,array(
                        'id_of_image_for'   =>  $commentId,
                        'user_id'           =>  $data['user_id'],
                        'usages_type'       =>  ImageUsagesType::COMMENT));
                    $imageModel->saveContentImage($imgInfo);
                }
                $removeFiles = $imageProcessor->removeImagesFromText($comment['details'], $imgInfo['details']);
                if (!empty($removeFiles)){
                    $removeFiles = $imageModel->deleteImage(array_merge($removeFiles,array('user_id'=>$data['user_id'],'id_of_image_for'=>$commentId)));
                    $imageProcessor->removeImages($data['user_id'],$removeFiles);
                }
                $data = array_merge($data,array('comment'=>$imgInfo['details']));
                $result = $this->getCommentModel()->updateByCommentId($data, $this->params()->fromRoute('id', null));
                if (empty($result)) {
                    $viewModel->setVariable('errorMsg', $this->translate('Something went wrong. Please try again.'));
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'invalid',
                        'html'=>$this->translate('Something went wrong. Please try again.')
                    )));
                } else {
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'success',
                        'comment' => $data['comment'],
                        'msg' => $this->translate('Comment has been successfully updated.'),
                        'type' => ($data['type'] == \Blog\Model\Comment::TYPE_NEW) ? 'comment' : 'reply'
                    )));
                }
            } else {
                $commentForm->setData($comment);
                $commentForm->setData(array('comment' => $comment['details']));
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'invalid',
                    'html'=>$this->translate('Something went wrong. Please try again.')
                )));
            }
        } elseif ($request->isGet()) {
            $commentForm->setData($comment);
            $commentForm->setData(array('comment' => $comment['details']));
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel)
            )));
        } else {
            return $this->getResponse()->setContent(Json::encode(array('status' => 'invalid', 'html' => 'unauthorized')));
        }
    }

    public function deleteCommentAction()
    {
        $username = $this->params()->fromRoute('username', null);
        $request = $this->getRequest();
        if ($username === 'me' || $username === $this->getSessionContainer()->offsetGet('username')) {
            $comment = $this->getCommentModel()->getDetail($this->params()->fromRoute('id', null));
            if (!empty($comment)){
                $userId = $this->getSessionContainer()->offsetGet('user_id');
                $imageProcessor = $this->ContentImageProcessor();
                $removeFiles = $imageProcessor->extractAllImages($comment['details']);
                if (!empty($removeFiles)){
                    $removeFiles = $this->getImageModel()->deleteImage(array_merge($removeFiles,array('user_id'=>$userId,'id_of_image_for'=>$comment['comment_id'])));
                    $imageProcessor->removeImages($userId,$removeFiles);
                }
            }
            $result = $this->getCommentModel()->remove($this->params()->fromRoute('id', null));
            if ($request->isXmlHttpRequest()) {
                if (empty($result)) {
                    $result = array('status' => 'error', 'data' => 'Unknown');
                } else {
                    $result = array('status' => 'success', 'data' => 'Done');
                }
                return $this->getResponse()->setContent(Json::encode($result, true));
            } else {
                if (empty($result)) {
                    return $this->redirectToPreviousUrlForFailure($this->translate('Something went wrong. Please try again.'));
                } else {
                    return $this->redirectToPreviousUrlForSuccess($this->translate('Comment has been deleted successfully.'));
                }
            }
        } else {

            if ($request->isXmlHttpRequest()) {
                return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unauthenticated'), true));
            } else {
                return $this->redirectToPreviousUrlForFailure($this->translate('You are not authenticated to do this.'));
            }
        }
    }

    public function ajaxDeleteCommentAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = (array) $request->getPost();
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            if (!empty($params) && $comment = $this->getCommentModel()->getDetailByUserId($userId,$params['comment_id'])){
                $imageProcessor = $this->ContentImageProcessor();
                $removeFiles = $imageProcessor->extractAllImages($comment['details']);
                if (!empty($removeFiles)){
                    $removeFiles = $this->getImageModel()->deleteImage(array_merge($removeFiles,array('user_id'=>$userId,'id_of_image_for'=>$comment['comment_id'])));
                    $imageProcessor->removeImages($userId,$removeFiles);
                }
                $result = $this->getCommentModel()->remove($comment['comment_id']);
                if (empty($result)) {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'Unknown'), true));
                } else {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'success', 'data' => 'Done'), true));
                }
            } else {
                return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'Unknown'), true));
            }
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    /**
     * @return  \Blog\Model\Comment
     */
    private function getCommentModel()
    {
        isset($this->commentModel) || $this->commentModel = $this->getServiceLocator()->get('Blog\Model\Comment');
        return $this->commentModel;
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
     * @return \NBlog\Model\Helper\User
     */
    private function getUserHelperModel()
    {
        isset($this->userHelperModel) || $this->userHelperModel = $this->getServiceLocator()->get('NBlog\Model\Helper\User');
        return $this->userHelperModel;
    }
}