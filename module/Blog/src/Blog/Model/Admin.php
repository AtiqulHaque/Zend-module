<?php
/**
 * Admin Model
 *
 * @category        Model
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Model;
use NBlog\Model\UserBase;

class Admin extends UserBase
{
    /**
     * @var     \Blog\Model\Dao\Admin
     */
    protected $dao = null;
    private $userHelperModel = null;

    public function getAdminUsersDetails(array $adminIds = array())
    {
        if (empty($adminIds)) {
            return false;
        }

        $resultSet = $this->getUserProfileImage($this->dao->getAdminUsersDetails($adminIds));
        $postsCommentsCount = $this->getUserHelperModel()->getPostCommentCountOfUsers($adminIds);
        $discussionsCommentsCount = $this->getUserHelperModel()->getDiscussionCommentCountOfUsers($adminIds);
        $friendsAndFollowersCount = $this->getUserHelperModel()->getFriendAndFollowerCountOfUsers($adminIds);

        foreach($resultSet AS $key => $usersDetail) {
            $userId = $usersDetail['user_id'];
            $postCounters = empty($postsCommentsCount[$userId]) ? array() : $postsCommentsCount[$userId];
            $discussionCounters = empty($discussionsCommentsCount[$userId]) ? array() : $discussionsCommentsCount[$userId];
            $friendsAndFollowersCounters = empty($friendsAndFollowersCount[$userId]) ? array() : $friendsAndFollowersCount[$userId];
            $resultSet[$key] = array_merge($usersDetail, $postCounters, $discussionCounters, $friendsAndFollowersCounters);
        }

        reset($resultSet);
        return $resultSet;
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
