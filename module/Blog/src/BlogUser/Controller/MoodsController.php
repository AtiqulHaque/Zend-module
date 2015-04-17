<?php
/**
 * Moods Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md.atiqul haque <md_atiqulhaque@yahoo.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;

use BlogUser\Model\Entity\Mood AS MoodEntity;
use BlogUser\Model\Notification;
use NBlog\Model\ImageConfig;
use NBlog\Model\ImageUsagesType;
use NBlog\Model\WritingStatus;
use NBlog\Model\WritingType;
use Zend\Json\Json;
use Zend\View\Model\ViewModel;
use BlogUser\Form\Comment AS CommentForm;
use BlogUser\Form\Report AS ReportForm;
use BlogUser\Form\Mood AS MoodForm;

class MoodsController extends UserBaseController
{
    protected $blockedUserModel;
    protected $categoryModel;
    protected $commentModel;
    protected $imageModel;
    protected $moodModel;
    protected $professionModel;
    protected $reportModel;
    protected $reportMessageModel;
    protected $writingStatusModel;
    protected $userHelperModel;
    protected $menuItem = 'dashboard';

    public function indexAction()
    {
        $options = array_merge($this->params()->fromRoute(), array('user_id' => $this->getSessionContainer()->offsetGet('user_id')));

        $moodModel = $this->getMoodModel();
        $moods = $moodModel->getAll($options);
        $countDiscussions = $moodModel->countAll($options);
        $viewModel = new ViewModel(array(
            'moods' => $moods,
            'statuses' => $this->getWritingStatusModel()->getAll()
        ));
        $this->setPagination($viewModel, $moodModel, $moods, $countDiscussions, array(
            'path' => '',
            'itemLink' => 'my-all-mood-statuses'
        ));

        $this->initialize();
        return $viewModel;
    }

    public function showAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('User has not been found.'));
        }

        $permalink = $this->params()->fromRoute('permalink', null);
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        if (empty($permalink) || !($moodDetail = $this->getMoodModel()->getByPermalink($permalink, array(
                'loggedInUser' => $currentUser,
                'withUserReporting' => true,
                'withHidingStatus' => true,
                'withFavoriteStatus' => true,
                'withCommentBlocking' => true,
                'withUserDetail' => true
            )))) {
            return $this->redirectForFailure('my-all-mood-statuses', $this->translate('Mood has not been found.'));
        }

        $comments = $this->getCommentModel()->getByMoodId($moodDetail['mood_id'], array(
            'loggedInUser' => $currentUser,
            'withUserReporting' => true,
            'withHidingStatus' => true,
            'withFavoriteStatus' => true
        ));
        $this->initialize();
        $viewModel = new ViewModel(array(
            'mood' => $moodDetail,
            'categories' => $this->getCategoryModel()->getAll(),
            'comments' => $comments,
            'blockedBloggers' => $this->getBlockedUserModel()->getByMoodId($moodDetail['mood_id']),
            'commentForm' => new CommentForm(),
            'reportForm' => new ReportForm(array(
                'messages' => $this->getReportMessageModel()->getAll()
            ))
        ));

        return $viewModel;
    }

    public function addAction()
    {
        $moodForm = new MoodForm(array(
            'translator' => $this->getTranslatorHelper(),
            'statuses' => $this->getWritingStatusModel()->getForUserMood()
        ));

        $viewModel = new ViewModel(array(
            'form' => $moodForm,
            'isEdit'=>true
        ));

        $request = $this->getRequest();
        if ($request->isXmlHttpRequest() && $request->isPost()) {
            $credentialEntity = new MoodEntity($this->getServiceLocator());
            $moodForm->setInputFilter($credentialEntity->getInputFilter());
            $moodForm->setData($request->getPost());
            if ($moodForm->isValid()) {
                $data = array_merge($moodForm->getData(), array('user_id' => $this->getSessionContainer()->offsetGet('user_id')));
                $imgInfo = $this->ContentImageProcessor()->dealWithImages($this->getEvent(), $data['title'], ImageConfig::MOOD);
                $data    = array_merge($data,array('title'=>$imgInfo['details']));
                $result  = $this->getMoodModel()->save($data);
                if (empty($result)) {
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'error',
                        'html' => $this->translate('Something went wrong. Please try again.'))));
                } else {
                    if ($data['status'] != WritingStatus::PUBLISHED) {
                        $data['writing_id'] = $result['mood_id'];
                        $this->getNotificationUserModel()->saveForWritingPublishing($data, Notification::MOOD_PUBLISH);
                    }

                    if (!empty($imgInfo['images'])) {
                        $imgInfo = array_merge($imgInfo, array(
                            'id_of_image_for'   =>  $result['mood_id'],
                            'user_id'           =>  $data['user_id'],
                            'usages_type'       =>  ImageUsagesType::MOOD));
                        $this->getImageModel()->saveContentImage($imgInfo);
                    }
                    $currentUser = $this->getSessionContainer()->offsetGet('user_id');
                    $userDetails = $this->getUserHelperModel()->getUsersDetail(array('user_id' => $data['user_id']), array(
                        'withProfile' => true,
                        'withFriendsAndFollowersCount' => true,
                        'withPostsCommentsCount' => true
                    ));
                    $result = array_merge($result, $userDetails, array(
                        'details'               =>  $result['title'],
                        'wall_content_created'  =>  $result['created'],
                        'total_comments'        =>  0,
                        'total_favorited'       =>  0,
                        'content_id'            =>  $result['mood_id'],
                        'created_by'            =>  $currentUser,
                        'writing_type'          =>  WritingType::MOOD
                    ));
                    $viewModel->setTemplate('blog-user/index/partials/user_wall_single_content_mood')->setVariables(array(
                        'professions' => $this->getProfessionModel()->getAll(),
                        'currentUser' => $currentUser,
                        'eachContent' => $result,
                        'commentForm' => new CommentForm(array(
                            'translator' => $this->getTranslatorHelper()
                        ))
                    ));

                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'success',
                        'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel),
                        'msg' => $this->translate('Your mood has been saved successfully.')
                    )));
                }
            } else {

                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html' => $this->translate('Something went wrong. Please try again.')
                )));
            }
        } elseif ($this->params()->fromRoute('isCalled')) {
            return $viewModel;
        } else {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        }
    }

    public function editAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        }

        $moodForm = new MoodForm(array(
            'translator' => $this->getTranslatorHelper(),
            'statuses' => $this->getWritingStatusModel()->getForUserMood()
        ));

        $viewModel = new ViewModel(array(
            'form' => $moodForm,
            'isEdit'=> false
        ));
        $viewModel->setTemplate('blog-user/moods/add');
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $moodId = $this->params()->fromRoute('mood_id', null);
        if (!$moodInfo = $this->getMoodModel()->getDetailByUserId($moodId, $currentUser)) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'error',
                'html' => $this->translate('Something went wrong. Please try again.'))
            ));
        }
        if ($request->isPost()) {
            $credentialEntity = new MoodEntity($this->getServiceLocator());
            $moodForm->setInputFilter($credentialEntity->getInputFilter());
            $moodForm->setData($request->getPost());
            if ($moodForm->isValid()) {
                $data = array_merge($moodForm->getData(), array('user_id' => $currentUser));
                $imageProcessor = $this->ContentImageProcessor();
                $imgInfo = $imageProcessor->dealWithImages($this->getEvent(), $data['title'], ImageConfig::MOOD, true);
                $imageModel = $this->getImageModel();
                if (!empty($imgInfo['images'])) {
                    $imgInfo = array_merge($imgInfo, array(
                        'id_of_image_for'   =>  $moodId,
                        'user_id'           =>  $currentUser,
                        'usages_type'       =>  ImageUsagesType::MOOD
                    ));
                    $imageModel->saveContentImage($imgInfo);
                }
                $removeFiles = $imageProcessor->removeImagesFromText($moodInfo['title'], $imgInfo['details']);
                if (!empty($removeFiles)){
                    $removeFiles = $imageModel->deleteImage(array_merge($removeFiles,array('user_id'=>$currentUser,'id_of_image_for'=>$moodId)));
                    $imageProcessor->removeImages($currentUser,$removeFiles);
                }

                $data    = array_merge($data,array('title' => $imgInfo['details']));
                $result  = $this->getMoodModel()->modify($data,$moodId);
                if (empty($result)) {
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'error',
                        'html' => $this->translate('Something went wrong. Please try again.'))));
                } else {
                    $result = array_merge($result,array('details'  =>  $result['title']));
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'success',
                        'html' => $result['details'])));
                }
            } else {
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html' => $this->translate('Something went wrong. Please try again.')
                )));
            }
        } elseif ($request->isGet()) {
            $moodForm->setData($moodInfo);
            if ($request->isXmlHttpRequest()) {
                return $this->getResponse()->setContent(Json::encode(array(
                    'status'=>'success',
                    'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel)
                )));
            } else {
                return $viewModel;
            }
        } else {
            return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'html' => 'unauthorized')));
        }
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        } elseif (!$this->validateUser()) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'not-logged-in',
                'html' => $this->translate('You are not authenticated. Please log in first.')
            ), true));
        }

        $params = (array) $request->getPost();
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $moodModel = $this->getMoodModel();
        $moodInfo = $moodModel->getDetailByUserId($params['mood_id'],$userId);
        if (empty($moodInfo) || empty($params)) {
            $result = array(
                'status' => 'error',
                'html' => $this->translate('Something went wrong. Please try again.')
            );
        } elseif ($moodModel->removeMood($params['mood_id'])) {
            $imageProcessor = $this->ContentImageProcessor();
            $removeFiles = $imageProcessor->extractAllImages($moodInfo['title']);
            if (!empty($removeFiles)){
                $removeFiles = $this->getImageModel()->deleteImage(array_merge($removeFiles,array('user_id'=>$userId,'id_of_image_for'=>$moodInfo['mood_id'])));
                $imageProcessor->removeImages($userId,$removeFiles);
            }
            $result = array(
                'status' => 'success',
                'html' => $this->translate('Mood remove successfully.')
            );
        } else {
            $result = array(
                'status' => 'error',
                'html' => $this->translate('Something went wrong. Please try again.')
            );
        }

        return $this->getResponse()->setContent(Json::encode($result, true));
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
     * @return \BlogUser\Model\Mood
     */
    private function getMoodModel()
    {
        isset($this->moodModel) || $this->moodModel = $this->getServiceLocator()->get('BlogUser\Model\Mood');
        return $this->moodModel;
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

    /**
     * @return \NBlog\Model\WritingStatus
     */
    private function getWritingStatusModel()
    {
        isset($this->writingStatusModel) || $this->writingStatusModel = $this->getServiceLocator()->get('NBlog\Model\WritingStatus');
        return $this->writingStatusModel;
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