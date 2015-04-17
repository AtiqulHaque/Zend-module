<?php
/**
 * Episodic Post Dao Model
 *
 * @category        Dao Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Dao;

use NBlog\Model\Dao\RelationBase;
use Zend\Db\Sql\Predicate\Expression;

class EpisodicPost extends RelationBase
{
    protected $table = 'posts';
    protected $primaryKey = 'post_id';

    public function getByPermalink($episodeId, $permalink, $status = null)
    {
        $select = $this->select()
                       ->columns(array('*', 'episode_created_by' => 'created_by', 'post_status' => 'status'))
                       ->where(array("episode_id = ?" => $episodeId, "{$this->table}.permalink = ?" => $permalink))
                       ->group("{$this->table}.{$this->primaryKey}")
                       ->limit(1);

        !isset($status) || $select->where(array("{$this->table}.status = ?" => $status));
        return $this->returnResultSet($select, true);
    }

    public function getPostsOfEpisode($episodeId)
    {
        $select = $this->select()->columns(array($this->primaryKey, 'episode_tag'))
                        ->where(array("{$this->table}.episode_id = ?" => $episodeId));

        return $this->returnResultSet($select);
    }

    public function countPostsOfEpisode($episodeId)
    {
        $select = $this->select()->columns(array('no' => new Expression("COUNT({$this->primaryKey})")))
                        ->where(array("{$this->table}.episode_id = ?" => $episodeId))
                        ->limit(1);

        $result = $this->returnResultSet($select, true);
        return empty($result) ? 0 : $result['no'];
    }
}