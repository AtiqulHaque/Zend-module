<?php
/**
 * Comment Model
 *
 * @category        Model
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Model;

use NBlog\Model\PostType;
use NBlog\Model\ReportStatus;
use NBlog\Model\Writing;
use NBlog\Model\WritingStatus;
use NBlog\Model\WritingType;

class Comment extends Writing
{
    const TYPE_NEW = 1;
    const TYPE_REPLY = 2;
    /**
     * @var     \Blog\Model\Dao\Comment
     */
    protected $dao = null;
    protected $blogModel;
    protected $discussionModel;
    protected $moodModel;
    protected $noticeModel;
    protected $userModel;
    protected $writingStatusModel;
    protected $postCategoryHelperModel;

    public function getAll()
    {
        return $this->dao->getAll();
    }

    public function getRecentComments($userId = null, $limit = 15)
    {
        $comments = $this->getUsersDetail($this->dao->getRecentCommentsOnPost(array('userId' => $userId, 'limit' => $limit, 'is_reported' => ReportStatus::NO_REPORT)));
        if (empty($comments)) {
            return array();
        }

        $userIds = $writingIds = array();
        foreach ($comments AS $comment) {
            $userIds[] = $comment['post_created_by'];
            $writingIds[] = $comment['id_of_comment_for'];
        }

        $userDetails = $this->getUserModel()->getUsersDetails($userIds, true);
        $otherCommenters = $this->getOtherCommentersByWritingIds($userId, $writingIds);
        foreach ($comments AS $key => $comment) {
            foreach ($userDetails AS $userDetail) {
                if ($comment['post_created_by'] == $userDetail['user_id']) {
                    $comments[$key]['post_user_detail'] = $this->getUsersDetail($userDetail, true, 'user_id');
                    $comments[$key]['count_commenter_person'] = $this->dao->countCommentsByPostIds(array('postIds' => $comment['id_of_comment_for']));
                    $comments[$key]['otherCommenters'] = empty($otherCommenters[$comment['id_of_comment_for']]) ? array() : $otherCommenters[$comment['id_of_comment_for']];
                    break;
                }
            }
        }

        return $comments;
    }

    private function getOtherCommentersByWritingIds($userId, $writingIds)
    {
        $result = array();
        $otherCommenters = $this->getUsersDetail($this->dao->getOtherCommentersByWritingIds($userId, $writingIds), true, 'user_id');
        if (!empty($otherCommenters)) {
            foreach ($otherCommenters AS $user) {
                $result[$user['writing_id']][] = $user;
            }
        }
        return $result;
    }

    public function getAllCommenters($writingId, $commentFor)
    {
        $users = $this->dao->getAllCommenters($writingId, $commentFor);
        $result = array();
        if (!empty($users)) {
            foreach ($users AS $user) {
                $result[$user['user_id']] = $user['user_id'];
            }
        }
        return $result;
    }

    public function getRecentCommentsOnPostOfUserId($userId, $options = array())
    {
        if (empty($userId)) {
            return array();
        }

        $options = array_merge(array('userId' => $userId), $this->setCountOffset($options));
        return $this->getUsersDetail($this->dao->getRecentCommentsOnPostOfUserId($options));
    }

    public function countRecentCommentsOnPostOfUserId($userId, $options = array())
    {
        if (empty($userId)) {
            return 0;
        }

        $options = array_merge(array('userId' => $userId), $options);
        return $this->dao->countRecentCommentsOnPostOfUserId($options);
    }

    public function getUserCommentsOnPosts($userId, $options = array())
    {
        if (empty($userId)) {
            return array();
        }

        $options = array_merge(array('userId' => $userId), $this->setCountOffset($options));
        return $this->getUsersDetail($this->dao->getUserCommentsOnPosts($options));
    }

    public function countUserCommentsOnPosts($userId, $options = array())
    {
        if (empty($userId)) {
            return 0;
        }

        $options = array_merge(array('userId' => $userId), $options);
        return $this->dao->countUserCommentsOnPosts($options);
    }

    public function getRepliesOfUserComments($userId, $options = array())
    {
        if (empty($userId)) {
            return array();
        }

        $options = array_merge(array('userId' => $userId), $this->setCountOffset($options));
        return $this->getUsersDetail($this->dao->getRepliesOfUserCommentsOnPost($options), false, 'reply_created_by');
    }

    public function countRepliesOfUserComments($userId, $options = array())
    {
        if (empty($userId)) {
            return 0;
        }

        $options = array_merge(array('userId' => $userId), $options);
        return $this->dao->countRepliesOfUserCommentsOnPost($options);
    }

    public function getMyRepliesOfComments($userId, $options = array())
    {
        if (empty($userId)) {
            return array();
        }

        $options = array_merge(array('userId' => $userId), $this->setCountOffset($options));
        return $this->getUsersDetail($this->dao->getMyRepliesOfComments($options), false, 'commenter');
    }

    public function countMyRepliesOfComments($userId, $options = array())
    {
        if (empty($userId)) {
            return 0;
        }

        $options = array_merge(array('userId' => $userId), $options);
        return $this->dao->countMyRepliesOfComments($options);
    }

    public function countCommentsOfASingleUser($userId)
    {
        if (empty($userId)) {
            return array();
        }

        $result = $this->dao->countCommentOnPostOfUsers(array($userId));
        return empty($result) ? 0 : current($result);
    }

    public function getTopCommentPosters($limit = 10)
    {
        return $this->getUsersDetail($this->dao->getTopCommentPosters($limit));
    }

    public function getDetailWithWriting($commentId)
    {
        if (empty($commentId)) {
            return array();
        }

        $result = $this->dao->getTraceabilityOfWriting($commentId);
        if (!empty($result['comment_for'])) {
            switch ($result['comment_for']) {
                case WritingType::POST:
                    $result['traceable'] = $result['post_permalink'];
                    $result['writing_id'] = $result['post_id'];
                    break;

                case WritingType::DISCUSSION:
                    $result['traceable'] = $result['discussion_permalink'];
                    $result['writing_id'] = $result['discussion_id'];
                    break;

                case WritingType::MOOD:
                    $result['traceable'] = $result['mood_permalink'];
                    $result['writing_id'] = $result['mood_id'];
                    break;

                case WritingType::NOTICE:
                    $result = array_merge($result, $this->getNoticeModel()->getDetail($result['id_of_comment_for']));
                    $result['traceable'] = $result['permalink'];
                    $result['writing_id'] = $result['notice_id'];
                    break;
            }
        }

        return $result;
    }

    public function getDetailByUserId($loggedInUser, $commentId)
    {
        if (empty($commentId)) {
            return false;
        }

        return $this->dao->getDetailByUserId($loggedInUser, $commentId);
    }

    public function getAllCommentsByUserId($userId)
    {
        if (empty($userId)) {
            return false;
        }

        return $this->dao->getAllCommentsByUserId($userId);
    }

    public function getAllCommentsForMyPosts($userId, array $options = array())
    {
        if (empty($userId)) {
            return false;
        }
        $options = $this->setCountOffset($options);
        switch($options['sequenceType']){
            case PostType::SEQUENCE_COMMENTS :
                $result = $this->dao->getAllCommentsForMyPosts($userId , $options);
                break;
            case PostType::LAST_UPDATED_COMMENTS :
                $result = $this->dao->getAllCommentsForMyPosts($userId , $options);
                break;
            default :
                $result = $this->dao->getAllCommentsForMyPostsThread($userId , $options);

                if(!empty($result)){

                $commentInfo = array();
                foreach($result AS $comment){
                    $commentInfo['postIds'][] = $comment['id_of_comment_for'];
                    $commentInfo['latestCommentIds'][] = $comment['comment_id'];
                }

                $comments = $this->dao->getAllOthersComments($userId, $commentInfo);

                foreach($result AS $key => $row){
                    foreach($comments AS $commentKey => $comment) {
                        if ($row['id_of_comment_for'] == $comment['id_of_comment_for']) {
                            $result[$key]['otherComments'][] = $comment;
                            unset($comments[$commentKey]);
                        }
                    }
                }
             }
        }

        return $this->getUsersDetail($this->getPostsCategories($result), true, 'created_by');
    }

    public function getByMoodId($moodId, array $options = array())
    {
        if (empty($moodId)) {
            return false;
        }

        return current($this->getByMoodIds(array($moodId), $options));
    }

    public function getByMoodIds(array $moodIds, array $options = array())
    {
        if (empty($moodIds)) {
            return array();
        }

        $comments = $this->getUsersDetail($this->dao->getByMoodIds($moodIds, $options));
        $comments = $this->arrangeCommentsAndReplies($comments);
        return $this->arrangeCommentsAsWritings($comments);
    }

    public function getByBlogId($blogId, array $options = array())
    {
        if (empty($blogId)) {
            return false;
        }

        $result = $this->getByBlogIds(array($blogId), $options);
        return empty($result) ? $result : current($result);
    }

    public function getByBlogIds(array $blogIds, array $options = array())
    {
        if (empty($blogIds)) {
            return array();
        }

        $comments = $this->getUsersDetail($this->dao->getByPostIds($blogIds, $options));
        $comments = $this->arrangeCommentsAndReplies($comments);
        return $this->arrangeCommentsAsWritings($comments);
    }

    public function getByDiscussionId($discussionId, array $options = array())
    {
        if (empty($discussionId)) {
            return false;
        }

        return current($this->getByDiscussionIds(array($discussionId), $options));
    }

    public function getByDiscussionIds(array $discussionIds, array $options = array())
    {
        if (empty($discussionIds)) {
            return array();
        }

        $comments = $this->getUsersDetail($this->dao->getByDiscussionIds($discussionIds, $options));
        $comments = $this->arrangeCommentsAndReplies($comments);
        return $this->arrangeCommentsAsWritings($comments);
    }

    private function arrangeCommentsAndReplies(array $comments)
    {
        if (empty($comments)) {
            return array();
        }

        foreach ($comments AS $key => $comment) {
            if (empty($comment['parent_id'])) {
                continue;
            }

            foreach ($comments AS $parentKey => $parentComment) {
                if ($parentComment['comment_id'] == $comment['parent_id']) {
                    $comments[$parentKey]['replies'][] = $comment;
                    unset($comments[$key]);
                    break;
                }
            }
        }

        return $comments;
    }

    private function arrangeCommentsAsWritings(array $comments)
    {
        $result = array();
        foreach((array)$comments AS $comment) {
            $result[$comment['id_of_comment_for']][] = $comment;
        }

        return $result;
    }

    public function getByNoticeId($noticeId, array $options = array())
    {
        if (empty($noticeId)) {
            return false;
        }

        $comments = $this->getUsersDetail($this->dao->getByNoticeId($noticeId, $options));
        $comments = $this->arrangeCommentsAndReplies($comments);
        return $this->arrangeCommentsAndReplies($comments);
    }

    public function getCommentCountByDiscussionId($discussionId)
    {
        if (empty($discussionId)) {
            return false;
        }

        return $this->dao->getCommentCountByDiscussionId($discussionId);
    }

    public function getPersonCountByDiscussionId($discussionId)
    {
        if (empty($discussionId)) {
            return false;
        }

        return $this->dao->getPersonCountByDiscussionId($discussionId);
    }

    public function save(array $data)
    {
        if (empty($data)) {
            return false;
        }

        empty($data['comment']) || $data['details'] = $data['comment'];
        $data['status'] = WritingStatus::PUBLISHED;
        $data['is_published'] = 1;
        $data['type'] = empty($data['type']) ? self::TYPE_NEW : $data['type'];
        $data['created'] = $this->getCurrentDateTime();

        switch ($data['comment_for']) {
            case WritingType::POST:
                $blogModel = $this->getBlogModel();
                $blogDetail = $blogModel->getByPermalink($data['permalink'], array(
                    'loggedInUser' => $data['user_id'],
                    'withCommentBlocking' => true
                ));
                if (empty($blogDetail)) {
                    return false;
                } else {
                    $data['id_of_comment_for'] = $blogDetail['post_id'];
                    $data['writer_id'] = $blogDetail['post_created_by'];
                    if (($data['comment_id'] = $this->dao->save($data))) {
                        $blogModel->incrementCommentCounting($data['id_of_comment_for']);
                    } else {
                        return false;
                    }
                }
                $data['commentOn'] = $blogDetail;
                break;

            case WritingType::NOTICE:
                $noticeModel = $this->getNoticeModel();
                $noticeDetail = $noticeModel->getByPermalink($data['permalink']);
                if (empty($noticeDetail)) {
                    return false;
                } else {
                    $data['id_of_comment_for'] = $noticeDetail['notice_id'];
                    $data['writer_id'] = $noticeDetail['notice_created_by'];
                    if (($data['comment_id'] = $this->dao->save($data))) {
                        $noticeModel->incrementCommentCounting($data['id_of_comment_for']);
                    } else {
                        return false;
                    }
                }
                $data['commentOn'] = $noticeDetail;
                break;

            case WritingType::DISCUSSION:
                $discussionModel = $this->getDiscussionModel();
                $discussionDetail = $discussionModel->getByPermalink($data['permalink'], array(
                    'loggedInUser' => $data['user_id'],
                    'withCommentBlocking' => true
                ));
                if (empty($discussionDetail)) {
                    return false;
                } else {
                    $data['id_of_comment_for'] = $discussionDetail['discussion_id'];
                    $data['writer_id'] = $discussionDetail['discussion_created_by'];
                    if (($data['comment_id'] = $this->dao->save($data))) {
                        $discussionModel->incrementCommentCounting($data['id_of_comment_for']);
                    } else {
                        return false;
                    }
                }
                $data['commentOn'] = $discussionDetail;
                break;

            case WritingType::MOOD:
                $moodModel = $this->getMoodModel();
                $moodDetail = $moodModel->getByPermalink($data['permalink'], array(
                    'loggedInUser' => $data['user_id'],
                    'withCommentBlocking' => true
                ));
                if (empty($moodDetail)) {
                    return false;
                } else {
                    $data['id_of_comment_for'] = $moodDetail['mood_id'];
                    $data['writer_id'] = $moodDetail['mood_created_by'];
                    if (($data['comment_id'] = $this->dao->save($data))) {
                        $moodModel->incrementCommentCounting($data['id_of_comment_for']);
                    } else {
                        return false;
                    }
                }
                $data['commentOn'] = $moodDetail;
                break;

            default:
                return false;
        }

        return array_merge(array(
            'comment_created' => $data['created'],
            'comment_created_by' => $data['user_id'],
            'total_comment_favorited' => 0
        ), $this->getUsersDetail($data, true, 'user_id'));
    }

    public function countCommentOnPostOfUsers(array $userIds)
    {
        if (empty($userIds)) {
            return 0;
        }

        return $this->dao->countCommentOnPostOfUsers($userIds);
    }

    public function countCommentOnDiscussionOfUsers(array $userIds)
    {
        if (empty($userIds)) {
            return 0;
        }

        return $this->dao->countCommentOnDiscussionOfUsers($userIds);
    }

    public function countUserRepliesOfComments($userId)
    {
        if (empty($userId)) {
            return 0;
        }

        return $this->dao->countUserRepliesOfComments($userId);
    }

    public function remove($commentId)
    {
        if (empty($commentId)) {
            return false;
        }

        $commentDetail = $this->getDetail($commentId);
        $totalReplies = $this->dao->countRepliesById($commentId);
        $result = $this->dao->removeRepliesById($commentId);
        if (empty($result)) {
            return false;
        }

        $result = $this->dao->remove($commentId);
        if (empty($result)) {
            return false;
        } else {
            $totalReplies++;
        }

        if ($commentDetail['comment_for'] == WritingType::POST) {
            $this->getBlogModel()->decrementCommentCounting($commentDetail['id_of_comment_for'], $totalReplies);
        } elseif ($commentDetail['comment_for'] == WritingType::DISCUSSION) {
            $this->getDiscussionModel()->decrementCommentCounting($commentDetail['id_of_comment_for'], $totalReplies);
        } elseif ($commentDetail['comment_for'] == WritingType::NOTICE) {
            $this->getNoticeModel()->decrementCommentCounting($commentDetail['id_of_comment_for'], $totalReplies);
        }
        return $result;
    }


    public function decreaseTotalCommentByReportedAction($commentId)
    {
        if (empty($commentId)) {
            return false;
        }

        $commentDetail = $this->getDetail($commentId);
        switch ($commentDetail['comment_for']) {
            case WritingType::MOOD :
                $result = $this->getMoodModel()->decrementCommentCounting($commentDetail['id_of_comment_for']);
                break;

            case WritingType::POST :
                $result = $this->getBlogModel()->decrementCommentCounting($commentDetail['id_of_comment_for']);
                break;

            case WritingType::DISCUSSION :
                $result = $this->getDiscussionModel()->decrementCommentCounting($commentDetail['id_of_comment_for']);
                break;

            case WritingType::NOTICE :
                $result = $this->getNoticeModel()->decrementCommentCounting($commentDetail['id_of_comment_for']);
                break;

            default:
                $result = false;
        }
        return $result;
    }

    public function removeByPostId($postId)
    {
        if (empty($postId)) {
            return array();
        }

        $totalComments = $this->dao->countByIdWithFor($postId, WritingType::POST);
        $result = $this->dao->removeByIdWithFor($postId, WritingType::POST);
        if (empty($result)) {
            return false;
        }

        $this->getBlogModel()->decrementCommentCounting($postId, (int)$totalComments);
        return $result;
    }

    public function removeByDiscussionId($discussionId)
    {
        if (empty($discussionId)) {
            return array();
        }

        $totalComments = $this->dao->countByIdWithFor($discussionId, WritingType::DISCUSSION);
        $result = $this->dao->removeByIdWithFor($discussionId, WritingType::DISCUSSION);
        if (empty($result)) {
            return false;
        }

        $this->getDiscussionModel()->decrementCommentCounting($discussionId, (int)$totalComments);
        return $result;
    }

    public function getCommentIdsFromWallData(array $allWallData)
    {
        if (empty($allWallData)) {
            return array();
        }

        $commentIds = array();
        foreach ($allWallData AS $row) {
            if (empty($row['comments'])) {
                continue;
            }

            foreach ($row['comments'] AS $comment) {
                $commentIds[] = $comment['comment_id'];
                if (!empty($comment['replies'])) {
                    $commentIds = array_merge($commentIds, $this->getCommentIds($comment['replies']));
                }
            }
        }

        return $commentIds;
    }

    public function getCommentIds(array $comments)
    {
        if (empty($comments)) {
            return array();
        }

        $commentIds = array();
        foreach ($comments AS $comment) {
            $commentIds[] = $comment['comment_id'];
            if (!empty($comment['replies'])) {
                $commentIds = array_merge($commentIds, $this->getCommentIds($comment['replies']));
            }
        }

        return $commentIds;
    }

    public function getByWallPostId($postId, array $options = array())
    {
        if (empty($postId)) {
            return false;
        }

        $options = array_merge($options, array('statuses' => $this->getWritingStatusModel()->getForUserWall()));
        $comments = $this->getUsersDetail($this->dao->getByWallPostId($postId, $options));
        return $this->arrangeCommentsAndReplies($comments);
    }

    public function getByWallMyPostId($postId, array $options = array())
    {
        if (empty($postId)) {
            return false;
        }

        $options = array_merge($options, array('statuses' => $this->getWritingStatusModel()->getForUserPosts()));
        $comments = $this->getUsersDetail($this->dao->getByWallPostId($postId, $options));
        return $this->arrangeCommentsAndReplies($comments);
    }

    public function updateByCommentId(array $options, $id)
    {
        if (empty($options) || empty($id)) {
            return false;
        }
        $data = array('user_id' => $options['user_id'], 'details' => $options['comment']);
        return $this->dao->updateByCommentId($data, $id);
    }

    protected function getUsersDetail($comments, $withProfile = true, $index = 'comment_created_by')
    {
        return parent::getUsersDetail($comments, $withProfile, $index);
    }

    private function getPostsCategories(array $posts, $index = 'post_id')
    {
        if (empty($posts)) {
            return $posts;
        }

        return $this->getPostCategoryHelperModel()->getPostsCategories($posts, $index);
    }

    /**
     * @return \Blog\Model\Blog
     */
    private function getBlogModel()
    {
        isset($this->blogModel) || $this->blogModel = $this->serviceManager->get('Blog\Model\Blog');
        return $this->blogModel;
    }

    /**
     * @return \Blog\Model\Notice
     */
    private function getNoticeModel()
    {
        isset($this->noticeModel) || $this->noticeModel = $this->serviceManager->get('Blog\Model\Notice');
        return $this->noticeModel;
    }

    /**
     * @return \BlogUser\Model\Discussion
     */
    private function getDiscussionModel()
    {
        isset($this->discussionModel) || $this->discussionModel = $this->serviceManager->get('BlogUser\Model\Discussion');
        return $this->discussionModel;
    }

    /**
     * @return \BlogUser\Model\Mood
     */
    private function getMoodModel()
    {
        isset($this->moodModel) || $this->moodModel = $this->serviceManager->get('BlogUser\Model\Mood');
        return $this->moodModel;
    }

    /**
     * @return \NBlog\Model\Helper\PostCategory
     */
    private function getPostCategoryHelperModel()
    {
        isset($this->postCategoryHelperModel) || $this->postCategoryHelperModel = $this->serviceManager->get('NBlog\Model\Helper\PostCategory');
        return $this->postCategoryHelperModel;
    }

    /**
     * @return  \NBlog\Model\User
     */
    private function getUserModel()
    {
        isset($this->userModel) || $this->userModel = $this->serviceManager->get('NBlog\Model\User');
        return $this->userModel;
    }

    /**
     * @return \NBlog\Model\WritingStatus
     */
    private function getWritingStatusModel()
    {
        isset($this->writingStatusModel) || $this->writingStatusModel = $this->serviceManager->get('NBlog\Model\WritingStatus');
        return $this->writingStatusModel;
    }
}
