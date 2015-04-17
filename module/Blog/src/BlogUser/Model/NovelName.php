<?php
/**
 * Novel Name Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;
use NBlog\Model\ServiceLocatorBlogDB;

class NovelName extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\NovelName
     */
    protected $dao = null;

    public function getByUser($userId)
    {
        if (empty($userId)) {
            return false;
        }

        return $this->dao->getByUser($userId);
    }

    public function getByPermalink($permalink)
    {
        if (empty($permalink)) {
            return false;
        }

        return $this->dao->getByPermalink($permalink);
    }

    public function save(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $data['novel_name_permalink'] = $this->getWritingPermalink();
        $data['create_by']            = $data['user_id'];
        $data['create_date']          = $this->getCurrentDateTime();

        return $this->dao->save($data);
    }

    public function modify(array $data, $novelNameId)
    {
        if (empty($data) || empty($novelNameId)) {
            return false;
        }

        $data['novel_name_is_published'] = '0';
        $data['updated_by'] = $data['user_id'];
        $data['updated'] = $this->getCurrentDateTime();
        $data['last_moderated_by'] = '0';
        $data['last_moderated'] = '0000-00-00 00:00:00';

        return $this->dao->modify($data, $novelNameId);
    }
}