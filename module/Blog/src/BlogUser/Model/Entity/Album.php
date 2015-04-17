<?php
/**
 * Album Entity
 *
 * @category        Entity
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Entity;
use NBlog\Model\Entity\ServiceLocatorBase;
use Zend\InputFilter\Factory AS InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\Validator\NotEmpty;

class Album extends ServiceLocatorBase
{
    public $albumId;
    public $userId;
    public $albumName;
    public $created;

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();

            $inputFilter->add($this->getAlbumFilter($factory));
            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

    protected function getAlbumFilter(InputFactory $factory)
    {
        return $factory->createInput(array(
            'name' => 'album_name',
            'filters' => array(
                array('name' => 'NBlog\Filter\StripSlashes'),
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array(
                    'name' => 'NotEmpty',
                    'options' => array(
                        'messages' => array(
                            NotEmpty::IS_EMPTY => $this->translate('Please enter your album name.')
                        )
                    )
                )
            )
        ));
    }
}