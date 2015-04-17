<?php
/**
 * UserWall Dao Model
 *
 * @category        Dao Model
 * @package         Blog
 * @author          Md.Atiqul Haque<md_atiqulhaque@yahoo.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Model\Dao;
use NBlog\Model\Dao\RelationBase;
use Zend\Db\Sql\Predicate\Literal;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\Expression;

class UserWall extends RelationBase
{
    protected $table = 'user_wall';
    protected $primaryKey = NULL;

    public function getAll(array $options = array())
    {
        $select = $this->select()
                       ->columns(array('*', 'wall_content_created' => 'created', 'wall_content_modified' =>'modified', 'wall_content_published' => 'published'));
        $select = $this->setConditions($select, $options)
            ->order(array("{$this->table}.created DESC"));
//            ->offset(empty($options['offset']) ? 0 : (int)$options['offset']);
//            ->limit(empty($options['limit']) ? 15 : (int)$options['limit']);

        $sql = new Select('reports');
        $sql->where(array('created_by' => $options['loggedInUser']));
        $predicateSet = new PredicateSet(array(
            new Literal("{$this->table}.content_id = reports.id_of_reported_on"),
            new Literal("{$this->table}.writing_type = reports.reported_on")
        ));
        $select->join(array('reports' => $sql), $predicateSet, array('hasUserReported' => 'status'), Select::JOIN_LEFT);

        $sql = new Select('hidings');
        $sql->where(array('hidden_by' => $options['loggedInUser'], 'status' => 1));
        $predicateSet = new PredicateSet(array(
            new Literal("{$this->table}.content_id = hidings.content_id"),
            new Literal("{$this->table}.writing_type = hidings.content_type")
        ));
        $select->join(array('hidings' => $sql), $predicateSet, array('isHidden' => 'status'), Select::JOIN_LEFT);

        $sql = new Select('subscribers');
        $sql->where(array('user_id' => $options['loggedInUser'], 'is_active' => 1));
        $predicateSet = new PredicateSet(array(
            new Literal("{$this->table}.content_id=subscribers.subscribed_id"),
            new Literal("{$this->table}.writing_type = subscribers.subscribed_for")
        ));
        $select->join(array('subscribers' => $sql), $predicateSet, array('isFavorite' => 'is_active'), Select::JOIN_LEFT);

        $sql = new Select('blocked_bloggers');
        $sql->where(array('blogger_id' => $options['loggedInUser']));
        $predicateSet = new PredicateSet(array(
            new Literal("{$this->table}.content_id=blocked_bloggers.writing_id"),
            new Literal("{$this->table}.writing_type = blocked_bloggers.blocked_for")
        ));
        $select->join(array('blocked_bloggers' => $sql), $predicateSet, array('isBlocked' => 'blocked_blogger_id'), Select::JOIN_LEFT);

        $limit = empty($options['limit']) ? 5 : intval($options['limit']);
        $offset = empty($options['offset']) ? 0 : intval($options['offset']);
        return $this->returnResultByRawQuery($this->getSqlStringForSqlObject($select) . " LIMIT {$limit} OFFSET {$offset}");
    }

    public function setConditions(Select $select, array $arrOptions)
    {
        return $select->where(array(new In($this->table.'.created_by', $arrOptions['user_id'])));
    }
}