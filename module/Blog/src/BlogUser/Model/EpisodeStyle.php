<?php
/**
 * EpisodeStyle Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;

use NBlog\Model\ServiceLocatorBlogDB;

class EpisodeStyle extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\EpisodeStyle
     */
    protected $dao = null;
    const CUSTOM = -99;

    public function getAll($options)
    {
        $options = array_merge($this->setCountOffset((array)$options));
        return $this->dao->getAll($options);
    }

    public function countAll()
    {
        return $this->dao->countAll();
    }

    public function getList()
    {
        return $this->dao->getList() + array(self::CUSTOM => 'Custom');
    }
}