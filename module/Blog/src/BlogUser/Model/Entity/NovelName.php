<?php

/**
 * Novel Name Entity
 * @category        Entity
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Entity;

use NBlog\Model\Entity\ServiceLocatorBase;
use Zend\InputFilter\Factory AS InputFactory;
use Zend\InputFilter\InputFilter;

class NovelName extends ServiceLocatorBase
{
    public $novelNameId;
    public $novelName;

    public function exchangeArray($data)
    {
        $this->novelNameId = (isset($data['novel_name_id'])) ? $data['novel_name_id'] : null;
        $this->novelName = (isset($data['novel_name'])) ? $data['novel_name'] : null;
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                'name' => 'novel_name',
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
                                'isEmpty' => $this->translate('Please enter the novel name.')
                            )
                        )
                    )
                )
            )));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }
}