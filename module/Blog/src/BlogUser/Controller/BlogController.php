<?php
/**
 * Blog Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;

use BlogUser\Form\Blog AS BlogForm;
use BlogUser\Form\Report AS ReportForm;
use BlogUser\Model\Notification;
use NBlog\Model\ImageConfig;
use NBlog\Model\ImageUsagesType;
use NBlog\Model\PostType;
use NBlog\Model\WritingType;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use NBlog\Model\WritingStatus;
use NBlog\Model\Entity\Post;
use BlogUser\Form\Comment AS CommentForm;

class BlogController extends UserBaseController
{
    protected $blockedUserModel;
    protected $categoryModel;
    protected $commentModel;
    protected $episodeModel;
    protected $imageModel;
    protected $professionModel;
    protected $reportModel;
    protected $reportMessageModel;
    protected $writingStatusModel;
    protected $userHelperModel;
    protected $menuItem = 'post';

    public function indexAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('User has not been found.'));
        }
        $this->initialize(null, $userDetail);
        $blogModel = $this->getBlogModel();
        $countStatusWisePosts = $blogModel->countStatusWisePosts($userDetail['user_id']);
        $viewModel = new ViewModel(array(
            'totalPublishedPosts' => $countStatusWisePosts[WritingStatus::PUBLISHED],
            'totalPendingPosts' => $countStatusWisePosts[WritingStatus::PENDING],
            'totalBouncedPosts' => $countStatusWisePosts[WritingStatus::BOUNCE],
            'totalTrashPosts' => $countStatusWisePosts[WritingStatus::TRASH],
            'totalDraftPosts' => $countStatusWisePosts[WritingStatus::DRAFT],
            'totalUnpublishedPosts' => $countStatusWisePosts[WritingStatus::QUEUE_POST],
            'totalFavoritePosts' => $this->getSubscribeModel()->countBeingSubscribers($userDetail['user_id']),
            'totalPosts' => $blogModel->countAllPosts($userDetail['user_id']),
            'totalCommentsPosts' => $this->getCommentModel()->countCommentsOfASingleUser($userDetail['user_id']),
            'userDetail' => $userDetail
        ));
        $this->enableLayoutBanner();
        return $viewModel;
    }

    public function showUserPostsAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('User has not been found.'));
        }
        $viewModel = new ViewModel(array(
            'userDetail' => $userDetail
        ));
        $this->initialize(null, $userDetail);
        $this->enableLayoutBanner();
        return $viewModel->setTemplate('blog-user/blog/index');
    }

    public function getPostByStatusAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        }
        return $this->getStatusBasedPosts($request->getPost()->toArray());
    }

    private function getStatusBasedPosts(array $options = array())
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail) || empty($options)) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'error',
                'html' => $this->translate('Something went wrong. Please try again.')
            )));
        }

        $blogModel = $this->getBlogModel();
        switch ($options['options']) {

            case WritingStatus::DRAFT :
                $blogPosts = $blogModel->getDraftPosts($userDetail['user_id'], $options);
                break;

            case WritingStatus::PENDING :
                $blogPosts = $blogModel->getPendingPosts($userDetail['user_id'], $options);
                break;

            case WritingStatus::BOUNCE :
                $blogPosts = $blogModel->getBouncedPosts($userDetail['user_id'], $options);
                break;

            case WritingStatus::TRASH :
                $blogPosts = $blogModel->getTrashedPosts($userDetail['user_id'], $options);
                break;

            case WritingStatus::PUBLISHED :
                $blogPosts = $blogModel->getPublishedPosts($userDetail['user_id'], $options);
                break;

            case WritingStatus::QUEUE_POST :
                $blogPosts = $blogModel->getQueuedPosts($userDetail['user_id'], $options);
                break;

            case WritingStatus::FAVORITED :
                $blogPosts = $this->getSubscribeModel()->getFavoritePosts($userDetail['user_id'], $options, true);
                break;

            case WritingStatus::MY_COMMENTS :
                $blogPosts = $this->getCommentModel()->getAllCommentsForMyPosts($userDetail['user_id'], $options);
                break;

            default:
                $options['isUserAnonymous'] = $this->getSessionContainer()->offsetGet('user_id') != $userDetail['user_id'];
                $blogPosts = $blogModel->getAllPosts($userDetail['user_id'], $options);
        }

        $viewModel = new ViewModel(array(
            'blogPosts' => $blogPosts,
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll(),
            'status' => $options['options'],
        ));
        $viewModel->setTemplate('blog-user/blog/partials/my-post-list');
        return $this->getResponse()->setContent(Json::encode(array(
            'status' => 'success',
            'html' => array(
                'postList' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel),
                'postDetails' => empty($blogPosts) ? array() : $this->renderSinglePost(current($blogPosts)),
            )
        )));
    }

    public function viewMyPostAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('User has not been found.'));
        }

        $permalink = $this->params()->fromRoute('permalink', null);
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        if (empty($permalink) || !($blogDetail = $this->getBlogModel()->getByPermalink($permalink, array(
                'loggedInUser' => $currentUser,
                'withUserReporting' => true,
                'withHidingStatus' => true,
                'withFavoriteStatus' => true,
                'withCommentBlocking' => true
            )))
        ) {
            return $this->redirectForFailure('my-all-posts', $this->translate('Post has not been found.'));
        } else if ($blogDetail['post_created_by'] != $currentUser) {
            $isFriendOrFollower = $this->getFriendModel()->checkFriendOrNot($blogDetail['post_created_by'], $currentUser);
            if (empty($isFriendOrFollower)) {
                $isFriendOrFollower = $this->getSubscribeModel()->checkFollowerOrNot($blogDetail['post_created_by'], $currentUser);
                if (empty($isFriendOrFollower)) {
                    return $this->redirectForFailure('profile-home', $this->translate('You are not authenticated to see the post here.'));
                }
            }
        }

        $this->initialize(null, $userDetail);
        $this->layout()->setVariables(array(
            'metaInfo' => array(
                'title' => $blogDetail['title'],
                'description' => $this->getServiceLocator()->get('viewHelperManager')->get('Text')->word_limiter(strip_tags($blogDetail['details']), 100),
                'author' => $blogDetail['nickname']
            )
        ));
        $comments = $this->getCommentModel()->getByWallPostId($blogDetail['post_id'], array(
            'loggedInUser' => $currentUser,
            'withUserReporting' => true,
            'withHidingStatus' => true,
            'withFavoriteStatus' => true
        ));
        $viewModel = new ViewModel(array(
            'blog' => $blogDetail,
            'categories' => $this->getCategoryModel()->getAll(),
            'episode' => $this->getBlogModel()->getOtherEpisodicPosts($blogDetail),
            'comments' => $comments,
            'blockedBloggers' => $this->getBlockedUserModel()->getByPost($blogDetail['post_id']),
            'userWallStatus' => $this->getWritingStatusModel()->getForUserWall(),
            'commentForm' => new CommentForm(array('translator' => $this->getTranslatorHelper())),
            'reportForm' => new ReportForm(array(
                'messages' => $this->getReportMessageModel()->getAll()
            ))
        ));

        return $viewModel;
    }

    public function showSinglePostAction()
    {
        $request = $this->getRequest();
        return $this->renderSinglePost($request->getPost()->toArray(), false);
    }

    private function renderSinglePost(array $singlePost = array(), $isCalled = true)
    {
        if (empty($singlePost)) {
            return array();
        }

        $singlePost = $this->getBlogModel()->getSinglePostByPermalink($singlePost['permalink']);
        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $comments = $this->getCommentModel()->getByWallMyPostId($singlePost['post_id'], array(
            'loggedInUser' => $currentUser,
            'withUserReporting' => true,
            'withHidingStatus' => true,
            'withFavoriteStatus' => true
        ));

        $singlePost = array_merge($singlePost, array('writing_type' => WritingType::POST, 'comments' => $comments));
        $reportMessages = $this->getReportMessageModel()->getAll();
        $commentForm = new CommentForm(array('translator' => $this->getTranslatorHelper()));
        $singlePostViewModel = new ViewModel(array(
            'eachContent' => $singlePost,
            'currentUser' => $currentUser,
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll(),
            'singleMyPost' => 'singleMyPost',
            'isEdit' => false,
            'isCurrentUserPost' => ($currentUser == $singlePost['post_created_by']),
            'commentForm' => $commentForm,
            'commentsData' => array (
                'commentOn'      => $singlePost,
                'comments'       => $singlePost['comments'],
                'commentFor'     => $singlePost['writing_type'],
                'commentForm'    => $commentForm,
                'reportForm'     => new ReportForm(array('messages' => $reportMessages)),
                'reportMessages' => $reportMessages
            ),
        ));
        $singlePostViewModel->setTemplate('blog-user/index/partials/user_wall_single_content_post');
        $html = $this->getServiceLocator()->get('viewRenderer')->render($singlePostViewModel);
        if ($isCalled) {
            return $html;
        } else {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'html' => $html
            )));
        }
    }

    public function addPostAction()
    {
        $blogCategoryModel = $this->getCategoryModel();
        $blogModel = $this->getBlogModel();
        $categories = $blogCategoryModel->getAll();
        $blogForm = new BlogForm(array(
            'translator' => $this->getTranslatorHelper(),
            'categories' => $blogCategoryModel->getCategoryList($categories),
            'statuses' => $this->getWritingStatusModel()->getSelectedStatuses()
        ));

        $viewModel = new ViewModel(array(
            'blogForm' => $blogForm,
            'categories_for_form' =>  $blogCategoryModel->getAllForNavigation($categories)
        ));

        $request = $this->getRequest();
        if ($request->isXmlHttpRequest() && $request->isPost()) {
            $blogEntity = new Post($this->getServiceLocator());
            if ($request->getPost('type') == PostType::EPISODE) {
                $blogEntity->setCheckEpisodicTag(true);
            }
            $blogForm->setInputFilter($blogEntity->getInputFilter());
            $blogForm->setData($request->getPost());

            if ($blogForm->isValid()) {
                $userId = $this->getSessionContainer()->offsetGet('user_id');
                $data = array_merge($blogForm->getData(), array(
                    'user_id' => $userId,
                    'isInHomePage' => $blogModel->checkUserPostExistsInHomePage($userId)
                ));

                $imgInfo = $this->ContentImageProcessor()->dealWithImages($this->getEvent(), $data['details'], ImageConfig::POST);
                $data = array_merge($data, array('details' => $imgInfo['details']));
                $result = $blogModel->save($data);

                if (empty($result)) {
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'error',
                        'html' => $this->translate('Something went wrong. Please try again.')
                    )));
                } else {
                    if (!empty($imgInfo['images'])) {
                        $imgInfo = array_merge($imgInfo, array(
                            'id_of_image_for' => $result['post_id'],
                            'user_id' => $data['user_id'],
                            'usages_type' => ImageUsagesType::BLOG));
                        $this->getImageModel()->saveContentImage($imgInfo);
                    }

                    if ($data['status'] != WritingStatus::DRAFT) {
                        $data['writing_id'] = $result['post_id'];
                        $this->getNotificationUserModel()->saveForWritingPublishing($data, Notification::POST_PUBLISH);
                    }

                    $informDelayPublishing = $data['isInHomePage'] && $data['status'] == WritingStatus::PUBLISHED;
                    if (empty($informDelayPublishing)) {
                        $message = $this->translate('Blog post has been saved successfully.');
                    } else {
                        $message = 'সুপ্রিয় ব্যবহারকারী, আপনার দেয়া পূর্বতন পোষ্টটি প্রথম পাতায় বর্তমান রয়েছে। যখনই পূর্বের পোষ্টটি প্রথম পাতা থেকে দ্বিতীয় পাতায় যাবে, তখন এই পোষ্টটি স্বয়ংক্রিয়ভাবে প্রকাশিত হয়ে যাবে। প্রথম পাতায় একজন ব্লগারের একাধিক পোষ্ট রহিত-করণে এবং সকল ব্লগারের পোষ্ট প্রথম পাতায় প্রকাশের সুবিধার্থে এই ব্যবস্থা নেয়া হয়েছে। আপনাদের সকলের সহযোগিতা কামনা করছি।';
                    }

                    $userDetails = $this->getUserHelperModel()->getUsersDetail(array(
                        'user_id' => $data['user_id']), array(
                        'withProfile' => true,
                        'withFriendsAndFollowersCount' => true,
                        'withPostsCommentsCount' => true
                    ));
                    $result = array_merge($result, $userDetails, array(
                        'wall_content_created' => $result['created'],
                        'total_comment_favorited' => 0,
                        'total_favorited' => 0,
                        'total_comments' => 0,
                        'content_id' => $result['post_id'],
                        'writing_type' => WritingType::POST
                    ));

                    $viewModel = new ViewModel(array(
                        'eachContent' => $result,
                        'categories' => $categories,
                        'professions' => $this->getProfessionModel()->getAll(),
                        'currentUser' => $userId,
                        'commentForm' => new CommentForm(array('translator' => $this->getTranslatorHelper()))
                    ));
                    $viewModel->setTemplate('blog-user/index/partials/user_wall_single_content_post');
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'success',
                        'msg' => $message,
                        'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel)
                    )));
                }
            } else {
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'validationError',
                    'html' => $blogForm->getMessages()
                )));
            }
        } elseif ($this->params()->fromRoute('isCalled')) {
            return $viewModel;
        } else {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        }
    }

    public function editPostAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        } elseif (!($postId = (int)$this->params()->fromRoute('postId', null))) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'error',
                'html' => $this->translate('Invalid post id')
            )));
        }

        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $blogModel = $this->getBlogModel();
        $blogCategoryModel = $this->getCategoryModel();
        $categories = $blogCategoryModel->getAll();
        $blogForm = new BlogForm(array(
            'translator' => $this->getTranslatorHelper(),
            'categories' => $blogCategoryModel->getCategoryList($categories),
            'statuses' => $this->getWritingStatusModel()->getSelectedStatuses()
        ));

        if ($request->isPost()) {
            $blogEntity = new Post($this->getServiceLocator());
            if ($request->getPost('type') == PostType::EPISODE) {
                $blogEntity->setCheckEpisodicTag(true);
            }
            $blogForm->setInputFilter($blogEntity->getInputFilter());
            $blogForm->setData($request->getPost());
            if ($blogForm->isValid()) {
                $data = array_merge($blogForm->getData(), array('user_id' => $userId, 'post_id' => $postId));
                $blogData = $blogModel->getDetail($data['post_id']);
                if (empty($blogData) || $blogData['created_by'] != $userId) {
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'error',
                        'html' => $this->translate('Something went wrong. Please try again.')
                    )));
                } else {
                    $data = array_merge($data, array(
                        'isPostConvertToEpisode' => (empty($blogData['episode_id']) && $blogData['type'] == PostType::BLOG && $data['type'] == PostType::EPISODE),
                        'old_status' => $blogData['status'],
                        'old_type' => $blogData['type'],
                        'old_episode_id' => $blogData['episode_id'],
                        'old_title' => $blogData['title'],
                        'isInHomePage' => $blogModel->checkUserPostExistsInHomePage($userId)
                    ));
                    $imageModel = $this->getImageModel();
                    $imageProcessor = $this->ContentImageProcessor();
                    $imgInfo = $imageProcessor->dealWithImages($this->getEvent(), $data['details'], ImageConfig::POST, true);
                    if (!empty($imgInfo['images'])) {
                        $imgInfo = array_merge($imgInfo, array(
                            'id_of_image_for' => $data['post_id'],
                            'user_id' => $data['user_id'],
                            'usages_type' => ImageUsagesType::BLOG
                        ));
                        $imageModel->saveContentImage($imgInfo);
                    }
                    $removeFiles = $imageProcessor->removeImagesFromText($blogData['details'], $data['details']);

                    if (!empty($removeFiles)) {
                        $removeFiles = $imageModel->deleteImage(array_merge($removeFiles, array('user_id' => $userId, 'id_of_image_for' => $data['post_id'])));
                        $imageProcessor->removeImages($userId, $removeFiles);
                    }
                    $blogId = $blogModel->modify(array_merge($data, array('details' => $imgInfo['details'])), $data['post_id']);
                    if (empty($blogId)) {
                        return $this->getResponse()->setContent(Json::encode(array(
                            'status' => 'error',
                            'html' => $this->translate('Something went wrong. Please try again.')
                        )));
                    } else {
                        $result = current($blogModel->getByIds(array($blogId), true));
                        if ($data['status'] == WritingStatus::PUBLISHED && $data['isInHomePage'] && $blogData['status'] != WritingStatus::PUBLISHED) {
                            $message = 'সুপ্রিয় ব্যবহারকারী, আপনার দেয়া পূর্বতন পোষ্টটি প্রথম পাতায় বর্তমান রয়েছে। যখনই পূর্বের পোষ্টটি প্রথম পাতা থেকে দ্বিতীয় পাতায় যাবে, তখন এই পোষ্টটি স্বয়ংক্রিয়ভাবে প্রকাশিত হয়ে যাবে। প্রথম পাতায় একজন ব্লগারের একাধিক পোষ্ট রহিত-করণে এবং সকল ব্লগারের পোষ্ট প্রথম পাতায় প্রকাশের সুবিধার্থে এই ব্যবস্থা নেয়া হয়েছে। আপনাদের সকলের সহযোগিতা কামনা করছি।';
                        } else {
                            $message = $this->translate('Blog post has been updated successfully.');
                        }

                        $userDetails = $this->getUserHelperModel()->getUsersDetail(array('user_id' => $data['user_id']), array(
                            'withProfile' => true,
                            'withFriendsAndFollowersCount' => true,
                            'withPostsCommentsCount' => false
                        ));
                        $result = array_merge($result, $userDetails, array(
                            'wall_content_created' => $result['modified'],
                            'content_id' => $result['post_id'],
                            'post_count' => 0
                        ));
                        $viewModel = new ViewModel(array(
                            'eachContent' => $result,
                            'categories' => $categories,
                            'professions' => $this->getProfessionModel()->getAll(),
                            'currentUser' => $userId,
                            'isEdit' => true
                        ));
                        $viewModel->setTemplate('blog-user/index/partials/user_wall_single_content_post');
                        return $this->getResponse()->setContent(Json::encode(array(
                            'status' => 'success',
                            'msg' => $message,
                            'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel)
                        )));
                    }
                }
            } else {
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'validationError',
                    'html' => $blogForm->getMessages()
                )));
            }
        } elseif ($request->isGet()) {
            $post_id = (int)$request->getQuery('post_id');
            $blogDetail = $this->getBlogModel()->getPostsByUserIdAndPostId($userId, $post_id);
            if (empty($blogDetail)) {
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html' => $this->translate('Post has not been found.')
                )));
            } else {
                $blogCategoryModel = $this->getCategoryModel();
                $viewModel = new ViewModel(array(
                    'professions' => $this->getProfessionModel()->getAll(),
                    'blogForm' => $blogForm,
                    'categories_for_form' => $blogCategoryModel->getAllForNavigation(),
                    'blogDetails' => $blogDetail
                ));
                $viewModel->setTemplate('blog-user/blog/edit-post');

                if (PostType::EPISODE == $blogDetail['type']) {
                    $episodeTitles = $this->getEpisodeModel()->getTitlesByUser($userId, true);
                    $blogForm->get('select-title')->setOptions(array(
                        'value_options' => $this->getEpisodeModel()->getTitlesByUser($userId, true)
                    ));
                    $blogDetail['select-title'] = array_search($blogDetail['title'], $episodeTitles);
                }
                $blogForm->setData($blogDetail);
                $blogForm->setAttribute('id', 'post_form_' . $blogDetail['post_id']);
                $blogForm->get('details')->setAttribute('id', 'details_' . $blogDetail['post_id']);
                $blogForm->get('episode_tag')->setAttribute('disabled', (empty($blogDetail['episode_id']) ? 'disabled' : ''));
                $result = array(
                    'status' => 'success',
                    'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel)
                );
                return $this->getResponse()->setContent(Json::encode($result, true));
            }
        } else {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'error',
                'html' => $this->translate('Something went wrong. Please try again.')
            )));
        }
    }

    public function trashPostAction()
    {
        $permalink = $this->params()->fromRoute('permalink', null);
        $blogDetail = $this->getBlogModel()->getByPermalink($permalink);

        $request = $this->getRequest();
        if (empty($blogDetail)) {
            $msg = $this->translate('Post has not been found.');
        } elseif ($blogDetail['post_created_by'] != $this->getSessionContainer()->offsetGet('user_id')) {
            $msg = $this->translate('You are not permitted to do this action');
        } else {
            $result = $this->getBlogModel()->setTrashedStatus($blogDetail['post_id']);
            if (empty($result)) {
                $msg = $this->translate('Something went wrong. Please try again.');
            } else {
                $msg = $this->translate('Blog has updated successfully.');
                if ($request->isXmlHttpRequest()) {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'success', 'msg' => $msg)));
                } else {
                    return $this->redirectToPreviousUrlForSuccess($msg);
                }
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'msg' => $msg)));
        } else {
            return $this->redirectToPreviousUrlForFailure($msg);
        }
    }

    public function publishPostAction()
    {
        $permalink = $this->params()->fromRoute('permalink', null);
        $blogDetail = $this->getBlogModel()->getByPermalink($permalink);

        if (empty($blogDetail)) {
            return $this->redirectToPreviousUrlForFailure($this->translate('Post has not been found.'));
        } else {
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            if ($blogDetail['post_created_by'] != $userId) {
                return $this->redirectToPreviousUrlForFailure($this->translate('Something went wrong. Please try again.'));
            } else {
                $isInHomePage = $this->getBlogModel()->checkUserPostExistsInHomePage($userId);
                $result = $this->getBlogModel()->makePostPublished($blogDetail['post_id'], $isInHomePage);
                if (empty($result)) {
                    return $this->redirectToPreviousUrlForFailure($this->translate('Something went wrong. Please try again.'));
                } else {
                    if ($isInHomePage) {
                        $this->UserInformer()->addMessage('সুপ্রিয় ব্যবহারকারী, আপনার দেয়া পূর্বতন পোষ্টটি প্রথম পাতায় বর্তমান রয়েছে। যখনই পূর্বের পোষ্টটি প্রথম পাতা থেকে দ্বিতীয় পাতায় যাবে, তখন এই পোষ্টটি স্বয়ংক্রিয়ভাবে প্রকাশিত হয়ে যাবে। প্রথম পাতায় একজন ব্লগারের একাধিক পোষ্ট রহিত-করণে এবং সকল ব্লগারের পোষ্ট প্রথম পাতায় প্রকাশের সুবিধার্থে এই ব্যবস্থা নেয়া হয়েছে। আপনাদের সকলের সহযোগিতা কামনা করছি।');
                    }
                    return $this->redirectToPreviousUrlForSuccess($this->translate('Blog has updated successfully.'));
                }
            }
        }
    }

    public function restorePostAction()
    {
        $permalink = $this->params()->fromRoute('permalink', null);
        $blogDetail = $this->getBlogModel()->getByPermalink($permalink);

        $redirectOptions = array('permalink' => $permalink);
        if (empty($blogDetail)) {
            return $this->redirectForFailure('view-my-post', $this->translate('Post has not been found.'), $redirectOptions);
        } elseif ($blogDetail['post_created_by'] != $this->getSessionContainer()->offsetGet('user_id')) {
            return $this->redirectForFailure('view-my-post', $this->translate('Something went wrong. Please try again.'), $redirectOptions);
        } else {
            $result = $this->getBlogModel()->setDraftStatus($blogDetail['post_id']);
            if (empty($result)) {
                return $this->redirectForFailure('view-my-post', $this->translate('Something went wrong. Please try again.'), $redirectOptions);
            } else {
                return $this->redirectForSuccess('view-my-post', $this->translate('Blog status has been set as Draft.'), $redirectOptions);
            }
        }
    }

    public function deletePostAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        }

        $post_id = (int)$request->getPost('post_id');
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $blogDetail = $this->getBlogModel()->getPostsByUserIdAndPostId($userId, $post_id);
        if (empty($blogDetail)) {
            $msg = $this->translate('Post has not been found.');
        } elseif ($blogDetail['post_created_by'] != $this->getSessionContainer()->offsetGet('user_id')) {
            $msg = $this->translate('You are not permitted to do this action');
        } else {
            $result = $this->getBlogModel()->delete($blogDetail['post_id']);
            if (empty($result)) {
                $msg = $this->translate('Something went wrong. Please try again.');
            } else {
                $imageProcessor = $this->ContentImageProcessor();
                $removeFiles = $imageProcessor->extractAllImages($blogDetail['details']);
                if (!empty($removeFiles)) {
                    $removeFiles = $this->getImageModel()->deleteImage(array_merge($removeFiles, array('user_id' => $userId, 'id_of_image_for' => $blogDetail['post_id'])));
                    $imageProcessor->removeImages($userId, $removeFiles);
                }
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'success',
                    'html' => $this->translate('Your post has been permanently deleted.'))
                ));
            }
        }

        return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'msg' => $msg)));
    }

    public function searchAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('User has not been found.'));
        }

        $blogModel = $this->getBlogModel();
        $viewModel = new ViewModel();

        $options = array_merge($this->params()->fromRoute(), (array)$this->getRequest()->getPost(), array('user_id' => $userDetail['user_id']));
        $blogPosts = $blogModel->searchBlog($options);
        $countPosts = $blogModel->countSearchedBlog($options);
        $this->setPagination($viewModel, $blogModel, $blogPosts, $countPosts, array(
            'path' => '',
            'itemLink' => 'search-my-posts'
        ));

        $viewModel->setVariables(array(
            'blogPosts' => $blogPosts,
            'countPosts' => $countPosts,
            'statuses' => $this->getWritingStatusModel()->getAll(),
            'categories' => $this->getCategoryModel()->getAll(),
        ));

        $this->initialize(null, $userDetail);
        return $viewModel;
    }

    public function doBulkActionAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('User has not been found.'));
        }

        $options = array_merge($this->params()->fromRoute(), (array)$this->getRequest()->getPost(), array('user_id' => $userDetail['user_id']));
        $options['writingIds'] = empty($options['postIds']) ? null : $options['postIds'];
        $this->getBlogModel()->setStatusBulky($options);

        return $this->redirect()->toUrl($this->getRequest()->getPost('urlFrom'));
    }

    public function blockCommenterAction()
    {
        $sessionContainer = $this->getSessionContainer();
        $username = $this->params()->fromRoute('username', null);
        $request = $this->getRequest();
        if ($username === 'me' || $username === $sessionContainer->offsetGet('username')) {
            $blogDetail = $this->getBlogModel()->getByPermalink($this->params()->fromRoute('permalink', null));
            $userDetail = $this->getUserModel()->getDetailByUsername($this->params()->fromRoute('commenter', null));
            if (empty($blogDetail) || empty($userDetail)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unknown'), true));
                } else {
                    return $this->redirectForSuccess('profile-home', $this->translate('Something went wrong. Please try again.'));
                }
            } else {
                $blockedUserModel = $this->getBlockedUserModel();
                $result = $blockedUserModel->save(array(
                    'writing_id' => $blogDetail['post_id'],
                    'blogger_id' => $userDetail['user_id'],
                    'blocked_for' => $blockedUserModel::FOR_POST
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
        } elseif ($request->isXmlHttpRequest()) {
            return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unauthenticated'), true));
        } else {
            return $this->redirectForSuccess('profile-home', $this->translate('You are not authenticated to do this.'));
        }
    }

    public function unblockCommenterAction()
    {
        $sessionContainer = $this->getSessionContainer();
        $username = $this->params()->fromRoute('username', null);
        $request = $this->getRequest();
        if ($username === 'me' || $username === $sessionContainer->offsetGet('username')) {
            $blogDetail = $this->getBlogModel()->getByPermalink($this->params()->fromRoute('permalink', null));
            $userDetail = $this->getUserModel()->getDetailByUsername($this->params()->fromRoute('commenter', null));
            if (empty($blogDetail) || empty($userDetail)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unknown'), true));
                } else {
                    return $this->redirectForSuccess('profile-home', $this->translate('Something went wrong. Please try again.'));
                }
            } else {
                $result = $this->getBlockedUserModel()->removeByPostAndUser($blogDetail['post_id'], $userDetail['user_id']);

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
        } elseif ($request->isXmlHttpRequest()) {
            return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'data' => 'unauthenticated'), true));
        } else {
            return $this->redirectForSuccess('profile-home', $this->translate('You are not authenticated to do this.'));
        }
    }

    public function getEpisodeTitlesAction()
    {
        return $this->getResponse()->setContent(Json::encode(array(
            'html' => $this->getEpisodeModel()->getTitlesByUser($this->getSessionContainer()->offsetGet('user_id')),
            'status' => 'success',
        ), true));
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
     * @return \BlogUser\Model\Episode
     */
    protected function getEpisodeModel()
    {
        isset($this->episodeModel) || $this->episodeModel = $this->getServiceLocator()->get('BlogUser\Model\Episode');
        return $this->episodeModel;
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
     * @return \Blog\Model\Comment
     */
    private function getCommentModel()
    {
        isset($this->commentModel) || $this->commentModel = $this->getServiceLocator()->get('Blog\Model\Comment');
        return $this->commentModel;
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

    /**
     * @return \NBlog\Model\Helper\User
     */
    private function getUserHelperModel()
    {
        isset($this->userHelperModel) || $this->userHelperModel = $this->getServiceLocator()->get('NBlog\Model\Helper\User');
        return $this->userHelperModel;
    }
}