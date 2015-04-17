<?php
/**
 * Episode Serial Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;
use NBlog\Model\ServiceLocatorBlogDB;

class EpisodeSerial extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\EpisodeSerial
     */
    protected $dao = null;

    public function getFirstSerial($episodicStyleId)
    {
        return $this->dao->getFirstSerial($episodicStyleId);
    }

    public function getNextSerial($episodicStyleId, $currentSerialId)
    {
        return $this->dao->getNextSerial($episodicStyleId, $currentSerialId);
    }

    public function getNthSerial($episodicStyleId, $offset)
    {
        $result = $this->dao->getNthSerial($episodicStyleId, $offset);
        return empty($result) ? $this->dao->getFirstSerial($episodicStyleId) : $result;
    }

    public function getSerials($episodicStyleId, $limit = -1)
    {
        return empty($episodicStyleId) ? array() : $this->dao->getSerials($episodicStyleId, $limit);
    }
}