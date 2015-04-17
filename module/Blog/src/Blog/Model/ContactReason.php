<?php
namespace Blog\Model;

use NBlog\Model\BaseNoDao;

/**
 * Contact Reason Model
 *
 * @category        Model
 * @package         NBlog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2014 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
class ContactReason extends BaseNoDao
{
    const COMPLAINT = 1;
    const TECH_PROBLEM = 2;
    const FORGOT_PASSWORD = 3;

    public function getAll()
    {
        return array(
            self::COMPLAINT => $this->translate('Complaint'),
            self::TECH_PROBLEM => $this->translate('Technical Problems'),
            self::FORGOT_PASSWORD => $this->translate('Forgot password'),
        );
    }
}
