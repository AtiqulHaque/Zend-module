<?php

namespace BlogUser\Model;
use NBlog\Model\ServiceLocatorUserDB;

class OtherSetting extends ServiceLocatorUserDB
{
    /**
     * @var \BlogUser\Model\Dao\OtherSetting
     */
    protected $dao = null;
    protected $userHelperModel = null;

    public function getUserSetting($userId)
    {
        if(empty($userId)){
            return false;
        }

        return $this->dao->getUserSettings($userId);
    }

    public function userSettingsModifiedByConditions(array $data, array $id)
    {
        return $this->dao->modifyByConditions($data, $id);
    }
}