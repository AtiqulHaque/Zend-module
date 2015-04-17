<?php
/**
 * Hidden Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Nuruzzaman Bappi <bappi.cse562@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;

use NBlog\Model\ServiceLocatorBlogDB;
use NBlog\Model\WritingType;

class Hidden extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\Hidden
     */
    protected $dao = null;

    public function save(array $data)
    {
        $data['status'] = 1;
        return parent::save($data);
    }

    public function getHiddenStatus(array $idsHiddenOf, $loggedInUserId, $hiddenFor)
    {
        if (empty($idsHiddenOf)) {
            return array();
        }

        $result = $this->dao->getHiddenStatus($idsHiddenOf, $loggedInUserId, $hiddenFor);

        if (empty($result)) {
            return array();
        }
        $temp = array();
        foreach ($result AS $row) {
            $temp[$row['content_id']] = $row['status'];
        }
        return $temp;
    }

    public function getHiddenCommentsOfUser($loggedInUser, $id, $hiddenStatus = WritingType::COMMENT)
    {
        if (empty($loggedInUser)) {
            return array();
        }
        $hidings = $this->dao->getHiddenCommentsOfUser($loggedInUser, $id, $hiddenStatus);
        return $this->processHiddenStatus($hidings);
    }

    public function getStatusOfHidden($loggedInUser, $postId, $hiddenType = WritingType::POST)
    {
        if (empty($loggedInUser) || empty($postId)) {
            return array();
        }

        $status = $this->dao->getStatusOfHidden($loggedInUser, $postId, $hiddenType);
        return !empty($status);
    }

    public function getDetails($contentType, $contentId, $isActive = 0)
    {
        if (empty($contentType) || empty($contentId)) {
            return array();
        }

        return $this->dao->getDetails($contentType, $contentId, $isActive);
    }

    private function processHiddenStatus($hidings)
    {
        $ids = array();
        foreach ($hidings AS $status) {
            $ids[$status['content_id']] = $status['content_id'];
        }

        return $ids;
    }
}