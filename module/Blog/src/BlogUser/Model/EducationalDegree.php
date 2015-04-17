<?php
/**
 * Educational Degree Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;
use NBlog\Model\ServiceLocatorUserDB;

class EducationalDegree extends ServiceLocatorUserDB
{
    /**
     * @var     \BlogUser\Model\Dao\EducationalDegree
     */
    protected $dao = null;

    public function getAll()
    {
        $result = $this->dao->getAll();
        $degrees = array('0' => $this->translate('Undefined'));
        foreach ($result AS $row) {
            $degrees[$row['educational_degree_id']] = $row['degree'];
        }

        return $degrees;
    }
}