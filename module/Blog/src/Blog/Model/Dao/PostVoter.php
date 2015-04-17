<?php
namespace Blog\Model\Dao;

use NBlog\Model\Dao\RelationBase;
use Zend\Db\Sql\Expression;

/**
 * Post Voter Dao Model
 *
 * @category        Dao Model
 * @package         NBlog
 * @author          Md.Atiqul Haque <md_atiqulhaque@yahoo.com>
 * @author          Md. Nuruzzaman Bappi <bappi.cse562@gmail.com>
 * @copyright       Copyright (c) 2014 Nokkhotro Lab. http://www.nokkhotrolab.com
 */
class PostVoter extends RelationBase
{
    protected $table = 'posts_voters';
    protected $primaryKey = 'post_voter_id';

    public function getVoteCount($currentUser, $competition, $episode)
    {
        $select = $this->select()
            ->columns(array('no' => new Expression("count(posts_voters.{$this->primaryKey})")))
            ->join('post_for_voting', "{$this->table}.voting_post_id = post_for_voting.post_id", array())
            ->where(array(
                "user_id" => $currentUser,
                "post_for_voting.episode" => $episode,
                "post_for_voting.vote_for" => $competition))
            ->limit(1);

        $result = $this->returnResultSet($select, true);
        return empty($result)? '0' : $result['no'];
    }
}
