<?php
/**
 * Episode Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;
use NBlog\Model\CategoryEpisode;
use NBlog\Model\ServiceLocatorBlogDB;

class Episode extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\Episode
     */
    protected $dao = null;
    protected $episodicPostModel = null;
    protected $episodeSerialModel = null;
    protected $categoryEpisodeModel = null;
    protected $userHelperModel = null;
    protected $episodeCategoryHelperModel = null;

    public function getAll($options)
    {
        $options = array_merge($this->setCountOffset((array)$options));
        $result = $this->dao->getAll($options);
        return $this->getUsersDetail($this->getEpisodesCategories($result));
    }

    public function countAll($options)
    {
        return $this->dao->countAll($options);
    }

    public function getByPermalink($permalink, $status = null)
    {
        if (empty($permalink)) {
            return false;
        }

        $result = $this->dao->getByPermalink($permalink, $status);
        if (empty($result)) {
            return $result;
        }
        return $this->getUsersDetail(current($this->getEpisodesCategories(array($result))));
    }

    public function getByEpisodeId($episodeIds)
    {
        if (empty($episodeIds)) {
            return array();
        }
        return $this->dao->getByEpisodeId($episodeIds);
    }

    public function getTopEpisodes()
    {
        return $this->dao->getTopEpisodes();
    }

    public function save(array $data)
    {
        if ($data['episodic_style_id'] == EpisodeStyle::CUSTOM) {
            $data['next_episodic_serial_id'] = 0;
        } else {
            $episodeSerialModel = $this->getEpisodeSerialModel();
            $data['next_episodic_serial_id'] = $episodeSerialModel->getFirstSerial($data['episodic_style_id']);
        }

        $data['permalink'] = $this->getWritingPermalink();
        $data['created'] = date(DATE_W3C);
        $data['modified'] = date(DATE_W3C);

        $episodeId = $this->dao->save($data);
        if (empty($episodeId)) {
            return false;
        }

        $episodeCategoryModel = $this->getCategoryEpisodeModel();
        foreach ($data['category_id'] AS $category) {
            $episodeCategoryModel->save(array(
                'category_id' => $category,
                'episode_id' => $episodeId
            ));
        }

        return $episodeId;
    }

    public function modify(array $data, $episodeId)
    {
        if ($data['episodic_style_id'] == EpisodeStyle::CUSTOM) {
            $data['next_episodic_serial_id'] = 0;
        } else {
            $countPostsOfEpisode = $this->getEpisodicPostModel()->countPostsOfEpisode($episodeId);
            $data['next_episodic_serial_id'] = $this->getEpisodeSerialModel()->getNthSerial($data['episodic_style_id'], $countPostsOfEpisode);
        }

        $data['modified'] = date(DATE_W3C);

        $result = $this->dao->modify($data, $episodeId);
        /*if (empty($result)) {
            return false;
        }

        $episodeCategoryModel = new CategoryEpisode($this->controller);
        $episodeCategoryModel->remove($episodeId);
        foreach ($data['category_id'] AS $category) {
            $episodeCategoryModel->save(array(
                'category_id' => $category,
                'episode_id' => $episodeId
            ));
        }

        if ($data['episodic_style_id'] != EpisodeStyle::CUSTOM) {
            $episodicPosts = $this->getEpisodicPostModel()->getPostsOfEpisode($episodeId);
            $episodicSerials = $this->getEpisodeSerialModel()->getSerials($data['episodic_style_id'], count($episodicPosts));
            foreach($episodicPosts AS $key => $post) {
                $this->getEpisodicPostModel()->modifyEpisodeTag(array(
                    'episode_tag' => $episodicSerials[$key]['serial']
                ), $post['post_id']);
            }
        }*/

        return $episodeId;
    }

    public function updateTitle($title, array $conditions)
    {
        return $this->dao->modifyByConditions(array(
            'title' => $title,
            'modified' => date(DATE_W3C)
        ), $conditions);
    }

    public function updateNextSerial($nextSerialId, $episodeId)
    {
        $data['next_episodic_serial_id'] = $nextSerialId;
        $data['modified'] = date(DATE_W3C);
        return $this->dao->modify($data, $episodeId);
    }

    public function countEpisodeOfUsers(array $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        return $this->dao->countEpisodeOfUsers($userIds);
    }

    public function getTitlesByUser($userId, $processAsKeyValuePair = false)
    {
        if (empty($userId)) {
            return false;
        }

        $result = $this->dao->getTitlesByUser($userId);
        if ($processAsKeyValuePair) {
            $temp = array();
            foreach($result AS $row) {
                $temp[$row['episode_id']] = $row['title'];
            }
            $result = $temp;
        }
        return $result;
    }

    private function getEpisodesCategories(array $episodes, $index = 'episode_id')
    {
        if (empty($episodes)) {
            return $episodes;
        }

        return $this->getEpisodeCategoryHelperModel()->getEpisodesCategories($episodes, $index);
    }

    private function getUsersDetail($episodes, $withProfile = false, $index = 'episode_created_by')
    {
        if (empty($episodes)) {
            return $episodes;
        }

        return $this->getUserHelperModel()->getUsersDetail($episodes, array(
            'withProfile' => $withProfile,
            'userKey' => $index,
            'withPostsCommentsCount' => false,
            'withEpisodesCommentsCount' => true
        ));
    }

    /**
     * @return  \BlogUser\Model\EpisodeSerial
     */
    private function getEpisodeSerialModel()
    {
        isset($this->episodeSerialModel) || $this->episodeSerialModel = $this->serviceManager->get('BlogUser\Model\EpisodeSerial');
        return $this->episodeSerialModel;
    }

    /**
     * @return \BlogUser\Model\EpisodicPost
     */
    protected function getEpisodicPostModel()
    {
        isset($this->episodicPostModel) || $this->episodicPostModel = $this->serviceManager->get('BlogUser\Model\EpisodicPost');
        return $this->episodicPostModel;
    }

    /**
     * @return  CategoryEpisode
     */
    private function getCategoryEpisodeModel()
    {
        isset($this->categoryEpisodeModel) || $this->categoryEpisodeModel = $this->serviceManager->get('NBlog\Model\CategoryEpisode');
        return $this->categoryEpisodeModel;
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
     * @return \NBlog\Model\Helper\EpisodeCategory
     */
    private function getEpisodeCategoryHelperModel()
    {
        isset($this->episodeCategoryHelperModel) || $this->episodeCategoryHelperModel = $this->serviceManager->get('NBlog\Model\Helper\EpisodeCategory');
        return $this->episodeCategoryHelperModel;
    }
}