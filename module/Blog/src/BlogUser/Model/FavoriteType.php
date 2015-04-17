<?php
/**
 * Favorite Type Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;
use NBlog\Model\WritingType;

class FavoriteType extends WritingType
{
    const WRITER = 9;

    public function getAll()
    {
        return array_merge(array(
            0 => '',
            self::WRITER => 'Writer',
        ), parent::getAll());
    }
}