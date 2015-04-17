<?php
/**
 * Blocked User Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;
use NBlog\Model\ServiceLocatorBlogDB;
use NBlog\Model\WritingType;

class BlockedUser extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\BlockedUser
     */
    protected $dao = null;
    protected $userHelperModel = null;

    const FOR_POST = WritingType::POST;
    const FOR_DISCUSSION = WritingType::DISCUSSION;
    const FOR_MOOD = WritingType::MOOD;
    const FOR_NOTICE = WritingType::NOTICE;
    const FOR_EPISODE = 9;

    public function getByPost($postId)
    {
        return $this->getByWritingId($postId, self::FOR_POST);
    }

    public function getByDiscussionId($discussionId)
    {
        return $this->getByWritingId($discussionId, self::FOR_DISCUSSION);
    }

    public function getByMoodId($moodId)
    {
        return $this->getByWritingId($moodId, self::FOR_MOOD);
    }

    private function getByWritingId($writingId, $writingType)
    {
        if (empty($writingId)) {
            return array();
        }

        return $this->getUserHelperModel()->getUsersDetail($this->dao->getByWritingIdWithFor($writingId, $writingType), array(
            'withProfile' => false,
            'userKey' => 'blogger_id',
            'withPostsCommentsCount' => false,
            'withDiscussionsCommentsCount' => false
        ));
    }

    public function checkBloggerBlocked($writingId, $loggedInUser, $writingType = self::FOR_POST)
    {
        if (empty($writingId) || empty($loggedInUser)) {
            return false;
        }

        return $this->dao->checkBloggerBlockedFor($writingId, $loggedInUser, $writingType);
    }

    public function removeByPostAndUser($postId, $userId)
    {
        if (empty($postId) || empty($userId)) {
            return false;
        }

        return $this->dao->removeByWritingAndUserWithFor($postId, $userId, self::FOR_POST);
    }

    public function checkBloggerBlockedForDiscussion($discussionId, $userId)
    {
        if (empty($discussionId) || empty($userId)) {
            return false;
        }

        return $this->dao->checkBloggerBlockedFor($discussionId, $userId, self::FOR_DISCUSSION);
    }

    public function removeByDiscussionAndUser($discussionId, $userId)
    {
        if (empty($discussionId) || empty($userId)) {
            return false;
        }

        return $this->dao->removeByWritingAndUserWithFor($discussionId, $userId, self::FOR_DISCUSSION);
    }

    public function removeByEpisodeAndUser($episodeId, $userId)
    {
        if (empty($episodeId) || empty($userId)) {
            return false;
        }

        return $this->dao->removeByWritingAndUserWithFor($episodeId, $userId, self::FOR_EPISODE);
    }

    /**
     * @return \NBlog\Model\Helper\User
     */
    private function getUserHelperModel()
    {
        isset($this->userHelperModel) || $this->userHelperModel = $this->serviceManager->get('NBlog\Model\Helper\User');
        return $this->userHelperModel;
    }
}