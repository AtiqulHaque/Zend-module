<?php
/**
 * Discussion Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;

use NBlog\Model\Writing;
use NBlog\Model\WritingStatus;
use NBlog\Model\CategoryDiscussion;
use NBlog\Model\ReportStatus;

class Discussion extends Writing
{
    /**
     * @var     \BlogUser\Model\Dao\Discussion
     */
    protected $dao = null;
    protected $discussionCategoryModel = null;
    protected $discussionCategoryHelperModel = null;

    public function getAll($options)
    {
        $options = array_merge($this->setCountOffset((array)$options));
        $result = $this->dao->getAll($options);
        return $this->getUsersDetail($this->getDiscussionsCategories($result), true);
    }

    public function getAllDiscussion($options, $limit = '')
    {
        $options = array_merge($this->setCountOffset((array)$options), array('limit' => $limit, 'is_reported' => ReportStatus::NO_REPORT));
        $commentModel = $this->getCommentModel();
        $result = $this->dao->getAll($options);
        $discussion = $this->getUsersDetail($this->getDiscussionsCategories($result), true);

        foreach ($discussion AS $key => $discussions) {
            $discussion[$key]['commenter_count'] = $commentModel->getCommentCountByDiscussionId($discussions['discussion_id']);

            $discussion[$key]['commenter_person_count'] = $commentModel->getPersonCountByDiscussionId($discussions['discussion_id']);
        }

        return $discussion;
    }

    public function getOldDiscussions($options, $discussionIds, $limit = '')
    {
        $options = array_merge($this->setCountOffset((array)$options), array('limit' => $limit));
        $result = $this->dao->getOldDiscussions($options, $discussionIds);
        return $this->getUsersDetail($this->getDiscussionsCategories($result), true);
    }

    public function getLatestDiscussion($options, $limit = '')
    {
        $options = array_merge($this->setCountOffset((array)$options), array('limit' => $limit));
        $commentModel = $this->getCommentModel();
        $result = $this->dao->getAll($options);
        $discussion = $this->getUsersDetail($this->getDiscussionsCategories($result), true);

        foreach ($discussion AS $key => $discussions) {
            $discussion[$key]['commenter_count'] = $commentModel->getCommentCountByDiscussionId($discussions['discussion_id']);
            $discussion[$key]['commenter_person_count'] = $commentModel->getPersonCountByDiscussionId($discussions['discussion_id']);
        }
        usort($discussion, array($this, 'sortByTopDiscussionComments'));

        $discussionResult = array();
        foreach ($discussion AS $latestDiscussions) {
            if (!empty($latestDiscussions['commenter_count'])) {
                $discussionResult[] = $latestDiscussions;
            }

        }

        return $discussionResult;
    }

    public function sortByTopDiscussionComments($item1, $item2)
    {
        if ($item1['commenter_count'] == $item2['commenter_count']) return 0;
        return ($item1['commenter_count'] < $item2['commenter_count']) ? 1 : -1;
    }

    public function getByPermalink($permalink, array $options = array())
    {
        if (empty($permalink)) {
            return array();
        }

        $result = $this->dao->getByPermalink($permalink, $options);
        if (empty($result)) {
            return $result;
        }
        empty($options['withCategories']) || $result = current($this->getDiscussionsCategories(array($result)));
        empty($options['withUserDetail']) || $result = $this->getUsersDetail($result, true);
        return $result;
    }

    public function getByIds(array $discussionIds, $withCategory = false)
    {
        if (empty($discussionIds)) {
            return false;
        }

        $result = $this->dao->getByIds($discussionIds);
        if (!empty($result)) {
            $discussions = array();
            foreach($result AS $discussion) {
                $discussions[$discussion['discussion_id']] = $discussion;
            }
            $result = empty($withCategory) ? $discussions : $this->getDiscussionsCategories($discussions);
        }
        return $result;
    }

    public function getOtherDiscussions($userId, $discussionIds, $options = array())
    {
        $result = $this->dao->getOtherDiscussions(array_merge($options, array(
            'userId' => $userId, 'discussionIds' => (array)$discussionIds, 'status' => WritingStatus::PUBLISHED
        )));
        $discussion = $this->getUsersDetail($this->getDiscussionsCategories($result), true);
        return $discussion;
    }

    public function getDiscussionIds(array $discussions)
    {
        $discussionIds = array();
        foreach ($discussions AS $discussion) {
            $discussionIds[] = $discussion['discussion_id'];
        }
        return $discussionIds;
    }

    public function getRelatedDiscussions($categoryId, $discussionIds, $options = array())
    {
        if (empty($discussionIds)) {
            return false;
        }

        $result = $this->dao->getRelatedDiscussions(array_merge($options, array(
            'categoryId' => $categoryId,
            'status' => WritingStatus::PUBLISHED,
            'discussionIds' => (array)$discussionIds
        )));

        return $this->getUsersDetail($this->getDiscussionsCategories($result), true);
    }

    public function countAll($options)
    {
        if (empty($options)) {
            return 0;
        }

        return $this->dao->countAll($options);
    }

    public function getAllDetails(array $discussionIds)
    {
        if (empty($discussionIds)) {
            return array();
        }

        $result = $this->dao->getAllDetails($discussionIds);
        $result = $this->getUsersDetail($this->getDiscussionCategoryHelperModel()->getDiscussionsCategories($result));
        if (empty($result)) {
            return array();
        }
        $discussions = array();
        foreach ($result AS $row) {
            $discussions[$row['discussion_id']] = $row;
        }
        return $discussions;
    }

    public function getTopDiscussions()
    {
        return $this->dao->getTopDiscussions();
    }

    public function save(array $data)
    {
        $data['permalink'] = $this->getWritingPermalink();
        $data['created'] = date(DATE_W3C);
        $data['modified'] = date(DATE_W3C);
        ($data['status'] != WritingStatus::PUBLISHED) || $data['published'] = date(DATE_W3C);
        $data['is_draft'] = ($data['status'] === WritingStatus::DRAFT) ? 1 : 0;

        $discussionId = $this->dao->save($data);
        if (empty($discussionId)) {
            return false;
        }

        $discussionCategoryModel = $this->getCategoryDiscussionModel();
        foreach ($data['category_id'] AS $category) {
            $discussionCategoryModel->save(array(
                'category_id' => $category,
                'discussion_id' => $discussionId
            ));
        }

        return $discussionId;
    }

    public function modify(array $data, $discussionId)
    {
        $data['modified'] = date(DATE_W3C);
        if ($data['status'] == WritingStatus::PUBLISHED) {
            if ($data['old_status'] != WritingStatus::PUBLISHED) {
                $data['published'] = date(DATE_W3C);
            }
        } else {
            $data['published'] = null;
        }
        $data['is_draft'] = ($data['status'] === WritingStatus::DRAFT) ? 1 : 0;

        $result = $this->dao->modify($data, $discussionId);
        if (empty($result)) {
            return false;
        }

        $discussionCategoryModel = $this->getCategoryDiscussionModel();
        $discussionCategoryModel->remove($discussionId);
        foreach ($data['category_id'] AS $category) {
            $discussionCategoryModel->save(array(
                'category_id' => $category,
                'discussion_id' => $discussionId
            ));
        }

        return $discussionId;
    }

    public function delete($discussionId)
    {
        if (empty($discussionId)) {
            return false;
        }

        $this->getCategoryDiscussionModel()->remove($discussionId);
        $this->getCommentModel()->removeByDiscussionId($discussionId);
        return $this->dao->remove($discussionId);
    }

    public function countDiscussionOfUsers(array $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        return $this->dao->countDiscussionOfUsers($userIds);
    }

    protected function getUsersDetail($discussions, $withProfile = false, $index = 'discussion_created_by')
    {
        return parent::getUsersDetail($discussions, $withProfile, $index);
    }

    private function getDiscussionsCategories(array $discussions, $index = 'discussion_id')
    {
        if (empty($discussions)) {
            return $discussions;
        }

        return $this->getDiscussionCategoryHelperModel()->getDiscussionsCategories($discussions, $index);
    }

    /**
     * @return  CategoryDiscussion
     */
    private function getCategoryDiscussionModel()
    {
        isset($this->discussionCategoryModel) || $this->discussionCategoryModel = $this->serviceManager->get('NBlog\Model\CategoryDiscussion');
        return $this->discussionCategoryModel;
    }

    /**
     * @return \NBlog\Model\Helper\DiscussionCategory
     */
    private function getDiscussionCategoryHelperModel()
    {
        isset($this->discussionCategoryHelperModel) || $this->discussionCategoryHelperModel = $this->serviceManager->get('NBlog\Model\Helper\DiscussionCategory');
        return $this->discussionCategoryHelperModel;
    }
}