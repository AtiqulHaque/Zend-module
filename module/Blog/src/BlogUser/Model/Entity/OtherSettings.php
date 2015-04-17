<?php

/**
 * Other Settings Entity
 * @category        Entity
 * @package         BlogUser
 * @copyright       Copyright (c) 2014 Nokkhotro Blog. http://www.nokkhotroblog.com
 * @author          Md. Nuruzzaman Bappi <bappi.cse562@gmail.com>
 */
namespace BlogUser\Model\Entity;

use NBlog\Model\Entity\ServiceLocatorBase;
use Zend\InputFilter\Factory AS InputFactory;
use Zend\InputFilter\InputFilter;

class OtherSettings extends ServiceLocatorBase
{
    public $keyboard;

    public function exchangeArray($data)
    {
        $this->keyboard = (isset($data['keyboard'])) ? $data['keyboard'] : null;
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                'name' => 'keyboard',
                'required' => true,
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
                                'isEmpty' => $this->translate('Please enter Question.')
                            )
                        )
                    )
                ),
            )));

            $inputFilter->add($factory->createInput(array(
                'name' => 'language',
                'required' => true,
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
                                'isEmpty' => $this->translate('Please enter Answer')
                            )
                        )
                    )
                ),
            )));

            $inputFilter->add($factory->createInput(array(
                'name' => 'date_time',
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'messages' => array(
                                'isEmpty' => $this->translate('Please Select Category')
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