<?php
/**
 * Episodic Post Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;

use NBlog\Model\ServiceLocatorBlogDB;
use NBlog\Model\WritingStatus;
use NBlog\Model\CategoryPost;
use NBlog\Model\PostType;

class EpisodicPost extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\EpisodicPost
     */
    protected $dao = null;
    protected $commentModel;
    protected $categoryPostModel;
    protected $userHelperModel;
    protected $postCategoryHelperModel;

    public function save(array $data)
    {
        $data['permalink'] = $this->getWritingPermalink();
        $data['created_by'] = $data['user_id'];
        $data['created'] = date(DATE_W3C);
        $data['type'] = PostType::EPISODE;
        $data['is_draft'] = ($data['status'] === WritingStatus::DRAFT) ? 1 : 0;

        $episodicPostId = $this->dao->save($data);
        if (empty($episodicPostId)) {
            return false;
        }

        $postCategoryModel = $this->getCategoryPostModel();
        foreach ($data['category_id'] AS $category) {
            $postCategoryModel->save(array(
                'category_id' => $category,
                'post_id' => $episodicPostId
            ));
        }

        return $episodicPostId;
    }

    public function modify(array $data, $episodeId)
    {
        $data['is_draft'] = ($data['status'] === WritingStatus::DRAFT) ? 1 : 0;

        $data['modified'] = date(DATE_W3C);
        $data['moderator_id'] = '0';
        $data['moderated'] = '0000-00-00 00:00:00';

        if ($data['status'] == WritingStatus::PUBLISHED) {
            if ($data['old_status'] != WritingStatus::PUBLISHED) {
                $data['published'] = date(DATE_W3C);
            }
        } else {
            $data['published'] = null;
        }

        $result = $this->dao->modify($data, $episodeId);
        if (empty($result)) {
            return false;
        }

        $postCategoryModel = $this->getCategoryPostModel();
        $postCategoryModel->remove($episodeId);
        foreach ($data['category_id'] AS $category) {
            $postCategoryModel->save(array(
                'category_id' => $category,
                'post_id' => $episodeId
            ));
        }

        return $episodeId;
    }

    public function modifyEpisodeTag(array $data, $episodicPostId)
    {
        return $this->dao->modify($data, $episodicPostId);
    }

    public function delete($episodeId)
    {
        if (empty($episodeId)) {
            return false;
        }

        $this->getCategoryPostModel()->remove($episodeId);
        $this->getCommentModel()->removeByPostId($episodeId);
        return $this->dao->remove($episodeId);
    }

    public function getByPermalink($episodeId, $permalink, $status = null)
    {
        if (empty($permalink)) {
            return false;
        }

        $result = $this->dao->getByPermalink($episodeId, $permalink, $status);
        if (empty($result)) {
            return $result;
        }
        return $this->getUsersDetail(current($this->getPostsCategories(array($result))));
    }

    public function getPostsOfEpisode($episodeId)
    {
        if (empty($episodeId)) {
            return false;
        }

        return $this->dao->getPostsOfEpisode($episodeId);
    }

    public function countPostsOfEpisode($episodeId)
    {
        if (empty($episodeId)) {
            return false;
        }

        return $this->dao->countPostsOfEpisode($episodeId);
    }

    public function setTrashedStatus($episodicPostId)
    {
        return $this->updateEpisodicPostStatus($episodicPostId, WritingStatus::TRASH);
    }

    public function setDraftStatus($episodicPostId)
    {
        return $this->updateEpisodicPostStatus($episodicPostId, WritingStatus::DRAFT);
    }

    private function updateEpisodicPostStatus($episodicPostId, $status)
    {
        if (empty($episodicPostId)) {
            return false;
        }

        return $this->dao->modify(array('status' => $status, 'modified' => date(DATE_W3C)), $episodicPostId);
    }

    private function getUsersDetail($blogPosts, $withProfile = false, $index = 'episode_created_by')
    {
        if (empty($blogPosts)) {
            return $blogPosts;
        }

        return $this->getUserHelperModel()->getUsersDetail($blogPosts, array(
            'withProfile' => $withProfile,
            'userKey' => $index,
            'withPostsCommentsCount' => true,
            'withDiscussionsCommentsCount' => false
        ));
    }

    private function getPostsCategories(array $posts, $index = 'post_id')
    {
        if (empty($posts)) {
            return $posts;
        }

        return $this->getPostCategoryHelperModel()->getPostsCategories($posts, $index);
    }

    /**
     * @return \Blog\Model\Comment
     */
    private function getCommentModel()
    {
        isset($this->commentModel) || $this->commentModel = $this->serviceManager->get('Blog\Model\Comment');
        return $this->commentModel;
    }

    /**
     * @return  CategoryPost
     */
    private function getCategoryPostModel()
    {
        isset($this->categoryPostModel) || $this->categoryPostModel = $this->serviceManager->get('NBlog\Model\CategoryPost');
        return $this->categoryPostModel;
    }

    /**
     * @return \NBlog\Model\Helper\User
     */
    private function getUserHelperModel()
    {
        isset($this->userHelperModel) || $this->userHelperModel = $this->serviceManager->get('NBlog\Model\Helper\User');
        return $this->userHelperModel;
    }

    /**
     * @return \NBlog\Model\Helper\PostCategory
     */
    private function getPostCategoryHelperModel()
    {
        isset($this->postCategoryHelperModel) || $this->postCategoryHelperModel = $this->serviceManager->get('NBlog\Model\Helper\PostCategory');
        return $this->postCategoryHelperModel;
    }
}