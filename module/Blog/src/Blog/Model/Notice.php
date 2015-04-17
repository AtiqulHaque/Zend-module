<?php
namespace Blog\Model;

use BlogUser\Model\BlockedUser;
use BlogUser\Model\FavoriteType;
use NBlog\Model\ServiceLocatorUserDB;

/**
 * Notice Model
 *
 * @category        Model
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Lab. http://www.nokkhotrolab.com
 */
class Notice extends ServiceLocatorUserDB
{
    /**
     * @var     \Blog\Model\Dao\Notice
     */
    protected $dao = null;
    private $adminModel;
    private $blockedUserModel;
    private $hiddenModel;
    protected $userHelperModel;

    public function getLatestActiveNotice($isUserLoggedIn = true)
    {
        $notice = $this->dao->getAll(array('is_active' => 1, 'limit' => 1, 'user_logged_in' => $isUserLoggedIn));
        return empty($notice) ? array() : current($notice);
    }

    public function getAll(array $options = array())
    {
        $options = $this->setCountOffset($options);
        return $this->getUsersDetail($this->dao->getAll($options), true);
    }

    public function countAll()
    {
        return $this->dao->countAll();
    }

    public function getByPermalink($permalink, array $options = array())
    {
        $notice = $this->dao->getByPermalink($permalink);
        if (empty($notice)) {
            return array();
        }

        empty($options['withProfile']) || $notice = $this->getAdminDetail($notice);
        if (!empty($options['loggedInUser'])) {
            if (isset($options['withHidingStatus'])) {
                $notice['isHidden'] = $this->getHiddenModel()->getStatusOfHidden($options['loggedInUser'], $notice['notice_id'], FavoriteType::NOTICE);
            }
            if (isset($options['withCommentBlocking'])) {
                $notice['isBlocked'] = $this->getBlockedUserModel()->checkBloggerBlocked($notice['notice_id'], $options['loggedInUser'], BlockedUser::FOR_NOTICE);
            }
        }

        return $notice;
    }

    public function incrementCommentCounting($noticeId)
    {
        return $this->dao->incrementCommentCounting($noticeId);
    }

    public function decrementCommentCounting($noticeId, $decrementValue = 1)
    {
        return $this->dao->decrementCommentCounting($noticeId, $decrementValue);
    }

    public function getByIds(array $noticeIds)
    {
        if (empty($noticeIds)) {
            return false;
        }

        $result = $this->dao->getByIds($noticeIds);
        $notices = array();
        if (!empty($result)) {
            foreach($result AS $notice) {
                $notices[$notice['notice_id']] = $notice;
            }
        }
        return $notices;
    }

    private function getAdminDetail($notices, $withProfile = false, $index = 'notice_created_by')
    {
        if (empty($notices)) {
            return $notices;
        }

        if (is_array(current($notices))) {
            $isCollection = true;
        } else {
            $isCollection = false;
            $notices = array($notices);
        }

        $userIds = array();
        foreach ($notices AS $notice) {
            $userIds[] = $notice[$index];
        }

        $usersDetails = $this->getAdminModel()->getAdminUsersDetails($userIds, $withProfile);
        foreach ($notices AS $key => $notice) {
            foreach($usersDetails AS $usersDetail) {
                if (in_array($notice[$index], $usersDetail)) {
                    $notices[$key] = array_merge($notice, $usersDetail);
                    break;
                }
            }
        }

        reset($notices);
        return empty($isCollection) ? current($notices) : $notices;
    }

    private function getUsersDetail($notices, $withProfile = false, $index = 'notice_created_by')
    {
        if (empty($notices)) {
            return $notices;
        }

        return $this->getUserHelperModel()->getUsersDetail($notices, array(
            'withProfile' => $withProfile,
            'userKey' => $index,
            'withPostsCommentsCount' => true,
            'withDiscussionsCommentsCount' => false,
            'withFriendsAndFollowersCount' => true
        ));
    }

    /**
     * @return \Blog\Model\Admin
     */
    private function getAdminModel()
    {
        isset($this->adminModel) || $this->adminModel = $this->serviceManager->get('Blog\Model\Admin');
        return $this->adminModel;
    }

    /**
     * @return \BlogUser\Model\BlockedUser
     */
    private function getBlockedUserModel()
    {
        isset($this->blockedUserModel) || $this->blockedUserModel = $this->serviceManager->get('BlogUser\Model\BlockedUser');
        return $this->blockedUserModel;
    }

    /**
     * @return \BlogUser\Model\Hidden
     */
    protected function getHiddenModel()
    {
        isset($this->hiddenModel) || $this->hiddenModel = $this->serviceManager->get('BlogUser\Model\Hidden');
        return $this->hiddenModel;
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
