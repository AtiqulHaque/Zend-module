<?php
/**
 * Other Settings Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Nuruzzaman Bappi <bappi.cse562@gmail.com>
 * @copyright       Copyright (c) 2014 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;
use NBlog\Model\BaseNoDao;

class OtherSettings extends BaseNoDao
{
    const BENGALI = 1;
    const ENGLISH = 2;

    const BANGLADESH = 1;
    const USA = 2;

    public  function getAllLanguages()
    {
        return array(
            self::BENGALI => $this->translate('Bengali')
        );
    }

    public  function getAllDateTimes()
    {
        return array(
            self::BANGLADESH => $this->translate('Dhaka, Bangladesh')
        );
    }
}