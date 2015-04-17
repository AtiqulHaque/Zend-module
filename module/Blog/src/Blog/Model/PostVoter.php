<?php
namespace Blog\Model;
use NBlog\Model\ServiceLocatorBlogDB;
use NBlog\Model\VoteConfig;

/**
 * Post Voter Model
 *
 * @category        Model
 * @package         NBlog
 * @author          Md.Atiqul Haque <md_atiqulhaque@yahoo.com>
 * @author          Md. Nuruzzaman Bappi <bappi.cse562@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2014 Nokkhotro Lab. http://www.nokkhotrolab.com
 */
class PostVoter extends ServiceLocatorBlogDB
{
    /**
     * @var     \Blog\Model\Dao\PostVoter
     */
    protected $dao = null;
    protected $postForVotingModel;

    public function voteForPost($postId, $loggedInUserId)
    {
        if (empty($loggedInUserId)) {
            return false;
        }

        $result = $this->save(array(
            'user_id' => $loggedInUserId,
            'voting_post_id' => $postId
        ));

        empty($result) || $this->getPostForVotingModel()->incrementVoteCountForPost($postId);
        return $result;
    }

    public function getVoteCount($currentUser, $competition, $episode = VoteConfig::EPISODE_1)
    {
        if (empty($currentUser) || empty($competition)) {
            return false;
        }

        return intval($this->dao->getVoteCount($currentUser, $competition, $episode));
    }

    /**
     * @return \NBlog\Model\PostForVoting
     */
    private function getPostForVotingModel()
    {
        isset($this->postForVotingModel) || $this->postForVotingModel = $this->serviceManager->get('NBlog\Model\PostForVoting');
        return $this->postForVotingModel;
    }
}